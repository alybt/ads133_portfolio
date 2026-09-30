<?php

namespace App\Support;

use App\Models\User;
use App\Repositories\UserRepository;

class Auth{
    private const SESSION_KEY = '_auth_user_id';

    private static ?User $cached = null;

    public static function attempt(string $email, string $password): bool
    {
        $repo = new UserRepository();
        $user = $repo->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            return false;
        }

        if (password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $repo->update($user);
        }

        session_regenerate_id(true);  
        $_SESSION[self::SESSION_KEY] = $user->id;
        self::$cached = $user;

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::$cached = null;
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?User
    {
        if (self::$cached) {
            return self::$cached;
        }

        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if (!$id) {
            return null;
        }

        $user = (new UserRepository())->find((int) $id);
        if (!$user) {
            unset($_SESSION[self::SESSION_KEY]); 
            return null;
        }

        return self::$cached = $user;
    }

    public static function id(): ?int
    {
        return self::user()?->id;
    }

    public static function requireLogin(string $redirectTo = '/login'): void
    {
        if (!self::check()) {
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect($redirectTo);
        }
    }
}