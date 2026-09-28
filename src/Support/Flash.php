<?php
namespace App\Support;

class Flash
{
    public static function set(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function get(string $key): ?string
    {
        if (empty($_SESSION['_flash'][$key])) return null;
        $msg = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }
}