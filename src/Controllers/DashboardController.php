<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use Phobos\Repositories\ClientRepository;
use Phobos\Repositories\MaterialRepository;
use Phobos\Repositories\OrderRepository;
use Phobos\Repositories\ProductRepository;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $orders = new OrderRepository($this->pdo);

        $this->view('dashboard/index', [
            'stats' => $orders->stats(),
            'recent' => array_slice($orders->summaries(), 0, 5),
            'counts' => [
                'clients' => count((new ClientRepository($this->pdo))->all()),
                'materials' => count((new MaterialRepository($this->pdo))->active()),
                'products' => count((new ProductRepository($this->pdo))->active()),
            ],
        ]);
    }
}
