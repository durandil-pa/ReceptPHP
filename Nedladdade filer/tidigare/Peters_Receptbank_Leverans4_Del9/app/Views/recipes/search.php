<?php require APP_PATH.'/Views/layout/header.php'; ?>

<h2>Sök recept</h2>

<form method="get" action="index.php">
<input type="hidden" name="page" value="recipe_search">

<input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Sök recept...">

<select name="category">
<option value="0">Alla kategorier</option>
<?php foreach($categories as $c): ?>
<option value="<?= $c->id ?>" <?= $selectedCategory==$c->id?'selected':'' ?>>
<?= htmlspecialchars($c->name) ?>
</option>
<?php endforeach; ?>
</select>

<button>Sök</button>
</form>

<hr>

<table class="recipe-table">
<tr><th>Recept</th><th>Kategori</th></tr>
<?php foreach($recipes as $recipe): ?>
<tr>
<td><?= htmlspecialchars($recipe->title) ?></td>
<td><?= htmlspecialchars($recipe->category_id) ?></td>
</tr>
<?php endforeach; ?>
</table>

<?php require APP_PATH.'/Views/layout/footer.php'; ?>
