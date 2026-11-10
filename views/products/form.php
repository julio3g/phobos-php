<?php
/** @var \Phobos\Models\Product|null $product */
/** @var array<string,mixed> $form */
/** @var array<string,string> $errors */
/** @var list<\Phobos\Models\Material> $materialOptions */

use Phobos\Enums\MarginType;
use Phobos\Pricing\PricingStrategyFactory;

$action = $product ? '/products/' . $product->id() : '/products';
$err = static fn (string $k): string => isset($errors[$k]) ? '<small class="error">' . e($errors[$k]) . '</small>' : '';
$lines = $form['lines'] ?? [];
?>
<h1><?= $product ? 'Editar produto' : 'Novo produto' ?></h1>

<?php if (isset($errors['_form'])): ?><div class="alert alert-error"><?= e($errors['_form']) ?></div><?php endif; ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="card stack">
  <?= csrf_field() ?>
  <div class="grid two">
    <label>Nome
      <input name="name" value="<?= old($form, 'name') ?>" maxlength="150" required>
      <?= $err('name') ?>
    </label>
    <label>Variação
      <input name="variant" value="<?= old($form, 'variant') ?>" maxlength="100">
    </label>
    <label class="wide">Descrição
      <textarea name="description" rows="3" maxlength="2000"><?= old($form, 'description') ?></textarea>
    </label>
    <label>Valor da hora (R$)
      <input name="hourly_rate" inputmode="decimal" value="<?= old($form, 'hourly_rate', '0') ?>">
      <?= $err('hourly_rate') ?>
    </label>
    <label>Tempo de trabalho (minutos)
      <input name="labor_minutes" inputmode="numeric" value="<?= old($form, 'labor_minutes', '0') ?>">
      <?= $err('labor_minutes') ?>
    </label>
    <label>Tipo de margem
      <select name="margin_type">
        <?php foreach (MarginType::cases() as $t): ?>
          <option value="<?= $t->value ?>" <?= ($form['margin_type'] ?? 'percent') === $t->value ? 'selected' : '' ?>><?= e(PricingStrategyFactory::for($t)->label()) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Valor da margem
      <input name="margin_value" inputmode="decimal" value="<?= old($form, 'margin_value') ?>" required>
      <?= $err('margin_value') ?>
    </label>
    <div class="wide">
      <label>Imagem (JPG, PNG, WebP ou GIF até 5MB)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      </label>
      <?php if ($product?->imageUrl()): ?>
        <div class="row"><img class="thumb-lg" src="<?= e($product->imageUrl()) ?>" alt="Imagem atual">
        <label class="inline"><input type="checkbox" name="remove_image" value="1"> Remover imagem</label></div>
      <?php endif; ?>
    </div>
  </div>

  <fieldset data-lines>
    <legend>Composição (insumos)</legend>
    <div data-lines-body>
      <?php foreach ($lines as $line): ?>
        <div class="row line">
          <select name="material_id[]">
            <?php foreach ($materialOptions as $m): ?>
              <option value="<?= $m->id() ?>" <?= $m->id() === $line['id'] ? 'selected' : '' ?>><?= e($m->label()) ?> (<?= e($m->unit()->value) ?>)</option>
            <?php endforeach; ?>
          </select>
          <input name="material_qty[]" inputmode="decimal" placeholder="Qtd." value="<?= e((string) $line['qty']) ?>">
          <button type="button" class="btn btn-ghost btn-sm" data-remove-line>Remover</button>
        </div>
      <?php endforeach; ?>
    </div>
    <template data-line-template>
      <div class="row line">
        <select name="material_id[]">
          <?php foreach ($materialOptions as $m): ?>
            <option value="<?= $m->id() ?>"><?= e($m->label()) ?> (<?= e($m->unit()->value) ?>)</option>
          <?php endforeach; ?>
        </select>
        <input name="material_qty[]" inputmode="decimal" placeholder="Qtd.">
        <button type="button" class="btn btn-ghost btn-sm" data-remove-line>Remover</button>
      </div>
    </template>
    <button type="button" class="btn btn-ghost btn-sm" data-add-line>+ Adicionar insumo</button>
  </fieldset>

  <div class="row">
    <button class="btn">Salvar</button>
    <a class="btn btn-ghost" href="/products">Cancelar</a>
  </div>
</form>
