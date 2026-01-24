<?php

namespace Rapo;

class Auth {
    protected static $user = null;

    public static function attempt($email, $password) {
        $userClass = \Rapo\Env::get('AUTH_MODEL', 'App\\Models\\User');
        if (!class_exists($userClass)) return false;

        $user = $userClass::where('email', $email)->first();
        if ($user && password_verify($password, $user->password)) {
            static::login($user);
            return true;
        }
        return false;
    }

    public static function login($user) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['_user_id'] = $user->id;
        static::$user = $user;
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        unset($_SESSION['_user_id']);
        static::$user = null;
    }

    public static function check() {
        return static::user() !== null;
    }

    public static function user() {
        if (static::$user) return static::$user;
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_SESSION['_user_id'] ?? null;
        if (!$id) return null;

        $userClass = \Rapo\Env::get('AUTH_MODEL', 'App\\Models\\User');
        if (!class_exists($userClass)) return null;

        static::$user = $userClass::find($id);
        return static::$user;
    }

    public static function id() {
        return $_SESSION['_user_id'] ?? null;
    }
}
