<?php
/**
 * Единый набор карточек «Популярные направления» для главной, офисов и API-fallback.
 * Порядок: Турция, Сочи (страница России), Абхазия, Египет, Вьетнам (countryId — Tourvisor, см. country_promo_tourvisor_map).
 */
declare(strict_types=1);

return [
    ['countryId' => 4, 'slug' => 'turkey', 'name' => 'Турция', 'href' => '/frontend/window/countries/turkey.php', 'image' => '/frontend/window/img/турция/ostrovok-filters-4-10.jpg'],
    ['countryId' => 47, 'slug' => 'russia', 'name' => 'Сочи', 'href' => '/frontend/window/countries/russia.php', 'image' => '/frontend/window/img/promo-departure-cities/sochi.png'],
    ['countryId' => 46, 'slug' => 'abkhazia', 'name' => 'Абхазия', 'href' => '/frontend/window/countries/abkhazia.php', 'image' => '/frontend/window/img/countries-list/abkhazia.png'],
    ['countryId' => 1, 'slug' => 'egypt', 'name' => 'Египет', 'href' => '/frontend/window/countries/egypt.php', 'image' => '/frontend/window/img/египет/photo_2025-11-27_17-02-33.jpg'],
    ['countryId' => 16, 'slug' => 'vietnam', 'name' => 'Вьетнам', 'href' => '/frontend/window/countries/vietnam.php', 'image' => '/frontend/window/img/countries-list/vietnam.png'],
];
