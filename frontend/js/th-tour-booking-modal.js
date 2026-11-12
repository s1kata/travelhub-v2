/**
 * Единая модалка заявки (#th-tour-booking-modal, th-qbm-modal).
 */
(function () {
  'use strict';

  var modal = null;
  var form = null;
  var summaryEl = null;
  var msgEl = null;
  var currentPayload = null;

  function applyCsrfToken(token) {
    if (!token) return '';
    token = String(token).trim();
    if (!token) return '';
    window.TH_CSRF = token;
    var hid = document.getElementById('th-csrf-token');
    if (hid) hid.value = token;
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) meta.setAttribute('content', token);
    return token;
  }

  function csrf() {
    var hid = document.getElementById('th-csrf-token');
    if (hid && hid.value) {
      var v = String(hid.value).trim();
      if (v) return v;
    }
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) {
      var m = String(meta.getAttribute('content')).trim();
      if (m) return m;
    }
    if (typeof window.TH_CSRF === 'string' && window.TH_CSRF) {
      return String(window.TH_CSRF).trim();
    }
    try {
      var cfgEl = document.getElementById('tour-detail-config');
      if (cfgEl && cfgEl.textContent) {
        var cfg = JSON.parse(cfgEl.textContent);
        if (cfg && cfg.csrfToken) return String(cfg.csrfToken).trim();
      }
    } catch (eCfg) {}
    return '';
  }

  function refreshCsrfToken() {
    return fetch('/backend/api/csrf-token.php', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (j) {
        if (j && j.token) return applyCsrfToken(j.token);
        return '';
      })
      .catch(function () { return ''; });
  }

  function isCsrfErrorMessage(msg) {
    if (!msg) return false;
    var s = String(msg).toLowerCase();
    return s.indexOf('csrf') >= 0 || s.indexOf('недействительный запрос') >= 0;
  }

  function parsePayload(btn) {
    var raw = btn.getAttribute('data-th-book-tour');
    if (!raw) return null;
    try {
      if (raw.indexOf('%7B') === 0 || raw.indexOf('%') >= 0) {
        try { raw = decodeURIComponent(raw); } catch (eDec) {}
      }
      return JSON.parse(raw);
    } catch (e) { return null; }
  }

  var SUBMIT_LABEL = '✈ Отправить заявку менеджеру';

  function leadSourceFromPayload(p) {
    p = p || {};
    if (p.lead_source) return String(p.lead_source);
    if (p.from_promo === '1' || p.from_promo === 1 || p.from_promo === true) return 'promo_card';
    if (p.tour_id || p.tour_link) return 'tour_detail';
    return 'card_booking';
  }

  function isPromoPayload(p) {
    return !!(p && (p.from_promo === '1' || p.from_promo === 1 || p.from_promo === true));
  }

  function isLeadOnlyPayload(p) {
    if (!p) return true;
    if (p.lead_only === true || p.lead_only === '1') return true;
    var tid = p.tour_id != null ? String(p.tour_id).trim() : '';
    var tl = p.tour_link != null ? String(p.tour_link).trim() : '';
    return !tid && !tl;
  }

  function setMsg(text, kind) {
    if (!msgEl) return;
    msgEl.textContent = text || '';
    msgEl.classList.remove('hidden', 'is-visible', 'is-ok', 'is-err');
    if (!text) {
      msgEl.classList.add('hidden');
      msgEl.hidden = true;
      return;
    }
    msgEl.hidden = false;
    msgEl.classList.add('is-visible', kind === 'ok' ? 'is-ok' : 'is-err');
  }

  function restorePendingBookingFields() {
    var pr = window.__thPendingBookingRestore;
    if (!pr) return;
    window.__thPendingBookingRestore = null;
    setTimeout(function () {
      var nameEl = document.getElementById('th-tb-name');
      var phoneEl = document.getElementById('th-tb-phone');
      var agreeEl = document.getElementById('th-tb-agree');
      var adsEl = document.getElementById('th-tb-agree-ads');
      if (nameEl && pr.name) nameEl.value = pr.name;
      if (phoneEl && pr.phone) phoneEl.value = pr.phone;
      if (agreeEl && pr.agree) agreeEl.checked = true;
      if (adsEl && pr.agree_ads) adsEl.checked = true;
      setMsg('Сессия обновлена. Ваши данные восстановлены — нажмите «Отправить заявку менеджеру».', 'ok');
    }, 50);
  }

  function ensureModalReady() {
    if (!modal) modal = document.getElementById('th-tour-booking-modal');
    if (!form) form = document.getElementById('th-tour-booking-form');
    if (!summaryEl) summaryEl = document.getElementById('th-tour-booking-summary');
    if (!msgEl) msgEl = document.getElementById('th-tour-booking-msg');
    if (form && !form.dataset.thBookingSubmitBound) {
      form.dataset.thBookingSubmitBound = '1';
      form.addEventListener('submit', submitForm);
      if (window.THLeadCapture && window.THLeadCapture.bindLeadInputs) {
        window.THLeadCapture.bindLeadInputs(form);
      }
    }
    return !!modal;
  }

  function openModal(payload) {
    if (!ensureModalReady()) return;
    currentPayload = payload || {};
    var src = leadSourceFromPayload(currentPayload);
    if (window.THLeadCapture && window.THLeadCapture.reachGoal) {
      window.THLeadCapture.reachGoal('booking_form_open', { source: src });
      window.THLeadCapture.reachGoal('tour_booking_modal_open', { source: src });
    }
    var titleEl = document.getElementById('th-tour-booking-title');
    if (titleEl) {
      if (currentPayload.modal_title) {
        titleEl.textContent = String(currentPayload.modal_title);
      } else if (isPromoPayload(currentPayload)) {
        titleEl.textContent = 'Заявка на акционный тур';
      } else {
        titleEl.textContent = 'Заявка на тур';
      }
    }
    var title = currentPayload.hotel_name || '';
    var loc = [currentPayload.country, currentPayload.region].filter(Boolean).join(', ');
    var summaryText = '';
    if (loc || title) {
      summaryText = (loc ? loc + (title ? ' — ' : '') : '') + title;
      if (currentPayload.price) summaryText += ' · ' + currentPayload.price + ' ₽';
      if (currentPayload.nights) summaryText += ' · ' + currentPayload.nights + ' ноч.';
    }
    if (summaryEl) {
      if (summaryText.trim()) {
        summaryEl.textContent = summaryText;
        summaryEl.classList.remove('hidden');
        summaryEl.hidden = false;
      } else {
        summaryEl.textContent = '';
        summaryEl.classList.add('hidden');
        summaryEl.hidden = true;
      }
    }
    setMsg('', '');
    if (form && window.THLeadCapture && window.THLeadCapture.bindLeadInputs) {
      window.THLeadCapture.bindLeadInputs(form);
    }
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('th-modal-open');
    if (window.THMobile && window.THMobile.lockScroll) window.THMobile.lockScroll(true);
    if (window.THMobile && typeof window.THMobile.pinLeadModals === 'function') {
      window.THMobile.pinLeadModals();
    }
    if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
    var nameInp = document.getElementById('th-tb-name');
    var phoneInpOpen = document.getElementById('th-tb-phone');
    if (window.THLeadCapture && window.THLeadCapture.ensurePhoneDefault && phoneInpOpen) {
      window.THLeadCapture.ensurePhoneDefault(phoneInpOpen);
    }
    restorePendingBookingFields();
    if (nameInp) try { nameInp.focus(); } catch (e) {}
  }

  function closeModal() {
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('th-modal-open');
    if (window.THMobile && window.THMobile.lockScroll) window.THMobile.lockScroll(false);
    if (window.THMobile && typeof window.THMobile.pinLeadModals === 'function') {
      window.THMobile.pinLeadModals();
    }
    if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
    currentPayload = null;
  }

  function leadOnlyMessage(p) {
    var parts = [];
    if (p.lead_message) return String(p.lead_message);
    if (isPromoPayload(p)) parts.push('Горящие туры / акции');
    if (p.departure_city) parts.push('Вылет: ' + p.departure_city);
    if (p.country) parts.push('Страна: ' + p.country);
    if (p.hotel_name) parts.push('Отель: ' + p.hotel_name);
    if (p.price) parts.push('Цена: ' + p.price + ' ₽');
    if (p.lead_note) parts.push(String(p.lead_note));
    if (!parts.length) parts.push('Заявка с сайта Travel Hub');
    return parts.join('\n');
  }

  function submitLeadOnly(p, nameVal, phoneVal, agreeAds, src) {
    if (!window.THLeadCapture || typeof window.THLeadCapture.submit !== 'function') {
      return Promise.resolve({ success: false, error: 'Модуль заявки не загружен' });
    }
    return window.THLeadCapture.submit({
      name: nameVal,
      phone: phoneVal,
      agree: true,
      agree_ads: agreeAds,
      website: '',
      source: src || 'home_quick_modal',
      phoneOnly: !!p.phone_only,
      message: leadOnlyMessage(p)
    });
  }

  function submitForm(e) {
    e.preventDefault();
    if (!currentPayload) return;
    var p = currentPayload;
    var src = leadSourceFromPayload(p);
    function trackBookingGoal(goal) {
      if (window.THLeadCapture && window.THLeadCapture.reachGoal) {
        window.THLeadCapture.reachGoal(goal, { source: src });
      }
    }
    trackBookingGoal('lead_submit_attempt');
    var nameInp = document.getElementById('th-tb-name');
    var phoneInp = document.getElementById('th-tb-phone');
    var agreeInp = document.getElementById('th-tb-agree');
    var submitBtn = document.getElementById('th-tb-submit');
    var nameVal = (nameInp && nameInp.value || '').trim();
    var phoneVal = (phoneInp && phoneInp.value || '').trim();
    if (window.THLeadCapture && typeof THLeadCapture.validateLeadFields === 'function') {
      var check = THLeadCapture.validateLeadFields({
        name: nameVal,
        phone: phoneVal,
        agree: !!(agreeInp && agreeInp.checked),
        phoneOnly: !!p.phone_only
      });
      if (!check.ok) {
        trackBookingGoal('lead_validation_error');
        if (window.THLeadCapture.markLeadFormErrors && form) {
          window.THLeadCapture.markLeadFormErrors(form, check);
        }
        setMsg(check.error || 'Проверьте данные формы', 'err');
        return;
      }
      nameVal = check.name;
      phoneVal = check.phone;
    } else {
      if (!nameVal || nameVal.length < 2 || !/^[\p{L}\s\-'.]+$/u.test(nameVal)) {
        trackBookingGoal('lead_validation_error');
        setMsg('Укажите корректное имя', 'err');
        return;
      }
      var cyr = nameVal.match(/\p{Script=Cyrillic}/gu);
      if (!cyr || cyr.length < 2) {
        trackBookingGoal('lead_validation_error');
        setMsg('Укажите имя русскими буквами', 'err');
        return;
      }
      var phoneDigits = phoneVal.replace(/\D/g, '');
      if (phoneDigits.length === 11 && phoneDigits[0] === '8') phoneDigits = '7' + phoneDigits.slice(1);
      var rest = phoneDigits.slice(1);
      if (phoneDigits.length !== 11 || phoneDigits[0] !== '7' || !rest || rest[0] !== '9' || /^(\d)\1{9}$/.test(rest)) {
        trackBookingGoal('lead_validation_error');
        setMsg('Укажите корректный мобильный телефон РФ (+7 9XX…)', 'err');
        return;
      }
      if (!agreeInp || !agreeInp.checked) {
        trackBookingGoal('lead_validation_error');
        setMsg('Нужно согласие на обработку данных', 'err');
        return;
      }
    }
    var agreeAds = !!(document.getElementById('th-tb-agree-ads') || {}).checked;

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Отправка…';
    }

    var leadOnly = isLeadOnlyPayload(p);
    var run;
    if (leadOnly) {
      run = submitLeadOnly(p, nameVal, phoneVal, agreeAds, src);
    } else {
      var body = {
        _csrf_token: csrf(),
        booking_type: p.booking_type || (p.booking_with_payment ? 'with_payment' : 'without_payment'),
        tour_link: p.tour_link || (p.tour_id ? 'tourvisor:tour:' + p.tour_id : ''),
        tour_id: p.tour_id || undefined,
        country: p.country || '',
        hotel_name: p.hotel_name || '',
        price: p.price || '',
        nights: p.nights || '',
        meal: p.meal || '',
        room_category: p.room_category || 'Стандарт',
        room_id: p.room_id || undefined,
        tour_operator: p.tour_operator || undefined,
        placement: p.placement || undefined,
        date_from: p.date_from || undefined,
        date_to: p.date_to || undefined,
        name: nameVal,
        phone: phoneVal,
        agree: true,
        agree_ads: agreeAds,
        departure_city: p.departure_city || 'Самара',
        flight_info: p.flight_info || undefined,
        search_adults: p.adults ? parseInt(String(p.adults), 10) : (p.search_adults || 2),
        search_childs: p.search_childs || undefined,
        applied_promo: p.applied_promo || (isPromoPayload(p) ? 'PROMO_PAGE' : undefined)
      };
      function postBooking(reqBody, retried) {
        var tok = reqBody._csrf_token || csrf();
        reqBody._csrf_token = tok;
        return fetch('/backend/api/uon-booking.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': tok
          },
          body: JSON.stringify(reqBody)
        }).then(function (r) {
          return r.text().then(function (t) {
            var data;
            try { data = JSON.parse(t); } catch (e) { data = { success: false, error: t || 'Ошибка' }; }
            if (!data.success && !retried && isCsrfErrorMessage(data.error)) {
              return refreshCsrfToken().then(function (fresh) {
                if (!fresh) return { ok: r.ok, data: data };
                reqBody._csrf_token = fresh;
                return postBooking(reqBody, true);
              });
            }
            return { ok: r.ok, data: data };
          });
        });
      }
      run = postBooking(body, false);
    }

    run
      .then(function (res) {
        var data = res.data || res;
        var ok = !!(data && data.success);
        if (ok) {
          if (window.THLeadCapture && window.THLeadCapture.reachGoal) {
            window.THLeadCapture.reachGoal('lead_ok', { source: src });
            if (!leadOnly) window.THLeadCapture.reachGoal('card_booking_lead_ok');
          }
          if (window.THLeadCapture && window.THLeadCapture.dispatchLeadOk) {
            window.THLeadCapture.dispatchLeadOk({ source: src });
          }
          setMsg((window.THLeadCapture && window.THLeadCapture.SUCCESS_MSG)
              || 'Заявка отправлена. Ждите звонок с +7 (846) 254-16-56.', 'ok');
          try {
            document.dispatchEvent(new CustomEvent('th:tour-booking-success', {
              detail: { payload: p, response: data, leadOnly: leadOnly }
            }));
          } catch (eEv) {}
          if (form) {
            form.reset();
            var phoneAfterReset = document.getElementById('th-tb-phone');
            if (window.THLeadCapture && window.THLeadCapture.ensurePhoneDefault && phoneAfterReset) {
              window.THLeadCapture.ensurePhoneDefault(phoneAfterReset);
            }
          }
          if (p.booking_with_payment) {
            /* tour-detail слушает событие и открывает оплату */
          } else {
            setTimeout(closeModal, 2500);
          }
        } else {
          trackBookingGoal('lead_err');
          var errText = (data && data.error) ? data.error : 'Не удалось отправить заявку';
          if (isCsrfErrorMessage(errText)) {
            setMsg('Сессия обновлена. Нажмите «Отправить заявку» ещё раз.', 'err');
          } else {
            setMsg(errText, 'err');
          }
        }
      })
      .catch(function () {
        trackBookingGoal('lead_err');
        setMsg('Ошибка сети. Попробуйте ещё раз.', 'err');
      })
      .finally(function () {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = SUBMIT_LABEL;
        }
      });
  }

  function openQuickLead(source, opts) {
    opts = opts || {};
    openModal({
      lead_only: true,
      lead_source: source || 'home_quick_modal',
      hotel_name: opts.hotel_name || 'Подбор тура',
      lead_note: opts.note || '',
      from_promo: opts.from_promo,
      modal_title: opts.title || '',
      country: opts.country || '',
      departure_city: opts.departure_city || '',
      phone_only: !!opts.phoneOnly
    });
  }

  function init() {
    if (window.__thTourBookingModalInit) return;
    window.__thTourBookingModalInit = true;
    modal = document.getElementById('th-tour-booking-modal');
    form = document.getElementById('th-tour-booking-form');
    summaryEl = document.getElementById('th-tour-booking-summary');
    msgEl = document.getElementById('th-tour-booking-msg');
    if (!modal) return;

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-th-book-tour]');
      if (btn) {
        e.preventDefault();
        e.stopPropagation();
        var payload = parsePayload(btn);
        if (payload) openModal(payload);
        return;
      }
      var promoLead = e.target.closest('[data-th-promo-card-lead]');
      if (promoLead && !promoLead.hasAttribute('data-th-book-tour')) {
        e.preventDefault();
        e.stopPropagation();
        var tourId = promoLead.getAttribute('data-tour-id') || '';
        window.THTourBookingModal.open({
          from_promo: '1',
          lead_source: 'promo_card',
          tour_id: tourId || undefined,
          tour_link: tourId ? ('tourvisor:tour:' + tourId) : '',
          hotel_name: promoLead.getAttribute('data-hotel-name') || '',
          price: promoLead.getAttribute('data-hotel-price') || '',
          country: promoLead.getAttribute('data-hotel-country') || '',
          departure_city: (window.__TH_PROMO_PAGE__ && window.__TH_PROMO_PAGE__.departureName)
            ? String(window.__TH_PROMO_PAGE__.departureName) : 'Самара'
        });
        return;
      }
      var leadBtn = e.target.closest('[data-open-lead-modal]');
      if (leadBtn) {
        e.preventDefault();
        e.stopPropagation();
        var source = leadBtn.getAttribute('data-open-lead-modal') || 'home_quick_modal';
        openQuickLeadWithIntent(source);
        return;
      }
      if (e.target.closest('[data-th-booking-close]')) {
        closeModal();
      }
    });

    if (form) {
      form.addEventListener('submit', submitForm);
      if (window.THLeadCapture && window.THLeadCapture.bindLeadInputs) {
        window.THLeadCapture.bindLeadInputs(form);
      }
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal && modal.style.display !== 'none') closeModal();
    });
  }

  window.THTourBookingModal = { open: openModal, close: closeModal, openQuickLead: openQuickLead };
  function openQuickLeadWithIntent(source) {
    var src = source || 'home_quick_modal';
    var cfg = (window.THConversionBoost && typeof window.THConversionBoost.applyIntent === 'function')
      ? window.THConversionBoost.applyIntent(src)
      : null;
    var fromPromo = String(src).indexOf('promo') === 0;
    openQuickLead(src, {
      title: fromPromo ? 'Заявка на акционный тур' : ((cfg && cfg.title) ? cfg.title : 'Заявка на тур'),
      from_promo: fromPromo ? '1' : '',
      phoneOnly: !!(cfg && cfg.phoneOnly),
      note: (cfg && cfg.sub) ? cfg.sub : ''
    });
  }
  window.openQuickLeadModal = openQuickLeadWithIntent;
  window.openQuickLeadModalWithIntent = openQuickLeadWithIntent;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
