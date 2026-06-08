<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', false);
    ini_set('session.use_only_cookies', 1);
    session_start();
}
