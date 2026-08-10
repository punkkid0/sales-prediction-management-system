<?php

declare(strict_types=1);

require_login();

$pdo = db();
$canEdit = user_can('manage_master');

// Delete
if (is_post() && request('action') === 'delete' && $canEdit) {
    verify_csrf();
    $id = (int) request('id');
    try {
        $stmt = $pdo->prepare('UPDATE products SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Product deactivated.');
    } catch (Throwable $e) {
        flash('danger', 'Could not deactivate product.');
    }
    redirect(url('products'));
}

// Create / update
if (is_post() && in_array(request('action'), ['create', 'update'], true) && $canEdit) {
    verify_csrf();
    $id = (int) request('id', 0);
    $name = trim((string) request('name', ''));
    $category = trim((string) request('category', ''));
    $unitPrice = (float) request('unit_price', 0);
    $stock = (int) request('stock_qty', 0);
    $reorder = (int) request('reorder_level', 10);
    $supplierId = request('supplier_id') !== '' ? (int) request('supplier_id') : null;
    $isActive = request('is_active') ? 1 : 0;

    if ($name === '' || $unitPrice < 0) {
        flash('danger', 'Name and a valid unit price are required.');
        redirect(url('products'));
    }

    if (request('action') === 'create') {
        $stmt = $pdo->prepare(
            'INSERT INTO products (name, category, unit_price, stock_qty, reorder_level, supplier_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$name, $category ?: null, $unitPrice, $stock, $reorder, $supplierId]);
        flash('success', 'Product created.');
    } else {
        $stmt = $pdo->prepare(
            'UPDATE products SET name=?, category=?, unit_price=?, stock_qty=?, reorder_level=?, supplier_id=?, is_active=?
             WHERE id=?'
        );
        $stmt->execute([$name, $category ?: null, $unitPrice, $stock, $reorder, $supplierId, $isActive, $id]);
        flash('success', 'Product updated.');
    }
    redirect(url('products'));
}

$editId = (int) request('edit', 0);
$edit = null;
if ($editId && $canEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}

$suppliers = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();
$q = trim((string) request('q', ''));
if ($q !== '') {
    $stmt = $pdo->prepare(
        'SELECT p.*, s.name AS supplier_name
         FROM products p
         LEFT JOIN suppliers s ON s.id = p.supplier_id
         WHERE p.name LIKE ? OR p.category LIKE ?
         ORDER BY p.name'
    );
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like]);
    $products = $stmt->fetchAll();
} else {
    $products = $pdo->query(
        'SELECT p.*, s.name AS supplier_name
         FROM products p
         LEFT JOIN suppliers s ON s.id = p.supplier_id
         ORDER BY p.is_active DESC, p.name'
    )->fetchAll();
}

render_header('Products');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <form class="d-flex gap-2" method="get" action="<?= e(base_url('index.php')) ?>">
        <input type="hidden" name="page" value="products">
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search products..."
               value="<?= e($q) ?>">
        <button class="btn btn-sm btn-outline-secondary">Search</button>
    </form>
</div>

<?php if ($canEdit): ?>
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold"><?= $edit ? 'Edit product' : 'Add product' ?></div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" required value="<?= e($edit['name'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <input name="category" class="form-control" value="<?= e($edit['category'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Unit price</label>
                <input type="number" step="0.01" min="0" name="unit_price" class="form-control" required
                       value="<?= e((string) ($edit['unit_price'] ?? '0')) ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">Stock</label>
                <input type="number" min="0" name="stock_qty" class="form-control"
                       value="<?= e((string) ($edit['stock_qty'] ?? '0')) ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">Reorder</label>
                <input type="number" min="0" name="reorder_level" class="form-control"
                       value="<?= e((string) ($edit['reorder_level'] ?? '10')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"
                            <?= isset($edit['supplier_id']) && (int) $edit['supplier_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($edit): ?>
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                        <?= (int) ($edit['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-12">
                <button class="btn btn-primary"><?= $edit ? 'Update' : 'Create' ?></button>
                <?php if ($edit): ?>
                    <a class="btn btn-link" href="<?= e(url('products')) ?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Reorder</th>
                <th>Supplier</th>
                <th>Status</th>
                <?php if ($canEdit): ?><th></th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr class="<?= (int) $p['is_active'] === 0 ? 'table-secondary' : '' ?>">
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['category'] ?? '—') ?></td>
                    <td><?= e(money((float) $p['unit_price'])) ?></td>
                    <td>
                        <?php if ((int) $p['stock_qty'] <= (int) $p['reorder_level']): ?>
                            <span class="badge text-bg-danger"><?= (int) $p['stock_qty'] ?></span>
                        <?php else: ?>
                            <?= (int) $p['stock_qty'] ?>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $p['reorder_level'] ?></td>
                    <td><?= e($p['supplier_name'] ?? '—') ?></td>
                    <td><?= (int) $p['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <?php if ($canEdit): ?>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('products', ['edit' => $p['id']])) ?>">Edit</a>
                        <?php if ((int) $p['is_active'] === 1): ?>
                        <form method="post" class="d-inline" onsubmit="return confirm('Deactivate this product?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
