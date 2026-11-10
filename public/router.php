<?php

// Roteador do servidor embutido (php -S): serve arquivos estáticos e delega o resto ao index.php.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}
require __DIR__ . '/index.php';
