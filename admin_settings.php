<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

$admin = require_admin();
$pdo = getPDO();
$pageTitle = 'System Settings';
$settings = [
    'system_name' => get_setting($pdo, 'system_name', 'Farm Record Management System'),
    'support_email' => get_setting($pdo, 'support_email', 'support@frms.local'),
    'currency_code' => get_setting($pdo, 'currency_code', 'KES'),
    'dashboard_welcome' => get_setting($pdo, 'dashboard_welcome', 'Digitized crop, livestock, finance and inventory records.'),
    'allow_self_registration' => get_setting($pdo, 'allow_self_registration', '1'),
    'low_stock_alert_enabled' => get_setting($pdo, 'low_stock_alert_enabled', '1'),
];
require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card">
    <h3>Platform Configuration</h3>
    <form action="/backend/handlers/admin_settings.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_settings">
        <div class="form-row">
            <label>System Name<input type="text" name="system_name" value="<?= e($settings['system_name']) ?>" required></label>
            <label>Support Email<input type="email" name="support_email" value="<?= e($settings['support_email']) ?>"></label>
        </div>
        <div class="form-row">
            <label>Currency Code<input type="text" name="currency_code" value="<?= e($settings['currency_code']) ?>" maxlength="10" required></label>
            <label>Dashboard Welcome Message<input type="text" name="dashboard_welcome" value="<?= e($settings['dashboard_welcome']) ?>"></label>
        </div>
        <div class="status-grid">
            <label><input type="checkbox" name="allow_self_registration" value="1" <?= $settings['allow_self_registration'] === '1' ? 'checked' : '' ?>> Allow self registration</label>
            <label><input type="checkbox" name="low_stock_alert_enabled" value="1" <?= $settings['low_stock_alert_enabled'] === '1' ? 'checked' : '' ?>> Enable low stock alerts</label>
        </div>
        <button type="submit">Save Settings</button>
    </form>
</section>

<section class="card-grid">
    <div class="card">
        <h3>Operational Notes</h3>
        <ul>
            <li>Disable self registration if only admins should create accounts.</li>
            <li>Set the support email users can contact when deactivated.</li>
            <li>Use the welcome message to adapt the dashboard subtitle to your institution or project title.</li>
        </ul>
    </div>
    <div class="card">
        <h3>Admin Reminder</h3>
        <p class="muted">After changing settings, reload the application to see the updated branding and controls reflected across login and dashboard pages.</p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
