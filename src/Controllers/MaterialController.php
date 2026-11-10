<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use DomainException;
use Phobos\Core\Validator;
use Phobos\Enums\Unit;
use Phobos\Models\Material;
use Phobos\Repositories\MaterialRepository;
use Phobos\Support\ImageUploader;

final class MaterialController extends Controller
{
    private MaterialRepository $materials;

    public function __construct()
    {
        parent::__construct();
        $this->materials = new MaterialRepository($this->pdo);
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $archived = ($_GET['archived'] ?? '') === '1';
        $this->view('materials/index', ['materials' => $this->materials->search($q, $archived), 'q' => $q, 'archived' => $archived]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->view('materials/form', ['material' => null, 'form' => ['unit' => 'un'], 'errors' => []]);
    }

    public function show(string $id): void
    {
        $this->view('materials/show', ['material' => $this->findOrFail($id)]);
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $m = $this->findOrFail($id);
        $this->view('materials/form', ['material' => $m, 'form' => [
            'name' => $m->name(), 'brand' => $m->brand(), 'variant' => $m->variant(), 'unit' => $m->unit()->value,
            'package_price' => $m->packagePrice(), 'package_quantity' => $m->packageQuantity(), 'purchase_url' => $m->purchaseUrl(),
        ], 'errors' => []]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->persist(null);
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $this->persist($this->findOrFail($id));
    }

    public function archive(string $id): void
    {
        $this->requireAdmin();
        $m = $this->findOrFail($id);
        $m->isArchived() ? $m->restore() : $m->archive();
        $this->materials->save($m);
        $this->flash('success', $m->isArchived() ? 'Material arquivado.' : 'Material restaurado.');
        $this->redirect('/materials');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $m = $this->findOrFail($id);
        try {
            $this->materials->delete((int) $m->id());
            $this->flash('success', 'Material excluído.');
        } catch (DomainException) {
            $this->flash('error', 'Material usado em produtos; arquive em vez de excluir.');
        }
        $this->redirect('/materials');
    }

    private function persist(?Material $existing): void
    {
        $form = $_POST;
        $v = (new Validator($form))
            ->required('name', 'Nome')->maxLength('name', 'Nome', 150)
            ->in('unit', 'Unidade', array_map(static fn (Unit $u) => $u->value, Unit::cases()))
            ->required('package_price', 'Preço da embalagem')->number('package_price', 'Preço da embalagem', 0)
            ->required('package_quantity', 'Quantidade da embalagem')->number('package_quantity', 'Quantidade da embalagem', 0, null, true)
            ->url('purchase_url', 'Link de compra');

        try {
            if ($v->fails()) {
                throw new DomainException('Corrija os campos destacados.');
            }
            $material = $existing ?? new Material($this->post('name'), Unit::from($this->post('unit')), 0, 1);
            $material->setName($this->post('name'));
            $material->setUnit(Unit::from($this->post('unit')));
            $material->setPackage($this->postFloat('package_price'), $this->postFloat('package_quantity'));
            $material->setDetails($this->postNullable('brand'), $this->postNullable('variant'), $this->postNullable('purchase_url'));

            $uploader = new ImageUploader(dirname(__DIR__, 2) . '/public/uploads');
            $url = $uploader->store($_FILES['image'] ?? null, 'materials');
            if ($url !== null) {
                $material->setImageUrl($url);
            } elseif ($this->post('remove_image') === '1') {
                $material->setImageUrl(null);
            }

            $this->materials->save($material);
            $this->flash('success', $existing ? 'Material atualizado.' : 'Material criado.');
            $this->redirect('/materials/' . $material->id());
        } catch (DomainException $e) {
            $v->add('_form', $e->getMessage());
            $this->view('materials/form', ['material' => $existing, 'form' => $form, 'errors' => $v->errors()], 422);
        }
    }

    private function findOrFail(string $id): Material
    {
        $m = $this->materials->find($this->intId($id));

        return $m instanceof Material ? $m : $this->notFound();
    }
}
