<?php
require_once __DIR__ . '/../backend/config/app.php';
$pdo = getPDO();
ensure_bootstrap_data($pdo);
if (is_logged_in()) {
    if (user_role() === 'admin') {
        redirect('/frontend/admin_dashboard.php');
    }
    redirect('/frontend/dashboard.php');
}
$flash = get_flash();
$appName = app_name($pdo);
$adminExists = has_admin_user($pdo);
$registrationEnabled = is_self_registration_enabled($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= e($appName) ?></title>
    <link rel="stylesheet" href="/frontend/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <section class="auth-side">
            <h1>Create Account</h1>
            <p>Register a secure FRMS profile and attach one primary farm for operational records.</p>
            <ul>
                <li>The first registered user becomes the system administrator.</li>
                <li>Later registrations are farmer accounts, unless an admin creates users manually.</li>
                <li>Admins can disable public self-registration from system settings.</li>
            </ul>
        </section>
        <section class="auth-form">
            <h2>Register</h2>
            <p class="muted">Fill in your profile and farm details.</p>
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($adminExists && !$registrationEnabled): ?>
                <div class="alert alert-error">Self registration is disabled. Contact the administrator to create your account.</div>
                <a class="btn secondary" href="/frontend/index.php">Back to Login</a>
            <?php else: ?>
                <form action="/backend/handlers/auth.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="register">
                    <div class="form-row">
                        <label>
                            Full Name
                            <input type="text" name="full_name" required>
                        </label>
                        <label>
                            Phone Number
                            <input type="text" name="phone">
                        </label>
                    </div>
                    <div class="form-row">
                        <label>
                            Email Address
                            <input type="email" name="email" required>
                        </label>
                        <label>
                            Farm Name
                            <input type="text" name="farm_name" placeholder="Optional for admin/officer first account">
                        </label>
                    </div>
                    <div class="form-row">
                        <label>
                            Location
                            <input type="text" name="location">
                        </label>
                        <label>
                            Farm Size (Acres)
                            <input type="number" step="0.01" name="farm_size_acres" min="0">
                        </label>
                    </div>
                    <div class="form-row">
                        <label>
                            Password
                            <input type="password" name="password" minlength="8" required>
                        </label>
                        <label>
                            Confirm Password
                            <input type="password" name="confirm_password" minlength="8" required>
                        </label>
                    </div>
                    <button type="submit">Register</button>
                    <a class="btn secondary" href="/frontend/index.php">Back to Login</a>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
<script src="/frontend/assets/js/app.js"></script>
</body>
</html>
