<?php
/** @var string $content */
/** @var array<int,array{type:string,message:string}> $flash */
/** @var \Phobos\Models\User|null $user */
$uri = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$nav = ['/' => 'Início', '/orders' => 'Ordens', '/products' => 'Produtos', '/materials' => 'Materiais', '/clients' => 'Clientes'];
if ($user && $user->isAdmin()) {
    $nav['/settings/users'] = 'Usuários';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Phobos</title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<?php if ($user): ?>
  <header class="topbar">
    <a class="brand" href="/">Phobos</a>
    <nav>
      <?php foreach ($nav as $href => $label): ?>
        <a href="<?= $href ?>" class="<?= ($href === '/' ? $uri === '/' : str_starts_with($uri, $href)) ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <form method="post" action="/logout" class="logout">
      <?= csrf_field() ?>
      <a href="/profile" class="muted" title="Meu perfil"><?= e($user->name()) ?></a>
      <button class="btn btn-ghost btn-sm">Sair</button>
    </form>
  </header>
<?php endif; ?>
<main class="container">
  <?php foreach ($flash as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
<script src="/assets/app.js"></script>
</body>
</html>
