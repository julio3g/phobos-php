<?php

declare(strict_types=1);

namespace Phobos\Repositories;

use PDOException;
use Phobos\Models\Entity;
use Phobos\Models\Material;
use Phobos\Models\Product;
use Phobos\Models\ProductMaterial;

final class ProductRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'products';
    }

    protected function hydrate(array $row): Product
    {
        return Product::fromRow($row, $this->loadLines((int) $row['id']));
    }

    /** @return list<Product> */
    public function search(string $term = '', bool $includeArchived = false): array
    {
        $term = trim($term);
        $rows = $this->run(
            'SELECT * FROM products WHERE (:arch OR archived_at IS NULL) AND (:t = \'\' OR name ILIKE :like) ORDER BY name',
            [
                'arch' => $includeArchived ? 'true' : 'false',
                't' => $term,
                'like' => '%' . addcslashes($term, '%_\\') . '%',
            ],
        )->fetchAll();

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /** @return list<Product> */
    public function active(): array
    {
        return $this->search();
    }

    /** @return list<ProductMaterial> */
    private function loadLines(int $productId): array
    {
        // pm_* = snapshot da composição; m.* = material atual (agregado)
        $rows = $this->run(
            'SELECT pm.quantity AS pm_quantity, pm.unit_cost AS pm_unit_cost, m.*
               FROM product_materials pm
               JOIN materials m ON m.id = pm.material_id
              WHERE pm.product_id = :id
              ORDER BY m.name',
            ['id' => $productId],
        )->fetchAll();

        return array_map(
            static fn (array $r) => new ProductMaterial(Material::fromRow($r), (float) $r['pm_quantity'], (float) $r['pm_unit_cost']),
            $rows,
        );
    }

    /** Salva produto e composição de forma atômica (transação). */
    public function save(Entity $entity): Entity
    {
        /** @var Product $entity */
        $params = [
            'name' => $entity->name(),
            'variant' => $entity->variant(),
            'image' => $entity->imageUrl(),
            'description' => $entity->description(),
            'rate' => $entity->hourlyRate(),
            'minutes' => $entity->laborMinutes(),
            'mtype' => $entity->marginType()->value,
            'mvalue' => $entity->marginValue(),
            'mcost' => $entity->materialCost(),
            'lcost' => $entity->laborCost(),
            'price' => $entity->salePrice(),
            'archived' => $entity->archivedAt()?->format('Y-m-d H:i:sP'),
        ];

        try {
            $this->transaction(function () use ($entity, $params): void {
                if ($entity->id() === null) {
                    $id = $this->run(
                        'INSERT INTO products (name, variant, image_url, description, hourly_rate, labor_minutes, margin_type, margin_value,
                                               material_cost, labor_cost, sale_price, archived_at)
                         VALUES (:name, :variant, :image, :description, :rate, :minutes, :mtype, :mvalue, :mcost, :lcost, :price, :archived)
                         RETURNING id',
                        $params,
                    )->fetchColumn();
                    $entity->assignId((int) $id);
                } else {
                    $this->run(
                        'UPDATE products SET name = :name, variant = :variant, image_url = :image, description = :description,
                                hourly_rate = :rate, labor_minutes = :minutes, margin_type = :mtype, margin_value = :mvalue,
                                material_cost = :mcost, labor_cost = :lcost, sale_price = :price, archived_at = :archived, updated_at = now()
                          WHERE id = :id',
                        $params + ['id' => $entity->id()],
                    );
                    $this->run('DELETE FROM product_materials WHERE product_id = :id', ['id' => $entity->id()]);
                }

                foreach ($entity->lines() as $line) {
                    $this->run(
                        'INSERT INTO product_materials (product_id, material_id, quantity, unit_cost, total_cost)
                         VALUES (:p, :m, :q, :u, :t)',
                        [
                            'p' => $entity->id(),
                            'm' => $line->material()->id(),
                            'q' => $line->quantity(),
                            'u' => $line->unitCostSnapshot(),
                            't' => $line->totalCost(),
                        ],
                    );
                }
            });
        } catch (PDOException $e) {
            $this->guardUnique($e, 'Já existe um produto com mesmo nome e variação.');
        }

        return $entity;
    }
}
