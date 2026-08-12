<?php
declare(strict_types=1);

use App\Auth\Authenticator;
use App\Database\Database;
use App\Security\Csrf;

$config = require __DIR__ . '/../bootstrap/app.php';

try {
    $auth = new Authenticator(Database::fromConfig($config));
} catch (Throwable $exception) {
    error_log('Recipe bank startup failed: ' . $exception->getMessage());
    http_response_code(503);
    echo 'Tjänsten är inte tillgänglig. Kontrollera att installationen är genomförd.';
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$page = isset($_GET['page']) ? (string) $_GET['page'] : 'dashboard';

if ($method === 'POST' && $page === 'login') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
    } elseif ($auth->attempt(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
        header('Location: /');
        exit;
    } else {
        $error = 'Fel användarnamn eller lösenord.';
    }
}

if ($method === 'POST' && $page === 'logout') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        echo 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        exit;
    }

    $auth->logout();
    header('Location: /?page=login');
    exit;
}

if (!$auth->check()) {
    $page = 'login';
}

$user = $auth->user();
$title = $page === 'login' ? 'Logga in' : 'Startsida';
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title . ' – ' . APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if ($page === 'login'): ?>
            <h2>Logga in</h2>
            <?php if (isset($error)): ?>
                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="post" action="/?page=login">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <p><label>Användarnamn<br><input name="username" autocomplete="username" required></label></p>
                <p><label>Lösenord<br><input name="password" type="password" autocomplete="current-password" required></label></p>
                <p><button type="submit">Logga in</button></p>
            </form>
        <?php else: ?>
            <p>Välkommen, <?= htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') ?>.</p>
            <p>Inloggningen fungerar. Receptmodulen byggs i nästa steg.</p>
            <form method="post" action="/?page=logout">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Logga ut</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
