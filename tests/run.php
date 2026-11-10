<?php

declare(strict_types=1);

/**
 * Testes unitários sem dependências (não usam banco). Uso: php tests/run.php
 */

use Phobos\Enums\MarginType;
use Phobos\Enums\OrderStatus;
use Phobos\Enums\Unit;
use Phobos\Models\CompanyClient;
use Phobos\Models\IndividualClient;
use Phobos\Models\Material;
use Phobos\Models\Order;
use Phobos\Models\Product;
use Phobos\Pricing\PricingStrategyFactory;

require dirname(__DIR__) . '/src/autoload.php';

$failures = 0;
$total = 0;

function check(string $name, bool $ok): void
{
    global $failures, $total;
    $total++;
    if (!$ok) {
        $failures++;
    }
    echo ($ok ? '  ok   ' : '  FAIL ') . $name . "\n";
}

function throwsDomain(callable $fn): bool
{
    try {
        $fn();
    } catch (DomainException) {
        return true;
    }

    return false;
}

echo "Precificação (polimorfismo / Strategy)\n";
check('markup 25% sobre 100 = 125', PricingStrategyFactory::for(MarginType::Percent)->salePrice(100, 25) === 125.0);
check('margem 20% sobre venda: 80 -> 100', PricingStrategyFactory::for(MarginType::Margin)->salePrice(80, 20) === 100.0);
check('valor fixo 15 sobre 100 = 115', PricingStrategyFactory::for(MarginType::Fixed)->salePrice(100, 15) === 115.0);
check('margem >= 100% é inválida', PricingStrategyFactory::for(MarginType::Margin)->validate(100) !== null);

echo "Material (encapsulamento / invariantes)\n";
$m = new Material('Linha', Unit::G, 20.0, 200);
check('custo unitário = preço / quantidade', abs($m->unitCost() - 0.1) < 1e-9);
check('quantidade zero é rejeitada', throwsDomain(fn () => new Material('X', Unit::Un, 10, 0)));
check('preço negativo é rejeitado', throwsDomain(fn () => new Material('X', Unit::Un, -1, 1)));
check('nome vazio é rejeitado', throwsDomain(fn () => new Material('  ', Unit::Un, 1, 1)));

echo "Produto (agregação + estratégia)\n";
$p = new Product('Urso', 30.0, 60, MarginType::Percent, 50);
$p->addMaterial($m, 100); // 100 g × 0,10 = 10,00
$p->recalculate();
check('custo de insumos = 10,00', $p->materialCost() === 10.0);
check('mão de obra = 30,00', $p->laborCost() === 30.0);
check('preço = 40 × 1,5 = 60,00', $p->salePrice() === 60.0);
$m->setPackage(40.0, 200); // custo sobe para 0,20
check('snapshot detecta custo desatualizado', $p->hasOutdatedLines());
$p->refreshCosts();
check('recalcular atualiza custo de insumos = 20,00', $p->materialCost() === 20.0);

echo "Clientes (herança + polimorfismo)\n";
$pf = new IndividualClient('Maria', '529.982.247-25');
$pj = (new CompanyClient('Empresa LTDA', '11.222.333/0001-81'))->setTradeName('Fantasia');
check('PF usa CPF', $pf->documentLabel() === 'CPF' && $pf->document() === '52998224725');
check('PJ usa CNPJ e nome fantasia', $pj->documentLabel() === 'CNPJ' && $pj->displayName() === 'Fantasia');
check('CPF inválido é rejeitado', throwsDomain(fn () => new IndividualClient('X', '111.111.111-11')));
check('CNPJ inválido é rejeitado', throwsDomain(fn () => new CompanyClient('X', '11.222.333/0001-00')));

echo "Ordem (composição + máquina de estados)\n";
$prod = Product::fromRow([
    'id' => 1, 'name' => 'Urso', 'variant' => null, 'description' => null, 'image_url' => null,
    'hourly_rate' => 0, 'labor_minutes' => 0, 'margin_type' => 'fixed', 'margin_value' => 0,
    'material_cost' => 40, 'labor_cost' => 0, 'sale_price' => 100,
]);
$o = new Order($pf);
$o->addItem($prod, 2);
$o->setAdjustments(20, 10);
check('subtotal = 200', $o->subtotal() === 200.0);
check('total = 200 − 20 + 10 = 190', $o->total() === 190.0);
check('lucro = 190 − 80 = 110', $o->profit() === 110.0);
check('desconto maior que subtotal é rejeitado', throwsDomain(fn () => $o->setAdjustments(500, 0)));
check('produto repetido é rejeitado', throwsDomain(fn () => $o->addItem($prod, 1)));
check('rascunho não pula para concluída', throwsDomain(fn () => $o->changeStatus(OrderStatus::Completed)));
$o->changeStatus(OrderStatus::Confirmed);
check('rascunho -> confirmada é permitido', $o->status() === OrderStatus::Confirmed);
check('ordem confirmada não é mais editável', throwsDomain(fn () => $o->setAdjustments(0, 0)));
check('ordem vazia não pode ser confirmada', throwsDomain(fn () => (new Order($pf))->changeStatus(OrderStatus::Confirmed)));

echo "\n$total verificações, $failures falha(s).\n";
exit($failures === 0 ? 0 : 1);
