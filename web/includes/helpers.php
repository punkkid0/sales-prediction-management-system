<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function money(float|int|string $amount): string
{
    return '₦' . number_format((float) $amount, 2);
}

function base_url(string $path = ''): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($dir === '/' || $dir === '\\') {
        $dir = '';
    }
    $path = ltrim($path, '/');
    return $dir . '/' . $path;
}

function url(string $page, array $params = []): string
{
    $params = array_merge(['page' => $page], $params);
    return base_url('index.php?' . http_build_query($params));
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function request(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function active_page(string $page): string
{
    $current = $_GET['page'] ?? 'dashboard';
    return $current === $page ? 'active' : '';
}

function role_label(string $role): string
{
    return match ($role) {
        'admin'   => 'Administrator',
        'manager' => 'Manager',
        'staff'   => 'Staff',
        default   => ucfirst($role),
    };
}
