<?php
declare(strict_types=1);

namespace App\Core;

/** Sesión del panel: usuarios con rol administrador, operador o solo lectura. */
final class Auth
{
    public const ROLES = ['admin', 'operator', 'viewer'];

    public static function user(): ?array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache ?: null;
        }
        $id = (int) ($_SESSION['uid'] ?? 0);
        if ($id === 0) {
            $cache = false;
            return null;
        }
        $user = Database::one('SELECT id, name, email, role FROM users WHERE id = ? AND active = 1', [$id]);
        $cache = $user ?? false;
        return $user;
    }

    public static function attempt(string $email, string $password): bool
    {
        $lock = (int) ($_SESSION['login_lock'] ?? 0);
        if ($lock > time()) {
            return false;
        }
        $user = Database::one('SELECT * FROM users WHERE email = ? AND active = 1', [mb_strtolower(trim($email))]);
        if ($user && password_verify($password, (string) $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $user['id'];
            $_SESSION['login_fails'] = 0;
            return true;
        }
        $fails = (int) ($_SESSION['login_fails'] ?? 0) + 1;
        $_SESSION['login_fails'] = $fails;
        if ($fails >= 5) {
            $_SESSION['login_lock'] = time() + 60;
            $_SESSION['login_fails'] = 0;
        }
        usleep(400000);
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    /** Exige sesión y, opcionalmente, uno de los roles dados. */
    public static function require(array $roles = []): array
    {
        $user = self::user();
        if ($user === null) {
            redirect(raw_url('admin/login'));
        }
        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo 'Sin permiso para esta acción.';
            exit;
        }
        return $user;
    }

    /** Operadores y administradores pueden modificar reservas. */
    public static function canWrite(): bool
    {
        $u = self::user();
        return $u !== null && in_array($u['role'], ['admin', 'operator'], true);
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === 'admin';
    }

    public static function checkCsrf(): void
    {
        $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
            http_response_code(419);
            echo 'La sesión expiró. Regresa y vuelve a intentarlo.';
            exit;
        }
    }
}
