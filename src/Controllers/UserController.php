<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use DomainException;
use Phobos\Core\Validator;
use Phobos\Models\User;
use Phobos\Repositories\UserRepository;

/** Gestão de usuários e perfis de acesso (somente administradores). */
final class UserController extends Controller
{
    private UserRepository $users;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->users = new UserRepository($this->pdo);
    }

    public function index(): void
    {
        $this->render([], []);
    }

    public function store(): void
    {
        $v = (new Validator($_POST))
            ->required('name', 'Nome')->maxLength('name', 'Nome', 120)
            ->required('email', 'E-mail')->email('email', 'E-mail')->maxLength('email', 'E-mail', 160)
            ->required('password', 'Senha')
            ->in('role', 'Perfil', ['admin', 'member']);

        if (!$v->fails()) {
            try {
                $this->users->save(User::register($this->post('name'), $this->post('email'), (string) $_POST['password'], $this->post('role')));
                $this->flash('success', 'Usuário criado.');
                $this->redirect('/settings/users');
            } catch (DomainException $e) {
                $v->add('_form', $e->getMessage());
            }
        }
        $this->render($_POST, $v->errors(), 422);
    }

    public function updateRole(string $id): void
    {
        $target = $this->findOrFail($id);
        if ($target->id() === $this->user()->id()) {
            $this->flash('error', 'Você não pode alterar o seu próprio perfil de acesso.');
            $this->redirect('/settings/users');
        }
        try {
            $target->setRole($this->post('role'));
            $this->users->save($target);
            $this->flash('success', 'Perfil de ' . $target->name() . ' atualizado.');
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect('/settings/users');
    }

    public function destroy(string $id): void
    {
        $target = $this->findOrFail($id);
        if ($target->id() === $this->user()->id()) {
            $this->flash('error', 'Você não pode excluir a própria conta.');
        } else {
            $this->users->delete((int) $target->id());
            $this->flash('success', 'Usuário excluído.');
        }
        $this->redirect('/settings/users');
    }

    private function findOrFail(string $id): User
    {
        $user = $this->users->find($this->intId($id));

        return $user instanceof User ? $user : $this->notFound();
    }

    /**
     * @param array<string,mixed> $form
     * @param array<string,string> $errors
     */
    private function render(array $form, array $errors, int $status = 200): void
    {
        $this->view('users/index', [
            'users' => $this->users->allByName(),
            'form' => $form,
            'errors' => $errors,
            'me' => $this->user()->id(),
        ], $status);
    }
}
