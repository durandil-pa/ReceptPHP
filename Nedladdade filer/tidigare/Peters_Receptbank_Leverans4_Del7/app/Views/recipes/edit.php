<?php require APP_PATH.'/Views/layout/header.php'; ?>

<h2>Redigera recept</h2>

<form method="post" action="index.php?page=recipe_update">

<input type="hidden" name="id" value="<?= $recipe->id ?>">

<label>Receptnamn</label><br>
<input type="text" name="title"
value="<?= htmlspecialchars($recipe->title) ?>" style="width:500px"><br><br>

<label>Kategori</label><br>
<select name="category_id">
<?php foreach($categories as $cat): ?>
<option value="<?= $cat->id ?>"
<?= $cat->id==$recipe->category_id?'selected':'' ?>>
<?= htmlspecialchars($cat->name) ?>
</option>
<?php endforeach; ?>
</select><br><br>

<label>Ingredienser</label>

<table border="1" width="100%">
<tr><th>Mängd</th><th>Ingrediens</th></tr>

<?php foreach($ingredients as $i): ?>
<tr>
<td><?= htmlspecialchars($i->amount) ?></td>
<td><?= htmlspecialchars($i->ingredient_name) ?></td>
</tr>
<?php endforeach; ?>

</table>

<br>

<label>Tillagningsbeskrivning</label><br>

<textarea name="instructions"
rows="12"
cols="90"><?= htmlspecialchars($recipe->instructions) ?></textarea>

<br><br>

<button>Spara ändringar</button>

</form>

<?php require APP_PATH.'/Views/layout/footer.php'; ?>
