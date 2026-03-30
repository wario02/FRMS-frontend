<?php
require_once __DIR__ . '/../../backend/config/app.php';
$flash = get_flash();
$user = current_user();
$pdo = getPDO();
ensure_bootstrap_data($pdo);
$appName = app_name($pdo);
$subtitleText = get_setting($pdo, 'dashboard_welcome', 'Digitized crop, livestock, finance and inventory records.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= e($appName) ?></title>
    <link rel="stylesheet" href="/frontend/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div>
            <div class="brand-card">
                <h1>FRMS</h1>
                <p><?= e($appName) ?></p>
            </div>
            <?php if ($user): ?>
                <nav class="nav-menu">
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="/frontend/admin_dashboard.php">Admin Dashboard</a>
                        <a href="/frontend/admin_users.php">User Management</a>
                        <a href="/frontend/admin_backup.php">Backup & Restore</a>
                        <a href="/frontend/admin_settings.php">Settings</a>
                        <a href="/frontend/admin_reports.php">Admin Reports</a>
                        <a href="/frontend/admin_notices.php">Notice Center</a>
                    <?php else: ?>
                        <a href="/frontend/dashboard.php">Dashboard</a>
                        <a href="/frontend/crops.php">Crops</a>
                        <a href="/frontend/livestock.php">Livestock</a>
                        <a href="/frontend/finance.php">Finance</a>
                        <a href="/frontend/inventory.php">Inventory</a>
                        <a href="/frontend/tasks.php">Tasks</a>
                        <a href="/frontend/reports.php">Reports</a>
                    <?php endif; ?>
                    <a href="/frontend/logout.php">Logout</a>
                </nav>
            <?php endif; ?>
        </div>
        <div class="meta-note">
            <strong>Stack</strong>
            <span>Frontend: HTML/CSS/JS</span>
            <span>Backend: PHP + MySQL</span>
            <span>Security: Sessions + CSRF + Hashing</span>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div>
                <h2><?= e($pageTitle ?? $appName) ?></h2>
                <p class="subtitle"><?= e($subtitleText) ?></p>
            </div>
            <?php if ($user): ?>
                <div class="user-chip">
                    <strong><?= e($user['full_name']) ?></strong>
                    <span><?= e(ucfirst($user['role'])) ?> | <?= e(ucfirst($user['account_status'] ?? 'active')) ?></span>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
