<?php

declare(strict_types=1);

if (is_logged_in()) {
    redirect(url('dashboard'));
}

$error = null;

if (is_post()) {
    verify_csrf();
    $email = trim((string) request('email', ''));
    $password = (string) request('password', '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } elseif (attempt_login($email, $password)) {
        flash('success', 'Welcome back, ' . current_user()['name'] . '!');
        redirect(url('dashboard'));
    } else {
        $error = 'Invalid email or password, or account is inactive.';
    }
}

render_login_header('Login');
?>
<div class="card login-card">
    <div class="card-body p-4">
        <h2 class="h4 text-center mb-1"><?= e(app_config('short_name')) ?></h2>
        <p class="text-center text-muted small mb-4">Sales Prediction Management System</p>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('login')) ?>" autocomplete="on">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= e((string) request('email', '')) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
        <hr>
        <p class="small text-muted mb-0">
            Demo: <code>admin@spms.local</code> / <code>password123</code>
        </p>
    </div>
</div>
<?php
render_login_footer();
