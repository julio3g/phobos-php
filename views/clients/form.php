<?php
/** @var \Phobos\Models\Client|null $client */
/** @var array<string,mixed> $form */
/** @var array<string,string> $errors */

$action = $client ? '/clients/' . $client->id() : '/clients';
$isCompany = ($form['person_type'] ?? 'individual') === 'company';
$err = static fn (string $k): string => isset($errors[$k]) ? '<small class="error">' . e($errors[$k]) . '</small>' : '';
?>
<h1><?= $client ? 'Editar cliente' : 'Novo cliente' ?></h1>

<?php if (isset($errors['_form'])): ?><div class="alert alert-error"><?= e($errors['_form']) ?></div><?php endif; ?>

<form method="post" action="<?= $action ?>" class="card stack" data-person-form>
  <?= csrf_field() ?>
  <div class="grid two">
    <label>Tipo
      <select name="person_type" data-person-type>
        <option value="individual" <?= !$isCompany ? 'selected' : '' ?>>Pessoa física</option>
        <option value="company" <?= $isCompany ? 'selected' : '' ?>>Pessoa jurídica</option>
      </select>
    </label>
    <label><span data-doc-label><?= $isCompany ? 'CNPJ' : 'CPF' ?></span>
      <input name="document" value="<?= old($form, 'document') ?>" required>
      <?= $err('document') ?>
    </label>
    <label>Nome / Razão social
      <input name="name" value="<?= old($form, 'name') ?>" maxlength="150" required>
      <?= $err('name') ?>
    </label>
    <label data-company-only>Nome fantasia
      <input name="trade_name" value="<?= old($form, 'trade_name') ?>" maxlength="150">
    </label>
    <label>E-mail
      <input type="email" name="email" value="<?= old($form, 'email') ?>">
      <?= $err('email') ?>
    </label>
    <label>Telefone
      <input name="phone" value="<?= old($form, 'phone') ?>" maxlength="30">
    </label>
    <label>Endereço
      <input name="street" value="<?= old($form, 'street') ?>">
    </label>
    <label>Cidade
      <input name="city" value="<?= old($form, 'city') ?>">
    </label>
    <label>UF
      <input name="state" value="<?= old($form, 'state') ?>" maxlength="2">
    </label>
    <label>CEP
      <input name="zip_code" value="<?= old($form, 'zip_code') ?>" maxlength="12">
    </label>
  </div>
  <div class="row">
    <button class="btn">Salvar</button>
    <a class="btn btn-ghost" href="/clients">Cancelar</a>
  </div>
</form>
