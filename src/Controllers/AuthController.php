<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use DomainException;
use Phobos\Core\Auth;
use Phobos\Core\Session;
use Phobos\Core\Validator;
use Phobos\Models\User;
use Phobos\Repositories\UserRepository;

final class AuthController extends Controller
{
    protected function requiresAuth(): bool
    {
        return false;
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login', ['email' => '']);
    }

    public function login(): void
    {
        $email = $this->post('email');
        if (!Auth::attempt($email, (string) ($_POST['password'] ?? ''))) {
            $this->flash('error', 'E-mail ou senha inválidos.');
            $this->view('auth/login', ['email' => $email], 422);

            return;
        }
        $this->redirect('/');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/register', ['form' => [], 'errors' => []]);
    }

    /**
     * Cadastro público. A primeira conta do sistema vira administradora;
     * as demais entram como "member" (somente consulta) e um admin pode promovê-las.
     */
    public function register(): void
    {
        $v = (new Validator($_POST))
            ->required('name', 'Nome')->maxLength('name', 'Nome', 120)
            ->required('email', 'E-mail')->email('email', 'E-mail')->maxLength('email', 'E-mail', 160)
            ->required('password', 'Senha');

        $password = (string) ($_POST['password'] ?? '');
        if ($password !== (string) ($_POST['password_confirm'] ?? '')) {
            $v->add('password_confirm', 'As senhas não conferem.');
        }

        if (!$v->fails()) {
            $users = new UserRepository($this->pdo);
            try {
                $role = $users->count() === 0 ? 'admin' : 'member';
                $user = User::register($this->post('name'), $this->post('email'), $password, $role);
                $users->save($user);
                Session::regenerate();
                Session::set('user_id', (int) $user->id());
                $this->flash('success', $role === 'admin'
                    ? 'Conta criada. Você é o administrador do sistema.'
                    : 'Conta criada! Seu perfil é somente consulta até um administrador liberar a edição.');
                $this->redirect('/');
            } catch (DomainException $e) {
                $v->add('_form', $e->getMessage());
            }
        }

        $this->view('auth/register', ['form' => $_POST, 'errors' => $v->errors()], 422);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
