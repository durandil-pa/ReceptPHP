function addIngredientRow(){
    const tbody=document.querySelector('#ingredientsTable tbody');
    const row=tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i=>i.value='');
    tbody.appendChild(row);
}

function removeRow(button){
    const tbody=document.querySelector('#ingredientsTable tbody');
    if(tbody.rows.length===1){
        tbody.rows[0].querySelectorAll('input').forEach(i=>i.value='');
        return;
    }
    button.closest('tr').remove();
}
