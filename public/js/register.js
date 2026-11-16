const form = document.getElementById('registerForm');

if (form) {
    const koraci = form.querySelectorAll('.korak');
    const indikatori = document.querySelectorAll('.korak-broj');
    let trenutni = parseInt(form.dataset.start, 10) || 1;

    function prikazi(n) {
        trenutni = n;
        koraci.forEach(k => k.classList.toggle('d-none', Number(k.dataset.korak) !== n));
        indikatori.forEach(i => i.classList.toggle('aktivan', Number(i.dataset.ind) <= n));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    const v = id => form.querySelector('#' + id).value.trim();
    let ok;

    function proveri(id, uslov) {
        form.querySelector('#' + id).classList.toggle('is-invalid', !uslov);
        if (!uslov) ok = false;
    }

    const validacije = {
        1: function () {
            const lozinka = form.querySelector('#password').value;
            const potvrda = form.querySelector('#password_confirm').value;
            proveri('first_name', v('first_name').length >= 2 && v('first_name').length <= 100);
            proveri('last_name', v('last_name').length >= 2 && v('last_name').length <= 100);
            proveri('email', /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v('email')));
            proveri('phone', v('phone') === '' || /^\+?[0-9 \/-]{6,20}$/.test(v('phone')));
            proveri('password', lozinka.length >= 8 && /[A-Za-z]/.test(lozinka) && /[0-9]/.test(lozinka));
            proveri('password_confirm', lozinka === potvrda);
        },
        2: function () {
            proveri('pet_name', v('pet_name').length >= 1 && v('pet_name').length <= 100);
            proveri('species', v('species') !== '');
            proveri('sex', v('sex') !== '');
            const d = v('birth_date');
            proveri('birth_date', d === '' || new Date(d) <= new Date());
        },
        3: function () {
            const izabran = form.querySelector('input[name="veterinarian_id"]:checked');
            document.getElementById('vetGreska').textContent = izabran ? '' : 'Izaberite veterinara.';
            if (!izabran) ok = false;
        }
    };

    form.querySelectorAll('.btn-dalje').forEach(btn => {
        btn.addEventListener('click', function () {
            ok = true;
            validacije[trenutni]();
            if (!ok) return;
            if (trenutni < 3) {
                prikazi(trenutni + 1);
            } else {
                new bootstrap.Modal(document.getElementById('potvrdaModal')).show();
            }
        });
    });

    form.querySelectorAll('.btn-nazad').forEach(btn => {
        btn.addEventListener('click', () => prikazi(trenutni - 1));
    });

    // Enter ne sme da pošalje formu pre poslednjeg koraka
    form.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            form.querySelector('.korak:not(.d-none) .btn-dalje').click();
        }
    });

    document.getElementById('btnKreiraj').addEventListener('click', () => form.submit());

    prikazi(trenutni);
}