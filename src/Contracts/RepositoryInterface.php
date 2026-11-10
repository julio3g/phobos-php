<?php

declare(strict_types=1);

namespace Phobos\Contracts;

use Phobos\Models\Entity;

/** Contrato de persistência (CRUD) implementado por todos os repositórios. */
interface RepositoryInterface
{
    public function find(int $id): ?Entity;

    /** @return list<Entity> */
    public function all(): array;

    /** Insere (id nulo) ou atualiza; devolve a entidade com id preenchido. */
    public function save(Entity $entity): Entity;

    public function delete(int $id): void;
}
