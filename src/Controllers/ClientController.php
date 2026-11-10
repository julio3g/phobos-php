<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use DomainException;
use Phobos\Core\Validator;
use Phobos\Enums\PersonType;
use Phobos\Models\Client;
use Phobos\Models\CompanyClient;
use Phobos\Models\IndividualClient;
use Phobos\Repositories\ClientRepository;

final class ClientController extends Controller
{
    private ClientRepository $clients;

    public function __construct()
    {
        parent::__construct();
        $this->clients = new ClientRepository($this->pdo);
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $this->view('clients/index', ['clients' => $this->clients->search($q), 'q' => $q]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->view('clients/form', ['client' => null, 'form' => ['person_type' => 'individual'], 'errors' => []]);
    }

    public function show(string $id): void
    {
        $this->view('clients/show', ['client' => $this->findOrFail($id)]);
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $client = $this->findOrFail($id);
        $this->view('clients/form', ['client' => $client, 'form' => $this->formFrom($client), 'errors' => []]);
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

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $client = $this->findOrFail($id);
        try {
            $this->clients->delete((int) $client->id());
            $this->flash('success', 'Cliente excluído.');
        } catch (DomainException) {
            $this->flash('error', 'Cliente possui ordens e não pode ser excluído.');
        }
        $this->redirect('/clients');
    }

    private function persist(?Client $existing): void
    {
        $form = $_POST;
        $type = PersonType::tryFrom($this->post('person_type')) ?? PersonType::Individual;

        $v = (new Validator($form))
            ->required('name', 'Nome')->maxLength('name', 'Nome', 150)
            ->required('document', $type === PersonType::Company ? 'CNPJ' : 'CPF')
            ->email('email', 'E-mail')
            ->maxLength('phone', 'Telefone', 30)
            ->maxLength('state', 'UF', 2);

        try {
            if ($v->fails()) {
                throw new DomainException('Corrija os campos destacados.');
            }
            $client = $this->buildClient($existing, $type);
            $client->setEmail($this->postNullable('email'));
            $client->setPhone($this->postNullable('phone'));
            $client->setAddress($this->postNullable('street'), $this->postNullable('city'), $this->postNullable('state'), $this->postNullable('zip_code'));
            $this->clients->save($client);

            $this->flash('success', $existing ? 'Cliente atualizado.' : 'Cliente criado.');
            $this->redirect('/clients/' . $client->id());
        } catch (DomainException $e) {
            if (!$v->fails()) {
                $v->add('_form', $e->getMessage());
            }
            $this->view('clients/form', ['client' => $existing, 'form' => $form, 'errors' => $v->errors()], 422);
        }
    }

    /** Troca de tipo (PF↔PJ) recria o objeto concreto mantendo o id. */
    private function buildClient(?Client $existing, PersonType $type): Client
    {
        $name = $this->post('name');
        $doc = $this->post('document');
        $client = $type === PersonType::Company
            ? (new CompanyClient($name, $doc))->setTradeName($this->postNullable('trade_name'))
            : new IndividualClient($name, $doc);

        if ($existing?->id() !== null) {
            $client->assignId((int) $existing->id());
        }

        return $client;
    }

    /** @return array<string,mixed> */
    private function formFrom(Client $c): array
    {
        return [
            'person_type' => $c->type()->value, 'name' => $c->name(), 'trade_name' => $c->tradeName(),
            'document' => $c->document(), 'email' => $c->email(), 'phone' => $c->phone(),
            'street' => $c->street(), 'city' => $c->city(), 'state' => $c->state(), 'zip_code' => $c->zipCode(),
        ];
    }

    private function findOrFail(string $id): Client
    {
        $client = $this->clients->find($this->intId($id));

        return $client instanceof Client ? $client : $this->notFound();
    }
}
