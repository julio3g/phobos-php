<?php

declare(strict_types=1);

namespace Phobos\Repositories;

use PDOException;
use Phobos\Enums\PersonType;
use Phobos\Models\Client;
use Phobos\Models\Entity;

final class ClientRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'clients';
    }

    protected function hydrate(array $row): Client
    {
        return Client::fromRow($row);
    }

    /** @return list<Client> */
    public function search(string $term = ''): array
    {
        $term = trim($term);
        $rows = $term === ''
            ? $this->run('SELECT * FROM clients ORDER BY name')->fetchAll()
            : $this->run(
                'SELECT * FROM clients WHERE name ILIKE :t OR trade_name ILIKE :t OR email ILIKE :t ORDER BY name',
                ['t' => '%' . addcslashes($term, '%_\\') . '%'],
            )->fetchAll();

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    public function save(Entity $entity): Entity
    {
        /** @var Client $entity */
        $isCompany = $entity->type() === PersonType::Company;
        $params = [
            'name' => $entity->name(),
            'trade' => $entity->tradeName(),
            'email' => $entity->email(),
            'phone' => $entity->phone(),
            'type' => $entity->type()->value,
            'cpf' => $isCompany ? null : $entity->document(),
            'cnpj' => $isCompany ? $entity->document() : null,
            'street' => $entity->street(),
            'city' => $entity->city(),
            'state' => $entity->state(),
            'zip' => $entity->zipCode(),
        ];
        try {
            if ($entity->id() === null) {
                $id = $this->run(
                    'INSERT INTO clients (name, trade_name, email, phone, person_type, cpf, cnpj, street, city, state, zip_code)
                     VALUES (:name, :trade, :email, :phone, :type, :cpf, :cnpj, :street, :city, :state, :zip) RETURNING id',
                    $params,
                )->fetchColumn();
                $entity->assignId((int) $id);
            } else {
                $this->run(
                    'UPDATE clients SET name = :name, trade_name = :trade, email = :email, phone = :phone, person_type = :type,
                     cpf = :cpf, cnpj = :cnpj, street = :street, city = :city, state = :state, zip_code = :zip, updated_at = now()
                     WHERE id = :id',
                    $params + ['id' => $entity->id()],
                );
            }
        } catch (PDOException $e) {
            $this->guardUnique($e, 'Já existe um cliente com este documento.');
        }

        return $entity;
    }
}
