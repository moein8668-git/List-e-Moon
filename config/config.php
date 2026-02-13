<?php
// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'listemon1');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Settings
define('APP_NAME', 'List-e-Moon');
define('BASE_URL', 'http://localhost/List-e-Moon'); // Update this on production

// API Keys
// Get your keys from:
// TMDB: https://www.themoviedb.org/settings/api
// RAWG: https://rawg.io/apidocs
define('TMDB_API_KEY', '');
define('RAWG_API_KEY', '');
define('GOOGLE_BOOKS_API_KEY', '');

// File Paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('THUMB_DIR', UPLOAD_DIR . 'thumbs/');

// Timezone
date_default_timezone_set('Asia/Tehran'); // Or user's preferred timezone

// Error Reporting (Turn off in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
