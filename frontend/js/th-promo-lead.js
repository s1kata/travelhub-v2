/**
 * Promo page lead — единая модалка THTourBookingModal.
 */
(function (global) {
  'use strict';

  function pageCfg() {
    return global.__TH_PROMO_PAGE__ || {};
  }

  function ymGoal(goal) {
    try {
      var c = pageCfg();
      var id = (c.ymId && String(c.ymId).replace(/\D/g, ''))
        ? parseInt(String(c.ymId).replace(/\D/g, ''), 10) : 0;
      if (id && typeof global.ym === 'function') global.ym(id, 'reachGoal', goal);
    } catch (e) {}
  }

  function departureLabel() {
    var c = pageCfg();
    return String(c.departureName || 'Самара').trim();
  }

  function countryLabel() {
    if (global.__promoActiveCountryName) {
      return String(global.__promoActiveCountryName).trim();
    }
    try {
      var u = new URL(global.location.href);
      return String(u.searchParams.get('countryName') || '').trim();
    } catch (e) {
      return '';
    }
  }

  function buildMessage(note, extras) {
    extras = extras || {};
    var parts = ['Горящие туры / акции'];
    parts.push('Вылет: ' + departureLabel());
    var country = extras.country || countryLabel();
    if (country) parts.push('Страна: ' + country);
    if (extras.hotelName) parts.push('Отель: ' + extras.hotelName);
    if (extras.hotelPrice) parts.push('Цена: ' + extras.hotelPrice + ' ₽');
    if (note) parts.push(String(note));
    return parts.join('\n');
  }

  /**
   * @param {object} [opts]
   */
  function open(opts) {
    opts = opts || {};
    var country = opts.country || countryLabel();
    ymGoal('promo_lead_modal_open');
    if (global.THTourBookingModal && typeof global.THTourBookingModal.open === 'function') {
      global.THTourBookingModal.open({
        from_promo: '1',
        lead_source: opts.source || 'promo_lead',
        country: country,
        hotel_name: opts.hotelName || (country ? ('Подбор: ' + country) : 'Горящие туры'),
        price: opts.hotelPrice || '',
        departure_city: departureLabel(),
        lead_note: opts.note || '',
        lead_message: opts.message || buildMessage(opts.note || '', {
          country: country,
          hotelName: opts.hotelName,
          hotelPrice: opts.hotelPrice
        }),
        modal_title: opts.title || 'Заявка на акционный тур',
        phone_only: !!opts.phoneOnly
      });
      return true;
    }
    return false;
  }

  global.THPromoLead = {
    open: open,
    buildMessage: buildMessage,
    ymGoal: ymGoal
  };
})(window);
