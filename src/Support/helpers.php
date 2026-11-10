<?php

declare(strict_types=1);

use Phobos\Core\Session;
use Phobos\Support\Format;

// Atalhos usados nas views.

function e(?string $value): string
{
    return Format::e($value);
}

function money(float $value): string
{
    return Format::money($value);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Session::csrfToken()) . '">';
}

/** Valor antigo do formulário (após erro de validação) ou o valor atual do registro. */
function old(array $form, string $key, mixed $default = ''): string
{
    return e((string) ($form[$key] ?? $default));
}
