<?php
declare(strict_types=1);

use App\Auth\Authenticator;
use App\Database\Database;
use App\Repositories\RecipeRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\FavoriteRepository;
use App\Repositories\RecipeNoteRepository;
use App\Repositories\UserRepository;
use App\Security\Csrf;
use App\Services\RecipeImageUploader;

$config = require __DIR__ . '/../bootstrap/app.php';

$scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '/index.php';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$basePath = $basePath === '.' ? '' : $basePath;
$homeUrl = ($basePath === '' ? '' : $basePath) . '/';
$url = static function (string $page, array $parameters = []) use ($homeUrl): string {
    return $homeUrl . '?' . http_build_query(array_merge(['page' => $page], $parameters));
};
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

try {
    $database = Database::fromConfig($config);
    $auth = new Authenticator($database);
    $recipes = new RecipeRepository($database);
    $categoryRepository = new CategoryRepository($database);
    $favorites = new FavoriteRepository($database);
    $notes = new RecipeNoteRepository($database);
    $users = new UserRepository($database);
    $images = new RecipeImageUploader();
} catch (Throwable $exception) {
    error_log('Recipe bank startup failed: ' . $exception->getMessage());
    http_response_code(503);
    echo 'Tjänsten är inte tillgänglig. Kontrollera att installationen är genomförd.';
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$page = isset($_GET['page']) ? (string) $_GET['page'] : 'dashboard';
$error = null;
$formData = ['title' => '', 'description' => '', 'category_id' => 0, 'servings' => 4, 'cook_time' => '', 'instructions' => ''];
$formIngredients = array_fill(0, 3, ['amount' => '', 'unit_id' => 0, 'name' => '']);

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

if ($method === 'POST' && $page === 'category-create') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        $page = 'categories';
    } else {
        try {
            $categoryRepository->create((string) ($_POST['name'] ?? ''));
            $_SESSION['flash'] = 'Kategorin har lagts till.';
            header('Location: ' . $url('categories'));
            exit;
        } catch (Throwable $exception) {
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Kategorin kunde inte sparas.';
            $page = 'categories';
        }
    }
}

if ($method === 'POST' && $page === 'favorite-toggle') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        echo 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        exit;
    }

    $recipeId = (int) ($_POST['recipe_id'] ?? 0);
    try {
        $isFavorite = $favorites->toggle((int) $auth->user()['id'], $recipeId);
        $_SESSION['flash'] = $isFavorite ? 'Receptet har lagts till bland favoriter.' : 'Receptet har tagits bort från favoriter.';
    } catch (RuntimeException $exception) {
        $_SESSION['flash'] = 'Receptet kunde inte hittas.';
    }

    if (($_POST['return_page'] ?? '') === 'recipe-show') {
        header('Location: ' . $url('recipe-show', ['id' => $recipeId]));
    } else {
        header('Location: ' . $url('recipes'));
    }
    exit;
}

if ($method === 'POST' && $page === 'recipe-note-save') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        echo 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        exit;
    }

    $recipeId = (int) ($_POST['recipe_id'] ?? 0);
    $note = trim((string) ($_POST['note'] ?? ''));
    if (strlen($note) > 5000) {
        $_SESSION['flash'] = 'Anteckningen får vara högst 5 000 tecken.';
    } else {
        try {
            $notes->save((int) $auth->user()['id'], $recipeId, $note);
            $_SESSION['flash'] = $note === '' ? 'Anteckningen har tagits bort.' : 'Din anteckning har sparats.';
        } catch (RuntimeException $exception) {
            $_SESSION['flash'] = 'Receptet kunde inte hittas.';
        }
    }

    header('Location: ' . $url('recipe-show', ['id' => $recipeId]));
    exit;
}

if ($method === 'POST' && $page === 'category-delete') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        echo 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        exit;
    }

    $_SESSION['flash'] = $categoryRepository->delete((int) ($_POST['id'] ?? 0))
        ? 'Kategorin har tagits bort. Recepten behåller ingen kategori.'
        : 'Kategorin kunde inte hittas.';
    header('Location: ' . $url('categories'));
    exit;
}

$user = $auth->user();
$isAdmin = $user !== null && ($user['role'] ?? '') === 'admin';
$adminPages = ['users', 'user-create', 'user-role-change'];

