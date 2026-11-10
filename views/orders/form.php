<?php
/** @var \Phobos\Models\Order|null $order */
/** @var array<string,mixed> $form */
/** @var array<string,string> $errors */
/** @var list<\Phobos\Models\Client> $clients */
/** @var list<\Phobos\Models\Product> $products */

$action = $order ? '/orders/' . $order->id() : '/orders';
$err = static fn (string $k): string => isset($errors[$k]) ? '<small class="error">' . e($errors[$k]) . '</small>' : '';
$lines = $form['lines'] ?? [];
$selectedClient = (string) ($form['client_id'] ?? '');
$option = static fn ($p, $sel = false) => '<option value="' . $p->id() . '"' . ($sel ? ' selected' : '') . '>'
    . e($p->label()) . ' — ' . e(money($p->salePrice())) . '</option>';
?>
<h1><?= $order ? 'Editar ordem ' . e($order->label()) : 'Nova ordem' ?></h1>

<?php if (isset($errors['_form'])): ?><div class="alert alert-error"><?= e($errors['_form']) ?></div><?php endif; ?>
<?php if ($order): ?><p class="muted">Os preços dos itens são atualizados com o cadastro atual dos produtos enquanto a ordem está em rascunho.</p><?php endif; ?>

<form method="post" action="<?= $action ?>" class="card stack">
  <?= csrf_field() ?>
  <div class="grid two">
    <label>Cliente
      <select name="client_id" required>
        <option value="">Selecione…</option>
        <?php foreach ($clients as $c): ?>
          <option value="<?= $c->id() ?>" <?= $selectedClient === (string) $c->id() ? 'selected' : '' ?>><?= e($c->displayName()) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $err('client_id') ?>
    </label>
    <label>Observações
      <input name="notes" maxlength="150" value="<?= old($form, 'notes') ?>">
    </label>
    <label>Desconto (R$)
      <input name="discount" inputmode="decimal" value="<?= old($form, 'discount', '0') ?>">
      <?= $err('discount') ?>
    </label>
    <label>Frete (R$)
      <input name="shipping" inputmode="decimal" value="<?= old($form, 'shipping', '0') ?>">
      <?= $err('shipping') ?>
    </label>
  </div>

  <fieldset data-lines>
    <legend>Itens</legend>
    <div data-lines-body>
      <?php foreach ($lines as $line): ?>
        <div class="row line">
          <select name="product_id[]">
            <?php foreach ($products as $p): ?><?= $option($p, $p->id() === $line['id']) ?><?php endforeach; ?>
          </select>
          <input name="product_qty[]" inputmode="decimal" placeholder="Qtd." value="<?= e((string) $line['qty']) ?>">
          <button type="button" class="btn btn-ghost btn-sm" data-remove-line>Remover</button>
        </div>
      <?php endforeach; ?>
    </div>
    <template data-line-template>
      <div class="row line">
        <select name="product_id[]">
          <?php foreach ($products as $p): ?><?= $option($p) ?><?php endforeach; ?>
        </select>
        <input name="product_qty[]" inputmode="decimal" placeholder="Qtd." value="1">
        <button type="button" class="btn btn-ghost btn-sm" data-remove-line>Remover</button>
      </div>
    </template>
    <button type="button" class="btn btn-ghost btn-sm" data-add-line>+ Adicionar item</button>
  </fieldset>

  <div class="row">
    <button class="btn">Salvar</button>
    <a class="btn btn-ghost" href="<?= $order ? '/orders/' . $order->id() : '/orders' ?>">Cancelar</a>
  </div>
</form>
