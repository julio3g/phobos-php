<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use Phobos\Core\Auth;

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

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