if (!$isAdmin && in_array($page, $adminPages, true)) {
    http_response_code(403);
    $error = 'Du har inte behörighet att hantera användare.';
    $page = 'dashboard';
}

if ($method === 'POST' && $page === 'password-change') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        $page = 'password';
    } elseif ((string) ($_POST['new_password'] ?? '') !== (string) ($_POST['new_password_confirmation'] ?? '')) {
        $error = 'Det nya lösenordet och bekräftelsen matchar inte.';
        $page = 'password';
    } else {
        try {
            $users->changeOwnPassword(
                (int) $user['id'],
                (string) ($_POST['current_password'] ?? ''),
                (string) ($_POST['new_password'] ?? '')
            );
            $_SESSION['flash'] = 'Ditt lösenord har uppdaterats.';
            header('Location: ' . $url('dashboard'));
            exit;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
            $page = 'password';
        }
    }
}

if ($isAdmin && $method === 'POST' && $page === 'user-create') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        $page = 'users';
    } else {
        try {
            $users->create(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['username'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['role'] ?? 'user')
            );
            $_SESSION['flash'] = 'Användaren har skapats.';
            header('Location: ' . $url('users'));
            exit;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
            $page = 'users';
        }
    }
}

if ($isAdmin && $method === 'POST' && $page === 'user-role-change') {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        $page = 'users';
    } else {
        try {
            $users->changeRole(
                (int) $user['id'],
                (int) ($_POST['id'] ?? 0),
                (string) ($_POST['role'] ?? '')
            );
            $_SESSION['flash'] = 'Behörigheten har uppdaterats.';
            header('Location: ' . $url('users'));
            exit;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
            $page = 'users';
        }
    }
}

$categoryList = $auth->check() ? $recipes->categories() : [];
$unitList = $auth->check() ? $recipes->units() : [];
$categoryIds = array_map('intval', array_column($categoryList, 'id'));
$unitIds = array_map('intval', array_column($unitList, 'id'));

$readRecipeForm = static function () use (&$formData, &$formIngredients, &$error, $categoryIds, $unitIds): array {
    $formData = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'servings' => (int) ($_POST['servings'] ?? 0),
        'cook_time' => (int) ($_POST['cook_time'] ?? 0),
        'instructions' => trim((string) ($_POST['instructions'] ?? '')),
    ];
    $rawNames = isset($_POST['ingredient_name']) && is_array($_POST['ingredient_name']) ? $_POST['ingredient_name'] : [];
    $rawAmounts = isset($_POST['amount']) && is_array($_POST['amount']) ? $_POST['amount'] : [];
    $rawUnits = isset($_POST['unit_id']) && is_array($_POST['unit_id']) ? $_POST['unit_id'] : [];
    $formIngredients = [];
    $ingredients = [];

    if (count($rawNames) > 100 || count($rawAmounts) > 100 || count($rawUnits) > 100) {
        $error = 'Ett recept kan ha högst 100 ingredienser.';
        return [];
    }

    foreach ($rawNames as $index => $rawName) {
        $name = trim((string) $rawName);
        $amount = str_replace(',', '.', trim((string) ($rawAmounts[$index] ?? '')));
        $unitId = (int) ($rawUnits[$index] ?? 0);
        $formIngredients[] = ['amount' => $amount, 'unit_id' => $unitId, 'name' => $name];

        if ($name === '') {
            continue;
        }
        if (strlen($name) > 255 || ($amount !== '' && (!is_numeric($amount) || (float) $amount < 0))) {
            $error = 'Kontrollera ingrediensnamn och mängder.';
            return [];
        }
        if ($unitId !== 0 && !in_array($unitId, $unitIds, true)) {
            $error = 'En ingrediens har en ogiltig enhet.';
            return [];
        }
        $ingredients[] = ['name' => $name, 'amount' => $amount === '' ? null : $amount, 'unit_id' => $unitId ?: null];
    }

    if ($formData['title'] === '' || strlen($formData['title']) > 255 || $formData['instructions'] === '') {
        $error = 'Receptnamn och tillagningsbeskrivning måste fyllas i.';
    } elseif ($formData['servings'] < 1) {
        $error = 'Ange minst en portion.';
    } elseif ($formData['cook_time'] < 0) {
        $error = 'Tillagningstiden kan inte vara negativ.';
    } elseif ($formData['category_id'] !== 0 && !in_array($formData['category_id'], $categoryIds, true)) {
        $error = 'Den valda kategorin finns inte.';
    } elseif ($ingredients === []) {
        $error = 'Lägg till minst en ingrediens.';
    }
    return $ingredients;
};

