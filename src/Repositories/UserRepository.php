<?php

declare(strict_types=1);

namespace Phobos\Repositories;

use PDOException;
use Phobos\Models\Entity;
use Phobos\Models\User;

final class UserRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'users';
    }

    protected function hydrate(array $row): User
    {
        return User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->run('SELECT * FROM users WHERE email = :email', ['email' => strtolower(trim($email))])->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function count(): int
    {
        return (int) $this->run('SELECT count(*) FROM users')->fetchColumn();
    }

    /** Todos os usuários em ordem alfabética (tela de gestão). @return list<User> */
    public function allByName(): array
    {
        $rows = $this->run('SELECT * FROM users ORDER BY lower(name)')->fetchAll();

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    public function save(Entity $entity): Entity
    {
        /** @var User $entity */
        $params = [
            'name' => $entity->name(),
            'email' => $entity->email(),
            'hash' => $entity->passwordHash(),
            'role' => $entity->role(),
        ];
        try {
            if ($entity->id() === null) {
                $id = $this->run(
                    'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role) RETURNING id',
                    $params,
                )->fetchColumn();
                $entity->assignId((int) $id);
            } else {
                $this->run(
                    'UPDATE users SET name = :name, email = :email, password_hash = :hash, role = :role, updated_at = now() WHERE id = :id',
                    $params + ['id' => $entity->id()],
                );
            }
        } catch (PDOException $e) {
            $this->guardUnique($e, 'Já existe um usuário com este e-mail.');
        }

        return $entity;
    }
}
