<?php

declare(strict_types=1);

namespace Phobos\Core;

/** Renderiza templates PHP de views/ dentro do layout principal. */
final class View
{
    /** @param array<string,mixed> $data */
    public static function render(string $template, array $data = [], bool $withLayout = true): void
    {
        $content = self::capture($template, $data);
        if (!$withLayout) {
            echo $content;

            return;
        }
        $flash = Session::pullFlash();
        $user = Auth::user();
        require dirname(__DIR__, 2) . '/views/layout/main.php';
    }

    /** @param array<string,mixed> $data */
    private static function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__, 2) . '/views/' . $template . '.php';

        return (string) ob_get_clean();
    }
}
