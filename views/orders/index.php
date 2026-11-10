<?php
/** @var list<array<string,mixed>> $orders */
/** @var string $status */

use Phobos\Enums\OrderStatus;
use Phobos\Enums\PaymentStatus;
?>
<div class="row between">
  <h1>Ordens de serviço</h1>
  <a class="btn" href="/orders/create">Nova ordem</a>
</div>

<form method="get" class="row search">
  <select name="status">
    <option value="">Todos os status</option>
    <?php foreach (OrderStatus::cases() as $s): ?>
      <option value="<?= $s->value ?>" <?= $status === $s->value ? 'selected' : '' ?>><?= e($s->label()) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-ghost">Filtrar</button>
</form>

<?php if ($orders === []): ?>
  <p class="muted">Nenhuma ordem encontrada.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Nº</th><th>Cliente</th><th>Status</th><th>Pagamento</th><th class="num">Total</th><th>Criada em</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><a href="/orders/<?= (int) $o['id'] ?>">#<?= str_pad((string) $o['number'], 4, '0', STR_PAD_LEFT) ?></a></td>
      <td><?= e($o['client_name']) ?></td>
      <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(OrderStatus::from($o['status'])->label()) ?></span></td>
      <td><?= e(PaymentStatus::from($o['payment_status'])->label()) ?></td>
      <td class="num"><?= money((float) $o['total']) ?></td>
      <td><?= e((new DateTimeImmutable($o['created_at']))->format('d/m/Y')) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
