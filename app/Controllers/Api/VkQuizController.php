<?php

namespace App\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

class VkQuizController extends VkController
{
    public function get($req, Res $res): Res
    {
        $params = (array)$req->getParsedBody();

        if (empty($params['mode'])) {
            return $this->error($res, 'missing mode');
        }

        $data = match ($params['mode']) {
            'easy' => $this->easyMode($res),
            'difficult' => $this->difficultMode($res),
            'reverse' => $this->reverseMode($res),
            default => [],
        };

        if (empty($data)) {
            return $this->error($res, 'invalid mode', 400);
        }

        return $res;
    }

    private function easyMode(Res $res): array
    {
        // TODO: Реализовать старт игры получение вопрос для этого режима

        return [];
    }

    private function difficultMode(Res $res): array
    {
        // TODO: Реализовать старт игры получение вопрос для этого режима

        return [];
    }

    private function reverseMode(Res $res): array
    {
        // TODO: Реализовать старт игры получение вопрос для этого режима

        return [];
    }
}