<?php

declare(strict_types=1);

require_login();
require_role('admin');

$pdo = db();

if (is_post() && request('action') === 'toggle') {
    verify_csrf();
    $id = (int) request('id');
    if ($id === (int) current_user()['id']) {
        flash('warning', 'You cannot deactivate your own account.');
        redirect(url('users'));
    }
    $pdo->prepare('UPDATE users SET is_active = IF(is_active=1,0,1) WHERE id = ?')->execute([$id]);
    flash('success', 'User status updated.');
    redirect(url('users'));
}

if (is_post() && in_array(request('action'), ['create', 'update'], true)) {
    verify_csrf();
    $id = (int) request('id', 0);
    $name = trim((string) request('name', ''));
    $email = trim((string) request('email', ''));
    $role = (string) request('role', 'staff');
    $password = (string) request('password', '');
    $isActive = request('is_active') ? 1 : 0;

    if ($name === '' || $email === '' || !in_array($role, ['admin', 'manager', 'staff'], true)) {
        flash('danger', 'Name, email and valid role are required.');
        redirect(url('users'));
    }

    if (request('action') === 'create') {
        if (strlen($password) < 6) {
            flash('danger', 'Password must be at least 6 characters.');
            redirect(url('users'));
        }
        try {
            $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)'
            )->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            flash('success', 'User created.');
        } catch (PDOException $e) {
            flash('danger', 'Could not create user (email may already exist).');
        }
    } else {
        if ($password !== '') {
            $pdo->prepare(
                'UPDATE users SET name=?, email=?, role=?, is_active=?, password_hash=? WHERE id=?'
            )->execute([$name, $email, $role, $isActive, password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            $pdo->prepare(
                'UPDATE users SET name=?, email=?, role=?, is_active=? WHERE id=?'
            )->execute([$name, $email, $role, $isActive, $id]);
        }
        flash('success', 'User updated.');
    }
    redirect(url('users'));
}

$editId = (int) request('edit', 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}

$users = $pdo->query(
    'SELECT id, name, email, role, is_active, created_at FROM users ORDER BY role, name'
)->fetchAll();

render_header('Users');
?>
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold"><?= $edit ? 'Edit user' : 'Add user' ?></div>
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
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?= e($edit['email'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <?php foreach (['admin', 'manager', 'staff'] as $r): ?>
                        <option value="<?= $r ?>" <?= ($edit['role'] ?? '') === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Password<?= $edit ? ' (optional)' : '' ?></label>
                <input type="password" name="password" class="form-control" <?= $edit ? '' : 'required' ?> minlength="6">
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
                <?php if ($edit): ?><a class="btn btn-link" href="<?= e(url('users')) ?>">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= e(role_label($u['role'])) ?></span></td>
                    <td><?= (int) $u['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td><?= e(substr((string) $u['created_at'], 0, 10)) ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('users', ['edit' => $u['id']])) ?>">Edit</a>
                        <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button class="btn btn-sm btn-outline-warning">
                                <?= (int) $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
