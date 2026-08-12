<?php require APP_PATH.'/Views/layout/header.php'; ?>

<h2>Recept</h2>

<p><a href="index.php?page=recipe_create">➕ Nytt recept</a></p>

<table border="1" cellpadding="8" cellspacing="0" width="100%">
<thead>
<tr>
<th>ID</th>
<th>Titel</th>
<th>Kategori</th>
<th>Skapad</th>
<th>Åtgärder</th>
</tr>
</thead>
<tbody>
<?php if(!empty($recipes)): ?>
<?php foreach($recipes as $recipe): ?>
<tr>
<td><?= htmlspecialchars($recipe->id) ?></td>
<td><?= htmlspecialchars($recipe->title) ?></td>
<td><?= htmlspecialchars($recipe->category_id) ?></td>
<td><?= htmlspecialchars($recipe->created_at) ?></td>
<td>
<a href="index.php?page=recipe_edit&id=<?= $recipe->id ?>">Redigera</a> |
<a href="index.php?page=recipe_delete&id=<?= $recipe->id ?>" onclick="return confirm('Ta bort receptet?');">Ta bort</a>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="5">Inga recept finns ännu.</td></tr>
<?php endif; ?>
</tbody>
</table>

<?php require APP_PATH.'/Views/layout/footer.php'; ?>
