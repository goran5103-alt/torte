// Kalendar slobodnih dana i dostava u formi za porudžbinu
(function () {
    var form = document.getElementById('checkout');
    if (!form) return;

    var data = JSON.parse(document.getElementById('calendar-data').textContent || 'null');
    var input = form.querySelector('[name=date]');
    var wrap = form.querySelector('[data-calendar]');
    var label = form.querySelector('[data-date-label]');
    var months = ['januar', 'februar', 'mart', 'april', 'maj', 'jun', 'jul', 'avgust', 'septembar', 'oktobar', 'novembar', 'decembar'];
    var days = ['nedelja', 'ponedeljak', 'utorak', 'sreda', 'četvrtak', 'petak', 'subota'];

    function iso(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function parse(s) {
        var p = s.split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
    }

    var min = parse(data.min);
    var max = parse(data.max);
    var view = input.value ? parse(input.value) : new Date(min);
    view.setDate(1);

    function available(s) {
        return s >= data.min && s <= data.max && !data.unavailable[s];
    }

    function describe(s) {
        var d = parse(s);
        return days[d.getDay()] + ', ' + d.getDate() + '. ' + months[d.getMonth()];
    }

    function render() {
        var y = view.getFullYear();
        var m = view.getMonth();
        var first = new Date(y, m, 1);
        var offset = (first.getDay() + 6) % 7; // ponedeljak prvi
        var count = new Date(y, m + 1, 0).getDate();
        var prevOk = new Date(y, m, 0) >= new Date(min.getFullYear(), min.getMonth(), 1);
        var nextOk = new Date(y, m + 1, 1) <= max;

        var html = '<div class="cal-head">' +
            '<button type="button" data-nav="-1" ' + (prevOk ? '' : 'disabled') + ' aria-label="Prethodni mesec">‹</button>' +
            '<strong>' + months[m] + ' ' + y + '</strong>' +
            '<button type="button" data-nav="1" ' + (nextOk ? '' : 'disabled') + ' aria-label="Sledeći mesec">›</button></div>' +
            '<div class="cal-grid">' + ['P', 'U', 'S', 'Č', 'P', 'S', 'N'].map(function (d) { return '<span class="dow">' + d + '</span>'; }).join('');
        for (var i = 0; i < offset; i++) html += '<span></span>';
        for (var day = 1; day <= count; day++) {
            var s = iso(new Date(y, m, day));
            var ok = available(s);
            var reason = data.unavailable[s];
            html += '<button type="button" class="day' + (s === input.value ? ' selected' : '') + (reason ? ' full' : '') + '" data-day="' + s + '"' +
                (ok ? '' : ' disabled') + (reason ? ' title="' + reason + '"' : '') + '>' + day + '</button>';
        }
        html += '</div>';
        wrap.innerHTML = html;
        if (input.value) label.textContent = 'Izabrano: ' + describe(input.value);
    }

    wrap.addEventListener('click', function (e) {
        var nav = e.target.closest('[data-nav]');
        if (nav && !nav.disabled) {
            view.setMonth(view.getMonth() + +nav.getAttribute('data-nav'));
            render();
            return;
        }
        var day = e.target.closest('[data-day]');
        if (day && !day.disabled) {
            input.value = day.getAttribute('data-day');
            render();
        }
    });

    form.addEventListener('submit', function (e) {
        if (!input.value) {
            e.preventDefault();
            label.textContent = 'Izaberite dan na kalendaru.';
            label.classList.add('error');
            wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    var address = form.querySelector('[data-address]');
    form.querySelectorAll('[name=fulfillment]').forEach(function (r) {
        r.addEventListener('change', function () {
            if (address) address.hidden = form.querySelector('[name=fulfillment]:checked').value !== 'dostava';
        });
    });

    render();
})();
