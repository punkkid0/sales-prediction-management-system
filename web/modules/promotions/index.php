<?php

declare(strict_types=1);

require_login();
require_role(['admin', 'manager']);

$pdo = db();

if (is_post() && request('action') === 'delete') {
    verify_csrf();
    $pdo->prepare('DELETE FROM promotions WHERE id = ?')->execute([(int) request('id')]);
    flash('success', 'Promotion deleted.');
    redirect(url('promotions'));
}

if (is_post() && in_array(request('action'), ['create', 'update'], true)) {
    verify_csrf();
    $id = (int) request('id', 0);
    $title = trim((string) request('title', ''));
    $productId = request('product_id') !== '' ? (int) request('product_id') : null;
    $discount = (float) request('discount_pct', 0);
    $start = (string) request('start_date', '');
    $end = (string) request('end_date', '');
    $isActive = request('is_active') ? 1 : 0;

    if ($title === '' || $start === '' || $end === '' || $discount < 0) {
        flash('danger', 'Title, dates and a valid discount are required.');
        redirect(url('promotions'));
    }

    if (request('action') === 'create') {
        $pdo->prepare(
            'INSERT INTO promotions (product_id, title, discount_pct, start_date, end_date, is_active)
             VALUES (?, ?, ?, ?, ?, 1)'
        )->execute([$productId, $title, $discount, $start, $end]);
        flash('success', 'Promotion created.');
    } else {
        $pdo->prepare(
            'UPDATE promotions SET product_id=?, title=?, discount_pct=?, start_date=?, end_date=?, is_active=?
             WHERE id=?'
        )->execute([$productId, $title, $discount, $start, $end, $isActive, $id]);
        flash('success', 'Promotion updated.');
    }
    redirect(url('promotions'));
}

$editId = (int) request('edit', 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM promotions WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}

$products = $pdo->query('SELECT id, name FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();
$promotions = $pdo->query(
    'SELECT pr.*, p.name AS product_name
     FROM promotions pr
     LEFT JOIN products p ON p.id = pr.product_id
     ORDER BY pr.start_date DESC'
)->fetchAll();

$today = date('Y-m-d');

render_header('Promotions');
?>
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold"><?= $edit ? 'Edit promotion' : 'Add promotion' ?></div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
            <div class="col-md-4">
                <label class="form-label">Title</label>
                <input name="title" class="form-control" required value="<?= e($edit['title'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All products</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"
                            <?= isset($edit['product_id']) && (int) $edit['product_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Discount %</label>
                <input type="number" step="0.01" min="0" max="100" name="discount_pct" class="form-control" required
                       value="<?= e((string) ($edit['discount_pct'] ?? '0')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Start date</label>
                <input type="date" name="start_date" class="form-control" required
                       value="<?= e($edit['start_date'] ?? $today) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">End date</label>
                <input type="date" name="end_date" class="form-control" required
                       value="<?= e($edit['end_date'] ?? $today) ?>">
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
                <?php if ($edit): ?><a class="btn btn-link" href="<?= e(url('promotions')) ?>">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>ID</th><th>Title</th><th>Product</th><th>Discount</th>
                <th>Start</th><th>End</th><th>Status</th><th>Running now?</th><th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($promotions as $pr):
                $running = (int) $pr['is_active'] === 1
                    && $pr['start_date'] <= $today
                    && $pr['end_date'] >= $today;
                ?>
                <tr>
                    <td><?= (int) $pr['id'] ?></td>
                    <td><?= e($pr['title']) ?></td>
                    <td><?= e($pr['product_name'] ?? 'All products') ?></td>
                    <td><?= e(number_format((float) $pr['discount_pct'], 2)) ?>%</td>
                    <td><?= e($pr['start_date']) ?></td>
                    <td><?= e($pr['end_date']) ?></td>
                    <td><?= (int) $pr['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <?php if ($running): ?>
                            <span class="badge text-bg-success">Yes</span>
                        <?php else: ?>
                            <span class="badge text-bg-light text-dark">No</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('promotions', ['edit' => $pr['id']])) ?>">Edit</a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this promotion?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $pr['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-3 mb-0">
    Promotions feed the <strong>is_promo</strong> feature in Phase 2 forecasting (external factors objective).
</p>
<?php
render_footer();