if ($method === 'POST' && in_array($page, ['recipe-store', 'recipe-update'], true)) {
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        $error = 'Formuläret har gått ut. Ladda om sidan och försök igen.';
    } else {
        $ingredients = $readRecipeForm();
    }

    $recipeId = (int) ($_POST['id'] ?? 0);
    if ($error !== null) {
        $page = $page === 'recipe-update' ? 'recipe-edit' : 'recipe-create';
        if ($page === 'recipe-edit') {
            $editingId = $recipeId;
        }
    } else {
        $uploadedImage = null;
        $previousRecipe = null;
        try {
            $uploadedImage = $images->upload(isset($_FILES['image']) && is_array($_FILES['image']) ? $_FILES['image'] : []);

            if ($page === 'recipe-store') {
                $recipeId = $recipes->create($formData, $ingredients, (int) $user['id'], $uploadedImage);
                $_SESSION['flash'] = 'Receptet har sparats.';
            } else {
                $previousRecipe = $recipes->find($recipeId);
                if ($previousRecipe === null) {
                    throw new RuntimeException('Recipe not found.');
                }
                $recipes->update($recipeId, $formData, $ingredients, $uploadedImage);
                if ($uploadedImage !== null) {
                    $images->delete($previousRecipe['image_path']);
                }
                $_SESSION['flash'] = 'Receptet har uppdaterats.';
            }
            header('Location: ' . $url('recipe-show', ['id' => $recipeId]));
            exit;
        } catch (Throwable $exception) {
            if ($uploadedImage !== null) {
                $images->delete($uploadedImage);
            }
            error_log('Recipe save failed: ' . $exception->getMessage());
            $page = $page === 'recipe-update' ? 'recipe-edit' : 'recipe-create';
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Receptet kunde inte sparas. Försök igen.';
            $editingId = $recipeId;
        }
    }
}

if ($method === 'POST' && $page === 'recipe-delete') {
    $recipeId = (int) ($_POST['id'] ?? 0);
    if (!Csrf::isValid($_POST['_token'] ?? null)) {
        http_response_code(419);
        echo 'Formuläret har gått ut. Ladda om sidan och försök igen.';
        exit;
    }
    $imagePath = $recipes->delete($recipeId);
    if ($imagePath !== null) {
        $images->delete($imagePath);
        $_SESSION['flash'] = 'Receptet har tagits bort.';
    } else {
        $_SESSION['flash'] = 'Receptet kunde inte hittas.';
    }
    header('Location: ' . $url('recipes'));
    exit;
}

$flash = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : null;
unset($_SESSION['flash']);

if ($page === 'recipe-show') {
    $recipe = $recipes->find((int) ($_GET['id'] ?? 0));
    if ($recipe === null) {
        http_response_code(404);
        $page = 'recipes';
        $error = 'Receptet kunde inte hittas.';
    } else {
        $recipe['is_favorite'] = $favorites->exists((int) $user['id'], (int) $recipe['id']);
        $recipe['personal_note'] = $notes->find((int) $user['id'], (int) $recipe['id']) ?? '';
    }
}

if ($page === 'recipe-edit' && !isset($editingId)) {
    $editingId = (int) ($_GET['id'] ?? 0);
    $existingRecipe = $recipes->find($editingId);
    if ($existingRecipe === null) {
        http_response_code(404);
        $page = 'recipes';
        $error = 'Receptet kunde inte hittas.';
    } else {
        $formData = [
            'title' => $existingRecipe['title'],
            'description' => $existingRecipe['description'] ?: '',
            'category_id' => (int) $existingRecipe['category_id'],
            'servings' => $existingRecipe['servings'],
            'cook_time' => $existingRecipe['cook_time'] ?: '',
            'instructions' => $existingRecipe['instructions'],
        ];
        $formIngredients = $existingRecipe['ingredients'];
        while (count($formIngredients) < 3) {
            $formIngredients[] = ['amount' => '', 'unit_id' => 0, 'name' => ''];
        }
        foreach ($formIngredients as &$ingredient) {
            $ingredient['name'] = $ingredient['ingredient_name'] ?? $ingredient['name'];
        }
        unset($ingredient);
    }
}

