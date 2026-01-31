<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Telemetry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use JsonException;

/**
 * Class GPTService
 *
 * Сервис для взаимодействия с GPT API через предоставленный AITunnel.
 * Поддерживает отправку структурированных и "обычных" запросов, а также получение баланса и статистики.
 *
 * @package App\Services
 */
class GPTService
{
    /**
     * @var Client HTTP-клиент Guzzle для выполнения запросов
     */
    private Client $client;

    /**
     * Стандартные заголовки для всех запросов
     *
     * @var array<string, string>
     */
    private array $defaultHeaders;

    private int $failureCount = 0;
    private bool $isOpen = false;
    private bool $isHalfOpen = false;
    private int $failureThreshold;
    private int $openTimeout;
    private float $openedAt = 0.0;

    /**
     * GPTService constructor.
     *
     * @param string $apiKey       API-ключ для авторизации (Bearer)
     * @param array<string, mixed> $guzzleConfig Дополнительная конфигурация клиента Guzzle (базовый URI, timeout, verify и т.д.)
     */
    public function __construct(string $apiKey, array $guzzleConfig = [], int $failureThreshold = 5, int $openTimeout = 30)
    {
        $timeout = (float)($_ENV['GPT_TIMEOUT'] ?? 15);
        $config = array_merge([
            'base_uri' => 'https://api.aitunnel.ru/v1/',
            'timeout' => $timeout,
            'verify' => true,
        ], $guzzleConfig);

        $this->client = new Client($config);
        $this->defaultHeaders = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];
        $this->failureThreshold = $failureThreshold;
        $this->openTimeout = $openTimeout;
        if (Telemetry::enabled()) {
            Telemetry::setGptBreakerState('closed');
        }
    }

    /**
     * Выполняет HTTP-запрос к GPT API с ретраями и обработкой ошибок.
     *
     * @param string                    $method   HTTP метод (GET, POST и т.п.)
     * @param string                    $endpoint Относительный путь к ресурсу API
     * @param array<string, mixed>|null $body     Тело запроса, будет автоматически закодировано в JSON
     *
     * @return array{status:int, body:mixed, error_code:string|null, error_message:string|null}
     * @throws JsonException При ошибке декодирования JSON ответа
     */
    private function request(string $method, string $endpoint, $body = null): array
    {
        if ($this->isOpen) {
            if ((microtime(true) - $this->openedAt) >= $this->openTimeout) {
                $this->isOpen = false;
                $this->isHalfOpen = true;
                if (Telemetry::enabled()) {
                    Telemetry::setGptBreakerState('half-open');
                }
            } else {
                return [
                    'status' => 0,
                    'body' => null,
                    'error_code' => 'circuit_open',
                    'error_message' => 'Circuit breaker is open',
                ];
            }
        }

        $uri = ltrim($endpoint, '/');
        $maxAttempts = 3;
        $delay = 1;
        $errorCode = null;
        $errorMessage = null;
        $decoded = null;
        $status = 0;
        $startTime = microtime(true);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $options = [
                    'headers' => $this->defaultHeaders,
                    'http_errors' => false,
                ];

                if ($body !== null) {
                    $options['json'] = $body;
                }

                $response = $this->client->request(strtoupper($method), $uri, $options);
                $status = $response->getStatusCode();
                $bodyContent = (string)$response->getBody();

                $isDebugEnabled = Logger::get()->isHandling(\Monolog\Logger::DEBUG);
                $logContext = ['status' => $status];
                if ($isDebugEnabled) {
                    $logContext['body'] = substr($bodyContent, 0, 2048);
                }

                if ($status >= 500) {
                    Logger::error('GPT API response', $logContext);
                } elseif ($status >= 400) {
                    Logger::warning('GPT API response', $logContext);
                } elseif ($isDebugEnabled) {
                    Logger::debug('GPT API response', $logContext);
                }

                $decoded = json_decode($bodyContent, true, 512, JSON_THROW_ON_ERROR);

                if ($status >= 500) {
                    $errorCode = 'http_' . $status;
                    $errorMessage = $decoded['error']['message'] ?? $bodyContent;

                    if ($attempt < $maxAttempts) {
                        sleep($delay);
                        $delay *= 2;
                    }

                    continue;
                } elseif ($status >= 400) {
                    $result = [
                        'status' => $status,
                        'body' => $decoded,
                        'error_code' => 'http_' . $status,
                        'error_message' => $decoded['error']['message'] ?? null,
                    ];
                    if (Telemetry::enabled()) {
                        Telemetry::observeGptResponseTime(microtime(true) - $startTime);
                    }
                    $this->updateBreaker(false);
                    return $result;
                }

                $result = [
                    'status' => $status,
                    'body' => $decoded,
                    'error_code' => null,
                    'error_message' => null,
                ];
                if (Telemetry::enabled()) {
                    Telemetry::observeGptResponseTime(microtime(true) - $startTime);
                }
                $this->updateBreaker(true);
                return $result;
            } catch (RequestException $e) {
                $errorCode = 'network_error';
                $errorMessage = $e->getMessage();

                if ($attempt < $maxAttempts) {
                    sleep($delay);
                    $delay *= 2;
                    continue;
                }
            } catch (JsonException $e) {
                Logger::error("Ошибка декодирования JSON: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
                throw $e;
            }
        }

        $result = [
            'status' => $status,
            'body' => $decoded,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ];
        if (Telemetry::enabled()) {
            Telemetry::observeGptResponseTime(microtime(true) - $startTime);
        }
        $this->updateBreaker(false);
        return $result;
    }

    private function updateBreaker(bool $success): void
    {
        if ($success) {
            $this->failureCount = 0;
            $this->isOpen = false;
            $this->isHalfOpen = false;
            if (Telemetry::enabled()) {
                Telemetry::setGptBreakerState('closed');
            }
            return;
        }

        $this->failureCount++;
        if ($this->failureCount >= $this->failureThreshold || $this->isHalfOpen) {
            $this->isOpen = true;
            $this->isHalfOpen = false;
            $this->openedAt = microtime(true);
            if (Telemetry::enabled()) {
                Telemetry::setGptBreakerState('open');
            }
        }
    }

    /**
     * Отправляет структурированный запрос к GPT API с использованием JSON Schema.
     *
     * @param array<int, array{role:string, content:string}> $messages    Список сообщений в формате
     *                                                                   [ ['role'=>..., 'content'=>...], ... ]
     * @param array<string, mixed>|string                    $jsonSchema  JSON Schema для валидации ответа.
     *                                                                   Может быть передан массивом или JSON-строкой;
     *                                                                   внутри метода преобразуется в массив.
     * @param string                                         $model       Название модели (по умолчанию gpt-4o-mini)
     * @param float                                          $temperature Температура для детерминированности (0.0 по умолчанию)
     * @param int|null                                       $maxTokens   Максимальное число токенов в ответе
     * @param string                                         $schemaName  Имя схемы для идентификации
     *
     * @return array{status:int, body:mixed, error_code:string|null, error_message:string|null}
     */
    public function chatStructured(
        array $messages,
        array|string $jsonSchema,
        string $model = 'gpt-4o-mini',
        float $temperature = 0.0,
        ?int $maxTokens = null,
        string $schemaName = ''
    ): array {
        if (is_string($jsonSchema)) {
            try {
                $jsonSchema = json_decode($jsonSchema, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return [
                    'status' => 0,
                    'body' => null,
                    'error_code' => 'schema_decode_error',
                    'error_message' => $e->getMessage(),
                ];
            }
        }

        if ($jsonSchema === null || $jsonSchema === false) {
            return [
                'status' => 0,
                'body' => null,
                'error_code' => 'schema_decode_error',
                'error_message' => 'Invalid JSON schema',
            ];
        }

        /** @var array<string, mixed> $jsonSchema */

        // Формируем полезную нагрузку запроса
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $schemaName,
                    'schema' => $jsonSchema,
                    'strict' => true,
                ],
            ],
            'temperature' => $temperature,
        ];

        if ($maxTokens !== null) {
            $payload['max_tokens'] = $maxTokens;
        }

        return $this->request('POST', 'chat/completions', $payload);
    }

    /**
     * Отправляет обычный запрос к GPT API без валидации по схеме.
     *
     * @param array<int, array{role:string, content:string}> $messages  Список сообщений (role/content)
     * @param string                                         $model     Название модели
     * @param array<string, mixed>                           $reasoning При необходимости причинный анализ
     * @param int|null                                       $maxTokens Максимальное число токенов в ответе
     *
     * @return array{status:int, body:mixed, error_code:string|null, error_message:string|null}
     * @throws JsonException
     */
    public function chat(
        array $messages,
        string $model,
        array $reasoning = [],
        ?int $maxTokens = null
    ): array {
        // Подготовка тела запроса
        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];

        if ($maxTokens !== null) {
            $payload['max_tokens'] = $maxTokens;
        }

        if (!empty($reasoning)) {
            $payload['reasoning'] = $reasoning;
        }

        return $this->request('POST', 'chat/completions', $payload);
    }

    /**
     * Получение текущего баланса по API-ключу.
     *
     * @return array{status:int, body:mixed, error_code:string|null, error_message:string|null}
     */
    public function getBalance(): array
    {
        return $this->request('GET', 'aitunnel/balance');
    }

    /**
     * Получение статистики использования API (промпты, токены и т.д.).
     *
     * @return array{status:int, body:mixed, error_code:string|null, error_message:string|null}
     */
    public function getStats(): array
    {
        return $this->request('GET', 'aitunnel/stats');
    }

    /**
     * Строит текст структуры воронки продаж на основе шаблона classic.tpl.
     *
     * @param string $product  Название продукта
     * @param string $audience Целевая аудитория
     * @param string $goal     Цель воронки
     * @param string $pains    Основные боли аудитории
     *
     * @return array{text:string}|array{error:string}
     */
    public function buildStructure(string $product, string $audience, string $goal, string $pains): array
    {
        $loader = new PromptLoader();
        $templatePath = __DIR__ . '/../Prompts/classic.tpl';

        // Проверяем наличие шаблона
        if (!file_exists($templatePath)) {
            return ['error' => 'template_not_found'];
        }

        try {
            $encoded = $loader->load($templatePath);
            $template = json_decode($encoded, true);
            if ($template === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid template JSON');
            }
        } catch (\Throwable $e) {
            Logger::error('Failed to load template', ['exception' => $e]);
            return ['error' => 'template_read_error'];
        }

        $prompt = str_replace(
            ['{{product}}', '{{audience}}', '{{goal}}', '{{pains}}'],
            [$product, $audience, $goal, $pains],
            $template
        );
        $prompt = JsonHelper::encodePrompt($prompt);

        return ['text' => $prompt];
    }
}
