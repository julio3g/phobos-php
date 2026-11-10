<?php

declare(strict_types=1);

namespace Phobos\Traits;

use DateTimeImmutable;

/** Reuso: arquivamento lógico (soft delete) de materiais e produtos. */
trait Archivable
{
    private ?DateTimeImmutable $archivedAt = null;

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    public function archivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function archive(): void
    {
        $this->archivedAt ??= new DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->archivedAt = null;
    }

    /** @param array<string,mixed> $row */
    protected function hydrateArchived(array $row): void
    {
        $this->archivedAt = !empty($row['archived_at']) ? new DateTimeImmutable((string) $row['archived_at']) : null;
    }
}
