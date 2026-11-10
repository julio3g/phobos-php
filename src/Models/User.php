<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;
use Phobos\Traits\HasTimestamps;

final class User extends Entity
{
    use HasTimestamps;

    private string $name;
    private string $email;
    private string $passwordHash;
    private string $role;

    private function __construct(string $name, string $email, string $passwordHash, string $role)
    {
        $this->setName($name);
        $this->setEmail($email);
        $this->passwordHash = $passwordHash;
        $this->setRole($role);
    }

    /** Cria um usuário novo a partir da senha em texto puro (gera o hash). */
    public static function register(string $name, string $email, string $plainPassword, string $role = 'admin'): self
    {
        $user = new self($name, $email, 'x', $role);
        $user->changePassword($plainPassword);

        return $user;
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        $user = new self((string) $row['name'], (string) $row['email'], (string) $row['password_hash'], (string) $row['role']);
        $user->id = (int) $row['id'];
        $user->hydrateTimestamps($row);

        return $user;
    }

    public function label(): string
    {
        return $this->name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = self::requireText($name, 'Nome', 120);
    }

    public function email(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $email = strtolower(trim($email));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('E-mail inválido.');
        }
        $this->email = $email;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        if (!in_array($role, ['admin', 'member'], true)) {
            throw new DomainException('Perfil inválido.');
        }
        $this->role = $role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function changePassword(string $plain): void
    {
        if (strlen($plain) < 8) {
            throw new DomainException('A senha deve ter no mínimo 8 caracteres.');
        }
        $this->passwordHash = password_hash($plain, PASSWORD_DEFAULT);
    }
}
