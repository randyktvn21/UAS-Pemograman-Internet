(() => {
    'use strict';

    document.addEventListener('click', (event) => {
        const panelButton = event.target.closest('[data-toggle-panel]');
        if (panelButton) {
            document.getElementById(panelButton.dataset.togglePanel)?.classList.toggle('open');
            return;
        }
        const detailButton = event.target.closest('[data-toggle-details]');
        if (detailButton) {
            document.getElementById(detailButton.dataset.toggleDetails)?.classList.toggle('open');
        }
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan tindakan ini?')) event.preventDefault();
        });
    });

    const search = document.getElementById('user-search');
    search?.addEventListener('input', () => {
        const keyword = search.value.trim().toLowerCase();
        document.querySelectorAll('#user-table-body > tr[data-search]').forEach((row) => {
            const matched = !keyword || (row.dataset.search || '').includes(keyword);
            row.style.display = matched ? '' : 'none';
            const detail = row.nextElementSibling;
            if (detail?.classList.contains('user-detail-row')) {
                detail.style.display = matched && detail.classList.contains('open') ? 'table-row' : '';
            }
        });
    });
})();
