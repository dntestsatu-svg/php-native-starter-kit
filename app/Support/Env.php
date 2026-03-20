<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Support;

final class Env
{
    public static function get(array $env, string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $env)) {
            return $default;
        }

        return self::normalize($env[$key]);
    }

    public static function string(array $env, string $key, string $default = ''): string
    {
        $value = self::get($env, $key, $default);

        return is_string($value) ? $value : (string) $value;
    }

    public static function nullableString(array $env, string $key): ?string
    {
        $value = self::get($env, $key);

        if ($value === null) {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }

    public static function int(array $env, string $key, int $default = 0): int
    {
        $value = self::get($env, $key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public static function float(array $env, string $key, float $default = 0.0): float
    {
        $value = self::get($env, $key, $default);

        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    public static function bool(array $env, string $key, bool $default = false): bool
    {
        $value = self::get($env, $key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $bool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($bool !== null) {
                return $bool;
            }
        }

        return $default;
    }

    private static function normalize(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        $lower = strtolower($trimmed);

        return match ($lower) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $trimmed,
        };
    }
}
