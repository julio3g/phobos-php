<?php
/** @var list<\Phobos\Models\Product> $products */
/** @var string $q */
/** @var bool $archived */
?>
<div class="row between">
  <h1>Produtos</h1>
  <a class="btn" href="/products/create">Novo produto</a>
</div>

<form method="get" class="row search">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome">
  <label class="inline"><input type="checkbox" name="archived" value="1" <?= $archived ? 'checked' : '' ?>> Incluir arquivados</label>
  <button class="btn btn-ghost">Filtrar</button>
</form>

<?php if ($products === []): ?>
  <p class="muted">Nenhum produto encontrado.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th></th><th>Produto</th><th class="num">Custo</th><th class="num">Preço de venda</th><th>Situação</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td class="thumb-cell"><?php if ($p->imageUrl()): ?><img class="thumb" src="<?= e($p->imageUrl()) ?>" alt=""><?php endif; ?></td>
      <td><a href="/products/<?= $p->id() ?>"><?= e($p->label()) ?></a></td>
      <td class="num"><?= money($p->totalCost()) ?></td>
      <td class="num"><?= money($p->salePrice()) ?></td>
      <td><span class="badge <?= $p->isArchived() ? 'badge-cancelled' : 'badge-completed' ?>"><?= $p->isArchived() ? 'Arquivado' : 'Ativo' ?></span></td>
      <td class="actions"><a class="btn btn-ghost btn-sm" href="/products/<?= $p->id() ?>/edit">Editar</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
