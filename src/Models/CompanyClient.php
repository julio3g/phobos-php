<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;
use Phobos\Enums\PersonType;

/** Cliente pessoa jurídica, identificado por CNPJ; exibe o nome fantasia quando houver. */
final class CompanyClient extends Client
{
    private string $cnpj;
    private ?string $tradeName = null;

    public function __construct(string $name, string $cnpj)
    {
        parent::__construct($name);
        $this->setCnpj($cnpj);
    }

    public function type(): PersonType
    {
        return PersonType::Company;
    }

    public function document(): string
    {
        return $this->cnpj;
    }

    public function documentLabel(): string
    {
        return 'CNPJ';
    }

    public function displayName(): string
    {
        return $this->tradeName ?? $this->name();
    }

    public function tradeName(): ?string
    {
        return $this->tradeName;
    }

    public function setTradeName(?string $tradeName): static
    {
        $this->tradeName = self::nullableText($tradeName, 'Nome fantasia', 150);

        return $this;
    }

    public function setCnpj(string $cnpj): void
    {
        $digits = self::onlyDigits($cnpj);
        if (!self::isValidCnpj($digits)) {
            throw new DomainException('CNPJ inválido.');
        }
        $this->cnpj = $digits;
    }

    public static function isValidCnpj(string $d): bool
    {
        if (strlen($d) !== 14 || preg_match('/^(\d)\1{13}$/', $d)) {
            return false;
        }
        foreach ([12, 13] as $len) {
            $weights = $len === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $sum = 0;
            foreach ($weights as $i => $w) {
                $sum += (int) $d[$i] * $w;
            }
            $r = $sum % 11;
            if ((int) $d[$len] !== ($r < 2 ? 0 : 11 - $r)) {
                return false;
            }
        }

        return true;
    }
}
