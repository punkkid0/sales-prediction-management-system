<?php

declare(strict_types=1);

require_login();

$pdo = db();
$user = current_user();

$productId = request('product_id') !== null && request('product_id') !== '' ? (int) request('product_id') : null;
$from = trim((string) request('from', ''));
$to = trim((string) request('to', ''));

$sql = 'SELECT s.*, p.name AS product_name, c.name AS customer_name, u.name AS staff_name
        FROM sales s
        JOIN products p ON p.id = s.product_id
        LEFT JOIN customers c ON c.id = s.customer_id
        JOIN users u ON u.id = s.staff_id
        WHERE 1=1';
$params = [];

if (!user_can('view_all_sales')) {
    $sql .= ' AND s.staff_id = ?';
    $params[] = $user['id'];
}
if ($productId) {
    $sql .= ' AND s.product_id = ?';
    $params[] = $productId;
}
if ($from !== '') {
    $sql .= ' AND s.sale_date >= ?';
    $params[] = $from;
}
if ($to !== '') {
    $sql .= ' AND s.sale_date <= ?';
    $params[] = $to;
}
$sql .= ' ORDER BY s.sale_date DESC, s.id DESC LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$products = $pdo->query('SELECT id, name FROM products ORDER BY name')->fetchAll();

render_header('Sales History');
?>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="sales">
            <div class="col-md-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All products</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $productId === (int) $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn-primary">Filter</button>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('sales')) ?>">Reset</a>
                <a class="btn btn-sm btn-success" href="<?= e(url('sales_new')) ?>">New sale</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>#</th><th>Date</th><th>Product</th><th>Customer</th>
                <th>Qty</th><th>Unit</th><th>Total</th><th>Staff</th><th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$sales): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No sales match your filters.</td></tr>
            <?php else: foreach ($sales as $s): ?>
                <tr>
                    <td><?= (int) $s['id'] ?></td>
                    <td><?= e($s['sale_date']) ?></td>
                    <td><?= e($s['product_name']) ?></td>
                    <td><?= e($s['customer_name'] ?? '—') ?></td>
                    <td><?= (int) $s['quantity'] ?></td>
                    <td><?= e(money((float) $s['unit_price'])) ?></td>
                    <td><?= e(money((float) $s['total'])) ?></td>
                    <td><?= e($s['staff_name']) ?></td>
                    <td><a href="<?= e(url('receipt', ['id' => $s['id']])) ?>">Receipt</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
