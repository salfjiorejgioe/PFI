<?php

/*
|--------------------------------------------------------------------------
| DARQUEST SESSION CONFIG
|--------------------------------------------------------------------------
| Stable session handling for shared/shared-like hosting
| Prevents:
| - session permission crashes
| - broken headers
| - login desync between pages
| - duplicate session_start warnings
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    /*
    |--------------------------------------------------------------------------
    | Prevent hosting session cleanup permission crash
    |--------------------------------------------------------------------------
    */
    ini_set('session.gc_probability', '0');

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    /*
    |--------------------------------------------------------------------------
    | Stable lifetime
    |--------------------------------------------------------------------------
    */
    ini_set('session.gc_maxlifetime', 86400);

    /*
    |--------------------------------------------------------------------------
    | Error handling
    |--------------------------------------------------------------------------
    */
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

 
    if (!headers_sent()) {

        session_name('DARQUESTSESSID');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    session_start();

 
    if (!isset($_SESSION['initialized'])) {

        session_regenerate_id(true);

        $_SESSION['initialized'] = true;
        $_SESSION['created_at'] = time();
    }


    if (
        isset($_SESSION['created_at']) &&
        (time() - $_SESSION['created_at']) > 86400
    ) {

        session_unset();
        session_destroy();

        session_start();

        $_SESSION = [];
    }
}