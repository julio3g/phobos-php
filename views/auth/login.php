<?php /** @var string $email */ ?>
<div class="card login">
  <h1>Entrar no Phobos</h1>
  <form method="post" action="/login" class="stack">
    <?= csrf_field() ?>
    <label>E-mail
      <input type="email" name="email" value="<?= e($email) ?>" required autofocus>
    </label>
    <label>Senha
      <input type="password" name="password" required>
    </label>
    <button class="btn">Entrar</button>
  </form>
</div>
