<?php

declare(strict_types=1);

logout_user();

// Start a fresh session so we can show a flash on the login page
if (session_status() === PHP_SESSION_NONE) {
    session_name(app_config('session_name'));
    session_start();
}
flash('success', 'You have been logged out.');
redirect(url('login'));
