<?php
/** @var list<\Phobos\Models\Client> $clients */
/** @var string $q */

use Phobos\Support\Format;
?>
<div class="row between">
  <h1>Clientes</h1>
  <?php if ($isAdmin): ?><a class="btn" href="/clients/create">Novo cliente</a><?php endif; ?>
</div>

<form method="get" class="row search">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome ou e-mail">
  <button class="btn btn-ghost">Buscar</button>
</form>

<?php if ($clients === []): ?>
  <p class="muted">Nenhum cliente encontrado.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Nome</th><th>Tipo</th><th>Documento</th><th>Contato</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($clients as $c): ?>
    <tr>
      <td><a href="/clients/<?= $c->id() ?>"><?= e($c->displayName()) ?></a></td>
      <td><?= e($c->type()->label()) ?></td>
      <td><?= e($c->documentLabel()) ?> <?= e(Format::document($c->document())) ?></td>
      <td><?= e($c->email() ?? $c->phone()) ?></td>
      <td class="actions"><?php if ($isAdmin): ?><a class="btn btn-ghost btn-sm" href="/clients/<?= $c->id() ?>/edit">Editar</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
