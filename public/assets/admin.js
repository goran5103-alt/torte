// Male pomoćne funkcije za admin stranice
(function () {
    // Potvrda pre slanja forme: <form data-confirm="Sigurno?">
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) {
            e.preventDefault();
        }
    });

    // Potvrda za dugme: <button data-confirm-click="Sigurno?">
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-confirm-click]');
        if (btn && !window.confirm(btn.getAttribute('data-confirm-click'))) {
            e.preventDefault();
        }
    });

    // Predlog poddomena iz naziva
    var source = document.querySelector('[data-slug-source]');
    var target = document.querySelector('[data-slug-target]');
    if (source && target) {
        var touched = target.value !== '';
        target.addEventListener('input', function () { touched = true; });
        source.addEventListener('input', function () {
            if (touched) return;
            var map = { 'č': 'c', 'ć': 'c', 'š': 's', 'ž': 'z', 'đ': 'dj' };
            target.value = source.value.toLowerCase()
                .replace(/[čćšžđ]/g, function (c) { return map[c]; })
                .replace(/poslasticarnica|pekara|torte/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 40);
        });
    }

    // Katalog: oznaka cene prati izbor kg/komad, a dugme za sliku pokazuje da je fajl izabran
    document.querySelectorAll('[data-unit-select]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var label = sel.closest('form').querySelector('[data-unit-label]');
            if (label) label.textContent = sel.value === 'kom' ? 'po komadu' : 'po kg';
        });
    });
    document.querySelectorAll('.file-btn input[type=file]').forEach(function (input) {
        input.addEventListener('change', function () {
            var btn = input.closest('.file-btn');
            if (input.files.length) {
                btn.classList.add('chosen');
                btn.firstChild.textContent = '✓ ' + input.files[0].name.slice(0, 18) + ' ';
            }
        });
    });

    // Klik na red tabele otvara link: <tr data-href="...">
    document.querySelectorAll('tr[data-href]').forEach(function (row) {
        row.classList.add('clickable');
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, input, select, form')) return;
            window.location = row.getAttribute('data-href');
        });
    });
})();
