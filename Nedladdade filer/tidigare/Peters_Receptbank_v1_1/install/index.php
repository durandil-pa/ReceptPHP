<?php
require_once __DIR__ . '/../config/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = "mysql:host={$_POST['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $_POST['user'], $_POST['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['database']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci");

        $config = "<?php\n"
            ."define('DB_HOST','{$_POST['host']}');\n"
            ."define('DB_NAME','$dbName');\n"
            ."define('DB_USER','{$_POST['user']}');\n"
            ."define('DB_PASS','{$_POST['password']}');\n"
            ."define('DB_CHARSET','utf8mb4');\n";

        file_put_contents(__DIR__ . '/../config/database.php', $config);

        $message = "Databasen skapades och database.php har skrivits.";
    } catch (Exception $e) {
        $message = "Fel: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="sv">
<head><meta charset="utf-8"><title>Installation</title></head>
<body>
<h1>Peters Receptbank - Installation</h1>
<?php if($message): ?><p><strong><?=htmlspecialchars($message)?></strong></p><?php endif; ?>
<form method="post">
<p>Databasserver<br><input name="host" value="localhost"></p>
<p>Databasnamn<br><input name="database" value="receptbank"></p>
<p>Användare<br><input name="user"></p>
<p>Lösenord<br><input type="password" name="password"></p>
<button type="submit">Installera</button>
</form>
</body>
</html>
