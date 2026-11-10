<?php

declare(strict_types=1);

namespace Phobos\Repositories;

use DomainException;
use PDO;
use PDOException;
use PDOStatement;
use Phobos\Contracts\RepositoryInterface;
use Phobos\Models\Entity;

/**
 * Base dos repositórios: implementa find/all/delete genéricos com consultas
 * parametrizadas; subclasses definem tabela, hidratação e o save().
 */
abstract class AbstractRepository implements RepositoryInterface
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    abstract protected function table(): string;

    /** @param array<string,mixed> $row */
    abstract protected function hydrate(array $row): Entity;

    public function find(int $id): ?Entity
    {
        $row = $this->run("SELECT * FROM {$this->table()} WHERE id = :id", ['id' => $id])->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function all(): array
    {
        $rows = $this->run("SELECT * FROM {$this->table()} ORDER BY id DESC")->fetchAll();

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    public function delete(int $id): void
    {
        try {
            $this->run("DELETE FROM {$this->table()} WHERE id = :id", ['id' => $id]);
        } catch (PDOException $e) {
            // 23503 = foreign_key_violation (registro referenciado por outro)
            if ($e->getCode() === '23503') {
                throw new DomainException('Registro em uso por outros dados; não pode ser excluído.', 0, $e);
            }
            throw $e;
        }
    }

    /** @param array<string,mixed> $params */
    protected function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /** Executa $work em transação; faz rollback e propaga em caso de erro. */
    protected function transaction(callable $work): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $work();
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Traduz violação de unicidade (23505) em mensagem de domínio. */
    protected function guardUnique(PDOException $e, string $message): never
    {
        if ($e->getCode() === '23505') {
            throw new DomainException($message, 0, $e);
        }
        throw $e;
    }
}
