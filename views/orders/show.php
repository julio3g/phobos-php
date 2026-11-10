<?php
/** @var \Phobos\Models\Order $order */

use Phobos\Enums\PaymentStatus;
use Phobos\Support\Format;
?>
<div class="row between">
  <h1>Ordem <?= e($order->label()) ?> <span class="badge badge-<?= e($order->status()->value) ?>"><?= e($order->status()->label()) ?></span></h1>
  <?php if ($isAdmin): ?>
  <div class="row">
    <?php if ($order->isEditable()): ?><a class="btn btn-ghost" href="/orders/<?= $order->id() ?>/edit">Editar</a><?php endif; ?>
    <?php if (in_array($order->status()->value, ['draft', 'cancelled'], true)): ?>
      <form method="post" action="/orders/<?= $order->id() ?>/delete" data-confirm="Excluir esta ordem?">
        <?= csrf_field() ?><button class="btn btn-danger">Excluir</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<dl class="card details">
  <div><dt>Cliente</dt><dd><a href="/clients/<?= $order->client()->id() ?>"><?= e($order->client()->displayName()) ?></a></dd></div>
  <div><dt>Criada em</dt><dd><?= e(Format::date($order->createdAt())) ?></dd></div>
  <div><dt>Pagamento</dt><dd><?= e($order->paymentStatus()->label()) ?> — <?= money($order->paidAmount()) ?> pago</dd></div>
  <div><dt>Observações</dt><dd><?= e($order->notes() ?? '—') ?></dd></div>
</dl>

<h2>Itens</h2>
<div class="table-wrap"><table>
  <thead><tr><th>Produto</th><th class="num">Qtd.</th><th class="num">Preço unit.</th><th class="num">Total</th></tr></thead>
  <tbody>
  <?php foreach ($order->items() as $i): ?>
    <tr>
      <td><?= e($i->productName()) ?></td>
      <td class="num"><?= e(Format::number($i->quantity())) ?></td>
      <td class="num"><?= money($i->unitPrice()) ?></td>
      <td class="num"><?= money($i->total()) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<dl class="card summary">
  <div><dt>Subtotal</dt><dd><?= money($order->subtotal()) ?></dd></div>
  <div><dt>Desconto</dt><dd>− <?= money($order->discount()) ?></dd></div>
  <div><dt>Frete</dt><dd><?= money($order->shipping()) ?></dd></div>
  <div class="total"><dt>Total</dt><dd><?= money($order->total()) ?></dd></div>
  <div><dt>Custo</dt><dd><?= money($order->costTotal()) ?></dd></div>
  <div><dt>Lucro</dt><dd><?= money($order->profit()) ?></dd></div>
</dl>

<?php if ($isAdmin): ?>
<div class="grid two">
  <?php if ($order->status()->next() !== []): ?>
  <section class="card stack">
    <h3>Avançar status</h3>
    <div class="row">
      <?php foreach ($order->status()->next() as $next): ?>
        <form method="post" action="/orders/<?= $order->id() ?>/status">
          <?= csrf_field() ?><input type="hidden" name="status" value="<?= $next->value ?>">
          <button class="btn <?= $next->value === 'cancelled' ? 'btn-danger' : '' ?>"><?= e($next->label()) ?></button>
        </form>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="card stack">
    <h3>Pagamento</h3>
    <form method="post" action="/orders/<?= $order->id() ?>/payment" class="stack">
      <?= csrf_field() ?>
      <label>Situação
        <select name="payment_status">
          <?php foreach (PaymentStatus::cases() as $p): ?>
            <option value="<?= $p->value ?>" <?= $order->paymentStatus() === $p ? 'selected' : '' ?>><?= e($p->label()) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Valor pago (R$)
        <input name="paid_amount" inputmode="decimal" value="<?= e((string) $order->paidAmount()) ?>">
      </label>
      <button class="btn btn-ghost">Atualizar pagamento</button>
    </form>
  </section>
</div>
<?php endif; ?>
<a href="/orders">← Ordens</a>
