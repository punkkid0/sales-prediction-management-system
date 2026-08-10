<?php

declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        redirect(url('login'));
    }
}

/**
 * @param string|array $roles
 */
function require_role(string|array $roles): void
{
    require_login();
    $roles = (array) $roles;
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        flash('danger', 'You do not have permission to access that page.');
        redirect(url('dashboard'));
    }
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT id, name, email, password_hash, role, is_active
         FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !(int) $user['is_active']) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function user_can(string $capability): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $role = $user['role'];

    $map = [
        'manage_users'      => ['admin'],
        'manage_master'     => ['admin', 'manager'],
        'manage_promotions' => ['admin', 'manager'],
        'view_reports'      => ['admin', 'manager'],
        'view_all_sales'    => ['admin', 'manager'],
        'create_sale'       => ['admin', 'manager', 'staff'],
        'view_forecasts'    => ['admin', 'manager'],
        'retrain_model'     => ['admin'],
    ];

    return in_array($role, $map[$capability] ?? [], true);
}
