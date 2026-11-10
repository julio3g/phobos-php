<?php
/** @var list<\Phobos\Models\User> $users */
/** @var array<string,mixed> $form */
/** @var array<string,string> $errors */
/** @var int|null $me */
$err = static fn (string $k): string => isset($errors[$k]) ? '<small class="error">' . e($errors[$k]) . '</small>' : '';
?>
<h1>Usuários e permissões</h1>
<p class="muted">Administradores criam e editam dados. Membros só consultam. Contas criadas pelo cadastro público entram como membro.</p>

<div class="table-wrap"><table>
  <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u->name()) ?><?= $u->id() === $me ? ' <span class="muted">(você)</span>' : '' ?></td>
      <td><?= e($u->email()) ?></td>
      <td>
        <?php if ($u->id() === $me): ?>
          <span class="badge badge-confirmed">Administrador</span>
        <?php else: ?>
          <form method="post" action="/settings/users/<?= $u->id() ?>/role" class="row">
            <?= csrf_field() ?>
            <select name="role" onchange="this.form.submit()">
              <option value="admin" <?= $u->isAdmin() ? 'selected' : '' ?>>Administrador</option>
              <option value="member" <?= !$u->isAdmin() ? 'selected' : '' ?>>Membro</option>
            </select>
            <noscript><button class="btn btn-ghost btn-sm">Salvar</button></noscript>
          </form>
        <?php endif; ?>
      </td>
      <td class="actions">
        <?php if ($u->id() !== $me): ?>
          <form method="post" action="/settings/users/<?= $u->id() ?>/delete" onsubmit="return confirm('Excluir este usuário?')">
            <?= csrf_field() ?>
            <button class="btn btn-danger btn-sm">Excluir</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<form method="post" action="/settings/users" class="card stack" style="margin-top:1.5rem">
  <?= csrf_field() ?>
  <h2>Novo usuário</h2>
  <?php if (isset($errors['_form'])): ?><div class="alert alert-error"><?= e($errors['_form']) ?></div><?php endif; ?>
  <div class="grid two">
    <label>Nome
      <input name="name" value="<?= old($form, 'name') ?>" maxlength="120" required>
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
    <label>Perfil
      <select name="role">
        <option value="member" <?= ($form['role'] ?? 'member') === 'member' ? 'selected' : '' ?>>Membro (somente consulta)</option>
        <option value="admin" <?= ($form['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
      </select>
      <?= $err('role') ?>
    </label>
  </div>
  <div><button class="btn">Criar usuário</button></div>
</form>
