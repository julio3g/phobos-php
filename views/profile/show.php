<?php
/** @var \Phobos\Models\User $profile */
/** @var array<string,mixed> $form */
/** @var array<string,string> $errors */
$err = static fn (string $k): string => isset($errors[$k]) ? '<small class="error">' . e($errors[$k]) . '</small>' : '';
?>
<div class="row between">
  <h1>Meu perfil</h1>
  <span class="badge <?= $profile->isAdmin() ? 'badge-confirmed' : 'badge-draft' ?>"><?= $profile->isAdmin() ? 'Administrador' : 'Membro (somente consulta)' ?></span>
</div>

<div class="grid two">
  <form method="post" action="/profile" class="card stack">
    <?= csrf_field() ?>
    <h2>Dados pessoais</h2>
    <?php if (isset($errors['_profile'])): ?><div class="alert alert-error"><?= e($errors['_profile']) ?></div><?php endif; ?>
    <label>Nome
      <input name="name" value="<?= old($form, 'name') ?>" maxlength="120" required>
      <?= $err('name') ?>
    </label>
    <label>E-mail
      <input type="email" name="email" value="<?= old($form, 'email') ?>" maxlength="160" required>
      <?= $err('email') ?>
    </label>
    <button class="btn">Salvar</button>
  </form>

  <form method="post" action="/profile/password" class="card stack">
    <?= csrf_field() ?>
    <h2>Alterar senha</h2>
    <label>Senha atual
      <input type="password" name="current_password" required autocomplete="current-password">
      <?= $err('current_password') ?>
    </label>
    <label>Nova senha (mínimo 8 caracteres)
      <input type="password" name="new_password" minlength="8" required autocomplete="new-password">
      <?= $err('new_password') ?>
    </label>
    <label>Confirmar nova senha
      <input type="password" name="new_password_confirm" minlength="8" required autocomplete="new-password">
      <?= $err('new_password_confirm') ?>
    </label>
    <button class="btn">Alterar senha</button>
  </form>
</div>
