<?php
declare(strict_types=1);

use App\Auth\Authenticator;
use App\Database\Database;
use App\Repositories\RecipeRepository;
use App\Security\Csrf;

$config = require __DIR__ . '/../bootstrap/app.php';

$scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '/index.php';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$basePath = $basePath === '.' ? '' : $basePath;
$homeUrl = ($basePath === '' ? '' : $basePath) . '/';
$url = static function (string $page, array $parameters = []) use ($homeUrl): string {
    $parameters = array_merge(['page' => $page], $parameters);

    return $homeUrl . '?' . http_build_query($parameters);
};
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

try {
    $database = Database::fromConfig($config);
    $auth = new Authenticator($database);
    $recipes = new RecipeRepository($database);
} catch (Throwable $exception) {
    error_log('Recipe bank startup failed: ' . $exception->getMessage());
    http_response_code(503);
    echo 'Tjänsten är inte tillgänglig. Kontrollera att installationen är genomförd.';
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$page = isset($_GET['page']) ? (string) $_GET['page'] : 'dashboard';
$error = null;

if ($method === 'POST' && $page === 'login') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
    } elseif ($auth->attempt(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
        header('Location: ' . $homeUrl);
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
    header('Location: ' . $url('login'));
    exit;
}

if (!$auth->check()) {
    $page = 'login';
}

$user = $auth->user();

if ($method === 'POST' && $page === 'recipe-store') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        $page = 'recipe-create';
    } else {
        $recipe = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'servings' => (int) ($_POST['servings'] ?? 0),
            'cook_time' => (int) ($_POST['cook_time'] ?? 0),
            'instructions' => trim((string) ($_POST['instructions'] ?? '')),
        ];

        if ($recipe['title'] === '' || strlen($recipe['title']) > 255 || $recipe['instructions'] === '') {
            $error = 'Receptnamn och tillagningsbeskrivning måste fyllas i.';
            $page = 'recipe-create';
        } elseif ($recipe['servings'] < 0 || $recipe['cook_time'] < 0) {
            $error = 'Portioner och tillagningstid kan inte vara negativa.';
            $page = 'recipe-create';
        } else {
            $recipeId = $recipes->create($recipe, (int) $user['id']);
            $_SESSION['flash'] = 'Receptet har sparats.';
            header('Location: ' . $url('recipe-show', ['id' => $recipeId]));
            exit;
        }
    }
}

$flash = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : null;
unset($_SESSION['flash']);

if ($page === 'recipe-show') {
    $recipe = $recipes->find((int) ($_GET['id'] ?? 0));
    if ($recipe === null) {
        http_response_code(404);
        $page = 'recipes';
        $error = 'Receptet kunde inte hittas.';
    }
}

if ($page === 'recipes') {
    $recipeList = $recipes->all();
}

$titles = [
    'login' => 'Logga in',
    'dashboard' => 'Startsida',
    'recipes' => 'Recept',
    'recipe-create' => 'Nytt recept',
    'recipe-show' => 'Recept',
];
$title = isset($titles[$page]) ? $titles[$page] : 'Sidan hittades inte';
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($title . ' – ' . APP_NAME) ?></title>
</head>
<body>
    <main>
        <h1><?= $escape(APP_NAME) ?></h1>

        <?php if ($page !== 'login'): ?>
            <nav>
                <a href="<?= $escape($homeUrl) ?>">Startsida</a>
                <a href="<?= $escape($url('recipes')) ?>">Recept</a>
                <a href="<?= $escape($url('recipe-create')) ?>">Nytt recept</a>
            </nav>
        <?php endif; ?>

        <?php if ($flash !== null): ?>
            <p><?= $escape($flash) ?></p>
        <?php endif; ?>

        <?php if ($page === 'login'): ?>
            <h2>Logga in</h2>
            <?php if ($error !== null): ?><p><?= $escape($error) ?></p><?php endif; ?>
            <form method="post" action="<?= $escape($url('login')) ?>">
                <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                <p><label>Användarnamn<br><input name="username" autocomplete="username" required></label></p>
                <p><label>Lösenord<br><input name="password" type="password" autocomplete="current-password" required></label></p>
                <p><button type="submit">Logga in</button></p>
            </form>
        <?php elseif ($page === 'dashboard'): ?>
            <h2>Startsida</h2>
            <p>Välkommen, <?= $escape($user['name']) ?>.</p>
            <p><a href="<?= $escape($url('recipes')) ?>">Visa alla recept</a></p>
        <?php elseif ($page === 'recipes'): ?>
            <h2>Recept</h2>
            <?php if ($error !== null): ?><p><?= $escape($error) ?></p><?php endif; ?>
            <p><a href="<?= $escape($url('recipe-create')) ?>">Skapa nytt recept</a></p>
            <?php if ($recipeList === []): ?>
                <p>Det finns inga recept ännu.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($recipeList as $listItem): ?>
                        <li>
                            <a href="<?= $escape($url('recipe-show', ['id' => $listItem['id']])) ?>"><?= $escape($listItem['title']) ?></a>
                            <?php if ($listItem['category_name'] !== null): ?> (<?= $escape($listItem['category_name']) ?>)<?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php elseif ($page === 'recipe-create'): ?>
            <h2>Nytt recept</h2>
            <?php if ($error !== null): ?><p><?= $escape($error) ?></p><?php endif; ?>
            <form method="post" action="<?= $escape($url('recipe-store')) ?>">
                <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                <p><label>Receptnamn<br><input name="title" maxlength="255" required value="<?= $escape($_POST['title'] ?? '') ?>"></label></p>
                <p><label>Kort beskrivning<br><textarea name="description" rows="3"><?= $escape($_POST['description'] ?? '') ?></textarea></label></p>
                <p><label>Portioner<br><input name="servings" type="number" min="1" value="<?= $escape($_POST['servings'] ?? '4') ?>"></label></p>
                <p><label>Tillagningstid i minuter<br><input name="cook_time" type="number" min="0" value="<?= $escape($_POST['cook_time'] ?? '') ?>"></label></p>
                <p><label>Tillagningsbeskrivning<br><textarea name="instructions" rows="12" required><?= $escape($_POST['instructions'] ?? '') ?></textarea></label></p>
                <p><button type="submit">Spara recept</button></p>
            </form>
        <?php elseif ($page === 'recipe-show'): ?>
            <article>
                <h2><?= $escape($recipe['title']) ?></h2>
                <?php if ($recipe['description'] !== null): ?><p><?= nl2br($escape($recipe['description'])) ?></p><?php endif; ?>
                <p>
                    <?php if ($recipe['servings'] !== null): ?>Portioner: <?= $escape($recipe['servings']) ?><?php endif; ?>
                    <?php if ($recipe['cook_time'] !== null): ?> · Tillagningstid: <?= $escape($recipe['cook_time']) ?> minuter<?php endif; ?>
                </p>
                <h3>Tillagning</h3>
                <p><?= nl2br($escape($recipe['instructions'])) ?></p>
            </article>
        <?php else: ?>
            <h2>Sidan hittades inte</h2>
        <?php endif; ?>

        <?php if ($page !== 'login'): ?>
            <form method="post" action="<?= $escape($url('logout')) ?>">
                <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                <button type="submit">Logga ut</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
