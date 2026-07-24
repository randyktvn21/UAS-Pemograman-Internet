(() => {
    'use strict';

    document.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-add]');
        if (addButton) {
            const type = addButton.dataset.add;
            const template = document.querySelector(`#template-${type}`);
            const list = document.querySelector(`[data-list="${type}"]`);
            if (template && list) {
                const fragment = template.content.cloneNode(true);
                list.appendChild(fragment);
                list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                list.lastElementChild?.querySelector('input,textarea')?.focus();
            }
            return;
        }

        const removeButton = event.target.closest('.remove-row');
        if (removeButton) {
            const card = removeButton.closest('.repeat-card');
            const list = card?.parentElement;
            if (!card || !list) return;
            const cards = list.querySelectorAll('.repeat-card');
            if (cards.length === 1) {
                card.querySelectorAll('input, textarea').forEach((field) => {
                    field.value = field.type === 'number' ? '60' : '';
                });
            } else if (window.confirm('Hapus baris ini dari formulir?')) {
                card.remove();
            }
        }
    });

    const links = [...document.querySelectorAll('.editor-nav a[href^="#"]')];
    links.forEach((link) => link.addEventListener('click', () => {
        links.forEach((item) => item.classList.remove('active'));
        link.classList.add('active');
    }));

    const sections = [...document.querySelectorAll('.editor-section[id]')];
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
            if (!visible) return;
            links.forEach((link) => link.classList.toggle('active', link.getAttribute('href') === `#${visible.target.id}`));
        }, { rootMargin: '-18% 0px -67% 0px', threshold: [0, .2, .5] });
        sections.forEach((section) => observer.observe(section));
    }
})();
