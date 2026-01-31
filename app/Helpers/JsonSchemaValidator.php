<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Минимальный валидатор JSON Schema (подмножество черт draft‑07/2020‑12):
 * type, properties, required, additionalProperties, enum,
 * minLength/maxLength, pattern, format: email, minimum/maximum,
 * items, minItems/maxItems, nested объекты.
 */
final class JsonSchemaValidator
{
    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $schema
     * @return array{ok:bool, errors?:array<string,string>}
     */
    public static function validate(array $data, array $schema): array
    {
        $errors = [];
        self::validateNode($data, $schema, '', $errors);
        return empty($errors) ? ['ok' => true] : ['ok' => false, 'errors' => $errors];
    }

    /**
     * @param mixed $value
     * @param array<string,mixed> $schema
     * @param string $path
     * @param array<string,string> $errors
     */
    private static function validateNode(mixed $value, array $schema, string $path, array &$errors): void
    {
        // type
        if (isset($schema['type'])) {
            $type = $schema['type'];
            $ok = match ($type) {
                'object' => is_array($value),
                'string' => is_string($value),
                'integer' => is_int($value) || (is_string($value) && filter_var($value, FILTER_VALIDATE_INT) !== false),
                'number' => is_int($value) || is_float($value) || is_numeric($value),
                'boolean' => is_bool($value),
                'array' => is_array($value),
                default => true,
            };
            if (!$ok) {
                $errors[$path ?: '$'] = 'type:' . (string)$type;
                return;
            }
            if ($type === 'integer') {
                $value = (int)$value;
            }
            if ($type === 'number' && is_string($value)) {
                $value = (float)$value;
            }
        }

        // enums
        if (isset($schema['enum']) && is_array($schema['enum'])) {
            if (!in_array($value, $schema['enum'], true)) {
                $errors[$path ?: '$'] = 'enum';
                return;
            }
        }

        // string constraints
        if (is_string($value)) {
            if (isset($schema['minLength']) && is_int($schema['minLength'])) {
                $len = self::strlen($value);
                if ($len < $schema['minLength']) {
                    $errors[$path ?: '$'] = 'minLength:' . $schema['minLength'];
                    return;
                }
            }
            if (isset($schema['maxLength']) && is_int($schema['maxLength'])) {
                $len = self::strlen($value);
                if ($len > $schema['maxLength']) {
                    $errors[$path ?: '$'] = 'maxLength:' . $schema['maxLength'];
                    return;
                }
            }
            if (isset($schema['pattern']) && is_string($schema['pattern'])) {
                if (@preg_match('/' . str_replace('/', '\/', $schema['pattern']) . '/u', '') === false) {
                    // некорректный паттерн — пропускаем, чтобы не ломать прод
                } else {
                    if (preg_match('/' . str_replace('/', '\/', $schema['pattern']) . '/u', $value) !== 1) {
                        $errors[$path ?: '$'] = 'pattern';
                        return;
                    }
                }
            }
            if (isset($schema['format']) && $schema['format'] === 'email') {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$path ?: '$'] = 'format:email';
                    return;
                }
            }
        }

        // numeric constraints
        if (is_int($value) || is_float($value)) {
            if (isset($schema['minimum']) && is_numeric($schema['minimum'])) {
                if ($value < (float)$schema['minimum']) {
                    $errors[$path ?: '$'] = 'minimum:' . $schema['minimum'];
                    return;
                }
            }
            if (isset($schema['maximum']) && is_numeric($schema['maximum'])) {
                if ($value > (float)$schema['maximum']) {
                    $errors[$path ?: '$'] = 'maximum:' . $schema['maximum'];
                    return;
                }
            }
        }

        // array
        if (is_array($value) && ($schema['type'] ?? null) === 'array') {
            $count = count($value);
            if (isset($schema['minItems']) && is_int($schema['minItems']) && $count < $schema['minItems']) {
                $errors[$path ?: '$'] = 'minItems:' . $schema['minItems'];
                return;
            }
            if (isset($schema['maxItems']) && is_int($schema['maxItems']) && $count > $schema['maxItems']) {
                $errors[$path ?: '$'] = 'maxItems:' . $schema['maxItems'];
                return;
            }
            if (isset($schema['items']) && is_array($schema['items'])) {
                foreach (array_values($value) as $idx => $item) {
                    self::validateNode($item, $schema['items'], $path . '[' . $idx . ']', $errors);
                }
            }
            return; // дальше проверки для object
        }

        // object
        if (is_array($value) && ($schema['type'] ?? null) === 'object') {
            $props = (array)($schema['properties'] ?? []);
            $required = (array)($schema['required'] ?? []);
            foreach ($required as $reqField) {
                if (!array_key_exists($reqField, $value)) {
                    $errors[self::join($path, $reqField)] = 'required';
                }
            }
            foreach ($props as $name => $propSchema) {
                if (array_key_exists($name, $value) && is_array($propSchema)) {
                    self::validateNode($value[$name], $propSchema, self::join($path, (string)$name), $errors);
                }
            }
            if (isset($schema['additionalProperties']) && $schema['additionalProperties'] === false) {
                $allowed = array_keys($props);
                foreach ($value as $k => $_) {
                    if (!in_array((string)$k, $allowed, true)) {
                        $errors[self::join($path, (string)$k)] = 'additionalProperties';
                    }
                }
            }
        }
    }

    private static function join(string $base, string $field): string
    {
        return $base === '' ? $field : $base . '.' . $field;
    }

    private static function strlen(string $s): int
    {
        return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
    }
}
