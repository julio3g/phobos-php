<?php

declare(strict_types=1);

/**
 * Cria o banco (se não existir) e aplica database/schema.sql.
 * Uso: php bin/migrate.php
 */

use Phobos\Core\Database;
use Phobos\Core\Env;

$root = dirname(__DIR__);
require $root . '/src/autoload.php';
Env::load($root . '/.env');

$dbName = Env::get('DB_NAME', 'phobos_php');
if (!preg_match('/^[a-z0-9_]+$/i', $dbName)) {
    fwrite(STDERR, "DB_NAME inválido.\n");
    exit(1);
}

// tenta criar o banco da aplicação; em bancos gerenciados (Neon, Supabase) ele já existe
// e o usuário pode não ter acesso ao banco "postgres" — nesse caso segue direto para o esquema.
try {
    $admin = new PDO(
        sprintf('pgsql:host=%s;port=%s;dbname=postgres;sslmode=%s', Env::get('DB_HOST', '127.0.0.1'), Env::get('DB_PORT', '5432'), Env::get('DB_SSLMODE', 'prefer')),
        Env::get('DB_USER', 'postgres'),
        Env::get('DB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $exists = $admin->prepare('SELECT 1 FROM pg_database WHERE datname = :n');
    $exists->execute(['n' => $dbName]);
    if (!$exists->fetchColumn()) {
        $admin->exec('CREATE DATABASE "' . $dbName . '"');
        echo "Banco $dbName criado.
";
    }
} catch (PDOException) {
    echo "Aviso: não foi possível checar/criar o banco; usando o existente.
";
}

Database::connection()->exec((string) file_get_contents($root . '/database/schema.sql'));
echo "Esquema aplicado.\n";
