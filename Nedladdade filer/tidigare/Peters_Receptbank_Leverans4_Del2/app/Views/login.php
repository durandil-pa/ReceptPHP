<!doctype html>
<html lang="sv">
<head>
<meta charset="utf-8">
<title>Logga in - Peters Receptbank</title>
<style>
body{font-family:Arial,sans-serif;background:#f5f5f5}
.login{width:360px;margin:60px auto;background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.15)}
input{width:100%;padding:10px;margin:8px 0}
button{padding:10px 20px}
</style>
</head>
<body>
<div class="login">
<h2>Logga in</h2>
<form method="post" action="index.php?page=login">
<label>Användarnamn</label>
<input type="text" name="username">
<label>Lösenord</label>
<input type="password" name="password">
<button type="submit">Logga in</button>
</form>
</div>
</body>
</html>
