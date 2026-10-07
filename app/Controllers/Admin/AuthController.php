<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Auth, View};

final class AuthController
{
    public function form(): void
    {
        if (Auth::user()) {
            redirect(raw_url('admin'));
        }
        View::render('admin/login', ['error' => flash()['message'] ?? null], null);
    }

    public function login(): void
    {
        Auth::checkCsrf();
        if (Auth::attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            redirect(raw_url('admin'));
        }
        flash('Correo o contraseña incorrectos (o demasiados intentos; espera un minuto).', 'err');
        redirect(raw_url('admin/login'));
    }

    public function logout(): void
    {
        Auth::checkCsrf();
        Auth::logout();
        redirect(raw_url('admin/login'));
    }
}
