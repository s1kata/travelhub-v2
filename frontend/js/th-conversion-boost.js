/**
 * Travel Hub — conversion boost: wizard sticky, abandon sheet, funnel analytics.
 */
(function (global) {
  'use strict';

  var ABANDON_KEY = 'th_abandon_sheet_shown';
  var IDLE_MS = 30000;
  var SCROLL_PCT = 0.5;
  var idleTimer = null;
  var sheetEl = null;

  function reach(goal) {
    if (global.THLeadCapture && global.THLeadCapture.reachGoal) {
      global.THLeadCapture.reachGoal(goal);
    }
  }

  function maxUrl() {
    try {
      var a = document.querySelector('.th-site-lead-bar__btn--max');
      return a && a.getAttribute('href') ? a.getAttribute('href') : '';
    } catch (e) { return ''; }
  }

  function sessionShown() {
    try { return sessionStorage.getItem(ABANDON_KEY) === '1'; } catch (e) { return false; }
  }
  function markShown() {
    try { sessionStorage.setItem(ABANDON_KEY, '1'); } catch (e) {}
  }

  function isHomeWizard() {
    return !!(document.getElementById('tour-search-section') && document.body.classList.contains('th-home-funnel-top'));
  }

  function syncWizardLeadBar(step) {
    var body = document.body;
    if (!body) return;
    body.classList.remove('th-wizard-step-1', 'th-wizard-step-2', 'th-wizard-step-3', 'th-wizard-step-4');
    body.classList.add('th-wizard-step-' + step);
    if (isHomeWizard() && step >= 2) {
      body.classList.add('th-wizard-lead-visible');
      reach('wizard_step_' + step);
    } else {
      body.classList.remove('th-wizard-lead-visible');
    }
  }

  function bindWizardSteps() {
    document.addEventListener('th:wizard-step', function (e) {
      var step = e.detail && e.detail.step ? parseInt(e.detail.step, 10) : 1;
      if (!isNaN(step)) syncWizardLeadBar(step);
    });
    var root = document.getElementById('tour-search-section');
    if (root) {
      var s = parseInt(root.getAttribute('data-step') || '1', 10);
      syncWizardLeadBar(isNaN(s) ? 1 : s);
    }
  }

  function ensureAbandonSheet() {
    if (sheetEl) return sheetEl;
    sheetEl = document.createElement('div');
    sheetEl.id = 'th-abandon-sheet';
    sheetEl.className = 'th-abandon-sheet hidden';
    sheetEl.setAttribute('role', 'dialog');
    sheetEl.setAttribute('aria-modal', 'true');
    sheetEl.innerHTML =
      '<div class="th-abandon-sheet__backdrop" data-th-abandon-close></div>' +
      '<div class="th-abandon-sheet__panel">' +
        '<button type="button" class="th-abandon-sheet__x" data-th-abandon-close aria-label="Закрыть">&times;</button>' +
        '<p class="th-abandon-sheet__badge">Ответ за 15 минут</p>' +
        '<h3 class="th-abandon-sheet__title">Не нашли тур?</h3>' +
        '<p class="th-abandon-sheet__sub">Оставьте телефон — менеджер подберёт лучшие варианты бесплатно.</p>' +
        '<form class="th-abandon-sheet__form" id="th-abandon-sheet-form">' +
          '<input type="tel" name="phone" required autocomplete="tel" placeholder="+7 (___) ___-__-__" class="th-abandon-sheet__input">' +
          '<label class="th-abandon-sheet__agree"><input type="checkbox" name="agree" required checked> Согласен на обработку данных</label>' +
          '<input type="text" name="website" tabindex="-1" autocomplete="off" class="th-abandon-sheet__hp">' +
          '<p class="th-abandon-sheet__msg hidden" id="th-abandon-sheet-msg"></p>' +
          '<button type="submit" class="th-abandon-sheet__submit">Подобрать тур за меня</button>' +
        '</form>' +
        '<p class="th-abandon-sheet__proof"><i class="fas fa-shield-alt"></i> Без спама · только по вашему запросу</p>' +
      '</div>';
    document.body.appendChild(sheetEl);

    sheetEl.querySelectorAll('[data-th-abandon-close]').forEach(function (el) {
      el.addEventListener('click', closeAbandonSheet);
    });

    var form = document.getElementById('th-abandon-sheet-form');
    var phone = form && form.querySelector('[name="phone"]');
    if (global.THLeadCapture && phone) global.THLeadCapture.formatPhoneInput(phone);

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var msg = document.getElementById('th-abandon-sheet-msg');
        var btn = form.querySelector('[type="submit"]');
        var fd = new FormData(form);
        if (btn) { btn.disabled = true; btn.textContent = 'Отправка…'; }
        reach('abandon_sheet_submit');
        var submit = (global.THLeadCapture && global.THLeadCapture.submit)
          ? global.THLeadCapture.submit({
            name: 'Клиент сайта',
            phone: String(fd.get('phone') || '').trim(),
            agree: !!fd.get('agree'),
            website: String(fd.get('website') || ''),
            source: 'abandon_sheet',
            message: 'Abandon sheet — scroll/idle'
          })
          : Promise.resolve({ success: false, error: 'Форма недоступна' });
        submit.then(function (res) {
          if (msg) {
            msg.classList.remove('hidden');
            if (res.success) {
              msg.textContent = res.message || 'Заявка принята! Перезвоним за 15 минут.';
              msg.className = 'th-abandon-sheet__msg th-abandon-sheet__msg--ok';
              form.reset();
              var mu = maxUrl();
              if (mu) {
                msg.innerHTML = (res.message || 'Заявка принята!') +
                  ' <a href="' + mu.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener">Написать в MAX</a>';
              }
              setTimeout(closeAbandonSheet, 3500);
            } else {
              msg.textContent = res.error || 'Ошибка отправки';
              msg.className = 'th-abandon-sheet__msg th-abandon-sheet__msg--err';
            }
          }
        }).finally(function () {
          if (btn) { btn.disabled = false; btn.textContent = 'Подобрать тур за меня'; }
        });
      });
    }
    return sheetEl;
  }

  function openAbandonSheet(reason) {
    if (sessionShown()) return;
    if (document.body.classList.contains('th-modal-open')) return;
    if (document.querySelector('#tv-search-loader.active')) return;
    markShown();
    reach(reason === 'scroll' ? 'abandon_sheet_scroll' : 'abandon_sheet_idle');
    ensureAbandonSheet();
    sheetEl.classList.remove('hidden');
    document.body.classList.add('th-abandon-open');
    if (global.THMobile && global.THMobile.lockScroll) global.THMobile.lockScroll(true);
  }

  function closeAbandonSheet() {
    if (!sheetEl) return;
    sheetEl.classList.add('hidden');
    document.body.classList.remove('th-abandon-open');
    if (global.THMobile && global.THMobile.lockScroll) global.THMobile.lockScroll(false);
  }

  function resetIdle() {
    if (idleTimer) clearTimeout(idleTimer);
    idleTimer = setTimeout(function () {
      if (!sessionShown()) openAbandonSheet('idle');
    }, IDLE_MS);
  }

  function bindAbandonTriggers() {
    if (!document.body || document.body.classList.contains('th-promo-page')) return;
    var maxScrollFired = false;
    global.addEventListener('scroll', function () {
      resetIdle();
      if (maxScrollFired || sessionShown()) return;
      var doc = document.documentElement;
      var max = Math.max(1, doc.scrollHeight - doc.clientHeight);
      if (max > 0 && (doc.scrollTop / max) >= SCROLL_PCT) {
        maxScrollFired = true;
        openAbandonSheet('scroll');
      }
    }, { passive: true });
    ['click', 'keydown', 'touchstart'].forEach(function (ev) {
      document.addEventListener(ev, resetIdle, { passive: true });
    });
    resetIdle();
  }

  /** Intent copy for quick lead modal */
  var INTENT = {
    'slow-search': { title: 'Подберём тур за вас', sub: 'Поиск идёт — пока ждёте, оставьте телефон. Перезвоним за 15 минут.', submit: 'Подобрать за меня', phoneOnly: true },
    'empty-state': { title: 'Не нашли подходящий тур?', sub: 'Менеджер подберёт варианты вручную — бесплатно.', submit: 'Получить подбор', phoneOnly: true },
    'search-error': { title: 'Не удалось загрузить туры', sub: 'Оставьте телефон — подберём варианты и перезвоним за 15 минут.', submit: 'Помочь с подбором', phoneOnly: true },
    'results-toolbar': { title: 'Помочь с выбором?', sub: 'Перезвоним за 15 минут с лучшими вариантами.', submit: 'Перезвоните мне', phoneOnly: true },
    'results-sticky': { title: 'Нужна помощь с туром?', sub: 'Без спама · ответ за 15 минут.', submit: 'Оставить телефон', phoneOnly: true },
    'home_quick_modal': { title: 'Подберём тур для вас', sub: 'Перезвоним за 15 минут. Без спама.', submit: 'Отправить заявку', phoneOnly: false },
    'promo_country_callback': { title: 'Перезвонить с акциями', sub: 'Подберём горящие туры по выбранной стране.', submit: 'Жду звонка', phoneOnly: true }
  };

  function applyIntent(source) {
    var cfg = INTENT[source] || INTENT.home_quick_modal;
    var modal = document.getElementById('quick-booking-modal');
    if (!modal) return cfg;
    var title = modal.querySelector('.th-qbm-modal__title');
    var sub = modal.querySelector('.th-qbm-modal__sub');
    var submit = document.getElementById('qbm-submit');
    var nameRow = modal.querySelector('[name="name"]');
    if (title) title.textContent = cfg.title;
    if (sub) sub.textContent = cfg.sub;
    if (submit) submit.textContent = cfg.submit;
    if (nameRow) {
      var wrap = nameRow.closest('.th-qbm-modal__field') || nameRow.parentElement;
      if (cfg.phoneOnly) {
        nameRow.removeAttribute('required');
        if (wrap) wrap.style.display = 'none';
      } else {
        nameRow.setAttribute('required', 'required');
        if (wrap) wrap.style.display = '';
      }
    }
    modal.dataset.thLeadSource = source || 'home_quick_modal';
    modal.dataset.thPhoneOnly = cfg.phoneOnly ? '1' : '0';
    return cfg;
  }

  function bindQuickModalIntent() {
    global.openQuickLeadModalWithIntent = function (source) {
      applyIntent(source || 'home_quick_modal');
      if (typeof global.openQuickLeadModal === 'function') {
        global.openQuickLeadModal(source);
      }
    };
    document.addEventListener('click', function (e) {
      var t = e.target && e.target.closest ? e.target.closest('[data-open-lead-modal]') : null;
      if (!t) return;
      applyIntent(t.getAttribute('data-open-lead-modal') || 'home_quick_modal');
    }, true);
  }

  function bindPromoCountryCallbacks() {
    document.addEventListener('click', function (e) {
      var btn = e.target && e.target.closest ? e.target.closest('[data-promo-country-callback]') : null;
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      var country = btn.getAttribute('data-country-name') || '';
      var msg = country ? ('Страна: ' + country + '. Перезвонить с горящими акциями.') : 'Перезвонить с горящими акциями.';
      if (typeof global.openSiteFeedbackModal === 'function') {
        global.openSiteFeedbackModal({
          source: 'promo_country_callback',
          title: 'Перезвонить с акциями',
          sub: 'Перезвоним за 15 минут · ' + (country || 'выбранное направление'),
          message: msg,
          focusPhone: true,
          phoneOnly: true
        });
      } else if (global.openQuickLeadModalWithIntent) {
        global.openQuickLeadModalWithIntent('promo_country_callback');
      }
      reach('promo_country_callback_click');
    });
  }

  function init() {
    bindWizardSteps();
    bindAbandonTriggers();
    bindQuickModalIntent();
    bindPromoCountryCallbacks();
  }

  global.THConversionBoost = {
    applyIntent: applyIntent,
    reach: reach,
    INTENT: INTENT
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(typeof window !== 'undefined' ? window : this);
