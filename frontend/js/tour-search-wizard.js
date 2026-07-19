/**
 * Tour Search Wizard — поэтапный UX поверх существующих #tv-* полей.
 * Не дублирует Tourvisor-логику: только шаги, валидация и summary.
 */
(function () {
    'use strict';

    var STEPS = [
        { id: 1, key: 'departure', label: 'Откуда' },
        { id: 2, key: 'country', label: 'Куда' },
        { id: 3, key: 'when', label: 'Когда' },
        { id: 4, key: 'who', label: 'Кто' }
    ];

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function textOfSelect(sel) {
        if (!sel || !sel.options || sel.selectedIndex < 0) return '';
        var opt = sel.options[sel.selectedIndex];
        var t = (opt && opt.textContent) || '';
        return t.replace(/^—\s*/, '').trim();
    }

    function TourSearchWizard(root, options) {
        this.root = root;
        this.options = options || {};
        this.step = Math.max(1, Math.min(4, parseInt(root.getAttribute('data-start-step') || '1', 10) || 1));
        this.depSel = qs('#tv-departure', root) || qs('#tv-departure');
        this.countrySel = qs('#tv-country', root) || qs('#tv-country');
        this.datesDisplay = qs('#tv-sc-dates-display', root) || qs('#tv-sc-dates-display');
        this.nightsText = qs('#tv-nights-summary-text', root) || qs('#tv-nights-summary-text');
        this.touristsText = qs('#tv-tourists-summary-text', root) || qs('#tv-tourists-summary-text');
        this.summaryEl = qs('#th-wizard-summary', root);
        this.progressEl = qs('.th-wizard__progress', root);
        this.bind();
        this.go(this.step, true);
        this.refreshSummary();
    }

    TourSearchWizard.prototype.bind = function () {
        var self = this;

        qsa('[data-thw-goto]', this.root).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var n = parseInt(btn.getAttribute('data-thw-goto'), 10);
                if (!n || n > self.step) return;
                self.go(n);
            });
        });

        qsa('[data-thw-next]', this.root).forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!self.validateCurrent()) return;
                if (self.step < 4) self.go(self.step + 1);
            });
        });

        qsa('[data-thw-back]', this.root).forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (self.step > 1) self.go(self.step - 1);
            });
        });

        var adv = qs('#th-wizard-open-filters', this.root);
        if (adv) {
            adv.addEventListener('click', function () {
                var openBtn = document.getElementById('tv-filters-modal-open');
                if (openBtn) openBtn.click();
            });
        }

        ['change', 'input'].forEach(function (ev) {
            if (self.depSel) self.depSel.addEventListener(ev, function () { self.refreshSummary(); });
            if (self.countrySel) self.countrySel.addEventListener(ev, function () { self.refreshSummary(); });
        });

        var observerTargets = [this.datesDisplay, this.nightsText, this.touristsText].filter(Boolean);
        if (typeof MutationObserver !== 'undefined' && observerTargets.length) {
            var mo = new MutationObserver(function () { self.refreshSummary(); });
            observerTargets.forEach(function (el) {
                mo.observe(el, { characterData: true, childList: true, subtree: true });
            });
        }

        document.addEventListener('click', function (e) {
            if (e.target && (e.target.id === 'tv-sc-dates-apply' || e.target.closest && e.target.closest('#tv-sc-dates-apply'))) {
                setTimeout(function () { self.refreshSummary(); }, 50);
            }
            if (e.target && (e.target.id === 'tv-nights-apply' || e.target.id === 'tv-tourists-apply')) {
                setTimeout(function () { self.refreshSummary(); }, 50);
            }
        });
    };

    TourSearchWizard.prototype.validateCurrent = function () {
        if (this.step === 1) {
            var dep = this.depSel && String(this.depSel.value || '').trim();
            if (!dep) {
                this.shake(this.depSel);
                return false;
            }
        }
        if (this.step === 2) {
            var c = this.countrySel && String(this.countrySel.value || '').trim();
            if (!c) {
                this.shake(this.countrySel);
                return false;
            }
        }
        return true;
    };

    TourSearchWizard.prototype.shake = function (el) {
        if (!el) return;
        el.focus && el.focus();
        var field = el.closest ? el.closest('.tv-sc-field, .th-wizard__field') : null;
        var target = field || el;
        target.classList.add('th-wizard--shake');
        setTimeout(function () { target.classList.remove('th-wizard--shake'); }, 420);
    };

    TourSearchWizard.prototype.go = function (step, silent) {
        step = Math.max(1, Math.min(4, step));
        this.step = step;
        this.root.setAttribute('data-step', String(step));

        qsa('.th-wizard__panel', this.root).forEach(function (panel) {
            var id = parseInt(panel.getAttribute('data-panel'), 10);
            panel.classList.toggle('is-active', id === step);
            panel.hidden = id !== step;
        });

        qsa('[data-thw-goto]', this.root).forEach(function (btn) {
            var n = parseInt(btn.getAttribute('data-thw-goto'), 10);
            btn.classList.toggle('is-active', n === step);
            btn.classList.toggle('is-done', n < step);
            btn.setAttribute('aria-current', n === step ? 'step' : 'false');
        });

        qsa('[data-thw-back]', this.root).forEach(function (btn) {
            if (step <= 1) btn.setAttribute('hidden', '');
            else btn.removeAttribute('hidden');
        });

        this.refreshSummary();
        if (!silent) {
            var active = qs('.th-wizard__panel.is-active .th-wizard__panel-title', this.root);
            if (active) active.focus && active.focus();
        }
    };

    TourSearchWizard.prototype.refreshSummary = function () {
        if (!this.summaryEl) return;
        var chips = [];
        var dep = textOfSelect(this.depSel);
        if (dep && this.depSel && this.depSel.value) {
            chips.push({ icon: 'fa-plane-departure', text: dep });
        }
        var country = textOfSelect(this.countrySel);
        if (country && this.countrySel && this.countrySel.value) {
            chips.push({ icon: 'fa-globe', text: country });
        }
        var dates = (this.datesDisplay && this.datesDisplay.textContent || '').trim();
        if (dates && dates !== 'Даты') {
            chips.push({ icon: 'fa-calendar-alt', text: dates });
        }
        var nights = (this.nightsText && this.nightsText.textContent || '').trim();
        if (nights) {
            chips.push({ icon: 'fa-moon', text: nights });
        }
        var who = (this.touristsText && this.touristsText.textContent || '').trim();
        if (who) {
            chips.push({ icon: 'fa-users', text: who });
        }

        this.summaryEl.innerHTML = chips.map(function (c) {
            return '<span class="th-wizard__chip"><i class="fas ' + c.icon + '" aria-hidden="true"></i>' +
                '<span class="th-wizard__chip-text">' + escapeHtml(c.text) + '</span></span>';
        }).join('');
    };

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initHomeWizard() {
        var root = document.getElementById('tour-search-section');
        if (!root || !root.classList.contains('th-wizard')) return;
        window.THTourSearchWizard = new TourSearchWizard(root, { mode: 'home' });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHomeWizard);
    } else {
        initHomeWizard();
    }
})();
