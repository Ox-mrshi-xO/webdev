<?php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dsrrm_clinic');
define('BASE_URL', 'http://localhost/clinic_system/');
define('SITE_NAME', 'DSRRM Clinic');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // set to 1 in production with HTTPS
    session_start();
}
