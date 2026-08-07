/**
 * Календарь выгодных дат — heatmap + туры на день (promo cache).
 */
(function () {
  'use strict';

  var cfg = window.TH_DEALS_CAL || {};
  var monthsRu = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
  var weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

  var state = {
    departureId: cfg.departureId || 7,
    countryId: cfg.countryId || 4,
    countryName: cfg.countryName || 'Турция',
    nightsFrom: 6,
    nightsTo: 9,
    viewYear: 0,
    viewMonth: 0, // 0-11
    priceMap: {},
    selectedDate: '',
    loadingMap: false,
    loadingDay: false
  };

  function $(id) { return document.getElementById(id); }

  function pad2(n) { return n < 10 ? '0' + n : String(n); }

  function ymd(y, m, d) {
    return y + '-' + pad2(m + 1) + '-' + pad2(d);
  }

  function formatPrice(n) {
    n = Math.round(Number(n) || 0);
    if (n <= 0) return '';
    if (n >= 1000) {
      var k = Math.round(n / 1000);
      return 'от ' + k + 'к';
    }
    return 'от ' + n.toLocaleString('ru-RU') + ' ₽';
  }

  function formatPriceFull(n) {
    n = Math.round(Number(n) || 0);
    if (n <= 0) return '';
    return 'от ' + n.toLocaleString('ru-RU') + ' ₽';
  }

  function esc(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/"/g, '&quot;');
  }

  function hotelImg(h) {
    var pic = (h && (h.picturelink || h.pictureLink || '')) || '';
    if (!pic && h && h.pictures && h.pictures[0]) {
      var p0 = h.pictures[0];
      pic = typeof p0 === 'string' ? p0 : (p0.src || p0.url || p0.link || '');
    }
    if (!pic) return 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=240&q=70';
    if (/^https?:\/\//i.test(pic)) return pic;
    var proxy = cfg.imageProxy || '/backend/api/tourvisor-image-proxy.php';
    return proxy + (proxy.indexOf('?') >= 0 ? '&' : '?') + 'src=' + encodeURIComponent(pic);
  }

  function depCityName() {
    return state.departureId === 1 ? 'Москва' : 'Самара';
  }

  function tourDateYmd(tour) {
    var raw = '';
    ['flydate', 'datefrom', 'dateFrom', 'checkIn', 'checkin', 'startDate', 'date'].forEach(function (k) {
      if (!raw && tour && tour[k]) raw = String(tour[k]).trim();
    });
    if (!raw) return '';
    var m = raw.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
    if (m) return m[3] + '-' + m[2] + '-' + m[1];
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10);
    return '';
  }

  function addDaysYmd(ymdStr, nights) {
    if (!ymdStr || !nights) return '';
    var p = ymdStr.split('-');
    if (p.length < 3) return '';
    var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    d.setDate(d.getDate() + (parseInt(nights, 10) || 0));
    return ymd(d.getFullYear(), d.getMonth(), d.getDate());
  }

  function tourDetailHref(hotel) {
    var t = (hotel.tours && hotel.tours[0]) || {};
    var mealObj = t.meal || {};
    var meal = mealObj.russianName || mealObj.name || mealObj.code || '';
    var dateFrom = tourDateYmd(t) || state.selectedDate || '';
    var nights = t.nights || '';
    var dateTo = addDaysYmd(dateFrom, nights);
    var region = (hotel.region && hotel.region.name) ? hotel.region.name : '';
    var desc = String(hotel.description || hotel.hotelDescription || hotel.descr || '').trim().slice(0, 4000);
    var params = {
      hotel_id: hotel.id || '',
      hotel_name: hotel.name || '',
      country: state.countryName || '',
      departure_city: depCityName(),
      departure_id: state.departureId || '',
      nights: nights,
      price: hotel._dayMinPrice || t.totalPrice || t.price || '',
      meal: meal,
      region: region,
      date_from: dateFrom,
      date_to: dateTo,
      image: hotelImg(hotel),
      description: desc,
      rating: hotel.rating || '',
      category: hotel.stars || hotel.hotelcategory || hotel.category || '',
      from_promo: '1',
      adults: '2'
    };
    var q = [];
    Object.keys(params).forEach(function (k) {
      if (params[k] !== '' && params[k] != null) {
        q.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(params[k])));
      }
    });
    return '/frontend/window/tour-detail.php?' + q.join('&');
  }

  function initViewMonth() {
    var now = new Date();
    state.viewYear = now.getFullYear();
    state.viewMonth = now.getMonth();
  }

  function canGoPrev() {
    var now = new Date();
    return state.viewYear > now.getFullYear() ||
      (state.viewYear === now.getFullYear() && state.viewMonth > now.getMonth());
  }

  function canGoNext() {
    var now = new Date();
    var max = new Date(now.getFullYear(), now.getMonth() + 2, 1);
    var cur = new Date(state.viewYear, state.viewMonth, 1);
    return cur < max;
  }

  function renderWeekdays() {
    var el = $('th-dc-weekdays');
    if (!el) return;
    el.innerHTML = weekdays.map(function (w) {
      return '<span>' + w + '</span>';
    }).join('');
  }

  function renderMonthHead() {
    var title = $('th-dc-month-title');
    if (title) title.textContent = monthsRu[state.viewMonth] + ' ' + state.viewYear;
    var prev = $('th-dc-prev');
    var next = $('th-dc-next');
    if (prev) prev.disabled = !canGoPrev();
    if (next) next.disabled = !canGoNext();
  }

  function renderGrid() {
    var grid = $('th-dc-grid');
    if (!grid) return;
    var y = state.viewYear;
    var m = state.viewMonth;
    var first = new Date(y, m, 1);
    var daysInMonth = new Date(y, m + 1, 0).getDate();
    var startPad = (first.getDay() + 6) % 7; // Mon=0
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var html = [];
    var i;
    for (i = 0; i < startPad; i++) {
      html.push('<div class="th-deals-cal__cell th-deals-cal__cell--empty" aria-hidden="true"></div>');
    }
    for (i = 1; i <= daysInMonth; i++) {
      var key = ymd(y, m, i);
      var cellDate = new Date(y, m, i);
      var past = cellDate < today;
      var info = state.priceMap[key];
      var has = !!(info && info.minPrice > 0) && !past;
      var deal = !!(info && info.deal);
      var cls = 'th-deals-cal__cell';
      if (past) cls += ' th-deals-cal__cell--muted';
      if (has) cls += ' th-deals-cal__cell--has';
      if (deal) cls += ' th-deals-cal__cell--deal';
      if (state.selectedDate === key) cls += ' th-deals-cal__cell--active';
      var priceHtml = has ? '<span class="th-deals-cal__price">' + esc(formatPrice(info.minPrice)) + '</span>' : '';
      var badge = deal ? '<span class="th-deals-cal__badge" title="Выгодно"></span>' : '';
      html.push(
        '<button type="button" class="' + cls + '" data-date="' + key + '"' +
        (has ? '' : ' disabled') +
        ' aria-label="' + key + (has ? ', ' + formatPriceFull(info.minPrice) : '') + '">' +
        badge +
        '<span class="th-deals-cal__daynum">' + i + '</span>' +
        priceHtml +
        '</button>'
      );
    }
    grid.innerHTML = html.join('');
  }

  function setResultsPlaceholder(msg) {
    var list = $('th-dc-list');
    var empty = $('th-dc-empty');
    var sub = $('th-dc-results-sub');
    if (list) list.innerHTML = '';
    if (empty) {
      empty.classList.remove('hidden');
      empty.textContent = msg || 'Выберите дату с ценой — покажем туры.';
    }
    if (sub) sub.textContent = '';
  }

  function renderTours(hotels, date) {
    var list = $('th-dc-list');
    var empty = $('th-dc-empty');
    var sub = $('th-dc-results-sub');
    var title = $('th-dc-results-title');
    if (title) title.textContent = 'Туры на ' + date.split('-').reverse().join('.');
    if (!hotels || !hotels.length) {
      if (list) list.innerHTML = '';
      if (empty) {
        empty.classList.remove('hidden');
        empty.textContent = 'На эту дату в кэше пока нет туров. Выберите соседний день или откройте акции.';
      }
      if (sub) sub.textContent = '';
      return;
    }
    if (empty) empty.classList.add('hidden');
    if (sub) sub.textContent = hotels.length + ' вариантов · цены ориентировочные';
    list.innerHTML = hotels.map(function (h) {
      var t = (h.tours && h.tours[0]) || {};
      var nights = t.nights ? (t.nights + ' н.') : '';
      var meal = (t.meal && (t.meal.name || t.meal.russianName)) || '';
      var stars = h.stars || h.hotelcategory || h.category || '';
      var meta = [stars ? (stars + '★') : '', nights, meal].filter(Boolean).join(' · ');
      var price = formatPriceFull(h._dayMinPrice || t.totalPrice || t.price);
      return (
        '<a class="th-deals-cal__card" href="' + esc(tourDetailHref(h)) + '">' +
          '<img class="th-deals-cal__card-img" src="' + esc(hotelImg(h)) + '" alt="" loading="lazy">' +
          '<div class="th-deals-cal__card-body">' +
            '<p class="th-deals-cal__card-name">' + esc(h.name || 'Отель') + '</p>' +
            '<p class="th-deals-cal__card-meta">' + esc(meta) + '</p>' +
            '<p class="th-deals-cal__card-price">' + esc(price) + '</p>' +
          '</div>' +
        '</a>'
      );
    }).join('');
  }

  function loadPriceMap() {
    var panel = $('th-dc-cal-panel');
    state.loadingMap = true;
    if (panel) panel.classList.add('th-deals-cal__loading');
    var url = (cfg.priceMapUrl || '/backend/api/calendar_price_map.php') +
      '?departureId=' + encodeURIComponent(state.departureId) +
      '&countryId=' + encodeURIComponent(state.countryId);
    return fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        state.priceMap = (j && j.success && j.dates) ? j.dates : {};
        renderGrid();
      })
      .catch(function () {
        state.priceMap = {};
        renderGrid();
      })
      .finally(function () {
        state.loadingMap = false;
        if (panel) panel.classList.remove('th-deals-cal__loading');
      });
  }

  function loadDay(date) {
    state.selectedDate = date;
    renderGrid();
    state.loadingDay = true;
    setResultsPlaceholder('Загружаем туры…');
    var url = (cfg.dayToursUrl || '/backend/api/calendar_day_tours.php') +
      '?departureId=' + encodeURIComponent(state.departureId) +
      '&countryId=' + encodeURIComponent(state.countryId) +
      '&date=' + encodeURIComponent(date) +
      '&nightsFrom=' + encodeURIComponent(state.nightsFrom) +
      '&nightsTo=' + encodeURIComponent(state.nightsTo) +
      '&limit=12';
    return fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var data = (j && j.success && Array.isArray(j.data)) ? j.data : [];
        renderTours(data, date);
        if (window.THSafeTrace) window.THSafeTrace(j);
      })
      .catch(function () {
        setResultsPlaceholder('Не удалось загрузить туры. Попробуйте ещё раз.');
      })
      .finally(function () {
        state.loadingDay = false;
      });
  }

  function syncFiltersFromUi() {
    var dep = $('th-dc-departure');
    var country = $('th-dc-country');
    var nights = $('th-dc-nights');
    if (dep) state.departureId = parseInt(dep.value, 10) || 7;
    if (country) {
      state.countryId = parseInt(country.value, 10) || 4;
      var opt = country.options[country.selectedIndex];
      state.countryName = opt ? (opt.getAttribute('data-name') || opt.textContent || '') : '';
    }
    if (nights) {
      var parts = String(nights.value || '6-9').split('-');
      state.nightsFrom = parseInt(parts[0], 10) || 6;
      state.nightsTo = parseInt(parts[1], 10) || 9;
    }
  }

  function refreshAll() {
    syncFiltersFromUi();
    state.selectedDate = '';
    setResultsPlaceholder('Выберите дату с ценой — покажем туры.');
    var title = $('th-dc-results-title');
    if (title) title.textContent = 'Туры на дату';
    renderMonthHead();
    return loadPriceMap();
  }

  function setChipActive(groupEl, activeBtn) {
    if (!groupEl || !activeBtn) return;
    groupEl.querySelectorAll('.th-deals-cal__chip').forEach(function (b) {
      b.classList.toggle('is-active', b === activeBtn);
    });
  }

  function bindChips() {
    var depChips = $('th-dc-departure-chips');
    var countryChips = $('th-dc-country-chips');
    var nightsChips = $('th-dc-nights-chips');
    var depSel = $('th-dc-departure');
    var countrySel = $('th-dc-country');
    var nightsSel = $('th-dc-nights');

    if (depChips && depSel) {
      depChips.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-dep]') : null;
        if (!btn) return;
        setChipActive(depChips, btn);
        depSel.value = btn.getAttribute('data-dep') || '7';
        refreshAll();
      });
    }
    if (countryChips && countrySel) {
      countryChips.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-country]') : null;
        if (!btn) return;
        setChipActive(countryChips, btn);
        countrySel.value = btn.getAttribute('data-country') || '4';
        refreshAll();
      });
    }
    if (nightsChips && nightsSel) {
      nightsChips.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-nights]') : null;
        if (!btn) return;
        setChipActive(nightsChips, btn);
        nightsSel.value = btn.getAttribute('data-nights') || '6-9';
        refreshAll();
      });
    }
  }

  function bind() {
    renderWeekdays();
    initViewMonth();
    renderMonthHead();
    setResultsPlaceholder('Выберите дату с ценой — покажем туры.');

    var prev = $('th-dc-prev');
    var next = $('th-dc-next');
    if (prev) {
      prev.addEventListener('click', function () {
        if (!canGoPrev()) return;
        state.viewMonth -= 1;
        if (state.viewMonth < 0) {
          state.viewMonth = 11;
          state.viewYear -= 1;
        }
        renderMonthHead();
        renderGrid();
      });
    }
    if (next) {
      next.addEventListener('click', function () {
        if (!canGoNext()) return;
        state.viewMonth += 1;
        if (state.viewMonth > 11) {
          state.viewMonth = 0;
          state.viewYear += 1;
        }
        renderMonthHead();
        renderGrid();
      });
    }

    var grid = $('th-dc-grid');
    if (grid) {
      grid.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-date]') : null;
        if (!btn || btn.disabled) return;
        var date = btn.getAttribute('data-date');
        if (!date) return;
        loadDay(date);
      });
    }

    bindChips();
    ['th-dc-departure', 'th-dc-country', 'th-dc-nights'].forEach(function (id) {
      var el = $(id);
      if (el) el.addEventListener('change', refreshAll);
    });

    refreshAll();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
