<?php
// config.example.php
// Copy this file to config.php and fill in your values

define('APP_NAME',    'Share Hope');
define('APP_VERSION', '2.0');

// ─── Paths ───────────────────────────────────────────────────────────────────
define('BASE_URL', '/share_hope'); // change to '' if hosted at domain root

define('APP_ROOT', __DIR__);

define('ASSETS_URL',  BASE_URL . '/assets');
define('CSS_URL',     ASSETS_URL . '/css');
define('JS_URL',      ASSETS_URL . '/js');
define('UPLOADS_URL', ASSETS_URL . '/uploads');

define('UPLOADS_PATH',   APP_ROOT . '/assets/uploads');
define('CAMPAIGNS_PATH', UPLOADS_PATH . '/campaigns');
define('DOCS_PATH',      UPLOADS_PATH . '/docs');
define('IMAGES_PATH',    UPLOADS_PATH . '/images');

// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST',    '127.0.0.1');
define('DB_NAME',    'share_hope');
define('DB_USER',    'your_db_username');
define('DB_PASS',    'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_TIMEOUT', 14400);

// ─── Environment ─────────────────────────────────────────────────────────────
define('APP_ENV', 'development'); // change to 'production' when live
define('DEBUG',   APP_ENV === 'development');
