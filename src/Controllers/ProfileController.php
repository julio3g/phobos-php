<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use DomainException;
use Phobos\Core\Validator;
use Phobos\Repositories\UserRepository;

/** Perfil do usuário logado: dados pessoais e troca de senha. */
final class ProfileController extends Controller
{
    public function show(): void
    {
        $this->render([], []);
    }

    public function update(): void
    {
        $v = (new Validator($_POST))
            ->required('name', 'Nome')->maxLength('name', 'Nome', 120)
            ->required('email', 'E-mail')->email('email', 'E-mail')->maxLength('email', 'E-mail', 160);

        if (!$v->fails()) {
            $user = $this->user();
            try {
                $user->setName($this->post('name'));
                $user->setEmail($this->post('email'));
                (new UserRepository($this->pdo))->save($user);
                $this->flash('success', 'Perfil atualizado.');
                $this->redirect('/profile');
            } catch (DomainException $e) {
                $v->add('_profile', $e->getMessage());
            }
        }
        $this->render($_POST, $v->errors(), 422);
    }

    public function password(): void
    {
        $user = $this->user();
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $errors = [];

        if (!password_verify($current, $user->passwordHash())) {
            $errors['current_password'] = 'Senha atual incorreta.';
        } elseif ($new !== (string) ($_POST['new_password_confirm'] ?? '')) {
            $errors['new_password_confirm'] = 'As senhas não conferem.';
        } else {
            try {
                $user->changePassword($new);
                (new UserRepository($this->pdo))->save($user);
                $this->flash('success', 'Senha alterada.');
                $this->redirect('/profile');
            } catch (DomainException $e) {
                $errors['new_password'] = $e->getMessage();
            }
        }
        $this->render([], $errors, 422);
    }

    /**
     * @param array<string,mixed> $form
     * @param array<string,string> $errors
     */
    private function render(array $form, array $errors, int $status = 200): void
    {
        $user = $this->user();
        $this->view('profile/show', [
            'profile' => $user,
            'form' => $form !== [] ? $form : ['name' => $user->name(), 'email' => $user->email()],
            'errors' => $errors,
        ], $status);
    }
}
