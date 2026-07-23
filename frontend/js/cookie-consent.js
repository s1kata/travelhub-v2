/**
 * Баннер согласия на cookies и обработку ПД (Роскомнадзор).
 * Показывается до нажатия «Принять»; выбор сохраняется в localStorage и cookie.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'cookie_consent_accepted';
    var COOKIE_NAME = 'cookie_consent_accepted';
    var COOKIE_MAX_AGE_DAYS = 365;
    var CONSENT_DOC_URL = '/frontend/window/consent.php';
    var CONSENT_DOCX_URL = '/docs/personal-data-consent.docx';
    var PRIVACY_URL = '/frontend/window/privacy.php';
    var TERMS_URL = '/frontend/window/terms.php';

    function hasConsent() {
        try {
            if (localStorage.getItem(STORAGE_KEY) === 'accepted') {
                return true;
            }
        } catch (e) { /* private mode */ }
        return document.cookie.split(';').some(function (part) {
            return part.trim().indexOf(COOKIE_NAME + '=accepted') === 0;
        });
    }

    function setConsent() {
        try {
            localStorage.setItem(STORAGE_KEY, 'accepted');
        } catch (e2) { /* ignore */ }
        var maxAge = COOKIE_MAX_AGE_DAYS * 24 * 60 * 60;
        var secure = typeof location !== 'undefined' && location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = COOKIE_NAME + '=accepted; path=/; max-age=' + maxAge + '; SameSite=Lax' + secure;
    }

    function removeBanner(banner) {
        if (!banner || !banner.parentNode) return;
        banner.classList.remove('is-visible');
        document.documentElement.classList.remove('th-cookie-consent-open');
        window.setTimeout(function () {
            if (banner.parentNode) {
                banner.parentNode.removeChild(banner);
            }
        }, 280);
    }

    function showConsentBanner() {
        if (document.getElementById('cookie-consent-banner')) {
            return;
        }

        var banner = document.createElement('div');
        banner.id = 'cookie-consent-banner';
        banner.className = 'cookie-consent-banner';
        banner.setAttribute('role', 'region');
        banner.setAttribute('aria-label', 'Согласие на использование cookies');
        banner.innerHTML =
            '<div class="cookie-consent-banner__inner">' +
                '<p class="cookie-consent-banner__text">' +
                    'Мы используем cookies для улучшения работы сайта. ' +
                    'Продолжая использовать сайт, вы соглашаетесь на обработку персональных данных. ' +
                    '<span class="cookie-consent-banner__links">' +
                    '<a href="' + CONSENT_DOC_URL + '" class="cookie-consent-banner__link" target="_blank" rel="noopener noreferrer">Согласие на обработку ПД</a>' +
                    '<span class="cookie-consent-banner__sep" aria-hidden="true">·</span>' +
                    '<a href="' + CONSENT_DOCX_URL + '" class="cookie-consent-banner__link" target="_blank" rel="noopener noreferrer" download>скачать .docx</a>' +
                    '<span class="cookie-consent-banner__sep" aria-hidden="true">·</span>' +
                    '<a href="' + PRIVACY_URL + '" class="cookie-consent-banner__link" target="_blank" rel="noopener noreferrer">Политика конфиденциальности</a>' +
                    '<span class="cookie-consent-banner__sep" aria-hidden="true">·</span>' +
                    '<a href="' + TERMS_URL + '" class="cookie-consent-banner__link" target="_blank" rel="noopener noreferrer">Пользовательское соглашение</a>' +
                    '</span>.' +
                '</p>' +
                '<div class="cookie-consent-banner__actions">' +
                    '<button type="button" class="cookie-consent-banner__btn cookie-consent-banner__btn--accept" id="cookie-consent-accept">Принять</button>' +
                    '<button type="button" class="cookie-consent-banner__btn cookie-consent-banner__btn--decline" id="cookie-consent-decline">Отклонить</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(banner);
        document.documentElement.classList.add('th-cookie-consent-open');

        requestAnimationFrame(function () {
            banner.classList.add('is-visible');
        });

        var acceptBtn = document.getElementById('cookie-consent-accept');
        var declineBtn = document.getElementById('cookie-consent-decline');

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function () {
                setConsent();
                removeBanner(banner);
            });
        }

        if (declineBtn) {
            declineBtn.addEventListener('click', function () {
                removeBanner(banner);
            });
        }
    }

    function init() {
        if (hasConsent()) {
            return;
        }
        function tryShow() {
            var promo = document.getElementById('th-promo-popup');
            if (promo && promo.classList.contains('th-promo-popup--visible')) {
                window.setTimeout(tryShow, 600);
                return;
            }
            if (document.body && document.body.classList.contains('th-promo-open')) {
                window.setTimeout(tryShow, 600);
                return;
            }
            if (window.THMobile && typeof window.THMobile.isHomeFunnelTop === 'function' && window.THMobile.isHomeFunnelTop()) {
                window.setTimeout(tryShow, 800);
                return;
            }
            showConsentBanner();
            if (window.THMobile && typeof window.THMobile.sync === 'function') {
                window.setTimeout(window.THMobile.sync, 80);
            }
        }
        if (document.readyState === 'complete') {
            window.setTimeout(tryShow, 1200);
        } else {
            window.addEventListener('load', function () {
                window.setTimeout(tryShow, 1200);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
