<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Исключение валидации с набором ошибок.
 */
final class ValidationException extends Exception
{
    /**
     * @param array $errors Ошибки валидации
     * @param string $message Сообщение об ошибке
     * @param int $code Код ошибки
     * @param Throwable|null $previous Предыдущее исключение
     */
    public function __construct(private array $errors = [], string $message = 'Validation failed', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Возвращает массив ошибок валидации.
     *
     * @return array Список ошибок
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
