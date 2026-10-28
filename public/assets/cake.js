// Čarobnjak za tortu: veličina → ukus → izgled (AI / galerija / opis) → pregled
(function () {
    var root = document.getElementById('cake-wizard');
    if (!root) return;

    var config = JSON.parse(document.getElementById('cake-config').textContent);
    var csrf = root.getAttribute('data-csrf');
    var $ = function (sel, el) { return (el || root).querySelector(sel); };
    var $$ = function (sel, el) { return Array.prototype.slice.call((el || root).querySelectorAll(sel)); };

    var state = {
        step: 1,
        sizeId: null,
        flavorId: null,
        mode: ($('.tabs .active') || {}).getAttribute ? $('.tabs .active').getAttribute('data-mode') : 'opis',
        colors: [],
        ai: { token: null, designs: [], selected: null, images: {}, busy: false, remaining: config.aiRemaining },
        galleryId: null
    };

    function money(n) {
        return Math.round(n).toLocaleString('sr-RS').replace(/,/g, '.') + ' ' + config.currency;
    }
    function find(list, id) {
        for (var i = 0; i < list.length; i++) if (list[i].id === id) return list[i];
        return null;
    }
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function basePrice() {
        var size = find(config.sizes, state.sizeId);
        var flavor = find(config.flavors, state.flavorId);
        if (!size || !flavor) return null;
        return Math.round(size.kg * flavor.pricePerKg);
    }
    function surcharge() {
        if (state.mode === 'ai' && state.ai.selected !== null) {
            return state.ai.designs[state.ai.selected].surcharge || 0;
        }
        return 0;
    }

    function showError(msg) {
        var el = $('[data-error]');
        el.textContent = msg || '';
        el.hidden = !msg;
        if (msg) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function render() {
        $$('[data-step]').forEach(function (s) { s.hidden = +s.getAttribute('data-step') !== state.step; });
        $$('[data-step-dot]').forEach(function (d) {
            var n = +d.getAttribute('data-step-dot');
            d.classList.toggle('active', n === state.step);
            d.classList.toggle('done', n < state.step);
        });
        $('[data-back]').hidden = state.step === 1;
        $('[data-next]').textContent = state.step === 4 ? 'Dodaj u porudžbinu' : 'Dalje';

        var base = basePrice();
        $('[data-estimate]').innerHTML = base === null ? '' :
            '<span>okvirno</span><strong>' + money(base + surcharge()) + '</strong>';

        // izgled
        $$('.tabs [data-mode]').forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-mode') === state.mode); });
        $$('[data-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== state.mode; });
        $$('.field-group').forEach(function (g) {
            g.hidden = g.getAttribute('data-for').split(' ').indexOf(state.mode) === -1;
        });
        var label = $('[data-label-ai]');
        if (label) label.textContent = state.mode === 'opis' ? 'Opišite izgled torte' : 'Motiv ili tema';

        $$('[data-color]').forEach(function (b) {
            b.classList.toggle('selected', state.colors.indexOf(b.getAttribute('data-color')) !== -1);
        });
        $$('[data-gallery]').forEach(function (b) {
            b.classList.toggle('selected', +b.getAttribute('data-gallery') === state.galleryId);
        });
        renderRemaining();
        if (state.step === 4) renderSummary();
    }

    function renderRemaining() {
        var el = $('[data-remaining]');
        if (!el) return;
        var btn = $('[data-generate]');
        if (state.ai.remaining <= 0) {
            el.textContent = state.ai.token ? 'Iskoristili ste sva generisanja. Izaberite jedan od predloga ili pređite na „Opisaću sam".' :
                'Generisanje trenutno nije dostupno. Izaberite „Opisaću sam".';
            btn.disabled = true;
        } else {
            el.textContent = state.ai.token
                ? 'Ne sviđa vam se nijedan? Promenite opis i probajte ponovo (još ' + state.ai.remaining + ').'
                : 'Napravićemo 6 različitih predloga koje možemo da izradimo. Traje oko pola minuta.';
            btn.disabled = state.ai.busy;
            btn.textContent = state.ai.token ? '✨ Napravi nove predloge' : '✨ Napravi 6 predloga';
        }
    }

    function renderDesigns() {
        var wrap = $('[data-designs]');
        wrap.innerHTML = '';
        state.ai.designs.forEach(function (d, i) {
            var img = state.ai.images[i];
            var card = document.createElement('button');
            card.type = 'button';
            card.className = 'design' + (state.ai.selected === i ? ' selected' : '');
            card.disabled = !img || !img.url;
            card.innerHTML =
                '<div class="design-img">' +
                (img && img.url ? '<img src="' + esc(img.url) + '" alt="">' :
                    img && img.error ? '<span class="img-error">' + esc(img.error) + '<br><u>Pokušaj ponovo</u></span>' :
                        '<span class="spinner" aria-label="Pravi se slika"></span>') +
                '</div>' +
                '<div class="design-body"><strong>' + esc(d.title) + '</strong>' +
                '<span>' + esc(d.description) + '</span>' +
                (d.surcharge > 0 ? '<em>+ ' + money(d.surcharge) + ' za izradu dekoracije</em>' : '<em>bez doplate</em>') +
                '</div>';
            if (img && img.error) {
                card.disabled = false;
                card.addEventListener('click', function () { loadImage(i); });
            } else {
                card.addEventListener('click', function () {
                    state.ai.selected = i;
                    renderDesigns();
                    render();
                });
            }
            wrap.appendChild(card);
        });
        $('[data-disclaimer]').hidden = state.ai.designs.length === 0;
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrf },
            credentials: 'same-origin',
            body: JSON.stringify(body || {})
        }).then(function (r) {
            return r.json().catch(function () { return { error: 'Greška na serveru. Pokušajte ponovo.' }; })
                .then(function (data) { if (!r.ok && !data.error) data.error = 'Greška (' + r.status + ')'; return data; });
        });
    }

    function loadImage(i, attempt) {
        attempt = attempt || 0;
        state.ai.images[i] = {};
        renderDesigns();
        var token = state.ai.token;
        post('/api/dizajn/' + token + '/' + i).then(function (res) {
            if (token !== state.ai.token) return;
            if (res.status === 'done') {
                state.ai.images[i] = { url: res.url };
            } else if (res.status === 'working' && attempt < 40) {
                setTimeout(function () { loadImage(i, attempt + 1); }, 3000);
                return;
            } else {
                state.ai.images[i] = { error: res.error || 'Slika nije uspela.' };
            }
            renderDesigns();
        }).catch(function () {
            state.ai.images[i] = { error: 'Veza je prekinuta.' };
            renderDesigns();
        });
    }

    function generate() {
        var description = $('[name=description]').value.trim();
        if (description.length < 3) {
            showError('Opišite motiv torte, npr. „jednorozi, roze i zlatna".');
            return;
        }
        showError('');
        state.ai.busy = true;
        state.ai.selected = null;
        var status = $('[data-ai-status]');
        status.hidden = false;
        status.innerHTML = '<span class="spinner small"></span> Smišljamo dizajne koje možemo da napravimo…';
        renderRemaining();
        post('/api/dizajn', {
            size_id: state.sizeId,
            occasion: $('[name=occasion]').value,
            description: description,
            colors: state.colors,
            inscription: $('[name=inscription]').value.trim()
        }).then(function (res) {
            state.ai.busy = false;
            status.hidden = true;
            if (res.error) {
                showError(res.error);
                renderRemaining();
                return;
            }
            state.ai.token = res.token;
            state.ai.designs = res.designs;
            state.ai.remaining = res.remaining;
            state.ai.images = {};
            renderDesigns();
            render();
            // Slike se prave paralelno, svaka u svom zahtevu.
            res.designs.forEach(function (_, i) { loadImage(i); });
        }).catch(function () {
            state.ai.busy = false;
            status.hidden = true;
            showError('Veza je prekinuta. Pokušajte ponovo.');
            renderRemaining();
        });
    }

    function designSummary() {
        if (state.mode === 'ai' && state.ai.selected !== null) {
            var d = state.ai.designs[state.ai.selected];
            return { title: d.title, text: d.description, img: state.ai.images[state.ai.selected].url };
        }
        if (state.mode === 'galerija' && state.galleryId) {
            var g = find(config.gallery, state.galleryId);
            return { title: 'Po uzoru na ' + (g.code || 'sliku iz galerije'), text: g.title, img: g.url };
        }
        return { title: 'Po vašem opisu', text: $('[name=description]').value.trim(), img: null };
    }

    function renderSummary() {
        var size = find(config.sizes, state.sizeId);
        var flavor = find(config.flavors, state.flavorId);
        var d = designSummary();
        var inscription = $('[name=inscription]').value.trim();
        var note = $('[name=note]').value.trim();
        var base = basePrice();
        var extra = surcharge();
        $('[data-summary]').innerHTML =
            (d.img ? '<img class="summary-img" src="' + esc(d.img) + '" alt="">' : '') +
            '<dl>' +
            '<dt>Veličina</dt><dd>' + esc(size.name) + ' · oko ' + esc(String(size.kg).replace('.', ',')) + ' kg</dd>' +
            '<dt>Ukus</dt><dd>' + esc(flavor.name) + '</dd>' +
            '<dt>Izgled</dt><dd><strong>' + esc(d.title) + '</strong>' + (d.text ? '<br>' + esc(d.text) : '') + '</dd>' +
            (state.colors.length && state.mode !== 'galerija' ? '<dt>Boje</dt><dd>' + esc(state.colors.join(', ')) + '</dd>' : '') +
            (inscription && state.mode !== 'galerija' ? '<dt>Natpis</dt><dd>„' + esc(inscription) + '"</dd>' : '') +
            (note ? '<dt>Napomena</dt><dd>' + esc(note) + '</dd>' : '') +
            '<dt>Cena</dt><dd>' + money(base) + (extra ? ' + ' + money(extra) + ' dekoracija' : '') +
            ' = <strong>' + money(base + extra) + '</strong> <small>(okvirno)</small></dd>' +
            '</dl>';
    }

    function validate(step) {
        if (step === 1 && !state.sizeId) return 'Izaberite veličinu.';
        if (step === 2 && !state.flavorId) return 'Izaberite ukus.';
        if (step === 3) {
            if (state.mode === 'ai' && state.ai.selected === null) {
                return state.ai.token ? 'Kliknite na predlog koji vam se dopada.' : 'Opišite motiv i kliknite „Napravi 6 predloga".';
            }
            if (state.mode === 'galerija' && !state.galleryId) return 'Izaberite tortu iz galerije.';
            if (state.mode === 'opis' && $('[name=description]').value.trim().length < 3) return 'Opišite kako torta treba da izgleda.';
        }
        return null;
    }

    function submit() {
        var btn = $('[data-next]');
        btn.disabled = true;
        post('/korpa/torta', {
            size_id: state.sizeId,
            flavor_id: state.flavorId,
            design_mode: state.mode,
            design_token: state.ai.token,
            design_idx: state.ai.selected,
            gallery_id: state.galleryId,
            design_text: $('[name=description]').value.trim(),
            colors: state.colors,
            occasion: $('[name=occasion]').value,
            inscription: state.mode === 'galerija' ? '' : $('[name=inscription]').value.trim(),
            note: $('[name=note]').value.trim()
        }).then(function (res) {
            if (res.error) {
                btn.disabled = false;
                showError(res.error);
                return;
            }
            window.location = res.redirect || '/korpa';
        }).catch(function () {
            btn.disabled = false;
            showError('Veza je prekinuta. Pokušajte ponovo.');
        });
    }

    // događaji
    $$('[name=size]').forEach(function (r) {
        r.addEventListener('change', function () { state.sizeId = +r.value; showError(''); render(); });
    });
    $$('[name=flavor]').forEach(function (r) {
        r.addEventListener('change', function () { state.flavorId = +r.value; showError(''); render(); });
    });
    $$('.tabs [data-mode]').forEach(function (t) {
        t.addEventListener('click', function () { state.mode = t.getAttribute('data-mode'); showError(''); render(); });
    });
    $$('[data-color]').forEach(function (b) {
        b.addEventListener('click', function () {
            var c = b.getAttribute('data-color');
            var i = state.colors.indexOf(c);
            if (i !== -1) state.colors.splice(i, 1);
            else if (state.colors.length < 4) state.colors.push(c);
            render();
        });
    });
    $$('[data-gallery]').forEach(function (b) {
        b.addEventListener('click', function () { state.galleryId = +b.getAttribute('data-gallery'); showError(''); render(); });
    });
    var gen = $('[data-generate]');
    if (gen) gen.addEventListener('click', generate);

    $('[data-next]').addEventListener('click', function () {
        var err = validate(state.step);
        if (err) { showError(err); return; }
        showError('');
        if (state.step === 4) { submit(); return; }
        state.step++;
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    $('[data-back]').addEventListener('click', function () {
        showError('');
        state.step = Math.max(1, state.step - 1);
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // dolazak iz galerije (?galerija=ID)
    if (config.preselectGallery && find(config.gallery, config.preselectGallery)) {
        state.mode = 'galerija';
        state.galleryId = config.preselectGallery;
    }

    render();
})();
