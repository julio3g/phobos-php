<?php
/** @var \Phobos\Models\Material|null $material */
/** @var array<string,mixed> $form */
/** @var array<string,string> $errors */

use Phobos\Enums\Unit;

$action = $material ? '/materials/' . $material->id() : '/materials';
$err = static fn (string $k): string => isset($errors[$k]) ? '<small class="error">' . e($errors[$k]) . '</small>' : '';
?>
<h1><?= $material ? 'Editar material' : 'Novo material' ?></h1>

<?php if (isset($errors['_form'])): ?><div class="alert alert-error"><?= e($errors['_form']) ?></div><?php endif; ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="card stack">
  <?= csrf_field() ?>
  <div class="grid two">
    <label>Nome
      <input name="name" value="<?= old($form, 'name') ?>" maxlength="150" required>
      <?= $err('name') ?>
    </label>
    <label>Marca
      <input name="brand" value="<?= old($form, 'brand') ?>" maxlength="100">
    </label>
    <label>Variação (cor, tamanho)
      <input name="variant" value="<?= old($form, 'variant') ?>" maxlength="100">
    </label>
    <label>Unidade
      <select name="unit">
        <?php foreach (Unit::cases() as $u): ?>
          <option value="<?= $u->value ?>" <?= ($form['unit'] ?? 'un') === $u->value ? 'selected' : '' ?>><?= e($u->label()) ?> (<?= $u->value ?>)</option>
        <?php endforeach; ?>
      </select>
      <?= $err('unit') ?>
    </label>
    <label>Preço da embalagem (R$)
      <input name="package_price" inputmode="decimal" value="<?= old($form, 'package_price') ?>" required>
      <?= $err('package_price') ?>
    </label>
    <label>Quantidade na embalagem
      <input name="package_quantity" inputmode="decimal" value="<?= old($form, 'package_quantity') ?>" required>
      <?= $err('package_quantity') ?>
    </label>
    <label class="wide">Link de compra
      <input type="url" name="purchase_url" value="<?= old($form, 'purchase_url') ?>">
      <?= $err('purchase_url') ?>
    </label>
    <div class="wide">
      <label>Imagem (JPG, PNG, WebP ou GIF até 5MB)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      </label>
      <?php if ($material?->imageUrl()): ?>
        <div class="row"><img class="thumb-lg" src="<?= e($material->imageUrl()) ?>" alt="Imagem atual">
        <label class="inline"><input type="checkbox" name="remove_image" value="1"> Remover imagem</label></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="row">
    <button class="btn">Salvar</button>
    <a class="btn btn-ghost" href="/materials">Cancelar</a>
  </div>
</form>
