<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

$admin = require_admin();
$pdo = getPDO();
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/includes/header.php';

$metrics = $pdo->query('SELECT 
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM users WHERE account_status = "active") AS active_users,
    (SELECT COUNT(*) FROM users WHERE account_status = "inactive") AS inactive_users,
    (SELECT COUNT(*) FROM farms) AS total_farms,
    (SELECT COUNT(*) FROM backup_logs WHERE backup_type = "manual_backup") AS total_backups,
    (SELECT COUNT(*) FROM system_notices WHERE status = "active") AS active_notices,
    (SELECT COUNT(*) FROM farm_tasks WHERE status <> "completed") AS open_tasks,
    (SELECT COALESCE(SUM(CASE WHEN fc.type = "income" THEN ft.amount ELSE 0 END),0) FROM financial_transactions ft INNER JOIN financial_categories fc ON fc.id = ft.financial_category_id) AS total_income,
    (SELECT COALESCE(SUM(CASE WHEN fc.type = "expense" THEN ft.amount ELSE 0 END),0) FROM financial_transactions ft INNER JOIN financial_categories fc ON fc.id = ft.financial_category_id) AS total_expense')->fetch() ?: [];

$recentUsers = fetch_all($pdo->query('SELECT full_name, email, role, account_status, created_at FROM users ORDER BY id DESC LIMIT 6'));
$recentBackups = fetch_all($pdo->query('SELECT file_name, backup_type, created_at FROM backup_logs ORDER BY id DESC LIMIT 5'));
$notices = get_notices($pdo, 'all', 5);
?>

<section class="hero">
    <div class="hero-box">
        <h3>Administrative Control Panel</h3>
        <p>Manage users, backup and restore the database, publish notices, update system settings, and review cross-farm analytics from this dashboard.</p>
    </div>
    <div class="card">
        <h3>Quick Actions</h3>
        <div class="inline-actions" style="margin-top:12px;">
            <a class="btn" href="/frontend/admin_users.php">Create User</a>
            <a class="btn secondary" href="/frontend/admin_backup.php">Run Backup</a>
            <a class="btn warning" href="/frontend/admin_notices.php">Publish Notice</a>
        </div>
    </div>
</section>

<section class="card-grid">
    <div class="card"><h3>Total Users</h3><div class="metric"><?= e((string) ($metrics['total_users'] ?? 0)) ?></div></div>
    <div class="card"><h3>Active Users</h3><div class="metric"><?= e((string) ($metrics['active_users'] ?? 0)) ?></div></div>
    <div class="card"><h3>Inactive Users</h3><div class="metric"><?= e((string) ($metrics['inactive_users'] ?? 0)) ?></div></div>
    <div class="card"><h3>Total Farms</h3><div class="metric"><?= e((string) ($metrics['total_farms'] ?? 0)) ?></div></div>
    <div class="card"><h3>Database Backups</h3><div class="metric"><?= e((string) ($metrics['total_backups'] ?? 0)) ?></div></div>
    <div class="card"><h3>Active Notices</h3><div class="metric"><?= e((string) ($metrics['active_notices'] ?? 0)) ?></div></div>
    <div class="card"><h3>Open Farm Tasks</h3><div class="metric"><?= e((string) ($metrics['open_tasks'] ?? 0)) ?></div></div>
    <div class="card"><h3>System Net Position</h3><div class="metric"><?= e(format_money($pdo, (float) ($metrics['total_income'] ?? 0) - (float) ($metrics['total_expense'] ?? 0))) ?></div></div>
</section>

<section class="card-grid">
    <div class="table-card">
        <div class="section-heading"><h3>Recently Registered Users</h3></div>
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
                <?php if (!$recentUsers): ?><tr><td colspan="5">No users found.</td></tr><?php else: ?>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?= e($user['full_name']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e(ucfirst($user['role'])) ?></td>
                            <td><span class="badge <?= e($user['account_status'] === 'active' ? 'success' : 'danger') ?>"><?= e(ucfirst($user['account_status'])) ?></span></td>
                            <td><?= e($user['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Recent Notices</h3>
        <div class="kpi-list" style="margin-top:14px;">
            <?php if (!$notices): ?>
                <div>No active notices published.</div>
            <?php else: ?>
                <?php foreach ($notices as $notice): ?>
                    <div class="notice-item">
                        <strong><?= e($notice['title']) ?></strong>
                        <p class="small-text muted" style="margin-top:8px;"><?= nl2br(e($notice['message'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Recent Backup Activity</h3></div>
    <table>
        <thead><tr><th>File</th><th>Type</th><th>Created</th></tr></thead>
        <tbody>
            <?php if (!$recentBackups): ?><tr><td colspan="3">No backup activity recorded yet.</td></tr><?php else: ?>
                <?php foreach ($recentBackups as $backup): ?>
                    <tr><td><?= e($backup['file_name']) ?></td><td><?= e($backup['backup_type']) ?></td><td><?= e($backup['created_at']) ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
