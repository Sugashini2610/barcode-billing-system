<?php
/**
 * Authentication Module - PHP 5.6 compatible
 */
class Auth
{
    public static function attempt($username, $password)
    {
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            Session::set('user_id', 1);
            Session::set('username', $username);
            Session::set('role', 'admin');
            Session::set('login_time', time());
            return true;
        }
        return false;
    }

    public static function check()
    {
        return Session::isLoggedIn();
    }

    public static function user()
    {
        return array(
            'id' => Session::get('user_id'),
            'username' => Session::get('username'),
            'role' => Session::get('role'),
        );
    }

    public static function logout()
    {
        Session::destroy();
    }

    public static function requireLogin()
    {
        if (!self::check()) {
            redirect(BASE_URL . '/login');
        }
    }

    public static function requireGuest()
    {
        if (self::check()) {
            redirect(BASE_URL . '/dashboard');
        }
    }
}
