<?php
/** @var array{revenue:float,profit:float,open:int,total:int} $stats */
/** @var list<array<string,mixed>> $recent */
/** @var array{clients:int,materials:int,products:int} $counts */

use Phobos\Enums\OrderStatus;
?>
<h1>Início</h1>

<section class="grid stats">
  <div class="card"><span class="muted">Faturamento (ordens confirmadas)</span><strong><?= money($stats['revenue']) ?></strong></div>
  <div class="card"><span class="muted">Lucro estimado</span><strong><?= money($stats['profit']) ?></strong></div>
  <div class="card"><span class="muted">Ordens em aberto</span><strong><?= $stats['open'] ?></strong></div>
  <div class="card"><span class="muted">Clientes · Materiais · Produtos</span><strong><?= $counts['clients'] ?> · <?= $counts['materials'] ?> · <?= $counts['products'] ?></strong></div>
</section>

<section>
  <div class="row between"><h2>Últimas ordens</h2><?php if ($isAdmin): ?><a class="btn btn-sm" href="/orders/create">Nova ordem</a><?php endif; ?></div>
  <?php if ($recent === []): ?>
    <p class="muted">Nenhuma ordem ainda.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Nº</th><th>Cliente</th><th>Status</th><th class="num">Total</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $o): ?>
      <tr>
        <td><a href="/orders/<?= (int) $o['id'] ?>">#<?= str_pad((string) $o['number'], 4, '0', STR_PAD_LEFT) ?></a></td>
        <td><?= e($o['client_name']) ?></td>
        <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(OrderStatus::from($o['status'])->label()) ?></span></td>
        <td class="num"><?= money((float) $o['total']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
