<?php
require_once __DIR__ . '/../backend/config/app.php';

$pdo = getPDO();
ensure_bootstrap_data($pdo);

// BREAK THE LOOP: Check login status carefully
if (is_logged_in()) {
    $role = user_role();
    $userId = $_SESSION['user_id'] ?? null;

    if ($role === 'admin') {
        redirect('/frontend/admin_dashboard.php');
        exit; // Always exit after a redirect
    }

    // Check if the user actually has a farm before sending them to the dashboard
    // This prevents the "No Farm -> Redirect to Index -> Is Logged In -> Redirect to Dashboard" loop.
    $farm = get_user_farm($pdo, (int)$userId);
    
    if ($farm) {
        redirect('/frontend/dashboard.php');
        exit;
    } 
    
    // If they are logged in but have no farm, we stay on this page 
    // or redirect to a 'create_farm.php' if you have one.
    $noFarmError = "Logged in, but no farm profile found. Please contact support.";
}

$flash = get_flash();
$appName = app_name($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e($appName) ?></title>
    <link rel="stylesheet" href="/frontend/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <section class="auth-side">
            <h1><?= e($appName) ?></h1>
            
        </section>
        <section class="auth-form">
            <h2>Login</h2>

            <?php if (isset($noFarmError)): ?>
                <div class="alert alert-error"><?= e($noFarmError) ?></div>
            <?php endif; ?>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <form action="/backend/handlers/auth.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="login">
                <label>
                    Email Address
                    <input type="email" name="email" placeholder="farmer@example.com" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" placeholder="••••••••" required>
                </label>

                <!-- ADDED FORGOT PASSWORD LINK -->
                <a href="/frontend/forgot_password.php" style="display:block; margin-top:10px;">
                    Forgot Password?
                </a>

                <button type="submit">Sign In</button>
                <p>Don't have an account? <a style="text-decoration: none; "href="/frontend/register.php">Create Account</a></p>
            </form>
        </section>
    </div>
</div>
<script src="/frontend/assets/js/app.js"></script>
</body>
</html>