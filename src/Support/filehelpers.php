<?php

use App\Database;

if (!function_exists('test_connection')) {
    function test_connection(bool $verbose = true): bool{
        try {
            $pdo = Database::connection();

            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            $dbName  = $pdo->query('SELECT DATABASE()')->fetchColumn();

            if ($verbose) {
                echo " Connected to '{$dbName}' (MySQL {$version})\n";
            }
            return true;

        } catch (\Throwable $e) {
            if ($verbose) {
                echo " Connection failed: " . $e->getMessage() . "\n";
            }
            return false;
        }
    }
}