/**
 * Travel Hub — мобильный runtime.
 * Приоритет: Яндекс.Браузер (Android/iOS) → затем Chrome / Safari / Firefox.
 * - детект YaBrowser → html.th-yandex / .th-yandex-mobile
 * - --th-vvh / --th-vv-offset из visualViewport (адресная строка Яндекса)
 * - --th-sticky-bottom-h под fixed CTA + site lead-bar
 */
(function () {
  'use strict';

  var root = document.documentElement;
  var MQ = '(max-width: 767.98px)';
  var stickySelectors = [
    '#th-results-sticky-lead.is-visible',
    '#promo-results-sticky-lead.is-visible',
    '#promo-sticky-cta',
    '.th-detail__mobile-sticky',
    '#mobile-sticky-cta.is-visible',
    '.th-site-lead-bar'
  ];

  function ua() {
    return String((navigator && (navigator.userAgent || navigator.vendor)) || '');
  }

  function isYandex() {
    var s = ua();
    return /YaBrowser|YaSearchBrowser|YandexSearch|YandexMobile|Yowser/i.test(s);
  }

  function isMobileUa() {
    var s = ua();
    return /Mobile|Android|iPhone|iPod|iPad|Opera Mini|IEMobile/i.test(s);
  }

  function isMobile() {
    try {
      if (window.matchMedia(MQ).matches) return true;
    } catch (e) {}
    return window.innerWidth < 768 || isMobileUa();
  }

  function setVvh() {
    var h = 0;
    var offsetTop = 0;
    var offsetLeft = 0;
    try {
      if (window.visualViewport) {
        if (window.visualViewport.height) h = window.visualViewport.height;
        offsetTop = window.visualViewport.offsetTop || 0;
        offsetLeft = window.visualViewport.offsetLeft || 0;
      }
    } catch (e2) {}
    if (!h) h = window.innerHeight || 0;
    if (h > 0) {
      root.style.setProperty('--th-vvh', Math.round(h) + 'px');
      /* Яндекс: 100dvh часто «выше» видимой области — дублируем в --vh */
      root.style.setProperty('--vh', (h * 0.01) + 'px');
    }
    var vw = 0;
    try {
      if (window.visualViewport && window.visualViewport.width) {
        vw = window.visualViewport.width;
      }
    } catch (e3) {}
    if (!vw) vw = document.documentElement.clientWidth || window.innerWidth || 0;
    if (vw > 0) {
      root.style.setProperty('--th-vvw', Math.round(vw) + 'px');
    }
    root.style.setProperty('--th-vv-offset-top', Math.round(offsetTop) + 'px');
    root.style.setProperty('--th-vv-offset-left', Math.round(offsetLeft) + 'px');
  }

  function elVisible(el) {
    if (!el) return false;
    var style = window.getComputedStyle(el);
    if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
    var rect = el.getBoundingClientRect();
    return rect.height > 0 && rect.width > 0;
  }

  function visibleStickyHeight() {
    var maxH = 0;
    for (var i = 0; i < stickySelectors.length; i++) {
      var nodes = document.querySelectorAll(stickySelectors[i]);
      for (var j = 0; j < nodes.length; j++) {
        var el = nodes[j];
        if (!elVisible(el)) continue;
        /* site lead скрыт, когда другой sticky CTA активен */
        if (el.classList && el.classList.contains('th-site-lead-bar')) {
          var body = document.body;
          if (body && (
            body.classList.contains('th-sticky-cta-active') ||
            body.classList.contains('has-results-sticky')
          )) continue;
          if (document.querySelector('.th-results-sticky-lead.is-visible')) continue;
        }
        var rect = el.getBoundingClientRect();
        if (rect.height > maxH) maxH = rect.height;
      }
    }
    /* Промо-таб на мобилке сверху — не учитываем в нижнем отступе */
    var promoTab = document.querySelector('.th-promo-popup--collapsed .th-promo-popup__tab');
    if (elVisible(promoTab) && document.body && document.body.classList.contains('th-promo-tab-bottom')) {
      if (window.innerWidth >= 768) {
        var th = promoTab.getBoundingClientRect().height + 12;
        if (th > maxH) maxH = th;
      }
    }
    return Math.ceil(maxH);
  }

  function syncStickyPad() {
    var h = visibleStickyHeight();
    root.style.setProperty('--th-sticky-bottom-h', h + 'px');
    root.classList.toggle('th-has-bottom-cta', h > 0);
    if (document.body) {
      document.body.classList.toggle('th-has-bottom-cta', h > 0);
    }
  }

  function syncBrowserClass() {
    var ya = isYandex();
    var mob = isMobile();
    var u = ua();
    var samsung = /SamsungBrowser/i.test(u);
    var firefox = /Firefox|FxiOS/i.test(u);
    var edge = /Edg\//i.test(u);
    var chrome = !edge && !firefox && !ya && /Chrome|CriOS/i.test(u);
    var safari = !chrome && !firefox && !ya && !samsung && /Safari/i.test(u);
    var browser = 'other';
    if (ya) browser = 'yandex';
    else if (samsung) browser = 'samsung';
    else if (firefox) browser = 'firefox';
    else if (edge) browser = 'edge';
    else if (chrome) browser = 'chrome';
    else if (safari) browser = 'safari';

    root.classList.toggle('th-yandex', ya);
    root.classList.toggle('th-yandex-mobile', ya && mob);
    root.classList.toggle('th-is-mobile', mob);
    ['yandex', 'safari', 'chrome', 'firefox', 'samsung', 'edge', 'other'].forEach(function (b) {
      root.classList.remove('th-browser-' + b);
    });
    root.classList.add('th-browser-' + browser);
    root.setAttribute('data-th-browser', browser);
  }

  function syncHomeFunnel() {
    var body = document.body;
    if (!body || !isMobile()) {
      if (body) {
        body.classList.remove('th-home-funnel-top', 'th-home-scrolled');
      }
      return;
    }
    var header = document.getElementById('site-header');
    var isHome = header && header.getAttribute('data-home') === '1';
    var y = window.scrollY || document.documentElement.scrollTop || 0;
    if (isHome && header) {
      header.classList.toggle('scrolled', y > 56);
      var wizard = document.getElementById('tour-search-section');
      var threshold = 280;
      if (wizard) {
        var rect = wizard.getBoundingClientRect();
        threshold = Math.max(220, Math.min(rect.bottom + y - 48, 520));
      }
      body.classList.toggle('th-home-funnel-top', y < threshold);
      body.classList.toggle('th-home-scrolled', y > 56);
    } else {
      body.classList.remove('th-home-funnel-top', 'th-home-scrolled');
    }
  }

  function syncHeaderScroll() {
    var header = document.getElementById('site-header');
    if (!header || header.getAttribute('data-home') !== '1') return;
    var y = window.scrollY || document.documentElement.scrollTop || 0;
    header.classList.toggle('scrolled', y > 56);
  }

  function isModalVisible(el) {
    if (!el) return false;
    if (el.hidden) return false;
    if (el.classList.contains('hidden')) return false;
    var st = window.getComputedStyle(el);
    if (st.display === 'none' || st.visibility === 'hidden' || st.opacity === '0') return false;
    var r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
  }

  function syncModalLock() {
    var body = document.body;
    if (!body) return;
    var open = false;
    var ids = [
      'quick-booking-modal',
      'tv-filters-modal',
      'booking-modal',
      'th-tour-booking-modal',
      'th-site-feedback-overlay',
      'promo-stars-popup',
      'certificateModal',
      'gallery-lightbox',
      'lightbox',
      'hotel-image-lightbox',
      'tv-nights-popup',
      'vip-tv-nights-popup',
      'country-tv-nights-popup',
      'tv-sc-date-popup',
      'tv-tourists-block',
      'tv-sc-overlay'
    ];
    for (var i = 0; i < ids.length; i++) {
      var el = document.getElementById(ids[i]);
      if (!el) continue;
      if (ids[i] === 'tv-filters-modal' && !el.classList.contains('tv-filters-modal--show')) continue;
      if (ids[i] === 'quick-booking-modal' && el.style.display === 'none') continue;
      if (ids[i] === 'booking-modal' && el.style.display === 'none') continue;
      if (ids[i] === 'th-tour-booking-modal' && el.style.display === 'none') continue;
      if (ids[i] === 'promo-stars-popup' && el.style.display === 'none') continue;
      if (isModalVisible(el)) {
        open = true;
        break;
      }
    }
    if (!open && document.querySelector('.th-promo-popup--visible')) open = true;
    if (!open && document.querySelector('#th-office-lead-ov.th-open')) open = true;
    if (!open && document.querySelector('.site-header-mobile-panel.is-open')) open = true;
    body.classList.toggle('th-modal-open', open);
    if (document.documentElement) {
      document.documentElement.classList.toggle('th-modal-open', open);
    }
  }

  function syncAll() {
    syncBrowserClass();
    setVvh();
    syncHomeFunnel();
    syncStickyPad();
    syncModalLock();
  }

  var scheduled = false;
  function schedule() {
    if (scheduled) return;
    scheduled = true;
    var run = function () {
      scheduled = false;
      syncAll();
    };
    if (typeof requestAnimationFrame === 'function') requestAnimationFrame(run);
    else setTimeout(run, 16);
  }

  function observeStickies() {
    if (typeof MutationObserver === 'undefined' || !document.body) return;
    var mo = new MutationObserver(schedule);
    stickySelectors.forEach(function (sel) {
      var bare = sel.replace(/\.is-visible|#/g, function (m) {
        return m === '#' ? '#' : '';
      }).split('.')[0];
      if (bare.charAt(0) === '#') {
        var el = document.getElementById(bare.slice(1));
        if (el) mo.observe(el, { attributes: true, attributeFilter: ['class', 'style', 'hidden'] });
      }
    });
    mo.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'style', 'hidden']
    });
  }

  /**
   * Яндекс.Браузер: fixed-бары иногда «уезжают» при скролле адресной строки.
   * Подтягиваем bottom к visualViewport.
   */
  function pinFixedBottomsForYandex() {
    if (!isYandex() || !isMobile()) return;
    if (!window.visualViewport) return;
    var vv = window.visualViewport;
    var gap = Math.max(0, (window.innerHeight || 0) - (vv.height || 0) - (vv.offsetTop || 0));
    root.style.setProperty('--th-yandex-fixed-gap', Math.round(gap) + 'px');
  }

  function bind() {
    syncAll();
    pinFixedBottomsForYandex();
    window.addEventListener('resize', function () {
      schedule();
      pinFixedBottomsForYandex();
    }, { passive: true });
    window.addEventListener('orientationchange', function () {
      setTimeout(function () { syncAll(); pinFixedBottomsForYandex(); }, 120);
      setTimeout(function () { syncAll(); pinFixedBottomsForYandex(); }, 400);
      setTimeout(function () { syncAll(); pinFixedBottomsForYandex(); }, 800);
    });
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', function () {
        schedule();
        pinFixedBottomsForYandex();
      }, { passive: true });
      window.visualViewport.addEventListener('scroll', function () {
        pinFixedBottomsForYandex();
        schedule();
      }, { passive: true });
    }
    var scrollTimer;
    window.addEventListener('scroll', function () {
      syncHomeFunnel();
      syncHeaderScroll();
      clearTimeout(scrollTimer);
      scrollTimer = setTimeout(function () {
        schedule();
        pinFixedBottomsForYandex();
      }, isYandex() ? 60 : 120);
    }, { passive: true });

    observeStickies();
    document.addEventListener('th:lead_ok', schedule);
    document.addEventListener('focusin', function (e) {
      var t = e.target;
      if (!t || !t.tagName) return;
      var tag = t.tagName.toLowerCase();
      if (tag === 'input' || tag === 'textarea' || tag === 'select') {
        root.classList.add('th-kb-open');
        setTimeout(function () { setVvh(); pinFixedBottomsForYandex(); }, 300);
      }
    }, true);
    document.addEventListener('focusout', function () {
      setTimeout(function () {
        var a = document.activeElement;
        var tag = a && a.tagName ? a.tagName.toLowerCase() : '';
        if (tag !== 'input' && tag !== 'textarea' && tag !== 'select') {
          root.classList.remove('th-kb-open');
          setVvh();
          pinFixedBottomsForYandex();
          schedule();
        }
      }, 200);
    }, true);
  }

  /* Синхронно при загрузке скрипта */
  syncBrowserClass();
  setVvh();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }

  window.THMobile = {
    sync: syncAll,
    isMobile: isMobile,
    isYandex: isYandex,
    isHomeFunnelTop: function () {
      return !!(document.body && document.body.classList.contains('th-home-funnel-top'));
    },
    lockScroll: function (on) {
      if (document.body) {
        document.body.classList.toggle('th-modal-open', !!on);
      }
      syncModalLock();
    }
  };
})();
