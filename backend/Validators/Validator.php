<?php

declare(strict_types=1);

namespace App\Validators;

use App\Database\Connection;

class Validator
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleDefinition) {
            $rulesList = is_array($ruleDefinition) ? $ruleDefinition : explode('|', (string)$ruleDefinition);
            $value = $data[$field] ?? null;
            $isPresent = array_key_exists($field, $data);
            $nullable = in_array('nullable', $rulesList, true);
            $isEmptyValue = $value === null || $value === '';

            if (!$isPresent || $isEmptyValue) {
                if (in_array('required', $rulesList, true)) {
                    if (!$nullable) {
                        $errors[$field] = self::message($field, 'The ' . $field . ' field is required.');
                    }
                    $validated[$field] = $value;
                    continue;
                }
                if (!$nullable) {
                    $validated[$field] = $value;
                    continue;
                }
                $validated[$field] = null;
                continue;
            }

            foreach ($rulesList as $rule) {
                if ($rule === 'nullable' || $rule === 'required') {
                    continue;
                }
                $ruleName = $rule;
                $parameter = null;
                if (str_contains($rule, ':')) {
                    [$ruleName, $parameter] = explode(':', $rule, 2);
                }
                $error = self::checkRule($field, $ruleName, $parameter, $value, $data);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }

            $validated[$field] = self::cast($rulesList, $value);
        }

        return [
            'valid' => $errors === [],
            'data' => $validated,
            'errors' => $errors,
        ];
    }

    private static function checkRule(string $field, string $rule, ?string $parameter, $value, array $data): ?string
    {
        switch ($rule) {
            case 'string':
                return is_string($value) ? null : self::message($field, 'The ' . $field . ' must be text.');
            case 'email':
                return filter_var((string)$value, FILTER_VALIDATE_EMAIL) !== false
                    ? null
                    : self::message($field, 'The ' . $field . ' must be a valid email address.');
            case 'phone':
                return preg_match('/^[0-9+\-\s()]{7,20}$/', (string)$value) === 1
                    ? null
                    : self::message($field, 'The ' . $field . ' must be a valid phone number.');
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false
                    ? null
                    : self::message($field, 'The ' . $field . ' must be a whole number.');
            case 'numeric':
                return is_numeric($value) ? null : self::message($field, 'The ' . $field . ' must be a number.');
            case 'min':
                $min = (int)$parameter;
                if (is_array($value)) {
                    return count($value) >= $min ? null : self::message($field, 'The ' . $field . ' must contain at least ' . $min . ' items.');
                }
                return mb_strlen((string)$value) >= $min
                    ? null
                    : self::message($field, 'The ' . $field . ' must be at least ' . $min . ' characters.');
            case 'max':
                $max = (int)$parameter;
                if (is_array($value)) {
                    return count($value) <= $max ? null : self::message($field, 'The ' . $field . ' must contain at most ' . $max . ' items.');
                }
                return mb_strlen((string)$value) <= $max
                    ? null
                    : self::message($field, 'The ' . $field . ' must not exceed ' . $max . ' characters.');
            case 'date':
                return self::isDate((string)$value) ? null : self::message($field, 'The ' . $field . ' must be a valid date.');
            case 'date_after':
                return self::isDate((string)$value) && strtotime((string)$value) > strtotime((string)$parameter)
                    ? null
                    : self::message($field, 'The ' . $field . ' must be after ' . $parameter . '.');
            case 'date_before':
                return self::isDate((string)$value) && strtotime((string)$value) < strtotime((string)$parameter)
                    ? null
                    : self::message($field, 'The ' . $field . ' must be before ' . $parameter . '.');
            case 'in':
                $allowed = explode(',', (string)$parameter);
                return in_array((string)$value, $allowed, true)
                    ? null
                    : self::message($field, 'The selected ' . $field . ' is invalid.');
            case 'in_array':
                return is_array($value) && $value !== [] && count(array_diff($value, explode(',', (string)$parameter))) === 0
                    ? null
                    : self::message($field, 'The ' . $field . ' contains an invalid selection.');
            case 'boolean':
                return in_array($value, [0, 1, '0', '1', true, false], true)
                    ? null
                    : self::message($field, 'The ' . $field . ' must be true or false.');
            case 'array':
                return is_array($value) ? null : self::message($field, 'The ' . $field . ' must be a list.');
            case 'unique':
                [$table, $column] = explode(',', (string)$parameter);
                $ignore = $data['ignore_id'] ?? null;
                $count = Connection::fetchColumn(
                    "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?" . ($ignore ? ' AND id <> ?' : ''),
                    $ignore ? [(string)$value, (int)$ignore] : [(string)$value]
                );
                return (int)$count === 0 ? null : self::message($field, 'This ' . $field . ' is already in use.');
            case 'exists':
                [$table, $column] = explode(',', (string)$parameter);
                $count = Connection::fetchColumn(
                    "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?",
                    [(string)$value]
                );
                return (int)$count > 0 ? null : self::message($field, 'The selected ' . $field . ' does not exist.');
            case 'url':
                return filter_var((string)$value, FILTER_VALIDATE_URL) !== false
                    ? null
                    : self::message($field, 'The ' . $field . ' must be a valid URL.');
        }
        return null;
    }

    private static function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year);
    }

    private static function cast(array $rules, $value)
    {
        if (in_array('integer', $rules, true)) {
            return is_numeric($value) ? (int)$value : $value;
        }
        if (in_array('boolean', $rules, true)) {
            return in_array($value, [1, '1', true, 'true'], true) ? 1 : 0;
        }
        return $value;
    }

    private static function message(string $field, string $message): string
    {
        return $message;
    }
}