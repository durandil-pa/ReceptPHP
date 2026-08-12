<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

$configPath = __DIR__ . '/../config/database.local.php';
$schemaPath = __DIR__ . '/../database/schema.sql';
$message = null;
$error = null;

if (is_file($configPath)) {
    $message = 'Installationen är redan genomförd. Ta bort config/database.local.php endast om du avsiktligt vill installera om.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['_token']) ? (string) $_POST['_token'] : '';

    if (!isset($_SESSION['install_token']) || !hash_equals($_SESSION['install_token'], $token)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
    } else {
        $host = trim((string) ($_POST['host'] ?? ''));
        $port = (int) ($_POST['port'] ?? 3306);
        $database = trim((string) ($_POST['database'] ?? ''));
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($host === '' || $username === '' || !preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            $error = 'Kontrollera server, databasnamn och användarnamn.';
        } elseif ($port < 1 || $port > 65535) {
            $error = 'Databasporten är ogiltig.';
        } elseif (!is_file($schemaPath)) {
            $error = 'Databasschemat kunde inte hittas.';
        } else {
            try {
                $pdo = new PDO(
                    sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );

                $databaseIdentifier = '`' . $database . '`';
                $pdo->exec(
                    'CREATE DATABASE IF NOT EXISTS ' . $databaseIdentifier
                    . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci'
                );
                $pdo->exec('USE ' . $databaseIdentifier);

                $statements = preg_split('/;\\s*(?:\\R|$)/', (string) file_get_contents($schemaPath));
                foreach ($statements as $statement) {
                    if (trim($statement) !== '') {
                        $pdo->exec($statement);
                    }
                }

                $localConfig = [
                    'driver' => 'mysql',
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8mb4',
                ];

                $contents = "<?php\\n\\nreturn " . var_export($localConfig, true) . ";\\n";
                if (file_put_contents($configPath, $contents, LOCK_EX) === false) {
                    throw new RuntimeException('Kunde inte skriva den lokala databasinställningen.');
                }

                @chmod($configPath, 0600);
                unset($_SESSION['install_token']);
                $message = 'Databasen och tabellerna skapades. Av säkerhetsskäl är installationen nu låst.';
            } catch (Throwable $exception) {
                error_log('Recipe bank installation failed: ' . $exception->getMessage());
                $error = 'Installationen kunde inte slutföras. Kontrollera databasuppgifterna och serverns fellogg.';
            }
        }
    }
}

if (!isset($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation – <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></h1>
        <h2>Databasinstallation</h2>

        <?php if ($message !== null): ?>
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (!is_file($configPath)): ?>
            <form method="post">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['install_token'], ENT_QUOTES, 'UTF-8') ?>">

                <p><label>Databasserver<br><input name="host" value="127.0.0.1" required></label></p>
                <p><label>Port<br><input name="port" type="number" min="1" max="65535" value="3306" required></label></p>
                <p><label>Databasnamn<br><input name="database" value="peters_receptbank" required></label></p>
                <p><label>Användarnamn<br><input name="username" required></label></p>
                <p><label>Lösenord<br><input name="password" type="password"></label></p>
                <p><button type="submit">Skapa databas och tabeller</button></p>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
