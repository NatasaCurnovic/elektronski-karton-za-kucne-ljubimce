// Provera slike u pregledaču (server proverava ponovo)
function proveriSliku(input) {
    const f = input.files[0];
    const ok = !f || (['image/jpeg', 'image/png', 'image/webp'].includes(f.type) && f.size <= 2 * 1024 * 1024);
    input.classList.toggle('is-invalid', !ok);
    return ok;
}

// Pregled izabrane slike pre slanja
document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
        const f = inp.files[0];
        const box = document.querySelector(inp.dataset.preview);
        if (f && f.type.startsWith('image/')) {
            box.innerHTML = '';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(f);
            box.appendChild(img);
        }
    });
});