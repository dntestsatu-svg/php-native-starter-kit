<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Validation;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $rawValue = $data[$field] ?? null;
            $value = is_string($rawValue) ? trim($rawValue) : $rawValue;

            if ($value === '') {
                $value = null;
            }

            $hasRequired = in_array('required', $ruleList, true);
            $hasNullable = in_array('nullable', $ruleList, true);

            if ($value === null && $hasRequired) {
                $errors[$field] = self::messageFor($field, 'required');
                continue;
            }

            if ($value === null && $hasNullable) {
                $validated[$field] = null;
                continue;
            }

            if ($value === null) {
                continue;
            }

            foreach ($ruleList as $rule) {
                if ($rule === 'required' || $rule === 'nullable') {
                    continue;
                }

                [$name, $parameter] = self::parseRule($rule);
                $failed = self::failsRule($name, $value, $parameter, $data, $field);

                if ($failed) {
                    $errors[$field] = self::messageFor($field, $name, $parameter);
                    break;
                }
            }

            if (!array_key_exists($field, $errors)) {
                $validated[$field] = self::castValue($value, $ruleList);
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    /**
     * @param array<int, string> $rules
     */
    private static function castValue(mixed $value, array $rules): mixed
    {
        if (in_array('integer', $rules, true)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function failsRule(
        string $rule,
        mixed $value,
        ?string $parameter,
        array $data,
        string $field
    ): bool {
        return match ($rule) {
            'string' => !is_string($value),
            'email' => !is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false,
            'integer' => filter_var($value, FILTER_VALIDATE_INT) === false,
            'alpha_num' => !is_string($value) || preg_match('/^[a-zA-Z0-9]+$/', $value) !== 1,
            'min' => self::failsMin($value, $parameter),
            'max' => self::failsMax($value, $parameter),
            'confirmed' => !self::passesConfirmed($field, $value, $data),
            default => false,
        };
    }

    private static function failsMin(mixed $value, ?string $parameter): bool
    {
        $min = (int) ($parameter ?? 0);

        if (is_string($value)) {
            return mb_strlen($value) < $min;
        }

        if (is_int($value) || is_float($value)) {
            return $value < $min;
        }

        return true;
    }

    private static function failsMax(mixed $value, ?string $parameter): bool
    {
        $max = (int) ($parameter ?? 0);

        if (is_string($value)) {
            return mb_strlen($value) > $max;
        }

        if (is_int($value) || is_float($value)) {
            return $value > $max;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function passesConfirmed(string $field, mixed $value, array $data): bool
    {
        $confirmationKey = $field . '_confirmation';
        $confirmation = $data[$confirmationKey] ?? null;

        if (is_string($confirmation)) {
            $confirmation = trim($confirmation);
        }

        return $value === $confirmation;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private static function parseRule(string $rule): array
    {
        if (!str_contains($rule, ':')) {
            return [$rule, null];
        }

        [$name, $parameter] = explode(':', $rule, 2);

        return [$name, $parameter];
    }

    private static function messageFor(string $field, string $rule, ?string $parameter = null): string
    {
        $readableField = str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => sprintf('%s is required.', ucfirst($readableField)),
            'string' => sprintf('%s must be a string.', ucfirst($readableField)),
            'email' => sprintf('%s must be a valid email address.', ucfirst($readableField)),
            'integer' => sprintf('%s must be a valid integer.', ucfirst($readableField)),
            'alpha_num' => sprintf('%s may only contain letters and numbers.', ucfirst($readableField)),
            'min' => sprintf('%s must be at least %s characters.', ucfirst($readableField), (string) $parameter),
            'max' => sprintf('%s may not be greater than %s characters.', ucfirst($readableField), (string) $parameter),
            'confirmed' => sprintf('%s confirmation does not match.', ucfirst($readableField)),
            default => sprintf('%s is invalid.', ucfirst($readableField)),
        };
    }
}
