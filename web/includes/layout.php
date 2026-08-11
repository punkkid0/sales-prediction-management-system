<?php

declare(strict_types=1);

function render_header(string $title = ''): void
{
    $short     = app_config('short_name');
    $user      = current_user();
    $pageTitle = $title !== '' ? $title . ' · ' . $short : app_config('name');
    $flashes   = get_flashes();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/app.css')) ?>?v=2" rel="stylesheet">
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
<div class="d-flex" id="wrapper">
    <nav id="sidebar" class="sidebar text-white" aria-label="Main menu">
        <div class="sidebar-brand px-3 py-3">
            <div>
                <div class="fw-bold"><?= e($short) ?></div>
                <small class="text-white-50">Sales &amp; Forecasts</small>
            </div>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
        </div>
        <ul class="nav flex-column px-2 pb-4">
            <li class="nav-item">
                <a class="nav-link <?= active_page('dashboard') ?>" href="<?= e(url('dashboard')) ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <?php if (user_can('create_sale')): ?>
            <li class="nav-item">
                <a class="nav-link <?= active_page('sales_new') ?>" href="<?= e(url('sales_new')) ?>">
                    <i class="bi bi-cart-plus me-2"></i>New Sale
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= active_page('sales') ?>" href="<?= e(url('sales')) ?>">
                    <i class="bi bi-receipt me-2"></i>Sales History
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-2"><span class="nav-section">Master data</span></li>
            <li class="nav-item">
                <a class="nav-link <?= active_page('products') ?>" href="<?= e(url('products')) ?>">
                    <i class="bi bi-box-seam me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= active_page('customers') ?>" href="<?= e(url('customers')) ?>">
                    <i class="bi bi-people me-2"></i>Customers
                </a>
            </li>
            <?php if (user_can('manage_master')): ?>
            <li class="nav-item">
                <a class="nav-link <?= active_page('suppliers') ?>" href="<?= e(url('suppliers')) ?>">
                    <i class="bi bi-truck me-2"></i>Suppliers
                </a>
            </li>
            <?php endif; ?>
            <?php if (user_can('manage_promotions')): ?>
            <li class="nav-item">
                <a class="nav-link <?= active_page('promotions') ?>" href="<?= e(url('promotions')) ?>">
                    <i class="bi bi-tag me-2"></i>Promotions
                </a>
            </li>
            <?php endif; ?>
            <?php if (user_can('view_reports')): ?>
            <li class="nav-item mt-2"><span class="nav-section">Insights</span></li>
            <li class="nav-item">
                <a class="nav-link <?= active_page('reports') ?>" href="<?= e(url('reports')) ?>">
                    <i class="bi bi-graph-up me-2"></i>Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= active_page('forecasts') ?>" href="<?= e(url('forecasts')) ?>">
                    <i class="bi bi-lightning me-2"></i>Forecasts
                </a>
            </li>
            <?php endif; ?>
            <?php if (user_can('manage_users')): ?>
            <li class="nav-item mt-2"><span class="nav-section">Admin</span></li>
            <li class="nav-item">
                <a class="nav-link <?= active_page('users') ?>" href="<?= e(url('users')) ?>">
                    <i class="bi bi-person-gear me-2"></i>Users
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div id="page-content" class="flex-grow-1">
        <header class="topbar d-flex justify-content-between align-items-center px-4 py-3 border-bottom bg-white">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="h5 mb-0 text-truncate"><?= e($title !== '' ? $title : 'Dashboard') ?></h1>
            </div>
            <div class="topbar-user">
                <span class="text-muted small d-flex align-items-center gap-1">
                    <span class="user-label"><?= e($user['name'] ?? '') ?></span>
                    <span class="badge text-bg-primary"><?= e(role_label($user['role'] ?? 'staff')) ?></span>
                </span>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('logout')) ?>">
                    <i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline"> Logout</span>
                </a>
            </div>
        </header>
        <main class="p-4">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
<?php
}

function render_footer(): void
{
    ?>
        </main>
        <footer class="px-4 py-3 text-muted small border-top">
            <?= e(app_config('name')) ?> &middot; Phase 1–3
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>?v=2"></script>
</body>
</html>
<?php
}

function render_login_header(string $title = 'Login'): void
{
    $pageTitle = $title . ' · ' . app_config('short_name');
    $flashes = get_flashes();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/app.css')) ?>?v=2" rel="stylesheet">
</head>
<body class="login-body">
<div class="container-fluid px-2 px-sm-3">
    <div class="row justify-content-center align-items-center min-vh-100 mx-0">
        <div class="col-12 col-sm-10 col-md-6 col-lg-4">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
<?php
}

function render_login_footer(): void
{
    ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
