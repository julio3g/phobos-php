<?php

declare(strict_types=1);

namespace Phobos\Core;

/**
 * Validação de entrada. Cada regra registra uma mensagem por campo;
 * o controller consulta fails()/errors() e devolve o formulário.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /** @param array<string,mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        if (trim((string) ($this->data[$field] ?? '')) === '') {
            $this->errors[$field] ??= "$label é obrigatório.";
        }

        return $this;
    }

    public function maxLength(string $field, string $label, int $max): self
    {
        if (strlen((string) ($this->data[$field] ?? '')) > $max) {
            $this->errors[$field] ??= "$label deve ter no máximo $max caracteres.";
        }

        return $this;
    }

    public function number(string $field, string $label, ?float $min = null, ?float $max = null, bool $exclusiveMin = false): self
    {
        $raw = trim((string) ($this->data[$field] ?? ''));
        if ($raw === '') {
            return $this;
        }
        $value = self::toFloat($raw);
        if ($value === null) {
            $this->errors[$field] ??= "$label deve ser um número.";
        } elseif ($min !== null && ($exclusiveMin ? $value <= $min : $value < $min)) {
            $this->errors[$field] ??= "$label deve ser " . ($exclusiveMin ? 'maior que' : 'no mínimo') . " $min.";
        } elseif ($max !== null && $value > $max) {
            $this->errors[$field] ??= "$label deve ser no máximo $max.";
        }

        return $this;
    }

    public function email(string $field, string $label): self
    {
        $raw = trim((string) ($this->data[$field] ?? ''));
        if ($raw !== '' && filter_var($raw, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] ??= "$label inválido.";
        }

        return $this;
    }

    public function url(string $field, string $label): self
    {
        $raw = trim((string) ($this->data[$field] ?? ''));
        if ($raw !== '' && (filter_var($raw, FILTER_VALIDATE_URL) === false || !preg_match('#^https?://#i', $raw))) {
            $this->errors[$field] ??= "$label deve ser uma URL http(s) válida.";
        }

        return $this;
    }

    /** @param list<string> $allowed */
    public function in(string $field, string $label, array $allowed): self
    {
        if (!in_array((string) ($this->data[$field] ?? ''), $allowed, true)) {
            $this->errors[$field] ??= "$label inválido.";
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function add(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
    }

    /** Aceita "1.234,56" (pt-BR) e "1234.56". */
    public static function toFloat(string $raw): ?float
    {
        $raw = trim($raw);
        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        return is_numeric($raw) ? (float) $raw : null;
    }
}
