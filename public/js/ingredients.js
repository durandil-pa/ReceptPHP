'use strict';

document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('ingredient-list');
    var template = document.getElementById('ingredient-row-template');
    var addButton = document.getElementById('add-ingredient');

    if (!list || !template || !addButton) {
        return;
    }

    addButton.addEventListener('click', function () {
        if (list.children.length >= 100) {
            window.alert('Ett recept kan ha högst 100 ingredienser.');
            return;
        }

        var row = template.content.cloneNode(true);
        list.appendChild(row);

        var newRow = list.lastElementChild;
        var ingredientName = newRow.querySelector('input[name="ingredient_name[]"]');
        if (ingredientName) {
            ingredientName.focus();
        }
    });

    list.addEventListener('click', function (event) {
        var removeButton = event.target.closest('[data-remove-ingredient]');
        if (!removeButton) {
            return;
        }

        var row = removeButton.closest('.ingredient-row');
        if (!row) {
            return;
        }

        if (list.children.length === 1) {
            row.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
            var unit = row.querySelector('select[name="unit_id[]"]');
            if (unit) {
                unit.value = '0';
            }
            return;
        }

        row.remove();
    });
});
