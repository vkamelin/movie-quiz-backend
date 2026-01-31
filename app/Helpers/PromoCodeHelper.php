<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class PromoCodeHelper
{
    /**
     * Разбирает CSV-стрим и возвращает массив кодов.
     * Ожидает минимум колонку `code` (без заголовка допускается — тогда берётся первый столбец).
     *
     * @param resource|StreamInterface|string $stream
     * @return string[]
     */
    public static function parseCsv($stream): array
    {
        $fh = null;
        if ($stream instanceof StreamInterface) {
            $fh = $stream->detach();
        } elseif (is_resource($stream)) {
            $fh = $stream;
        } elseif (is_string($stream)) {
            $fh = @fopen($stream, 'rb');
        }
        if (!is_resource($fh)) {
            throw new RuntimeException('Invalid CSV stream');
        }

        $codes = [];
        $header = null;
        while (($row = fgetcsv($fh)) !== false) {
            if ($row === [null] || $row === false) {
                continue; // empty line
            }
            // Определяем заголовок: ищем колонку `code`
            if ($header === null) {
                $maybeHeader = array_map(static fn ($v) => strtolower(trim((string)$v)), $row);
                $idx = array_search('code', $maybeHeader, true);
                if ($idx !== false) {
                    $header = $maybeHeader;
                    // Заголовок обработан, переходим к следующей строке
                    continue;
                } else {
                    // без заголовка — считаем первым столбцом code
                    $code = trim((string)($row[0] ?? ''));
                    if ($code !== '') {
                        $codes[] = $code;
                    }
                    continue;
                }
            }

            // Если есть заголовок — ищем индекс колонки code
            $idx = array_search('code', $header, true);
            $code = trim((string)($row[$idx] ?? ''));
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        // Закрываем дескриптор, если это мы его открывали
        if (is_string($stream) && is_resource($fh)) {
            fclose($fh);
        }

        return $codes;
    }

    /**
     * Массовая вставка кодов для батча. Бросает RuntimeException при наличии дубликатов.
     *
     * @param PDO $db
     * @param int $batchId
     * @param string[] $codes
     * @return int Количество вставленных записей
     */
    public static function insertCodes(PDO $db, int $batchId, array $codes): int
    {
        // Нормализация и проверка на дубликаты в самом CSV
        $codes = array_values(array_filter(array_map(static fn ($c) => trim((string)$c), $codes), static fn ($c) => $c !== ''));
        if ($codes === []) {
            return 0;
        }
        $unique = array_unique($codes);
        if (count($unique) !== count($codes)) {
            throw new RuntimeException('Duplicate codes in CSV');
        }

        // Вставка пачками по 1000 строк
        $inserted = 0;
        $chunkSize = 1000;
        $sql = 'INSERT INTO promo_codes(batch_id, code, status) VALUES ';

        foreach (array_chunk($codes, $chunkSize) as $chunk) {
            $params = [];
            $placeholders = [];
            foreach ($chunk as $code) {
                $placeholders[] = '(?, ?, "available")';
                $params[] = $batchId;
                $params[] = $code;
            }
            $q = $db->prepare($sql . implode(',', $placeholders));
            try {
                $q->execute($params);
            } catch (\PDOException $e) {
                // 1062 — duplicate key
                if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                    throw new RuntimeException('Duplicate code conflict');
                }
                throw $e;
            }
            $inserted += $q->rowCount();
        }

        return $inserted;
    }
}
