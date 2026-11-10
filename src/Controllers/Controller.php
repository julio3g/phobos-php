<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use Phobos\Core\Auth;
use Phobos\Core\Database;
use Phobos\Core\Session;
use Phobos\Core\Validator;
use Phobos\Core\View;
use Phobos\Models\User;

/** Base dos controllers: exige login, centraliza render/redirect/flash e checagem de perfil. */
abstract class Controller
{
    protected \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
        if ($this->requiresAuth() && !Auth::check()) {
            $this->redirect('/login');
        }
    }

    protected function requiresAuth(): bool
    {
        return true;
    }

    /** @param array<string,mixed> $data */
    protected function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        View::render($template, $data);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    protected function user(): User
    {
        return Auth::user() ?? $this->redirect('/login');
    }

    /** Operações de escrita são restritas a administradores. */
    protected function requireAdmin(): void
    {
        if (!$this->user()->isAdmin()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    protected function post(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    protected function postFloat(string $key, float $default = 0.0): float
    {
        return Validator::toFloat($this->post($key)) ?? $default;
    }

    protected function postNullable(string $key): ?string
    {
        $value = $this->post($key);

        return $value === '' ? null : $value;
    }

    protected function intId(string $id): int
    {
        return ctype_digit($id) ? (int) $id : 0;
    }

    protected function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404');
        exit;
    }
}
