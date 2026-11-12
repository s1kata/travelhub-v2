/**
 * Лента постов: раскрытие карточки на месте, deep link #slug.
 */
(function () {
    'use strict';

    function setExpanded(card, open) {
        if (!card) return;
        var panel = card.querySelector('.th-post-card__panel');
        var btn = card.querySelector('.th-post-card__toggle');
        card.classList.toggle('is-open', open);
        if (btn) {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            var label = btn.getAttribute('data-label-open');
            var labelClose = btn.getAttribute('data-label-close');
            if (label && labelClose) {
                btn.querySelector('.th-post-card__toggle-text').textContent = open ? labelClose : label;
            }
        }
        if (panel) {
            panel.hidden = !open;
            panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        }
    }

    function openByHash() {
        var hash = (window.location.hash || '').replace(/^#/, '');
        if (!hash) return;
        var card = document.getElementById('post-' + hash);
        if (!card) return;
        setExpanded(card, true);
        window.setTimeout(function () {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 80);
    }

    function init() {
        var cards = document.querySelectorAll('.th-post-card[data-post-slug]');
        cards.forEach(function (card) {
            var btn = card.querySelector('.th-post-card__toggle');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var isOpen = card.classList.contains('is-open');
                setExpanded(card, !isOpen);
                var slug = card.getAttribute('data-post-slug') || '';
                if (!isOpen && slug && history.replaceState) {
                    history.replaceState(null, '', window.location.pathname + window.location.search + '#' + slug);
                }
            });
        });
        openByHash();
        window.addEventListener('hashchange', openByHash);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
