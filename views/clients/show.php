<?php
/** @var \Phobos\Models\Client $client */

use Phobos\Support\Format;

$addr = implode(', ', array_filter([$client->street(), $client->city(), $client->state(), $client->zipCode()]));
?>
<div class="row between">
  <h1><?= e($client->displayName()) ?></h1>
  <?php if ($isAdmin): ?>
  <div class="row">
    <a class="btn btn-ghost" href="/clients/<?= $client->id() ?>/edit">Editar</a>
    <form method="post" action="/clients/<?= $client->id() ?>/delete" data-confirm="Excluir este cliente?">
      <?= csrf_field() ?><button class="btn btn-danger">Excluir</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<dl class="card details">
  <div><dt>Tipo</dt><dd><?= e($client->type()->label()) ?></dd></div>
  <div><dt><?= e($client->documentLabel()) ?></dt><dd><?= e(Format::document($client->document())) ?></dd></div>
  <div><dt>Razão social / Nome</dt><dd><?= e($client->name()) ?></dd></div>
  <?php if ($client->tradeName()): ?><div><dt>Nome fantasia</dt><dd><?= e($client->tradeName()) ?></dd></div><?php endif; ?>
  <div><dt>E-mail</dt><dd><?= e($client->email() ?? '—') ?></dd></div>
  <div><dt>Telefone</dt><dd><?= e($client->phone() ?? '—') ?></dd></div>
  <div class="wide"><dt>Endereço</dt><dd><?= e($addr ?: '—') ?></dd></div>
  <div><dt>Cadastrado em</dt><dd><?= e(Format::date($client->createdAt())) ?></dd></div>
</dl>
<a href="/clients">← Clientes</a>
