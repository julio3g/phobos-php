<?php

declare(strict_types=1);

namespace Phobos\Core;

/** Carrega variáveis do arquivo .env (formato CHAVE=valor) e expõe leitura. */
final class Env
{
    /** @var array<string,string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            self::$values[trim($key)] = trim(trim($value), "\"'");
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        return self::$values[$key] ?? ($_ENV[$key] ?? (getenv($key) ?: $default));
    }
}
