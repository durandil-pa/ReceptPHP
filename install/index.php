<?php
session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
?>
<!doctype html><html lang="sv"><head><meta charset="utf-8">
<title>Installation - Peters Receptbank</title></head><body>
<h1>Peters Receptbank - Installation</h1>
<?php if($step===1): ?>
<h2>Steg 1 - Systemkontroll</h2>
<ul>
<li>PHP <?=phpversion()?> <?=version_compare(PHP_VERSION,'7.2','>=')?'✅':'❌'?></li>
<li>PDO <?=extension_loaded('pdo')?'✅':'❌'?></li>
<li>PDO MySQL <?=extension_loaded('pdo_mysql')?'✅':'❌'?></li>
<li>GD <?=extension_loaded('gd')?'✅':'❌'?></li>
</ul>
<p><a href="?step=2">Nästa steg</a></p>
<?php elseif($step===2): ?>
<h2>Steg 2 - Databas</h2>
<p>Här ansluts databasen och receptbank.sql importeras (nästa version).</p>
<p><a href="?step=3">Nästa steg</a></p>
<?php else: ?>
<h2>Steg 3 - Administratör</h2>
<form>
<p>Namn<br><input></p>
<p>Användarnamn<br><input></p>
<p>Lösenord<br><input type="password"></p>
<button disabled>Skapa administratör (aktiveras i nästa version)</button>
</form>
<?php endif; ?>
</body></html>