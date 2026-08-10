<?php

declare(strict_types=1);

require_login();
require_role(['admin', 'manager']);

$pdo = db();

if (is_post() && request('action') === 'delete') {
    verify_csrf();
    $id = (int) request('id');
    $pdo->prepare('UPDATE products SET supplier_id = NULL WHERE supplier_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
    flash('success', 'Supplier deleted.');
    redirect(url('suppliers'));
}

if (is_post() && in_array(request('action'), ['create', 'update'], true)) {
    verify_csrf();
    $id = (int) request('id', 0);
    $name = trim((string) request('name', ''));
    $phone = trim((string) request('phone', ''));
    $email = trim((string) request('email', ''));
    $address = trim((string) request('address', ''));

    if ($name === '') {
        flash('danger', 'Supplier name is required.');
        redirect(url('suppliers'));
    }

    if (request('action') === 'create') {
        $pdo->prepare(
            'INSERT INTO suppliers (name, phone, email, address) VALUES (?, ?, ?, ?)'
        )->execute([$name, $phone ?: null, $email ?: null, $address ?: null]);
        flash('success', 'Supplier created.');
    } else {
        $pdo->prepare(
            'UPDATE suppliers SET name=?, phone=?, email=?, address=? WHERE id=?'
        )->execute([$name, $phone ?: null, $email ?: null, $address ?: null, $id]);
        flash('success', 'Supplier updated.');
    }
    redirect(url('suppliers'));
}

$editId = (int) request('edit', 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}

$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();

render_header('Suppliers');
?>
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold"><?= $edit ? 'Edit supplier' : 'Add supplier' ?></div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
            <div class="col-md-3">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" required value="<?= e($edit['name'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Phone</label>
                <input name="phone" class="form-control" value="<?= e($edit['phone'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($edit['email'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Address</label>
                <input name="address" class="form-control" value="<?= e($edit['address'] ?? '') ?>">
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $edit ? 'Update' : 'Create' ?></button>
                <?php if ($edit): ?><a class="btn btn-link" href="<?= e(url('suppliers')) ?>">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><?= (int) $s['id'] ?></td>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['phone'] ?? '—') ?></td>
                    <td><?= e($s['email'] ?? '—') ?></td>
                    <td><?= e($s['address'] ?? '—') ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('suppliers', ['edit' => $s['id']])) ?>">Edit</a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this supplier?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
