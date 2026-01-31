<?php

declare(strict_types=1);

namespace App\Helpers;

final class Validator
{
    /**
     * Валидация входных данных по простым правилам.
     *
     * Поддерживаемые правила:
     * - required
     * - string, int, bool, array
     * - email
     * - enum:a,b,c
     * - min:n, max:n (для чисел)
     * - length:n или length:min,max (для строк)
     * - shape (для вложенных массивов, когда правило задаётся в виде ['rules' => 'array', 'shape' => [...]] )
     *
     * @param array<string,mixed> $input
     * @param array<string,string|array{rules?:string,shape?:array<string,string|array>}> $rules
     * @return array{ok:bool, data?:array<string,mixed>, errors?:array<string,string>}
     */
    public static function validate(array $input, array $rules): array
    {
        $out = [];
        $errors = [];

        foreach ($rules as $field => $ruleDef) {
            $val = $input[$field] ?? null;
            $ruleStr = is_array($ruleDef) ? ($ruleDef['rules'] ?? '') : (string)$ruleDef;
            $rulesArr = $ruleStr === '' ? [] : explode('|', $ruleStr);

            // required
            if (in_array('required', $rulesArr, true)) {
                if ($val === null || $val === '' || (is_array($val) && count($val) === 0)) {
                    $errors[$field] = 'required';
                    continue;
                }
            }

            // type rules
            if (in_array('string', $rulesArr, true) && $val !== null && !is_string($val)) {
                $errors[$field] = 'string';
                continue;
            }
            if (in_array('int', $rulesArr, true) && $val !== null) {
                if (!(is_int($val) || (is_string($val) && filter_var($val, FILTER_VALIDATE_INT) !== false))) {
                    $errors[$field] = 'int';
                    continue;
                }
                // normalize
                $val = (int)$val;
            }
            if (in_array('bool', $rulesArr, true) && $val !== null && !is_bool($val)) {
                $errors[$field] = 'bool';
                continue;
            }
            if (in_array('array', $rulesArr, true) && $val !== null && !is_array($val)) {
                $errors[$field] = 'array';
                continue;
            }

            // email
            if (in_array('email', $rulesArr, true) && $val !== null && !is_string($val)) {
                $errors[$field] = 'email';
                continue;
            }
            if (in_array('email', $rulesArr, true) && is_string($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'email';
                continue;
            }

            // enum:opt1,opt2
            foreach ($rulesArr as $r) {
                if (str_starts_with($r, 'enum:')) {
                    $list = substr($r, 5);
                    $allowed = $list === '' ? [] : explode(',', $list);
                    if ($val !== null && !in_array((string)$val, $allowed, true)) {
                        $errors[$field] = 'enum:' . $list;
                        continue 3; // next field
                    }
                }
            }

            // numeric min/max
            foreach ($rulesArr as $r) {
                if ($val === null) {
                    break;
                }
                if (str_starts_with($r, 'min:')) {
                    $min = (int)substr($r, 4);
                    if (is_int($val)) {
                        if ($val < $min) {
                            $errors[$field] = "min:$min";
                            continue 3;
                        }
                    }
                }
                if (str_starts_with($r, 'max:')) {
                    $max = (int)substr($r, 4);
                    if (is_int($val)) {
                        if ($val > $max) {
                            $errors[$field] = "max:$max";
                            continue 3;
                        }
                    }
                }
            }

            // length
            foreach ($rulesArr as $r) {
                if (!is_string($val)) {
                    continue;
                }
                if ($r === 'trim') {
                    $val = trim($val);
                    continue;
                }
                if (str_starts_with($r, 'length:')) {
                    $arg = substr($r, 7);
                    $len = function_exists('mb_strlen') ? mb_strlen($val) : strlen($val);
                    if ($arg !== '' && str_contains($arg, ',')) {
                        [$minS, $maxS] = array_map('trim', explode(',', $arg, 2));
                        $min = (int)$minS;
                        $max = (int)$maxS;
                        if ($len < $min || $len > $max) {
                            $errors[$field] = "length:$min,$max";
                            continue 3;
                        }
                    } elseif ($arg !== '') {
                        $max = (int)$arg;
                        if ($len > $max) {
                            $errors[$field] = "length:$max";
                            continue 3;
                        }
                    }
                }
                if (str_starts_with($r, 'max:')) {
                    // для строк: max — как максимальная длина
                    $max = (int)substr($r, 4);
                    $len = function_exists('mb_strlen') ? mb_strlen($val) : strlen($val);
                    if ($len > $max) {
                        $errors[$field] = "max:$max";
                        continue 3;
                    }
                }
            }

            // nested shape
            if (is_array($ruleDef) && isset($ruleDef['shape']) && is_array($ruleDef['shape'])) {
                if ($val !== null && !is_array($val)) {
                    $errors[$field] = 'array';
                    continue;
                }
                $sub = self::validate(is_array($val) ? $val : [], $ruleDef['shape']);
                if (!($sub['ok'] ?? false)) {
                    $errors[$field] = 'shape';
                    continue;
                }
                $val = $sub['data'] ?? $val;
            }

            // normalize strings: trim
            if (is_string($val)) {
                $val = trim($val);
            }

            $out[$field] = $val;
        }

        return empty($errors) ? ['ok' => true, 'data' => $out] : ['ok' => false, 'errors' => $errors];
    }
}
