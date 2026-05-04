(function () {
    const i18n = window.eleadsWooCommerce?.i18n || {};

    const categoryPanel = document.querySelector('[data-eleads-category-panel]');

    if (categoryPanel) {
        const checkboxes = () => Array.from(categoryPanel.querySelectorAll('input[type="checkbox"]'));

        document.querySelectorAll('[data-eleads-categories-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const checked = button.getAttribute('data-eleads-categories-action') === 'select';

                checkboxes().forEach((checkbox) => {
                    checkbox.checked = checked;
                });
            });
        });
    }

    const filterPanel = (key) => document.querySelector(`[data-eleads-filter-panel="${key}"]`);
    const filterActions = (key) => Array.from(document.querySelectorAll(`[data-eleads-filter-actions="${key}"]`));
    const filterCheckboxes = (key) => {
        const panel = filterPanel(key);

        return panel ? Array.from(panel.querySelectorAll('input[type="checkbox"]')) : [];
    };

    document.querySelectorAll('[data-eleads-filter-toggle]').forEach((toggle) => {
        const key = toggle.getAttribute('data-eleads-filter-toggle');
        const panel = filterPanel(key);

        if (!key || !panel) {
            return;
        }

        const update = () => {
            panel.classList.toggle('is-hidden', !toggle.checked);
            filterActions(key).forEach((actions) => {
                actions.classList.toggle('is-hidden', !toggle.checked);
            });
        };

        toggle.addEventListener('change', update);
        update();
    });

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        const button = target ? target.closest('[data-eleads-filter-action][data-eleads-filter-target]') : null;

        if (!button) {
            return;
        }

        const key = button.getAttribute('data-eleads-filter-target') || '';
        const checked = button.getAttribute('data-eleads-filter-action') === 'select';

        filterCheckboxes(key).forEach((checkbox) => {
            checkbox.checked = checked;
        });
    });

    document.querySelectorAll('[data-eleads-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-eleads-copy') || '';

            try {
                await navigator.clipboard.writeText(value);
                button.textContent = i18n.copied || 'Скопійовано';
                window.setTimeout(() => {
                    button.textContent = i18n.copy || 'Копіювати URL';
                }, 1400);
            } catch (error) {
                window.prompt('Copy URL', value);
            }
        });
    });

    const postFeedAction = async (action, language) => {
        const body = new window.URLSearchParams();
        body.set('action', action);
        body.set('language', language);
        body.set('nonce', window.eleadsWooCommerce?.nonce || '');

        const response = await window.fetch(window.eleadsWooCommerce?.ajaxUrl || window.ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body,
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload?.data?.message || 'Feed generation failed');
        }

        return payload.data;
    };

    const updateFeedRow = (row, state) => {
        const status = row.querySelector('.eleads-status');
        const progressBar = row.querySelector('[data-eleads-progress-bar]');
        const progressMeta = row.querySelector('[data-eleads-progress-meta]');
        const download = row.querySelector('.button-secondary');

        const total = Number(state.total || 0);
        const processed = Number(state.processed || 0);
        const offers = Number(state.offers || 0);
        const percent = total > 0 ? Math.min(100, Math.floor((processed / total) * 100)) : (state.status === 'ready' ? 100 : 0);

        if (progressBar) {
            progressBar.style.width = `${percent}%`;
        }

        if (progressMeta) {
            progressMeta.textContent = `Товарів: ${processed} / ${total}`;
        }

        if (status) {
            status.classList.toggle('eleads-status--ready', state.status === 'ready');
            status.classList.toggle('eleads-status--muted', state.status !== 'ready' && state.status !== 'failed');
            status.classList.toggle('eleads-status--failed', state.status === 'failed');

            if (state.status === 'ready') {
                status.textContent = `${i18n.ready || 'Фід готовий'}, пропозицій: ${offers}`;
            } else if (state.status === 'failed') {
                status.textContent = i18n.failed || 'Помилка генерації';
            } else {
                status.textContent = i18n.generating || 'Генерація...';
            }
        }

        if (download && state.status === 'ready') {
            download.classList.remove('is-disabled');
            download.removeAttribute('aria-disabled');
            download.removeAttribute('tabindex');
        }
    };

    document.querySelectorAll('[data-eleads-generate-feed]').forEach((button) => {
        button.addEventListener('click', async () => {
            const language = button.getAttribute('data-eleads-generate-feed') || '';
            const row = button.closest('[data-eleads-feed-row]');

            if (!language || !row) {
                return;
            }

            button.disabled = true;

            try {
                let state = await postFeedAction('eleads_feed_start', language);
                updateFeedRow(row, state);

                while (state.status === 'running') {
                    state = await postFeedAction('eleads_feed_process', language);
                    updateFeedRow(row, state);
                }
            } catch (error) {
                updateFeedRow(row, { status: 'failed', total: 0, processed: 0, offers: 0 });
            } finally {
                button.disabled = false;
            }
        });
    });
})();
