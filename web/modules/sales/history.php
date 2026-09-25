<?php

declare(strict_types=1);

require_login();

$pdo = db();
$user = current_user();

$productId = request('product_id') !== null && request('product_id') !== '' ? (int) request('product_id') : null;
$from = trim((string) request('from', ''));
$to = trim((string) request('to', ''));

if (is_post() && request('action') === 'update_sale') {
    verify_csrf();
    $saleId = (int) request('id');
    $newProduct = (int) request('product_id');
    $newCustomer = request('customer_id') !== '' ? (int) request('customer_id') : null;
    $newQty = (int) request('quantity');
    $newDate = (string) request('sale_date');

    if ($saleId <= 0 || $newProduct <= 0 || $newQty <= 0 || $newDate === '') {
        flash('danger', 'Product, quantity and date are required to update a sale.');
        redirect(url('sales'));
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ? FOR UPDATE');
        $stmt->execute([$saleId]);
        $old = $stmt->fetch();
        if (!$old) {
            throw new RuntimeException('Sale not found.');
        }
        if (!user_can('view_all_sales') && (int) $old['staff_id'] !== (int) $user['id']) {
            throw new RuntimeException('You can only update your own sales.');
        }

        $pdo->prepare('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?')
            ->execute([(int) $old['quantity'], (int) $old['product_id']]);

        $stmt = $pdo->prepare('SELECT id, unit_price, stock_qty, is_active FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$newProduct]);
        $prod = $stmt->fetch();
        if (!$prod || !(int) $prod['is_active']) {
            throw new RuntimeException('Product not found or inactive.');
        }
        if ((int) $prod['stock_qty'] < $newQty) {
            throw new RuntimeException('Not enough stock for the new quantity. Available: ' . (int) $prod['stock_qty']);
        }

        $unit = (float) $prod['unit_price'];
        $total = $unit * $newQty;
        $pdo->prepare(
            'UPDATE sales SET product_id=?, customer_id=?, quantity=?, unit_price=?, total=?, sale_date=? WHERE id=?'
        )->execute([$newProduct, $newCustomer, $newQty, $unit, $total, $newDate, $saleId]);
        $pdo->prepare('UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?')
            ->execute([$newQty, $newProduct]);
        $pdo->commit();
        flash('success', 'Sale updated. Stock was adjusted.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('danger', $e->getMessage());
    }
    redirect(url('sales'));
}

$editId = (int) request('edit', 0);
$editSale = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ?');
    $stmt->execute([$editId]);
    $editSale = $stmt->fetch() ?: null;
    if ($editSale && !user_can('view_all_sales') && (int) $editSale['staff_id'] !== (int) $user['id']) {
        $editSale = null;
        flash('danger', 'You cannot edit that sale.');
    }
}

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

$products = $pdo->query('SELECT id, name, unit_price, stock_qty FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();
$customers = $pdo->query('SELECT id, name FROM customers ORDER BY name')->fetchAll();

render_header('Sales Data Management');
?>
<?php if ($editSale): ?>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Data update — edit sale #<?= (int) $editSale['id'] ?></div>
    <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_sale">
            <input type="hidden" name="id" value="<?= (int) $editSale['id'] ?>">
            <div class="col-md-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select" required>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (int) $editSale['product_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select">
                    <option value="">Walk-in</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($editSale['customer_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" min="1" name="quantity" class="form-control" required value="<?= (int) $editSale['quantity'] ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sale date</label>
                <input type="date" name="sale_date" class="form-control" required value="<?= e($editSale['sale_date']) ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Save update</button>
                <a class="btn btn-link" href="<?= e(url('sales')) ?>">Cancel</a>
            </div>
        </form>
        <p class="small text-muted mb-0 mt-2">Saving returns the old quantity to stock, then deducts the new quantity. Price is taken from the current product price.</p>
    </div>
</div>
<?php endif; ?>
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
                    <td class="text-nowrap">
                        <a href="<?= e(url('receipt', ['id' => $s['id']])) ?>">Receipt</a>
                        <?php if (user_can('view_all_sales') || (int) $s['staff_id'] === (int) $user['id']): ?>
                            · <a href="<?= e(url('sales', ['edit' => $s['id']])) ?>">Update</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
