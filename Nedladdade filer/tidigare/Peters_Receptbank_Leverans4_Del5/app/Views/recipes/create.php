<?php require APP_PATH.'/Views/layout/header.php'; ?>
<h2>Nytt recept</h2>

<form method="post" action="index.php?page=recipe_store">

<label>Receptnamn</label><br>
<input type="text" name="title" style="width:500px"><br><br>

<label>Kategori</label><br>
<select name="category_id">
<?php foreach($categories as $c): ?>
<option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
<?php endforeach; ?>
</select><br><br>

<label>Portioner</label><br>
<input type="number" name="servings" value="4"><br><br>

<label>Tillagningstid (min)</label><br>
<input type="number" name="cook_time"><br><br>

<label>Tillagningsbeskrivning</label><br>
<textarea name="instructions" rows="12" cols="80"></textarea><br><br>

<button type="submit">Spara recept</button>

</form>

<?php require APP_PATH.'/Views/layout/footer.php'; ?>
