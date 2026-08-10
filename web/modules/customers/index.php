<?php

declare(strict_types=1);

require_login();

$pdo = db();
$canEdit = user_can('manage_master') || user_can('create_sale');

if (is_post() && request('action') === 'delete' && user_can('manage_master')) {
    verify_csrf();
    $id = (int) request('id');
    $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    flash('success', 'Customer deleted.');
    redirect(url('customers'));
}

if (is_post() && in_array(request('action'), ['create', 'update'], true) && $canEdit) {
    verify_csrf();
    $id = (int) request('id', 0);
    $name = trim((string) request('name', ''));
    $phone = trim((string) request('phone', ''));
    $email = trim((string) request('email', ''));
    $address = trim((string) request('address', ''));

    if ($name === '') {
        flash('danger', 'Customer name is required.');
        redirect(url('customers'));
    }

    if (request('action') === 'create') {
        $pdo->prepare(
            'INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)'
        )->execute([$name, $phone ?: null, $email ?: null, $address ?: null]);
        flash('success', 'Customer created.');
    } else {
        $pdo->prepare(
            'UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?'
        )->execute([$name, $phone ?: null, $email ?: null, $address ?: null, $id]);
        flash('success', 'Customer updated.');
    }
    redirect(url('customers'));
}

$editId = (int) request('edit', 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}

$customers = $pdo->query('SELECT * FROM customers ORDER BY name')->fetchAll();

render_header('Customers');
?>
<?php if ($canEdit): ?>
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold"><?= $edit ? 'Edit customer' : 'Add customer' ?></div>
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
                <?php if ($edit): ?><a class="btn btn-link" href="<?= e(url('customers')) ?>">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?= (int) $c['id'] ?></td>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['phone'] ?? '—') ?></td>
                    <td><?= e($c['email'] ?? '—') ?></td>
                    <td><?= e($c['address'] ?? '—') ?></td>
                    <?php if ($canEdit): ?>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('customers', ['edit' => $c['id']])) ?>">Edit</a>
                        <?php if (user_can('manage_master')): ?>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this customer?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
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
