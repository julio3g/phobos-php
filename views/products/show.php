<?php
/** @var \Phobos\Models\Product $product */

use Phobos\Pricing\PricingStrategyFactory;
use Phobos\Support\Format;

$strategy = PricingStrategyFactory::for($product->marginType());
$marginText = $product->marginType()->value === 'fixed' ? money($product->marginValue()) : Format::number($product->marginValue(), 2) . '%';
?>
<div class="row between">
  <h1><?= e($product->label()) ?></h1>
  <div class="row">
    <form method="post" action="/products/<?= $product->id() ?>/recalculate">
      <?= csrf_field() ?><button class="btn btn-ghost"><?= $product->hasOutdatedLines() ? 'Recalcular (há insumos com custo novo)' : 'Recalcular' ?></button>
    </form>
    <a class="btn btn-ghost" href="/products/<?= $product->id() ?>/edit">Editar</a>
    <form method="post" action="/products/<?= $product->id() ?>/archive">
      <?= csrf_field() ?><button class="btn btn-ghost"><?= $product->isArchived() ? 'Restaurar' : 'Arquivar' ?></button>
    </form>
    <form method="post" action="/products/<?= $product->id() ?>/delete" data-confirm="Excluir este produto?">
      <?= csrf_field() ?><button class="btn btn-danger">Excluir</button>
    </form>
  </div>
</div>

<?php if ($product->imageUrl()): ?>
  <img class="hero-image" src="<?= e($product->imageUrl()) ?>" alt="<?= e($product->name()) ?>">
<?php endif; ?>
<?php if ($product->description()): ?><p><?= nl2br(e($product->description())) ?></p><?php endif; ?>

<h2>Composição</h2>
<div class="table-wrap"><table>
  <thead><tr><th>Insumo</th><th class="num">Quantidade</th><th class="num">Custo unit. (salvo)</th><th class="num">Total</th><th class="num">Custo atual</th></tr></thead>
  <tbody>
  <?php if ($product->lines() === []): ?>
    <tr><td colspan="5" class="muted">Sem insumos.</td></tr>
  <?php endif; ?>
  <?php foreach ($product->lines() as $l): $m = $l->material(); ?>
    <tr>
      <td><a href="/materials/<?= $m->id() ?>"><?= e($m->label()) ?></a></td>
      <td class="num"><?= e(Format::number($l->quantity())) ?> <?= e($m->unit()->value) ?></td>
      <td class="num">R$ <?= number_format($l->unitCostSnapshot(), 4, ',', '.') ?></td>
      <td class="num"><?= money($l->totalCost()) ?></td>
      <td class="num">R$ <?= number_format($m->unitCost(), 4, ',', '.') ?>
        <?php if ($l->isOutdated()): ?><span class="badge badge-confirmed">mudou</span><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<dl class="card summary">
  <div><dt>Insumos</dt><dd><?= money($product->materialCost()) ?></dd></div>
  <div><dt>Mão de obra (<?= $product->laborMinutes() ?> min a <?= money($product->hourlyRate()) ?>/h)</dt><dd><?= money($product->laborCost()) ?></dd></div>
  <div><dt>Custo total</dt><dd><?= money($product->totalCost()) ?></dd></div>
  <div><dt><?= e($strategy->label()) ?></dt><dd><?= e($marginText) ?></dd></div>
  <div class="total"><dt>Preço de venda</dt><dd><?= money($product->salePrice()) ?></dd></div>
</dl>
<a href="/products">← Produtos</a>
