<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Res;

/**
 * Утилиты для формирования HTTP-ответов.
 */
final class Response
{
    /**
     * Возвращает JSON-ответ.
     *
     * @param Res $res Объект ответа
     * @param int $status HTTP-статус
     * @param array $data Данные для сериализации в JSON
     * @return Res Ответ с заголовком application/json
     */
    public static function json(Res $res, int $status, array $data): Res
    {
        $res->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    /**
     * Возвращает ответ формата application/problem+json.
     *
     * @param Res $res Объект ответа
     * @param int $status HTTP-статус
     * @param string $title Краткое описание проблемы
     * @param array $extra Дополнительные поля для тела ответа
     * @return Res Ответ с заголовком application/problem+json
     */
    public static function problem(Res $res, int $status, string $title, array $extra = []): Res
    {
        $body = array_merge([
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
        ], $extra);
        $res->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $res->withHeader('Content-Type', 'application/problem+json')->withStatus($status);
    }
}
