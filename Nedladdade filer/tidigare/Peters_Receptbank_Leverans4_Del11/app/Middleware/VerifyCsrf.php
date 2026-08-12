<?php
declare(strict_types=1);

class VerifyCsrf
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['_csrf'] ?? null)) {
                throw new Exception('Ogiltig säkerhetstoken.');
            }
        }
    }
}
