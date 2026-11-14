document.querySelectorAll('.rivage-gallery-editor').forEach((editor) => {
    let items = JSON.parse(editor.dataset.items);
    const list = editor.querySelector('ol');
    const render = () => {
        editor.querySelector('input').value = items.map(item => item.id).join(',');
        list.replaceChildren();
        items.forEach((item, index) => {
            const li = document.createElement('li');
            li.style.marginBottom = '12px';
            const image = document.createElement('img'); image.src = item.url; image.alt = ''; image.width = 70; image.style.verticalAlign = 'middle'; li.append(image);
            li.append(document.createTextNode(' ' + item.title + ' '));
            for (const [label, delta] of [['Move up', -1], ['Move down', 1], ['Remove', 0]]) {
                const button = document.createElement('button'); button.type = 'button'; button.className = 'button'; button.textContent = label; button.setAttribute('aria-label', label + ': ' + item.title);
                button.disabled = delta !== 0 && !items[index + delta];
                button.addEventListener('click', () => {
                    if (!delta) items.splice(index, 1);
                    else [items[index], items[index + delta]] = [items[index + delta], items[index]];
                    render();
                    list.querySelectorAll('li')[Math.min(Math.max(0,index + delta),items.length-1)]?.querySelector('button')?.focus();
                });
                li.append(button);
            }
            list.append(li);
        });
    };
    editor.querySelector('.rivage-gallery-choose').addEventListener('click', () => {
        const frame = wp.media({title: 'Choose room gallery photos', button: {text: 'Use Selected Photos'}, library: {type: 'image'}, multiple: true});
        frame.on('open', () => items.forEach(item => frame.state().get('selection').add(wp.media.attachment(item.id))));
        frame.on('select', () => {
            items = frame.state().get('selection').toJSON().slice(0,12).map(item => ({id:item.id,url:item.sizes?.thumbnail?.url || item.url,title:item.title}));
            render();
        });
        frame.open();
    });
    render();
});
