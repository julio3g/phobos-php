<?php
/** @var array<string,mixed> $form */
/** @var array<string,string> $errors */
$err = static fn (string $k): string => isset($errors[$k]) ? '<small class="error">' . e($errors[$k]) . '</small>' : '';
?>
<div class="card login">
  <h1>Criar conta</h1>
  <?php if (isset($errors['_form'])): ?><div class="alert alert-error"><?= e($errors['_form']) ?></div><?php endif; ?>
  <form method="post" action="/register" class="stack">
    <?= csrf_field() ?>
    <label>Nome
      <input name="name" value="<?= old($form, 'name') ?>" maxlength="120" required autofocus>
      <?= $err('name') ?>
    </label>
    <label>E-mail
      <input type="email" name="email" value="<?= old($form, 'email') ?>" maxlength="160" required>
      <?= $err('email') ?>
    </label>
    <label>Senha (mínimo 8 caracteres)
      <input type="password" name="password" minlength="8" required autocomplete="new-password">
      <?= $err('password') ?>
    </label>
    <label>Confirmar senha
      <input type="password" name="password_confirm" minlength="8" required autocomplete="new-password">
      <?= $err('password_confirm') ?>
    </label>
    <button class="btn">Criar conta</button>
  </form>
  <p class="muted">Já tem conta? <a href="/login">Entrar</a></p>
</div>
