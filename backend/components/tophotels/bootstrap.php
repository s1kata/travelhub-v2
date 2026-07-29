<?php
declare(strict_types=1);

/**
 * TopHotels integration bootstrap (safe no-op until TOPHOTELS_ENABLED / fixture).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/client.php';
require_once __DIR__ . '/match.php';
require_once __DIR__ . '/enrich.php';
require_once __DIR__ . '/sync.php';
