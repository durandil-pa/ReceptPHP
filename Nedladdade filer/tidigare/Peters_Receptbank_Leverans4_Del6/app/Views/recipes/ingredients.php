<h3>Ingredienser</h3>

<table id="ingredientsTable" width="100%">
<thead>
<tr>
<th>Mängd</th>
<th>Enhet</th>
<th>Ingrediens</th>
<th></th>
</tr>
</thead>
<tbody>
<tr>
<td><input type="number" step="0.01" name="amount[]"></td>

<td>
<select name="unit_id[]">
<?php foreach($units as $unit): ?>
<option value="<?= $unit->id ?>">
<?= htmlspecialchars($unit->short_name ?: $unit->name) ?>
</option>
<?php endforeach; ?>
</select>
</td>

<td>
<input type="text" name="ingredient_name[]" style="width:100%">
</td>

<td>
<button type="button" onclick="removeRow(this)">✖</button>
</td>
</tr>
</tbody>
</table>

<p>
<button type="button" onclick="addIngredientRow()">
➕ Lägg till ingrediens
</button>
</p>
