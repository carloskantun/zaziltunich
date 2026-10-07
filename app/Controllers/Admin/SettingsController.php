<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database as DB;
use App\Domain\Settings;

final class SettingsController extends Base
{
    private const KEYS = ['site_name', 'contact_whatsapp', 'contact_phone', 'contact_email', 'contact_sms', 'hold_minutes', 'payment_instructions_es', 'payment_instructions_en'];

    public function index(): void
    {
        $this->page('settings', [
            'title' => 'Ajustes', 's' => Settings::all(),
            'users' => DB::all('SELECT id, name, email, role, active FROM users ORDER BY id'),
        ], ['admin']);
    }

    public function save(): void
    {
        $this->guard(['admin']);
        foreach (self::KEYS as $k) {
            Settings::set($k, trim((string) ($_POST[$k] ?? '')));
        }
        $this->back('admin/ajustes', 'Ajustes guardados.');
    }

    /** Crea un usuario o, si el correo ya existe, actualiza rol, estado y contraseña (si se indica). */
    public function saveUser(): void
    {
        $me = $this->guard(['admin']);
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $pass = (string) ($_POST['password'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'operator', 'viewer'], true) ? (string) $_POST['role'] : 'operator';
        $active = isset($_POST['active']) ? 1 : 0;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($pass !== '' && strlen($pass) < 10)) {
            $this->back('admin/ajustes', 'Correo inválido o contraseña menor a 10 caracteres.', 'err');
        }
        $u = DB::one('SELECT * FROM users WHERE email = ?', [$email]);
        if ($u) {
            if ((int) $u['id'] === (int) $me['id'] && (!$active || $role !== 'admin')) {
                $this->back('admin/ajustes', 'No puedes quitarte a ti mismo el acceso de administrador.', 'err');
            }
            $row = ['role' => $role, 'active' => $active];
            if (trim((string) ($_POST['name'] ?? '')) !== '') {
                $row['name'] = trim((string) $_POST['name']);
            }
            if ($pass !== '') {
                $row['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
            }
            DB::update('users', $row, 'id = ?', [$u['id']]);
        } else {
            if ($pass === '' || trim((string) ($_POST['name'] ?? '')) === '') {
                $this->back('admin/ajustes', 'Para un usuario nuevo indica nombre y contraseña.', 'err');
            }
            DB::insert('users', ['name' => trim((string) $_POST['name']), 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => $role, 'active' => $active, 'created_at' => now_site()->format('Y-m-d H:i:s')]);
        }
        $this->back('admin/ajustes', 'Usuario guardado.');
    }
}
