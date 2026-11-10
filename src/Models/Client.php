<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;
use Phobos\Enums\PersonType;
use Phobos\Traits\HasTimestamps;

/**
 * Cliente (classe abstrata). Pessoa física e jurídica herdam daqui e
 * especializam documento e nome de exibição (polimorfismo).
 */
abstract class Client extends Entity
{
    use HasTimestamps;

    private string $name;
    private ?string $email = null;
    private ?string $phone = null;
    private ?string $city = null;
    private ?string $state = null;
    private ?string $street = null;
    private ?string $zipCode = null;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    abstract public function type(): PersonType;

    /** Documento somente com dígitos, validado pela subclasse. */
    abstract public function document(): string;

    abstract public function documentLabel(): string;

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        $type = PersonType::from((string) $row['person_type']);
        $client = match ($type) {
            PersonType::Individual => new IndividualClient((string) $row['name'], (string) $row['cpf']),
            PersonType::Company => (new CompanyClient((string) $row['name'], (string) $row['cnpj']))
                ->setTradeName($row['trade_name'] ?? null),
        };
        $client->id = (int) $row['id'];
        $client->setEmail($row['email'] ?? null);
        $client->setPhone($row['phone'] ?? null);
        $client->setAddress($row['street'] ?? null, $row['city'] ?? null, $row['state'] ?? null, $row['zip_code'] ?? null);
        $client->hydrateTimestamps($row);

        return $client;
    }

    public function label(): string
    {
        return $this->displayName();
    }

    public function displayName(): string
    {
        return $this->name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = self::requireText($name, 'Nome', 150);
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $email = self::nullableText($email, 'E-mail', 150);
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('E-mail inválido.');
        }
        $this->email = $email === null ? null : strtolower($email);
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = self::nullableText($phone, 'Telefone', 30);
    }

    public function setAddress(?string $street, ?string $city, ?string $state, ?string $zip): void
    {
        $this->street = self::nullableText($street, 'Endereço', 200);
        $this->city = self::nullableText($city, 'Cidade', 100);
        $state = self::nullableText($state, 'UF', 2);
        $this->state = $state === null ? null : strtoupper($state);
        $this->zipCode = self::nullableText($zip, 'CEP', 12);
    }

    public function street(): ?string
    {
        return $this->street;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function state(): ?string
    {
        return $this->state;
    }

    public function zipCode(): ?string
    {
        return $this->zipCode;
    }

    public function tradeName(): ?string
    {
        return null;
    }

    protected static function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
