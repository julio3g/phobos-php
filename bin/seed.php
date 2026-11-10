<?php

declare(strict_types=1);

/**
 * Popula o banco com dados FICTÍCIOS para teste/demonstração.
 * Uso: php bin/seed.php   (idempotente: não duplica o usuário admin nem recria dados existentes)
 */

use Phobos\Core\Database;
use Phobos\Core\Env;
use Phobos\Enums\MarginType;
use Phobos\Enums\Unit;
use Phobos\Models\CompanyClient;
use Phobos\Models\IndividualClient;
use Phobos\Models\Material;
use Phobos\Models\Order;
use Phobos\Models\Product;
use Phobos\Models\User;
use Phobos\Repositories\ClientRepository;
use Phobos\Repositories\MaterialRepository;
use Phobos\Repositories\OrderRepository;
use Phobos\Repositories\ProductRepository;
use Phobos\Repositories\UserRepository;

$root = dirname(__DIR__);
require $root . '/src/autoload.php';
Env::load($root . '/.env');

$pdo = Database::connection();
$users = new UserRepository($pdo);

$email = Env::get('SEED_ADMIN_EMAIL', 'admin@phobos.local');
if ($users->findByEmail($email) === null) {
    $users->save(User::register('Administrador', $email, Env::get('SEED_ADMIN_PASSWORD', 'troque-esta-senha'), 'admin'));
    echo "Usuário admin criado: $email\n";
}

if ((int) $pdo->query('SELECT COUNT(*) FROM materials')->fetchColumn() > 0) {
    echo "Dados de exemplo já existem; nada a fazer.\n";
    exit(0);
}

$materials = new MaterialRepository($pdo);
$linha = $materials->save(new Material('Linha de crochê', Unit::G, 18.90, 200));
$enchimento = $materials->save(new Material('Fibra siliconada', Unit::G, 24.00, 500));
$olhos = $materials->save(new Material('Olho de segurança 8mm', Unit::Un, 9.50, 50));
$caixa = $materials->save(new Material('Caixa kraft', Unit::Un, 42.00, 20));

$products = new ProductRepository($pdo);
$urso = new Product('Urso de crochê', 25.00, 180, MarginType::Percent, 80);
$urso->setDetails('Médio', 'Urso amigurumi feito à mão.');
$urso->addMaterial($linha, 120);
$urso->addMaterial($enchimento, 60);
$urso->addMaterial($olhos, 2);
$urso->recalculate();
$products->save($urso);

$kit = new Product('Kit presente', 25.00, 30, MarginType::Margin, 40);
$kit->addMaterial($caixa, 1);
$kit->recalculate();
$products->save($kit);

$clients = new ClientRepository($pdo);
$maria = $clients->save(new IndividualClient('Maria Souza', '529.982.247-25'));
$loja = $clients->save((new CompanyClient('Brinquedos Alegria LTDA', '11.222.333/0001-81'))->setTradeName('Loja Alegria'));

$orders = new OrderRepository($pdo);
$o = new Order($maria);
$o->addItem($urso, 2);
$o->addItem($kit, 1);
$o->setAdjustments(10, 15);
$o->setNotes('Entrega no sábado');
$orders->save($o);

$o2 = new Order($loja);
$o2->addItem($urso, 10);
$orders->save($o2);

echo "Dados de exemplo criados.\n";
