<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;
use Phobos\Enums\PersonType;

/** Cliente pessoa física, identificado por CPF. */
final class IndividualClient extends Client
{
    private string $cpf;

    public function __construct(string $name, string $cpf)
    {
        parent::__construct($name);
        $this->setCpf($cpf);
    }

    public function type(): PersonType
    {
        return PersonType::Individual;
    }

    public function document(): string
    {
        return $this->cpf;
    }

    public function documentLabel(): string
    {
        return 'CPF';
    }

    public function setCpf(string $cpf): void
    {
        $digits = self::onlyDigits($cpf);
        if (!self::isValidCpf($digits)) {
            throw new DomainException('CPF inválido.');
        }
        $this->cpf = $digits;
    }

    public static function isValidCpf(string $d): bool
    {
        if (strlen($d) !== 11 || preg_match('/^(\d)\1{10}$/', $d)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $d[$i] * (($t + 1) - $i);
            }
            if ((int) $d[$t] !== ((10 * $sum) % 11) % 10) {
                return false;
            }
        }

        return true;
    }
}
