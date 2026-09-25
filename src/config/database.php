<?php

namespace App;

use PDO;
use PDOException;

class Database{
    private static ?PDO $connection = null;

    public static function connection(): PDO {
        if (self::$connection === null) {
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_CONNECTION'],
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_DATABASE']
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $_ENV['DB_USERNAME'],
                    $_ENV['DB_PASSWORD'],
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                throw new \RuntimeException('Database connection failed.');
            }
        }

        return self::$connection;
    }

}
