<?php
/**
 * Session Handler - PHP 5.6 compatible
 */
class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('HARITHA_BILLING_SID');
            $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200;
            session_set_cookie_params($lifetime, '/');
            session_start();
        }
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public static function destroy()
    {
        session_unset();
        session_destroy();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
    }

    public static function flash($key, $value = null)
    {
        if ($value === null) {
            $val = isset($_SESSION['_flash'][$key]) ? $_SESSION['_flash'][$key] : null;
            unset($_SESSION['_flash'][$key]);
            return $val;
        }
        $_SESSION['_flash'][$key] = $value;
    }

    public static function isLoggedIn()
    {
        return self::has('user_id') && self::has('username');
    }
}
