// Kolači: dugmad −/+ po kilogramu ili komadu, sa ukupnom cenom na dugmetu
(function () {
    var form = document.getElementById('products');
    if (!form) return;
    var currency = form.getAttribute('data-currency');
    var submit = form.querySelector('[data-submit]');

    function fmt(n) {
        return (Math.round(n * 100) / 100).toString().replace('.', ',');
    }
    function money(n) {
        return Math.round(n).toLocaleString('sr-RS').replace(/,/g, '.') + ' ' + currency;
    }

    function update() {
        var total = 0;
        form.querySelectorAll('[data-product]').forEach(function (p) {
            var qty = parseFloat(p.querySelector('input').value) || 0;
            total += qty * parseFloat(p.getAttribute('data-price'));
        });
        submit.disabled = total <= 0;
        submit.textContent = total > 0 ? 'Dodaj u porudžbinu · ' + money(total) : 'Izaberite količinu';
    }

    form.querySelectorAll('[data-product]').forEach(function (p) {
        var step = parseFloat(p.getAttribute('data-step'));
        var min = parseFloat(p.getAttribute('data-min'));
        var unit = p.getAttribute('data-unit') === 'kg' ? 'kg' : 'kom';
        var input = p.querySelector('input');
        var out = p.querySelector('[data-value]');

        function set(v) {
            v = Math.max(0, Math.round(v * 100) / 100);
            input.value = v;
            out.textContent = fmt(v) + ' ' + unit;
            p.classList.toggle('selected', v > 0);
            update();
        }
        p.querySelector('[data-plus]').addEventListener('click', function () {
            var v = parseFloat(input.value) || 0;
            set(v < min ? min : v + step);
        });
        p.querySelector('[data-minus]').addEventListener('click', function () {
            var v = parseFloat(input.value) || 0;
            set(v - step < min ? 0 : v - step);
        });
    });
    update();
})();
