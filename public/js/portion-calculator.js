(function () {
    'use strict';

    var input = document.getElementById('portion-calculator');
    var servings = document.getElementById('recipe-servings');
    if (!input || !servings) {
        return;
    }

    var baseServings = Number(input.getAttribute('data-base-servings'));
    if (!Number.isFinite(baseServings) || baseServings <= 0) {
        return;
    }

    var format = new Intl.NumberFormat('sv-SE', { maximumFractionDigits: 2 });

    function update() {
        var requestedServings = Number(input.value);
        if (!Number.isFinite(requestedServings) || requestedServings < 1) {
            return;
        }

        servings.textContent = String(requestedServings);
        document.querySelectorAll('[data-base-amount]').forEach(function (item) {
            var rawAmount = item.getAttribute('data-base-amount');
            var amount = Number(String(rawAmount).replace(',', '.'));
            var output = item.querySelector('[data-portion-amount]');

            if (!output || rawAmount === '' || !Number.isFinite(amount)) {
                return;
            }

            output.textContent = format.format(amount * requestedServings / baseServings);
        });
    }

    input.addEventListener('input', update);
}());
