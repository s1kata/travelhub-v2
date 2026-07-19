/**
 * Tourvisor: в ответе tour-flights может быть несколько пакетов перелёта.
 * Выбираем вариант по городу вылета (подсказки + аэропорты), иначе isDefault, иначе первый.
 * Мета перелёта для API/фильтров — thFlightMetaFromPackage (карточки: только «Вылет из …»).
 */
(function (global) {
    'use strict';

    function norm(s) {
        return String(s || '').trim().toLowerCase().replace(/ё/g, 'е');
    }

    function portDepText(leg) {
        if (!leg || !leg.departure) return '';
        var p = leg.departure.port || {};
        var bits = [p.shortName, p.name, p.id, p.code, p.iata, p.enName, p.enname];
        var out = [];
        bits.forEach(function (b) {
            var x = norm(b);
            if (x && out.indexOf(x) < 0) out.push(x);
        });
        return out.join(' ');
    }

    function hintsForCity(cityRaw) {
        var s = norm(cityRaw);
        if (!s) return [];
        var out = [];
        function add(x) {
            x = norm(x);
            if (x && out.indexOf(x) < 0) out.push(x);
        }
        add(s);
        if (s.indexOf('самар') !== -1) { add('самара'); add('курумоч'); add('kuf'); }
        if (s.indexOf('москв') !== -1) {
            add('москва'); add('домодедово'); add('внуково'); add('шереметьево'); add('жуковский');
            add('dme'); add('svo'); add('vko'); add('zia'); add('mow');
        }
        if (s.indexOf('петербург') !== -1 || s === 'спб' || s.indexOf('с-петербург') !== -1) { add('пулков'); add('петербург'); add('санкт'); }
        if (s.indexOf('казан') !== -1) add('казань');
        if (s.indexOf('екатеринбург') !== -1) { add('екатеринбург'); add('кольцово'); }
        if (s.indexOf('новосибирск') !== -1) { add('новосибирск'); add('толмачево'); }
        if (s.indexOf('красноярск') !== -1) { add('красноярск'); add('емельяново'); }
        if (s.indexOf('ростов') !== -1) { add('ростов'); add('платов'); }
        if (s.indexOf('краснодар') !== -1) { add('краснодар'); add('пашковский'); }
        if (s.indexOf('уфа') !== -1) add('уфа');
        if (s.indexOf('перм') !== -1) add('пермь');
        if (s.indexOf('воронеж') !== -1) add('воронеж');
        if (s.indexOf('сочи') !== -1) add('сочи');
        if (s.indexOf('нижн') !== -1 && s.indexOf('новгород') !== -1) { add('нижний'); add('стригино'); }
        if (s.indexOf('саратов') !== -1) add('саратов');
        if (s.indexOf('омск') !== -1) add('омск');
        if (s.indexOf('тюмен') !== -1) add('тюмень');
        if (s.indexOf('иркутск') !== -1) add('иркутск');
        if (s.indexOf('челябинск') !== -1) add('челябинск');
        if (s.indexOf('калининград') !== -1) { add('калининград'); add('храброво'); }
        if (s.indexOf('минеральн') !== -1) add('минеральн');
        if (s.indexOf('мурманск') !== -1) add('мурманск');
        if (s.indexOf('владивосток') !== -1) add('владивосток');
        if (s.indexOf('хабаровск') !== -1) add('хабаровск');
        if (s.indexOf('южно-сахалинск') !== -1 || s.indexOf('сахалинск') !== -1) add('сахалинск');
        return out;
    }

    var DEPARTURE_IATA_HINTS = {
        '1': ['dme', 'svo', 'vko', 'zia', 'mow', 'москва', 'домодедово', 'шереметьево', 'внуково', 'жуковский'],
        '7': ['kuf', 'самара', 'курумоч']
    };

    function mergeDepartureHints(cityHint, departureIdHint) {
        var hints = hintsForCity(cityHint);
        var depKey = departureIdHint != null ? String(departureIdHint) : '';
        var iataList = DEPARTURE_IATA_HINTS[depKey];
        if (iataList) {
            iataList.forEach(function (code) {
                code = norm(code);
                if (code && hints.indexOf(code) < 0) hints.push(code);
            });
        }
        return hints;
    }

    function depTextMatchesHints(depText, hints) {
        if (!depText) return false;
        for (var i = 0; i < hints.length; i++) {
            var h = hints[i];
            if (!h) continue;
            if (h.length === 3 && depText.indexOf(h) !== -1) return true;
            if (h.length >= 2 && depText.indexOf(h) !== -1) return true;
        }
        return false;
    }

    function portTextIsBlocked(depText) {
        var t = norm(depText);
        if (!t) return false;
        var blocked = ['красноярск', 'krasnoyarsk', 'емельяново', 'kja'];
        for (var i = 0; i < blocked.length; i++) {
            if (t.indexOf(blocked[i]) !== -1) return true;
        }
        return false;
    }

    function pickTourvisorFlightPackage(flights, cityHint, departureIdHint) {
        if (!flights || !flights.length) return null;
        var hints = mergeDepartureHints(cityHint, departureIdHint);
        if (hints.length) {
            for (var i = 0; i < flights.length; i++) {
                var f = flights[i];
                var fw = f.forward && f.forward[0];
                var depTxt = portDepText(fw);
                if (portTextIsBlocked(depTxt)) continue;
                if (depTextMatchesHints(depTxt, hints)) return f;
            }
            return null;
        }
        for (var j = 0; j < flights.length; j++) {
            if (!flights[j]) continue;
            var fw3 = flights[j].forward && flights[j].forward[0];
            if (portTextIsBlocked(portDepText(fw3))) continue;
            if (flights[j].isDefault) return flights[j];
        }
        for (var m = 0; m < flights.length; m++) {
            var fw4 = flights[m].forward && flights[m].forward[0];
            if (!portTextIsBlocked(portDepText(fw4))) return flights[m];
        }
        return null;
    }

    function legCompanyName(leg) {
        if (!leg) return '';
        return (leg.company && leg.company.name) || (leg.airline && leg.airline.name) || leg.companyName || '';
    }

    function legPortLabel(leg, end) {
        end = end || 'departure';
        if (!leg || !leg[end]) return '';
        var port = leg[end].port ? (leg[end].port.shortName || leg[end].port.name || '') : '';
        var time = String(leg[end].time || '').trim();
        if (port && time) return port + ' ' + time;
        return port || time;
    }

    /** Город вылета из порта первого сегмента (не подсказка поиска). */
    function departureCityFromPackageLeg(fw) {
        if (!fw || !fw.departure || !fw.departure.port) return '';
        var raw = String(fw.departure.port.shortName || fw.departure.port.name || '').trim();
        if (!raw) return '';
        var n = norm(raw);
        if (n.indexOf('домодедово') !== -1 || n.indexOf('шереметьево') !== -1 || n.indexOf('внуково') !== -1 || n.indexOf('жуковск') !== -1) return 'Москва';
        if (n.indexOf('курумоч') !== -1 || n === 'kuf') return 'Самара';
        if (n.indexOf('пулков') !== -1) return 'Санкт-Петербург';
        if (n.indexOf('самар') !== -1) return 'Самара';
        if (n.indexOf('москв') !== -1) return 'Москва';
        return raw;
    }

    /** Маршрут сегмента(ов): авиакомпания · вылет → прилёт */
    function directionLineFromPackageLegs(legs) {
        if (!legs || !legs.length) return '';
        var first = legs[0];
        var last = legs[legs.length - 1];
        var dep = legPortLabel(first, 'departure');
        var arr = legPortLabel(last, 'arrival');
        var route = (dep && arr) ? dep + ' \u2192 ' + arr : (dep || arr);
        var companies = [];
        legs.forEach(function (leg) {
            var n = legCompanyName(leg);
            if (n && companies.indexOf(n) < 0) companies.push(n);
        });
        var airline = companies.join(' / ');
        if (airline && route) return airline + ' \u00b7 ' + route;
        if (airline) return airline;
        return route;
    }

    function collectCompaniesFromPackage(pkg) {
        var companies = [];
        function add(n) {
            n = String(n || '').trim();
            if (n && companies.indexOf(n) < 0) companies.push(n);
        }
        var legs = []
            .concat(Array.isArray(pkg.forward) ? pkg.forward : [])
            .concat(Array.isArray(pkg.backward) ? pkg.backward : [])
            .concat(Array.isArray(pkg.back) ? pkg.back : []);
        legs.forEach(function (leg) {
            add(legCompanyName(leg));
            var segs = Array.isArray(leg.segments) ? leg.segments : [];
            segs.forEach(function (seg) { add(legCompanyName(seg)); });
        });
        return companies;
    }

    function promoTourvisorPackageIsDirect(pkg) {
        if (!pkg) return false;
        var fw = pkg.forward;
        var bw = pkg.backward || pkg.back;
        if (!Array.isArray(fw) || fw.length !== 1) return false;
        if (Array.isArray(bw) && bw.length > 1) return false;
        return true;
    }

    function thFlightMetaFromPackage(pkg, cityHint) {
        if (!pkg) return null;
        var city = String(cityHint || '').trim();
        var airline = '';
        var time = '';
        var route = '';
        var companies = collectCompaniesFromPackage(pkg);
        airline = companies[0] || '';
        var fw = pkg.forward && pkg.forward[0];
        if (fw && fw.departure) {
            time = String(fw.departure.time || '').trim();
        }
        if (fw) {
            var depP = (fw.departure && fw.departure.port)
                ? (fw.departure.port.shortName || fw.departure.port.name || '')
                : '';
            var arrP = (fw.arrival && fw.arrival.port)
                ? (fw.arrival.port.shortName || fw.arrival.port.name || '')
                : '';
            if (depP && arrP) route = depP + ' \u2192 ' + arrP;
            var actualCity = departureCityFromPackageLeg(fw);
            if (actualCity) city = actualCity;
        }
        if (!city) city = 'Самара';
        var forwardLine = directionLineFromPackageLegs(pkg.forward);
        var backwardLegs = pkg.backward || pkg.back;
        var backwardLine = directionLineFromPackageLegs(backwardLegs);
        var subline = forwardLine;
        if (!subline) {
            if (airline && time) subline = airline + ' \u00b7 ' + time;
            else if (airline) subline = airline;
            else if (time) subline = time;
            else if (route) subline = route;
        }
        return {
            city: city,
            airline: airline,
            airlines: companies,
            companies: companies,
            time: time,
            route: route,
            forwardLine: forwardLine,
            backwardLine: backwardLine,
            subline: subline,
            summary: forwardLine || route || subline,
            direct: promoTourvisorPackageIsDirect(pkg)
        };
    }

    /** Город из строки «Аэрофлот · Самара 12:10 → …». */
    function cityFromForwardLine(line) {
        var s = String(line || '').trim();
        if (!s) return '';
        var idx = s.indexOf('\u00b7');
        if (idx < 0) idx = s.indexOf('·');
        if (idx < 0) return '';
        var rest = s.slice(idx + 1).trim();
        var m = rest.match(/^(.+?)\s+\d{1,2}:\d{2}/);
        return (m && m[1]) ? m[1].trim() : '';
    }

    function thFlightMetaNormalize(raw, fallbackCity) {
        if (!raw) {
            var fb = String(fallbackCity || '').trim();
            return fb ? { city: fb, subline: '' } : null;
        }
        if (raw.city) {
            var m = {
                city: String(raw.city || fallbackCity || '').trim() || String(fallbackCity || 'Самара'),
                airline: raw.airline || (raw.companies && raw.companies[0]) || '',
                time: raw.time || '',
                route: raw.route || '',
                subline: raw.subline || '',
                summary: raw.summary || '',
                companies: raw.companies || raw.airlines || [],
                direct: !!raw.direct
            };
            m.forwardLine = raw.forwardLine || m.forwardLine || '';
            m.backwardLine = raw.backwardLine || m.backwardLine || '';
            if (!m.subline) {
                if (m.forwardLine) m.subline = m.forwardLine;
                else if (m.airline && m.time) m.subline = m.airline + ' \u00b7 ' + m.time;
                else if (m.airline) m.subline = m.airline;
                else if (m.time) m.subline = m.time;
                else if (m.route) m.subline = m.route;
                else if (m.summary) m.subline = m.summary;
            }
            if (!m.forwardLine && m.subline) m.forwardLine = m.subline;
            var cityFromLine = cityFromForwardLine(m.forwardLine);
            if (cityFromLine) m.city = cityFromLine;
            return m;
        }
        var companies = raw.companies || [];
        var airline0 = companies[0] || raw.airline || '';
        var city0 = String(fallbackCity || 'Самара').trim();
        var sub = airline0 || raw.summary || '';
        return {
            city: city0,
            airline: airline0,
            companies: companies,
            subline: sub,
            summary: raw.summary || sub,
            direct: !!raw.direct
        };
    }

    function thFlightsCacheKey(tourId, depCity) {
        return String(tourId) + '@' + norm(depCity || '');
    }

    function thFlightsCacheGet(tourId, depCity) {
        if (!tourId) return null;
        var key = thFlightsCacheKey(tourId, depCity);
        var stores = [
            global.__thFlightsByTourId,
            global.__promoFlightsByTourId,
            global.__countryFlightsByTourId,
            global.__mainFlightsByTourId
        ];
        for (var i = 0; i < stores.length; i++) {
            if (stores[i] && stores[i][key]) return stores[i][key];
        }
        return null;
    }

    function thFlightsCacheSet(tourId, meta, depCity) {
        if (!tourId || !meta) return;
        var cityKey = depCity || meta.city || '';
        var key = thFlightsCacheKey(tourId, cityKey);
        global.__thFlightsByTourId = global.__thFlightsByTourId || {};
        global.__thFlightsByTourId[key] = meta;
        global.__promoFlightsByTourId = global.__promoFlightsByTourId || {};
        global.__promoFlightsByTourId[key] = meta;
        global.__countryFlightsByTourId = global.__countryFlightsByTourId || {};
        global.__countryFlightsByTourId[key] = meta;
        global.__mainFlightsByTourId = global.__mainFlightsByTourId || {};
        global.__mainFlightsByTourId[key] = meta;
    }

    function thFlightsCacheFromJson(tourId, json, depCity, departureIdHint) {
        if (!tourId || !json || json.success === false) return null;
        var flights = json.flights;
        if (!Array.isArray(flights) || !flights.length) return null;
        var pkg = pickTourvisorFlightPackage(flights, depCity, departureIdHint);
        if (!pkg) return null;
        var meta = thFlightMetaFromPackage(pkg, depCity);
        if (meta) thFlightsCacheSet(tourId, meta, depCity || meta.city);
        return meta;
    }

    function thFlightChipHtml(meta, depCity) {
        if (global.THTourCard && typeof global.THTourCard.buildFlightBlockHtml === 'function') {
            return global.THTourCard.buildFlightBlockHtml(depCity || '', '', { flightMeta: meta });
        }
        var city = String((meta && meta.city) || depCity || '').trim() || 'Самара';
        var subline = '';
        if (meta) {
            var airline = String(meta.airline || (meta.companies && meta.companies[0]) || '').trim();
            var time = String(meta.time || '').trim();
            subline = String(meta.subline || meta.summary || '').trim();
            if (!subline && airline && time) subline = airline + ' \u00b7 ' + time;
            else if (!subline && airline) subline = airline;
            else if (!subline && time) subline = time;
        }
        var hasFlightData = !!subline;
        if (!subline) subline = '\u0423\u0442\u043e\u0447\u043d\u0438\u0442\u0435 \u0443 \u043c\u0435\u043d\u0435\u0434\u0436\u0435\u0440\u0430';
        var subClass = hasFlightData
            ? 'th-tour-card__flight-sub'
            : 'th-tour-card__flight-sub th-tour-card__flight-sub--stub';
        return (
            '<div class="th-tour-card__flight-chip">' +
            '<span class="th-tour-card__flight-icon" aria-hidden="true"><i class="fas fa-plane"></i></span>' +
            '<div class="th-tour-card__flight-text">' +
            '<b>\u0412\u044b\u043b\u0435\u0442</b>' +
            '<span class="th-tour-card__flight-city">' + escHtml(city) + '</span>' +
            '<span class="' + subClass + '">' + escHtml(subline) + '</span>' +
            '</div></div>'
        );
    }

    function escHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function thTvFetch(url) {
        return fetch(url, { cache: 'no-store' }).then(function (r) {
            if (r.status !== 503 && r.status !== 429) return r;
            return new Promise(function (resolve) {
                setTimeout(resolve, 1200);
            }).then(function () {
                return fetch(url, { cache: 'no-store' });
            });
        });
    }

    function thLoadTourFlightsForHotels(hotels, opts) {
        opts = opts || {};
        var base = opts.apiBase || global.TH_TV_API_BASE || global.TV_API_BASE || '';
        var depCity = opts.departureCity || (global.TH_DEPARTURE && global.TH_DEPARTURE.name) || 'Самара';
        var departureIdHint = opts.departureId != null ? opts.departureId : (global.TH_DEPARTURE && global.TH_DEPARTURE.id);
        var maxTours = opts.maxTours != null ? opts.maxTours : 12;
        var maxConcurrent = opts.maxConcurrent != null ? opts.maxConcurrent : 3;
        var patchEvery = opts.patchEvery != null ? opts.patchEvery : 4;
        var loadGen = opts.loadGen;
        var getTourId = opts.getTourId;
        var onDone = opts.onDone;
        if (!base) {
            if (onDone) onDone();
            return Promise.resolve();
        }
        var tourIds = [];
        (hotels || []).forEach(function (h) {
            var tid = '';
            if (typeof getTourId === 'function') tid = getTourId(h);
            else {
                var tour = (h && h.tours && h.tours[0]) ? h.tours[0] : (h && h._tour) ? h._tour : {};
                tid = (tour.id != null && tour.id !== '') ? String(tour.id) : '';
            }
            if (tid && tourIds.indexOf(tid) < 0) tourIds.push(tid);
        });
        tourIds.splice(maxTours);
        if (!tourIds.length) {
            if (onDone) onDone();
            return Promise.resolve();
        }
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        var queue = tourIds.slice();
        var active = 0;
        var loaded = 0;
        var stale = function () {
            return loadGen != null && loadGen !== global.__thFlightsLoadGen;
        };

        function patchFlightsNow() {
            if (stale()) return;
            if (opts.patchContainer && global.THTourCard && typeof global.THTourCard.patchFlightsInContainer === 'function') {
                global.THTourCard.patchFlightsInContainer(opts.patchContainer);
            } else if (global.THTourCard && typeof global.THTourCard.patchFlightsInContainer === 'function') {
                global.THTourCard.patchFlightsInContainer(document);
            }
        }

        function finishAll() {
            if (stale()) {
                if (onDone) onDone();
                return;
            }
            patchFlightsNow();
            if (onDone) onDone();
        }

        function drain() {
            if (stale()) return;
            while (active < maxConcurrent && queue.length) {
                (function (tourId) {
                    active++;
                    var url = base + sep + 'type=tour-flights&tourId=' + encodeURIComponent(tourId) + '&currency=RUB';
                    thTvFetch(url)
                        .then(function (r) { return r.json(); })
                        .then(function (j) {
                            if (!stale()) thFlightsCacheFromJson(tourId, j, depCity, departureIdHint);
                        })
                        .catch(function () {})
                        .finally(function () {
                            active--;
                            loaded++;
                            if (!stale() && (loaded % patchEvery === 0 || !queue.length)) {
                                patchFlightsNow();
                            }
                            if (!queue.length && active === 0) {
                                finishAll();
                            } else {
                                drain();
                            }
                        });
                })(queue.shift());
            }
        }

        return new Promise(function (resolve) {
            var userDone = onDone;
            onDone = function () {
                if (typeof userDone === 'function') userDone();
                resolve();
            };
            opts.onDone = onDone;
            drain();
        });
    }

    global.thPickTourvisorFlightPackage = pickTourvisorFlightPackage;
    global.thFlightMetaFromPackage = thFlightMetaFromPackage;
    global.thFlightMetaNormalize = thFlightMetaNormalize;
    global.thFlightChipHtml = thFlightChipHtml;
    global.thFlightsCacheGet = thFlightsCacheGet;
    global.thFlightsCacheSet = thFlightsCacheSet;
    global.thFlightsCacheFromJson = thFlightsCacheFromJson;
    global.thLoadTourFlightsForHotels = thLoadTourFlightsForHotels;
})(typeof window !== 'undefined' ? window : this);
