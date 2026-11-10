<?php
/** @var \Phobos\Models\Material $material */

use Phobos\Support\Format;
?>
<div class="row between">
  <h1><?= e($material->label()) ?></h1>
  <?php if ($isAdmin): ?>
  <div class="row">
    <a class="btn btn-ghost" href="/materials/<?= $material->id() ?>/edit">Editar</a>
    <form method="post" action="/materials/<?= $material->id() ?>/archive">
      <?= csrf_field() ?><button class="btn btn-ghost"><?= $material->isArchived() ? 'Restaurar' : 'Arquivar' ?></button>
    </form>
    <form method="post" action="/materials/<?= $material->id() ?>/delete" data-confirm="Excluir este material?">
      <?= csrf_field() ?><button class="btn btn-danger">Excluir</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php if ($material->imageUrl()): ?>
  <img class="hero-image" src="<?= e($material->imageUrl()) ?>" alt="<?= e($material->name()) ?>">
<?php endif; ?>

<dl class="card details">
  <div><dt>Marca</dt><dd><?= e($material->brand() ?? '—') ?></dd></div>
  <div><dt>Variação</dt><dd><?= e($material->variant() ?? '—') ?></dd></div>
  <div><dt>Embalagem</dt><dd><?= money($material->packagePrice()) ?> / <?= e(Format::number($material->packageQuantity())) ?> <?= e($material->unit()->value) ?></dd></div>
  <div><dt>Custo unitário</dt><dd>R$ <?= number_format($material->unitCost(), 4, ',', '.') ?> / <?= e($material->unit()->value) ?></dd></div>
  <div><dt>Situação</dt><dd><?= $material->isArchived() ? 'Arquivado' : 'Ativo' ?></dd></div>
  <div class="wide"><dt>Link de compra</dt><dd>
    <?php if ($material->purchaseUrl()): ?>
      <a href="<?= e($material->purchaseUrl()) ?>" target="_blank" rel="noopener noreferrer"><?= e($material->purchaseUrl()) ?></a>
    <?php else: ?>—<?php endif; ?>
  </dd></div>
</dl>
<a href="/materials">← Materiais</a>
