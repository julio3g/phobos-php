<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;

/**
 * Raiz da hierarquia de domínio. Guarda a identidade (id do banco) e
 * fornece os guardas de invariantes reutilizados pelas subclasses.
 */
abstract class Entity
{
    protected ?int $id = null;

    public function id(): ?int
    {
        return $this->id;
    }

    /** Chamado pelo repositório após o INSERT. */
    public function assignId(int $id): void
    {
        if ($this->id !== null) {
            throw new DomainException('A entidade já possui identificador.');
        }
        $this->id = $id;
    }

    /** Texto curto para listas e selects. */
    abstract public function label(): string;

    protected static function requireText(string $value, string $field, int $max = 255): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new DomainException("$field é obrigatório.");
        }
        if (strlen($value) > $max) {
            throw new DomainException("$field deve ter no máximo $max caracteres.");
        }

        return $value;
    }

    protected static function nullableText(?string $value, string $field, int $max = 255): ?string
    {
        $value = $value === null ? '' : trim($value);

        return $value === '' ? null : self::requireText($value, $field, $max);
    }
}