if ($page === 'categories') {
    $managedCategories = $categoryRepository->all();
}

if ($page === 'users' && $isAdmin) {
    $managedUsers = $users->all();
}

if ($page === 'recipes') {
    $searchQuery = trim((string) ($_GET['q'] ?? ''));
    $selectedCategoryId = (int) ($_GET['category'] ?? 0);
    $favoritesOnly = isset($_GET['favorites']) && $_GET['favorites'] === '1';

    if ($selectedCategoryId !== 0 && !in_array($selectedCategoryId, $categoryIds, true)) {
        $selectedCategoryId = 0;
    }

    if (strlen($searchQuery) > 100) {
        $searchQuery = substr($searchQuery, 0, 100);
    }

    $recipeList = $recipes->search($searchQuery, $selectedCategoryId, (int) $user['id'], $favoritesOnly);
}

$titles = ['login' => 'Logga in', 'dashboard' => 'Startsida', 'recipes' => 'Recept', 'recipe-create' => 'Nytt recept', 'recipe-show' => 'Recept', 'recipe-edit' => 'Redigera recept', 'categories' => 'Kategorier', 'users' => 'Användare', 'password' => 'Byt lösenord'];
$title = $titles[$page] ?? 'Sidan hittades inte';
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($title . ' – ' . APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= $escape($basePath . '/css/app.css') ?>">
</head>
<body>
<main>
    <h1><?= $escape(APP_NAME) ?></h1>
    <?php if ($page !== 'login'): ?>
        <nav>
            <a href="<?= $escape($homeUrl) ?>">Startsida</a>
            <a href="<?= $escape($url('recipes')) ?>">Recept</a>
            <a href="<?= $escape($url('recipes', ['favorites' => 1])) ?>">Favoriter</a>
            <a href="<?= $escape($url('categories')) ?>">Kategorier</a>
            <a href="<?= $escape($url('password')) ?>">Byt lösenord</a>
            <?php if ($isAdmin): ?><a href="<?= $escape($url('users')) ?>">Användare</a><?php endif; ?>
            <a href="<?= $escape($url('recipe-create')) ?>">Nytt recept</a>
        </nav>
    <?php endif; ?>
    <?php if ($flash !== null): ?><p><?= $escape($flash) ?></p><?php endif; ?>

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
    <?php elseif ($page === 'password'): ?>
        <h2>Byt lösenord</h2>
        <?php if ($error !== null): ?><p><?= $escape($error) ?></p><?php endif; ?>
        <form method="post" action="<?= $escape($url('password-change')) ?>">
            <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
            <p><label>Nuvarande lösenord<br><input name="current_password" type="password" autocomplete="current-password" required></label></p>
            <p><label>Nytt lösenord (minst 12 tecken)<br><input name="new_password" type="password" minlength="12" autocomplete="new-password" required></label></p>
            <p><label>Bekräfta nytt lösenord<br><input name="new_password_confirmation" type="password" minlength="12" autocomplete="new-password" required></label></p>
            <p><button type="submit">Byt lösenord</button></p>
        </form>
    <?php elseif ($page === 'categories'): ?>
        <h2>Kategorier</h2>
        <?php if ($error !== null): ?><p><?= $escape($error) ?></p><?php endif; ?>
        <form method="post" action="<?= $escape($url('category-create')) ?>">
            <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
            <p><label>Ny kategori<br><input name="name" maxlength="100" required></label></p>
            <p><button type="submit">Lägg till kategori</button></p>
        </form>
        <?php if ($managedCategories === []): ?>
            <p>Det finns inga kategorier ännu.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($managedCategories as $category): ?>
                    <li>
                        <?= $escape($category['name']) ?> (<?= $escape($category['recipe_count']) ?> recept)
                        <form method="post" action="<?= $escape($url('category-delete')) ?>" style="display: inline;" onsubmit="return confirm('Ta bort kategorin? Recepten blir då okategoriserade.');">
                            <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                            <input type="hidden" name="id" value="<?= $escape($category['id']) ?>">
                            <button type="submit">Ta bort</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php elseif ($page === 'users' && $isAdmin): ?>
        <h2>Användare</h2>
        <?php if ($error !== null): ?><p><?= $escape($error) ?></p><?php endif; ?>
        <form method="post" action="<?= $escape($url('user-create')) ?>">
            <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
            <h3>Skapa användare</h3>
            <p><label>Namn<br><input name="name" maxlength="100" required></label></p>
            <p><label>Användarnamn<br><input name="username" pattern="[A-Za-z0-9_.-]{3,100}" maxlength="100" required></label></p>
            <p><label>Lösenord (minst 12 tecken)<br><input name="password" type="password" minlength="12" required></label></p>
            <p><label>Behörighet<br><select name="role"><option value="user">Vanlig användare</option><option value="admin">Administratör</option></select></label></p>
            <p><button type="submit">Skapa användare</button></p>
        </form>
        <h3>Befintliga användare</h3>
        <div class="user-list">
            <?php foreach ($managedUsers as $managedUser): ?>
                <div class="user-row">
                    <div>
                        <strong><?= $escape($managedUser['name']) ?></strong><br>
                        <span><?= $escape($managedUser['username']) ?></span>
                    </div>
                    <?php if ((int) $managedUser['id'] === (int) $user['id']): ?>
                        <span class="user-role"><?= $escape($managedUser['role'] === 'admin' ? 'Administratör (du)' : 'Vanlig användare') ?></span>
                    <?php else: ?>
                        <form method="post" action="<?= $escape($url('user-role-change')) ?>" class="user-role-form">
                            <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                            <input type="hidden" name="id" value="<?= $escape($managedUser['id']) ?>">
                            <select name="role">
                                <option value="user"<?= $managedUser['role'] === 'user' ? ' selected' : '' ?>>Vanlig användare</option>
                                <option value="admin"<?= $managedUser['role'] === 'admin' ? ' selected' : '' ?>>Administratör</option>
                            </select>
                            <button type="submit">Spara</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($page === 'recipes'): ?>
        <h2>Recept</h2>
        <?php if ($error !== null): ?><p><?= $escape($error) ?></p><?php endif; ?>
        <p><a href="<?= $escape($url('recipe-create')) ?>">Skapa nytt recept</a></p>
        <form method="get" action="<?= $escape($homeUrl) ?>">
            <input type="hidden" name="page" value="recipes">
            <p><label>Sök recept eller ingrediens<br><input name="q" maxlength="100" value="<?= $escape($searchQuery) ?>" placeholder="Till exempel vitlök"></label></p>
            <p><label>Kategori<br><select name="category">
                <option value="0">Alla kategorier</option>
                <?php foreach ($categoryList as $category): ?>
                    <option value="<?= $escape($category['id']) ?>"<?= (int) $category['id'] === $selectedCategoryId ? ' selected' : '' ?>><?= $escape($category['name']) ?></option>
                <?php endforeach; ?>
            </select></label></p>
            <p><label class="checkbox-label"><input type="checkbox" name="favorites" value="1"<?= $favoritesOnly ? ' checked' : '' ?>> Endast favoriter</label></p>
            <p><button type="submit">Sök</button> <a href="<?= $escape($url('recipes')) ?>">Rensa</a></p>
        </form>
        <?php if ($recipeList === []): ?><p>Inga recept matchar sökningen.</p><?php else: ?>
            <div class="recipe-list">
                <?php foreach ($recipeList as $listItem): ?>
                    <article class="recipe-card">
                        <?php if ($listItem['image_path'] !== null): ?>
                            <a class="recipe-card-image" href="<?= $escape($url('recipe-show', ['id' => $listItem['id']])) ?>">
                                <img src="<?= $escape($basePath . '/' . $listItem['image_path']) ?>" alt="<?= $escape($listItem['title']) ?>">
                            </a>
                        <?php else: ?>
                            <div class="recipe-card-image recipe-card-placeholder" aria-hidden="true">Ingen bild</div>
                        <?php endif; ?>
                        <div class="recipe-card-content">
                            <h3><a href="<?= $escape($url('recipe-show', ['id' => $listItem['id']])) ?>"><?= $escape($listItem['title']) ?></a></h3>
                            <?php if ($listItem['category_name'] !== null): ?><p><?= $escape($listItem['category_name']) ?></p><?php endif; ?>
                            <div class="recipe-card-actions">
                                <form method="post" action="<?= $escape($url('favorite-toggle')) ?>" class="favorite-form">
                                    <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                                    <input type="hidden" name="recipe_id" value="<?= $escape($listItem['id']) ?>">
                                    <input type="hidden" name="return_page" value="recipes">
                                    <button type="submit" class="favorite-button<?= $listItem['is_favorite'] ? ' is-favorite' : '' ?>" aria-label="<?= $listItem['is_favorite'] ? 'Ta bort från favoriter' : 'Lägg till som favorit' ?>">
                                        <?= $listItem['is_favorite'] ? '★ Favorit' : '☆ Lägg till favorit' ?>
                                    </button>
                                </form>
                                <a href="<?= $escape($url('recipe-edit', ['id' => $listItem['id']])) ?>">Redigera receptet</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php elseif ($page === 'recipe-create' || $page === 'recipe-edit'): ?>
        <h2><?= $page === 'recipe-edit' ? 'Redigera recept' : 'Nytt recept' ?></h2>
        <?php if ($error !== null): ?><p><?= $escape($error) ?></p><?php endif; ?>
        <form method="post" enctype="multipart/form-data" action="<?= $escape($url($page === 'recipe-edit' ? 'recipe-update' : 'recipe-store')) ?>">
            <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
            <?php if ($page === 'recipe-edit'): ?><input type="hidden" name="id" value="<?= $escape($editingId) ?>"><?php endif; ?>
            <p><label>Receptnamn<br><input name="title" maxlength="255" required value="<?= $escape($formData['title']) ?>"></label></p>
            <p><label>Kategori<br><select name="category_id"><option value="0">Ingen kategori</option>
                <?php foreach ($categoryList as $category): ?><option value="<?= $escape($category['id']) ?>"<?= (int) $category['id'] === (int) $formData['category_id'] ? ' selected' : '' ?>><?= $escape($category['name']) ?></option><?php endforeach; ?>
            </select></label></p>
            <p><label>Kort beskrivning<br><textarea name="description" rows="3"><?= $escape($formData['description']) ?></textarea></label></p>
            <p><label>Portioner<br><input name="servings" type="number" min="1" required value="<?= $escape($formData['servings']) ?>"></label></p>
            <p><label>Tillagningstid i minuter<br><input name="cook_time" type="number" min="0" value="<?= $escape($formData['cook_time']) ?>"></label></p>
            <p><label>Receptbild (JPG, PNG eller WebP, högst 5 MB)<br><input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label></p>
            <h3>Ingredienser</h3>
            <div id="ingredient-list" class="ingredient-list">
                <?php foreach ($formIngredients as $ingredient): ?>
                    <div class="ingredient-row">
                        <input name="amount[]" inputmode="decimal" placeholder="Mängd" value="<?= $escape($ingredient['amount']) ?>">
                        <select name="unit_id[]"><option value="0">Enhet</option>
                            <?php foreach ($unitList as $unit): ?><option value="<?= $escape($unit['id']) ?>"<?= (int) $unit['id'] === (int) $ingredient['unit_id'] ? ' selected' : '' ?>><?= $escape($unit['short_name']) ?></option><?php endforeach; ?>
                        </select>
                        <input name="ingredient_name[]" maxlength="255" placeholder="Ingrediens" value="<?= $escape($ingredient['name']) ?>">
                        <button type="button" class="ingredient-remove" data-remove-ingredient>Ta bort</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <template id="ingredient-row-template">
                <div class="ingredient-row">
                    <input name="amount[]" inputmode="decimal" placeholder="Mängd">
                    <select name="unit_id[]"><option value="0">Enhet</option>
                        <?php foreach ($unitList as $unit): ?><option value="<?= $escape($unit['id']) ?>"><?= $escape($unit['short_name']) ?></option><?php endforeach; ?>
                    </select>
                    <input name="ingredient_name[]" maxlength="255" placeholder="Ingrediens">
                    <button type="button" class="ingredient-remove" data-remove-ingredient>Ta bort</button>
                </div>
            </template>
            <p><button type="button" id="add-ingredient">Lägg till ingrediens</button></p>
            <p><label>Tillagningsbeskrivning<br><textarea name="instructions" rows="12" required><?= $escape($formData['instructions']) ?></textarea></label></p>
            <p><button type="submit"><?= $page === 'recipe-edit' ? 'Spara ändringar' : 'Spara recept' ?></button></p>
        </form>
    <?php elseif ($page === 'recipe-show'): ?>
        <article>
            <h2><?= $escape($recipe['title']) ?></h2>
            <div class="recipe-detail-actions">
                <button type="button" class="print-button" onclick="window.print()">Skriv ut recept</button>
                <form method="post" action="<?= $escape($url('favorite-toggle')) ?>" class="favorite-form">
                    <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                    <input type="hidden" name="recipe_id" value="<?= $escape($recipe['id']) ?>">
                    <input type="hidden" name="return_page" value="recipe-show">
                    <button type="submit" class="favorite-button<?= $recipe['is_favorite'] ? ' is-favorite' : '' ?>">
                        <?= $recipe['is_favorite'] ? '★ Ta bort från favoriter' : '☆ Lägg till som favorit' ?>
                    </button>
                </form>
                <a href="<?= $escape($url('recipe-edit', ['id' => $recipe['id']])) ?>">Redigera receptet</a>
            </div>
            <?php if ($recipe['image_path'] !== null): ?><p><img src="<?= $escape($basePath . '/'. $recipe['image_path']) ?>" alt="<?= $escape($recipe['title']) ?>" style="max-width: 500px; height: auto;"></p><?php endif; ?>
            <?php if ($recipe['category_name'] !== null): ?><p>Kategori: <?= $escape($recipe['category_name']) ?></p><?php endif; ?>
            <?php if ($recipe['description'] !== null): ?><p><?= nl2br($escape($recipe['description'])) ?></p><?php endif; ?>
            <p>Portioner: <?= $escape($recipe['servings']) ?><?php if ($recipe['cook_time'] !== null): ?> · Tillagningstid: <?= $escape($recipe['cook_time']) ?> minuter<?php endif; ?></p>
            <h3>Ingredienser</h3><ul><?php foreach ($recipe['ingredients'] as $ingredient): ?><li><?= $escape($ingredient['amount']) ?> <?= $escape($ingredient['unit']) ?> <?= $escape($ingredient['ingredient_name']) ?></li><?php endforeach; ?></ul>
            <h3>Tillagning</h3><p><?= nl2br($escape($recipe['instructions'])) ?></p>
            <section class="personal-note">
                <h3>Mina anteckningar</h3>
                <form method="post" action="<?= $escape($url('recipe-note-save')) ?>" class="note-form">
                    <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                    <input type="hidden" name="recipe_id" value="<?= $escape($recipe['id']) ?>">
                    <p><label>Endast synlig för dig<br><textarea name="note" rows="5" maxlength="5000" placeholder="Till exempel: blev bäst med lite mer vitlök."><?= $escape($recipe['personal_note']) ?></textarea></label></p>
                    <p><button type="submit">Spara anteckning</button></p>
                </form>
            </section>
            <form method="post" action="<?= $escape($url('recipe-delete')) ?>" onsubmit="return confirm('Är du säker på att du vill ta bort receptet?');">
                <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
                <input type="hidden" name="id" value="<?= $escape($recipe['id']) ?>">
                <button type="submit">Ta bort recept</button>
            </form>
        </article>
    <?php else: ?><h2>Sidan hittades inte</h2><?php endif; ?>

    <?php if ($page !== 'login'): ?>
        <form method="post" action="<?= $escape($url('logout')) ?>">
            <input type="hidden" name="_token" value="<?= $escape(Csrf::token()) ?>">
            <button type="submit">Logga ut</button>
        </form>
    <?php endif; ?>
</main>
<?php if ($page === 'recipe-create' || $page === 'recipe-edit'): ?>
<script src="<?= $escape($basePath . '/js/ingredients.js') ?>" defer></script>
<?php endif; ?>
</body>
</html>
