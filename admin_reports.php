<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

$admin = require_admin();
$pdo = getPDO();
$pageTitle = 'Admin Reports';

$userRoleSummary = fetch_all($pdo->query('SELECT role, account_status, COUNT(*) AS total_users FROM users GROUP BY role, account_status ORDER BY role, account_status'));
$farmFinanceSummary = fetch_all($pdo->query('SELECT f.farm_name, u.full_name, COALESCE(SUM(CASE WHEN fc.type = "income" THEN ft.amount ELSE 0 END),0) AS income_total, COALESCE(SUM(CASE WHEN fc.type = "expense" THEN ft.amount ELSE 0 END),0) AS expense_total FROM farms f INNER JOIN users u ON u.id = f.user_id LEFT JOIN financial_transactions ft ON ft.farm_id = f.id LEFT JOIN financial_categories fc ON fc.id = ft.financial_category_id GROUP BY f.id, f.farm_name, u.full_name ORDER BY income_total DESC'));
$cropAnalytics = fetch_all($pdo->query('SELECT cc.name AS crop_name, COUNT(c.id) AS crop_cycles, COALESCE(SUM(h.yield_qty_kg),0) AS total_yield FROM crops c INNER JOIN crop_categories cc ON cc.id = c.crop_category_id LEFT JOIN harvests h ON h.crop_id = c.id GROUP BY cc.id, cc.name ORDER BY total_yield DESC'));
$taskAnalytics = fetch_all($pdo->query('SELECT task_type, status, COUNT(*) AS total_tasks FROM farm_tasks GROUP BY task_type, status ORDER BY task_type, status'));
$backupLogs = fetch_all($pdo->query('SELECT bl.file_name, bl.backup_type, bl.created_at, u.full_name FROM backup_logs bl LEFT JOIN users u ON u.id = bl.initiated_by ORDER BY bl.id DESC LIMIT 12'));
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-box">
        <h3>Cross-System Analytics</h3>
        <p>These reports summarize user activity, farm financial performance, crop output, task execution and backup governance across the entire FRMS platform.</p>
    </div>
    <div class="card">
        <h3>Coverage Areas</h3>
        <ul>
            <li>Account status and user role distribution</li>
            <li>Farm financial position by owner</li>
            <li>Crop cycle and yield comparisons</li>
            <li>Task management execution overview</li>
        </ul>
    </div>
</section>

<section class="table-card">
    <div class="section-heading"><h3>User Role & Status Summary</h3></div>
    <table>
        <thead><tr><th>Role</th><th>Status</th><th>Total Users</th></tr></thead>
        <tbody>
            <?php if (!$userRoleSummary): ?><tr><td colspan="3">No user summary data.</td></tr><?php else: ?>
                <?php foreach ($userRoleSummary as $row): ?>
                    <tr><td><?= e(ucfirst($row['role'])) ?></td><td><?= e(ucfirst($row['account_status'])) ?></td><td><?= e((string) $row['total_users']) ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Farm Financial Overview</h3></div>
    <table>
        <thead><tr><th>Farm</th><th>Owner</th><th>Total Income</th><th>Total Expense</th><th>Net Position</th></tr></thead>
        <tbody>
            <?php if (!$farmFinanceSummary): ?><tr><td colspan="5">No farm finance data.</td></tr><?php else: ?>
                <?php foreach ($farmFinanceSummary as $row): ?>
                    <tr>
                        <td><?= e($row['farm_name']) ?></td>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e(format_money($pdo, (float) $row['income_total'])) ?></td>
                        <td><?= e(format_money($pdo, (float) $row['expense_total'])) ?></td>
                        <td><?= e(format_money($pdo, (float) $row['income_total'] - (float) $row['expense_total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="card-grid">
    <div class="table-card">
        <div class="section-heading"><h3>Crop Analytics</h3></div>
        <table>
            <thead><tr><th>Crop</th><th>Crop Cycles</th><th>Total Yield (KG)</th></tr></thead>
            <tbody>
                <?php if (!$cropAnalytics): ?><tr><td colspan="3">No crop analytics data.</td></tr><?php else: ?>
                    <?php foreach ($cropAnalytics as $row): ?>
                        <tr><td><?= e($row['crop_name']) ?></td><td><?= e((string) $row['crop_cycles']) ?></td><td><?= e(number_format((float) $row['total_yield'], 2)) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-card">
        <div class="section-heading"><h3>Task Analytics</h3></div>
        <table>
            <thead><tr><th>Task Type</th><th>Status</th><th>Total Tasks</th></tr></thead>
            <tbody>
                <?php if (!$taskAnalytics): ?><tr><td colspan="3">No task analytics data.</td></tr><?php else: ?>
                    <?php foreach ($taskAnalytics as $row): ?>
                        <tr><td><?= e($row['task_type']) ?></td><td><?= e(ucwords(str_replace('_', ' ', $row['status']))) ?></td><td><?= e((string) $row['total_tasks']) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Backup Governance Log</h3></div>
    <table>
        <thead><tr><th>File</th><th>Type</th><th>User</th><th>Timestamp</th></tr></thead>
        <tbody>
            <?php if (!$backupLogs): ?><tr><td colspan="4">No backup history found.</td></tr><?php else: ?>
                <?php foreach ($backupLogs as $row): ?>
                    <tr><td><?= e($row['file_name']) ?></td><td><?= e($row['backup_type']) ?></td><td><?= e($row['full_name'] ?: 'System') ?></td><td><?= e($row['created_at']) ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
