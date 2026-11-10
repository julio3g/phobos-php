<?php
/** @var list<\Phobos\Models\Material> $materials */
/** @var string $q */
/** @var bool $archived */
?>
<div class="row between">
  <h1>Materiais</h1>
  <?php if ($isAdmin): ?><a class="btn" href="/materials/create">Novo material</a><?php endif; ?>
</div>

<form method="get" class="row search">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome ou marca">
  <label class="inline"><input type="checkbox" name="archived" value="1" <?= $archived ? 'checked' : '' ?>> Incluir arquivados</label>
  <button class="btn btn-ghost">Filtrar</button>
</form>

<?php if ($materials === []): ?>
  <p class="muted">Nenhum material encontrado.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th></th><th>Material</th><th>Embalagem</th><th class="num">Custo unitário</th><th>Situação</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($materials as $m): ?>
    <tr>
      <td class="thumb-cell"><?php if ($m->imageUrl()): ?><img class="thumb" src="<?= e($m->imageUrl()) ?>" alt=""><?php endif; ?></td>
      <td><a href="/materials/<?= $m->id() ?>"><?= e($m->label()) ?></a></td>
      <td><?= money($m->packagePrice()) ?> / <?= e(\Phobos\Support\Format::number($m->packageQuantity())) ?> <?= e($m->unit()->value) ?></td>
      <td class="num"><?= e('R$ ' . number_format($m->unitCost(), 4, ',', '.')) ?> / <?= e($m->unit()->value) ?></td>
      <td><span class="badge <?= $m->isArchived() ? 'badge-cancelled' : 'badge-completed' ?>"><?= $m->isArchived() ? 'Arquivado' : 'Ativo' ?></span></td>
      <td class="actions"><?php if ($isAdmin): ?><a class="btn btn-ghost btn-sm" href="/materials/<?= $m->id() ?>/edit">Editar</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
