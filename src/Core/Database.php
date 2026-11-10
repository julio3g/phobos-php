<?php

declare(strict_types=1);

namespace Phobos\Core;

use PDO;

/** Fábrica/singleton da conexão PDO (PostgreSQL). Sempre com exceções e prepared statements reais. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
                Env::get('DB_HOST', '127.0.0.1'),
                Env::get('DB_PORT', '5432'),
                Env::get('DB_NAME', 'phobos_php'),
                Env::get('DB_SSLMODE', 'prefer'),
            );
            self::$pdo = new PDO($dsn, Env::get('DB_USER', 'postgres'), Env::get('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // parametrização feita pelo servidor
            ]);
        }

        return self::$pdo;
    }
}
