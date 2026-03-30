<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

$user = require_role(['farmer', 'officer']);
$pdo = getPDO();
$farm = get_user_farm($pdo, (int) $user['id']);

if (!$farm) {
    set_flash('error', 'Farm profile not found.');
    redirect('/frontend/dashboard.php');
}

$farmId = (int) $farm['id'];

$cropSummaryStmt = $pdo->prepare('SELECT cc.name AS crop_name, COUNT(c.id) AS crop_cycles, COALESCE(SUM(h.yield_qty_kg),0) AS total_yield, COALESCE(SUM(h.total_revenue),0) AS total_revenue FROM crops c INNER JOIN crop_categories cc ON cc.id = c.crop_category_id LEFT JOIN harvests h ON h.crop_id = c.id WHERE c.farm_id = :farm_id GROUP BY cc.id, cc.name ORDER BY total_yield DESC');
$cropSummaryStmt->execute([':farm_id' => $farmId]);
$cropSummary = fetch_all($cropSummaryStmt);

$livestockSummaryStmt = $pdo->prepare('SELECT lc.name AS category_name, COUNT(l.id) AS total_animals, SUM(CASE WHEN l.status = "active" THEN 1 ELSE 0 END) AS active_animals FROM livestock l INNER JOIN livestock_categories lc ON lc.id = l.livestock_category_id WHERE l.farm_id = :farm_id GROUP BY lc.id, lc.name ORDER BY total_animals DESC');
$livestockSummaryStmt->execute([':farm_id' => $farmId]);
$livestockSummary = fetch_all($livestockSummaryStmt);

$financeSummaryStmt = $pdo->prepare('SELECT fc.type, fc.name AS category_name, COALESCE(SUM(ft.amount),0) AS amount_total FROM financial_transactions ft INNER JOIN financial_categories fc ON fc.id = ft.financial_category_id WHERE ft.farm_id = :farm_id GROUP BY fc.id, fc.name, fc.type ORDER BY fc.type, amount_total DESC');
$financeSummaryStmt->execute([':farm_id' => $farmId]);
$financeSummary = fetch_all($financeSummaryStmt);

$taskSummaryStmt = $pdo->prepare('SELECT status, COUNT(*) AS total_tasks FROM farm_tasks WHERE farm_id = :farm_id GROUP BY status ORDER BY total_tasks DESC');
$taskSummaryStmt->execute([':farm_id' => $farmId]);
$taskSummary = fetch_all($taskSummaryStmt);

$auditStmt = $pdo->prepare('SELECT action, table_name, record_id, created_at FROM audit_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10');
$auditStmt->execute([':user_id' => (int) $user['id']]);
$auditLogs = fetch_all($auditStmt);

$pageTitle = 'Reports & Audit Trail';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-box">
        <h3>FRMS Reporting Center</h3>
        <p>These report sections support evidence-based decisions on crop yields, livestock activities, finances and the new task planner module.</p>
    </div>
    <div class="card">
        <h3>Normalization Highlights</h3>
        <ul>
            <li>Reference tables store reusable categories.</li>
            <li>Health records are separated from livestock master records.</li>
            <li>Harvest data is separated from crop planning data.</li>
            <li>Task planner records are separated from production and financial transactions.</li>
        </ul>
    </div>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Crop Performance Summary</h3></div>
    <table>
        <thead><tr><th>Crop</th><th>Crop Cycles</th><th>Total Yield (KG)</th><th>Total Revenue</th></tr></thead>
        <tbody>
            <?php if (!$cropSummary): ?><tr><td colspan="4">No crop summary data yet.</td></tr><?php else: ?>
                <?php foreach ($cropSummary as $row): ?>
                    <tr><td><?= e($row['crop_name']) ?></td><td><?= e((string) $row['crop_cycles']) ?></td><td><?= e(number_format((float) $row['total_yield'], 2)) ?></td><td><?= e(format_money($pdo, (float) $row['total_revenue'])) ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Livestock Summary</h3></div>
    <table>
        <thead><tr><th>Category</th><th>Total Animals</th><th>Active Animals</th></tr></thead>
        <tbody>
            <?php if (!$livestockSummary): ?><tr><td colspan="3">No livestock summary data yet.</td></tr><?php else: ?>
                <?php foreach ($livestockSummary as $row): ?>
                    <tr><td><?= e($row['category_name']) ?></td><td><?= e((string) $row['total_animals']) ?></td><td><?= e((string) $row['active_animals']) ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="card-grid">
    <div class="table-card">
        <div class="section-heading"><h3>Financial Summary by Category</h3></div>
        <table>
            <thead><tr><th>Type</th><th>Category</th><th>Total Amount</th></tr></thead>
            <tbody>
                <?php if (!$financeSummary): ?><tr><td colspan="3">No financial summary data yet.</td></tr><?php else: ?>
                    <?php foreach ($financeSummary as $row): ?>
                        <tr><td><span class="badge"><?= e(ucfirst($row['type'])) ?></span></td><td><?= e($row['category_name']) ?></td><td><?= e(format_money($pdo, (float) $row['amount_total'])) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-card">
        <div class="section-heading"><h3>Task Summary</h3></div>
        <table>
            <thead><tr><th>Status</th><th>Total Tasks</th></tr></thead>
            <tbody>
                <?php if (!$taskSummary): ?><tr><td colspan="2">No task summary data yet.</td></tr><?php else: ?>
                    <?php foreach ($taskSummary as $row): ?>
                        <tr><td><?= e(ucwords(str_replace('_', ' ', $row['status']))) ?></td><td><?= e((string) $row['total_tasks']) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Recent Audit Trail</h3></div>
    <table>
        <thead><tr><th>Action</th><th>Table</th><th>Record ID</th><th>Timestamp</th></tr></thead>
        <tbody>
            <?php if (!$auditLogs): ?><tr><td colspan="4">No audit logs found.</td></tr><?php else: ?>
                <?php foreach ($auditLogs as $log): ?>
                    <tr><td><?= e($log['action']) ?></td><td><?= e($log['table_name']) ?></td><td><?= e((string) $log['record_id']) ?></td><td><?= e($log['created_at']) ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
