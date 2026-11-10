<?php

declare(strict_types=1);

namespace Phobos\Repositories;

use PDOException;
use Phobos\Models\Entity;
use Phobos\Models\Material;

final class MaterialRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'materials';
    }

    protected function hydrate(array $row): Material
    {
        return Material::fromRow($row);
    }

    /** @return list<Material> */
    public function search(string $term = '', bool $includeArchived = false): array
    {
        $term = trim($term);
        $sql = 'SELECT * FROM materials WHERE (:arch OR archived_at IS NULL) AND (:t = \'\' OR name ILIKE :like OR brand ILIKE :like) ORDER BY name';
        $rows = $this->run($sql, [
            'arch' => $includeArchived ? 'true' : 'false',
            't' => $term,
            'like' => '%' . addcslashes($term, '%_\\') . '%',
        ])->fetchAll();

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /** @return list<Material> somente ativos, para selects */
    public function active(): array
    {
        return $this->search();
    }

    /** @return array<int,Material> indexados por id */
    public function findManyById(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM materials WHERE id IN ($in)");
        $stmt->execute(array_values($ids));
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['id']] = $this->hydrate($row);
        }

        return $map;
    }

    public function save(Entity $entity): Entity
    {
        /** @var Material $entity */
        $params = [
            'name' => $entity->name(),
            'brand' => $entity->brand(),
            'variant' => $entity->variant(),
            'image' => $entity->imageUrl(),
            'purchase' => $entity->purchaseUrl(),
            'unit' => $entity->unit()->value,
            'price' => $entity->packagePrice(),
            'qty' => $entity->packageQuantity(),
            'archived' => $entity->archivedAt()?->format('Y-m-d H:i:sP'),
        ];
        try {
            if ($entity->id() === null) {
                $id = $this->run(
                    'INSERT INTO materials (name, brand, variant, image_url, purchase_url, unit, package_price, package_quantity, archived_at)
                     VALUES (:name, :brand, :variant, :image, :purchase, :unit, :price, :qty, :archived) RETURNING id',
                    $params,
                )->fetchColumn();
                $entity->assignId((int) $id);
            } else {
                $this->run(
                    'UPDATE materials SET name = :name, brand = :brand, variant = :variant, image_url = :image, purchase_url = :purchase,
                     unit = :unit, package_price = :price, package_quantity = :qty, archived_at = :archived, updated_at = now()
                     WHERE id = :id',
                    $params + ['id' => $entity->id()],
                );
            }
        } catch (PDOException $e) {
            $this->guardUnique($e, 'Já existe um material com mesmo nome, marca e variação.');
        }

        return $entity;
    }
}
