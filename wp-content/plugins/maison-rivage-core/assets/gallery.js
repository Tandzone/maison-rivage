document.querySelectorAll('.rivage-gallery').forEach((gallery) => {
    if (typeof HTMLDialogElement === 'undefined' || typeof HTMLDialogElement.prototype.showModal !== 'function') return;
    const photos = Array.from(gallery.querySelectorAll('[data-gallery-photo]'));
    const main = gallery.querySelector('.rivage-gallery-main');
    const mainImage = main.querySelector('img');
    const caption = gallery.querySelector('.rivage-gallery-caption');
    let index = 0; let opener; let start;
    const dialog = document.createElement('dialog');
    dialog.className = 'rivage-lightbox'; dialog.setAttribute('aria-label', 'Room photo viewer');
    dialog.innerHTML = '<button type="button" class="rivage-lightbox-close" aria-label="Close photo viewer" autofocus>Close <span aria-hidden="true">×</span></button><figure><img alt=""><figcaption aria-live="polite"></figcaption></figure><div class="rivage-lightbox-nav"><button type="button" data-direction="-1" aria-label="Previous photo">← <span>Previous</span></button><button type="button" data-direction="1" aria-label="Next photo"><span>Next</span> →</button></div>';
    gallery.append(dialog);
    [main, ...photos].forEach(control => {
        control.setAttribute('role', 'button');
        control.addEventListener('keydown', event => { if (event.key === ' ') { event.preventDefault(); control.click(); } });
    });
    main.setAttribute('aria-haspopup', 'dialog');
    const image = dialog.querySelector('img'); const label = dialog.querySelector('figcaption');
    const show = (next) => {
        index = (next + photos.length) % photos.length;
        const photo = photos[index];
        main.href = photo.href; mainImage.src = photo.dataset.src;
        mainImage.srcset = photo.dataset.srcset; mainImage.sizes = '(max-width: 800px) 100vw, 65vw'; mainImage.alt = photo.dataset.alt;
        const text = photo.dataset.caption + ' · ' + (index + 1) + ' / ' + photos.length;
        caption.textContent = text;
        photos.forEach((p,i) => { if (i === index) p.setAttribute('aria-current','true'); else p.removeAttribute('aria-current'); });
        image.src = photo.href; image.alt = photo.dataset.alt; label.textContent = text;
    };
    photos.forEach((photo,i) => photo.addEventListener('click', (event) => { event.preventDefault(); show(i); }));
    main.addEventListener('click', event => {
        event.preventDefault(); opener = document.activeElement; show(index); dialog.showModal(); document.documentElement.classList.add('rivage-viewer-open');
    });
    dialog.querySelector('.rivage-lightbox-close').addEventListener('click', () => dialog.close());
    dialog.querySelectorAll('[data-direction]').forEach(button => button.addEventListener('click', () => show(index + Number(button.dataset.direction))));
    dialog.addEventListener('keydown', event => {
        if (event.key === 'Tab') {
            const controls = Array.from(dialog.querySelectorAll('button:not([disabled])'));
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') { event.preventDefault(); show(index + (event.key === 'ArrowRight' ? 1 : -1)); }
    });
    dialog.addEventListener('close', () => { document.documentElement.classList.remove('rivage-viewer-open'); opener?.focus(); });
    image.draggable = false;
    image.addEventListener('pointerdown', event => {
        if (!event.isPrimary || event.button !== 0) { start = null; return; }
        start = [event.clientX, event.clientY]; image.setPointerCapture(event.pointerId);
    });
    image.addEventListener('pointerup', event => {
        if (!start) return;
        const dx = event.clientX - start[0], dy = event.clientY - start[1];
        if (Math.abs(dx)>60 && Math.abs(dx)>Math.abs(dy)*1.5) show(index + (dx<0 ? 1 : -1));
        start = null;
    });
    image.addEventListener('pointercancel', () => { start = null; });
});
