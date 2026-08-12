<?php
declare(strict_types=1);

class Validator
{
    public static function required(string $value): bool
    {
        return trim($value) !== '';
    }

    public static function maxLength(string $value, int $length): bool
    {
        return mb_strlen($value) <= $length;
    }
}
