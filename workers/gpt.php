<?php
/**
 * Воркер обработки GPT-задач.
 *
 * Назначение:
 * - Читает задания из Redis-очереди и выполняет GPT-запросы;
 * - Обрабатывает повторы, ошибки и таймауты;
 * - Записывает результаты и статус для последующей обработки.
 */

declare(strict_types=1);

use App\Helpers\Logger;
use App\Helpers\GPTService;
use App\Helpers\RedisHelper;
use App\Config;
use App\Helpers\RedisKeyHelper;
use Slim\App;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var App $app */
$config = Config::getInstance();

$redis = null;
try {
    $redis = RedisHelper::getInstance();
} catch (\RedisException $e) {
    Logger::error('Redis connection failed: ' . $e->getMessage());
    exit();
}
$service = new GPTService($_ENV['AITUNNEL_API_KEY'] ?? '');

while (true) {
    $start = microtime(true);
    $processed = 0;

    while ($processed < 10) {
        try {
            $job = $redis->lPop(RedisKeyHelper::key('gpt', 'queue'));
        } catch (Throwable $e) {
            Logger::error('Redis lPop failed: ' . $e->getMessage());
            break;
        }

        if ($job === false || $job === null) {
            break;
        }

        if (!is_array($job)) {
            continue;
        }

        $dedupSource = $job['key'] ?? sha1(json_encode($job));
        $dedupRedisKey = RedisKeyHelper::key('gpt', 'dedup', (string)$dedupSource);
        try {
            $stored = $redis->set($dedupRedisKey, 1, ['nx', 'ex' => $config->get('IDEMPOTENCY_KEY_TTL')]);
        } catch (Throwable $e) {
            $stored = true;
        }
        if ($stored === false) {
            Logger::info('Duplicate GPT job skipped', ['key' => $dedupSource]);
            continue;
        }

        $taskId = $job['task_id'] ?? null;
        $stepId = $job['step_id'] ?? null;
        if ($taskId === null || $stepId === null) {
            continue;
        }

        $response = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                if (!empty($job['json_schema'])) {
                    if (!is_array($job['json_schema'])) {
                        throw new JsonException('json_schema must be an array');
                    }
                    $response = $service->chatStructured(
                        $job['messages'] ?? [],
                        $job['json_schema'],
                        $job['model'] ?? 'gpt-4o-mini',
                        $job['temperature'] ?? 0.0,
                        $job['max_tokens'] ?? null,
                        $job['schema_name'] ?? ''
                    );
                } else {
                    $response = $service->chat(
                        $job['messages'] ?? [],
                        $job['model'] ?? 'gpt-4o-mini',
                    );
                }
            } catch (JsonException $e) {
                $response = [
                    'status' => 0,
                    'body' => null,
                    'error_code' => 'invalid_json_schema',
                    'error_message' => $e->getMessage(),
                ];
                $logJob = $job;
                if (isset($logJob['json_schema']) && is_array($logJob['json_schema'])) {
                    $logJob['json_schema'] = json_encode($logJob['json_schema']);
                }
                Logger::error('GPT worker error: ' . $e->getMessage(), ['task' => $logJob]);
                $attempt = 3;
            } catch (Throwable $e) {
                $response = [
                    'status' => 0,
                    'body' => null,
                    'error_code' => 'exception',
                    'error_message' => $e->getMessage(),
                ];
                $logJob = $job;
                if (isset($logJob['json_schema']) && is_array($logJob['json_schema'])) {
                    $logJob['json_schema'] = json_encode($logJob['json_schema']);
                }
                Logger::error('GPT worker error: ' . $e->getMessage(), ['task' => $logJob]);
            }

            if (($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300 && empty($response['error_code'])) {
                $resultKey = RedisKeyHelper::key('gpt', 'result', (string)$taskId) . ':' . $stepId;
                $taskKey = RedisKeyHelper::key('gpt', 'task', (string)$taskId);
                $redis->set($resultKey, $response);
                $ttl = $config->get('GPT_TASK_TTL');
                $redis->expire($resultKey, $ttl);
                $resultListKey = RedisKeyHelper::key('gpt', 'result_keys', (string)$taskId);
                $redis->rPush($resultListKey, $resultKey);
                $redis->expire($resultListKey, $ttl);
                $redis->hSet($taskKey, (string)$stepId, 'done');
                $redis->expire($taskKey, $ttl);
                break;
            }

            if ($attempt < 3) {
                usleep((int)(1_000_000 * (2 ** ($attempt - 1))));
                continue;
            }

            $resultKey = RedisKeyHelper::key('gpt', 'result', (string)$taskId) . ':' . $stepId;
            $taskKey = RedisKeyHelper::key('gpt', 'task', (string)$taskId);
            $redis->set($resultKey, $response);
            $ttl = $config->get('GPT_TASK_TTL');
            $redis->expire($resultKey, $ttl);
            $resultListKey = RedisKeyHelper::key('gpt', 'result_keys', (string)$taskId);
            $redis->rPush($resultListKey, $resultKey);
            $redis->expire($resultListKey, $ttl);
            $redis->hSet($taskKey, (string)$stepId, 'failed');
            $redis->expire($taskKey, $ttl);
            $logJob = $job;
            if (isset($logJob['json_schema']) && is_array($logJob['json_schema'])) {
                $logJob['json_schema'] = json_encode($logJob['json_schema']);
            }
            Logger::error('GPT worker request failed', ['task' => $logJob, 'response' => $response]);
        }

        $taskKey = RedisKeyHelper::key('gpt', 'task', (string)$taskId);
        $remaining = $redis->hIncrBy($taskKey, 'pending_count', -1);
        if ($remaining <= 0) {
            $doneKey = RedisKeyHelper::key('gpt', 'task_done', (string)$taskId);
            $resultListKey = RedisKeyHelper::key('gpt', 'result_keys', (string)$taskId);
            $keys = $redis->lRange($resultListKey, 0, -1);

            $pipe = $redis->multi(\Redis::PIPELINE);
            $pipe->set($doneKey, '1');
            $ttl = $config->get('GPT_TASK_TTL');
            $pipe->expire($doneKey, $ttl);
            $pipe->expire($taskKey, $ttl);
            if ($keys !== false) {
                foreach ($keys as $key) {
                    $pipe->expire((string)$key, $ttl);
                }
                $pipe->expire($resultListKey, $ttl);
            }
            $pipe->rPush(RedisKeyHelper::key('gpt', 'done'), $taskId);
            $pipe->exec();
        }

        $processed++;
    }

    $elapsed = microtime(true) - $start;
    if ($elapsed < 1.0) {
        usleep((int)((1.0 - $elapsed) * 1_000_000));
    }
}
