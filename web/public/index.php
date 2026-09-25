<?php
/**
 * Front controller / simple router.
 * Point Apache document root (or alias) to this /public directory.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$page = (string) ($_GET['page'] ?? 'dashboard');

$routes = [
    'login'       => __DIR__ . '/../modules/auth/login.php',
    'logout'      => __DIR__ . '/../modules/auth/logout.php',
    'dashboard'   => __DIR__ . '/../modules/dashboard/index.php',
    'products'    => __DIR__ . '/../modules/products/index.php',
    'customers'   => __DIR__ . '/../modules/customers/index.php',
    'suppliers'   => __DIR__ . '/../modules/suppliers/index.php',
    'users'       => __DIR__ . '/../modules/users/index.php',
    'promotions'  => __DIR__ . '/../modules/promotions/index.php',
    'sales'       => __DIR__ . '/../modules/sales/history.php',
    'sales_new'   => __DIR__ . '/../modules/sales/new.php',
    'receipt'     => __DIR__ . '/../modules/sales/receipt.php',
    'reports'     => __DIR__ . '/../modules/reports/index.php',
    'forecasts'   => __DIR__ . '/../modules/forecasts/index.php',
    'processing'  => __DIR__ . '/../modules/processing/index.php',
    'decisions'   => __DIR__ . '/../modules/decisions/index.php',
];

// Public pages
$publicPages = ['login'];

if (!in_array($page, $publicPages, true) && $page !== 'logout') {
    // require_login is enforced inside modules too; redirect early for unknown unauth
    if (!is_logged_in() && isset($routes[$page])) {
        flash('warning', 'Please log in to continue.');
        redirect(url('login'));
    }
}

if ($page === 'dashboard' && !is_logged_in()) {
    redirect(url('login'));
}

if (!isset($routes[$page])) {
    http_response_code(404);
    if (is_logged_in()) {
        render_header('Not found');
        echo '<div class="alert alert-warning">Page not found.</div>';
        render_footer();
    } else {
        redirect(url('login'));
    }
    exit;
}

try {
    require $routes[$page];
} catch (PDOException $e) {
    http_response_code(500);
    $msg = 'Database error. Check that MySQL is running and you imported schema.sql + seed.sql. '
        . 'Also verify web/config/database.php credentials.';
    if (is_logged_in()) {
        render_header('Error');
        echo '<div class="alert alert-danger">' . e($msg) . '</div>';
        echo '<pre class="small text-muted">' . e($e->getMessage()) . '</pre>';
        render_footer();
    } else {
        render_login_header('Error');
        echo '<div class="alert alert-danger">' . e($msg) . '</div>';
        render_login_footer();
    }
}
