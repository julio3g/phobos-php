<?php

declare(strict_types=1);

namespace Phobos\Traits;

use DateTimeImmutable;

/** Reuso: datas de criação/atualização presentes em várias entidades. */
trait HasTimestamps
{
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTimeImmutable $updatedAt = null;

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @param array<string,mixed> $row */
    protected function hydrateTimestamps(array $row): void
    {
        $this->createdAt = isset($row['created_at']) ? new DateTimeImmutable((string) $row['created_at']) : null;
        $this->updatedAt = isset($row['updated_at']) ? new DateTimeImmutable((string) $row['updated_at']) : null;
    }
}
