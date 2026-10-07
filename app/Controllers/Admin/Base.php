<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Auth, View};

abstract class Base
{
    /** Páginas del panel: exige sesión. */
    protected function page(string $tpl, array $data = [], array $roles = []): void
    {
        $user = Auth::require($roles);
        View::render('admin/' . $tpl, $data + ['user' => $user, 'flash' => flash()], 'layout/admin');
    }

    /** Acciones POST: sesión, rol con escritura y CSRF. */
    protected function guard(array $roles = ['admin', 'operator']): array
    {
        $user = Auth::require($roles);
        Auth::checkCsrf();
        return $user;
    }

    protected function back(string $path, ?string $msg = null, string $type = 'ok'): never
    {
        if ($msg !== null) {
            flash($msg, $type);
        }
        redirect(raw_url($path));
    }
}
