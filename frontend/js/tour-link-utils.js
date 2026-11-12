/**
 * Навигация по ссылкам туров; блокировка самоссылки на текущий сайт.
 */
(function (global) {
    'use strict';

    function currentHostname() {
        try {
            return String(window.location.hostname || '').toLowerCase();
        } catch (e) {
            return '';
        }
    }

    function isExternalUrl(url) {
        return typeof url === 'string' && /^https?:\/\//i.test(url.trim());
    }

    function isInternalTourDetailPage(url) {
        try {
            var u = new URL(url, window.location.origin);
            return String(u.pathname || '').toLowerCase().indexOf('tour-detail') !== -1;
        } catch (e) {
            return false;
        }
    }

    function isSelfTourLink(url) {
        if (url == null || typeof url !== 'string') {
            return true;
        }
        var s = url.trim();
        if (s === '') {
            return false;
        }
        if (isInternalTourDetailPage(s)) {
            return false;
        }
        try {
            var parsed = new URL(s, window.location.origin);
            var h = parsed.hostname.toLowerCase();
            var cur = currentHostname();
            if (cur && h === cur) {
                return true;
            }
            if (/(\.|^)travelhub63\.ru$/i.test(h)) {
                return true;
            }
        } catch (err) {
            return false;
        }
        return false;
    }

    function sanitizeTourLink(link) {
        var u = typeof link === 'string' ? link.trim() : '';
        if (!u) {
            return '';
        }
        if (isInternalTourDetailPage(u)) {
            return u;
        }
        if (isSelfTourLink(u)) {
            console.error('Blocked self redirect (tour_link)');
            return '';
        }
        return u;
    }

    /**
     * Внешний URL — полный переход; относительный путь — в той же вкладке (навигация по сайту).
     */
    function openTourLink(url) {
        var u = sanitizeTourLink(url);
        if (!u) {
            return;
        }
        if (isExternalUrl(u)) {
            console.log('Redirect to:', u);
            global.location.href = u;
        } else {
            console.log('Redirect to:', u);
            global.location.assign(u);
        }
    }

    global.TourLinkUtils = {
        isExternalUrl: isExternalUrl,
        isSelfTourLink: isSelfTourLink,
        sanitizeTourLink: sanitizeTourLink,
        openTourLink: openTourLink
    };
})(typeof window !== 'undefined' ? window : this);
