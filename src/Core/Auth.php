<?php

declare(strict_types=1);

namespace Phobos\Core;

use Phobos\Models\User;
use Phobos\Repositories\UserRepository;

/** Autenticação por sessão. Senhas com password_hash/password_verify (bcrypt/argon). */
final class Auth
{
    private static ?User $cached = null;

    public static function attempt(string $email, string $password): bool
    {
        $repo = new UserRepository(Database::connection());
        $user = $repo->findByEmail($email);

        // verifica sempre um hash para não vazar, pelo tempo, se o e-mail existe
        $hash = $user?->passwordHash() ?? '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012';
        if (!password_verify($password, $hash) || $user === null) {
            return false;
        }

        Session::regenerate();
        Session::set('user_id', $user->id());

        return true;
    }

    public static function logout(): void
    {
        self::$cached = null;
        Session::destroy();
    }

    public static function user(): ?User
    {
        $id = Session::get('user_id');
        if (!is_int($id)) {
            return null;
        }

        return self::$cached ??= (new UserRepository(Database::connection()))->find($id);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }
}
