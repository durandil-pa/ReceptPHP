<?php
declare(strict_types=1);

class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['flash'] = compact('type','message');
    }

    public static function get(): ?array
    {
        $msg = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $msg;
    }
}
