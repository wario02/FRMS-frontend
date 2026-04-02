<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

// 1. Ensure user is logged in (handled by middleware)
$user = require_role(['farmer', 'officer']);
$pdo = getPDO();

// 2. Fetch Farm Data
$farm = get_user_farm($pdo, (int) $user['id']);

// FIX: If no farm exists, do NOT redirect to index.php (this causes the loop).
// Either show a message or redirect to a dedicated "Create Farm" page if you have one.
if (!$farm) {
    $pageTitle = 'Setup Required';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="hero">
        <div class="hero-box" style="border-left: 4px solid #e74c3c;">
            <h3>Farm Profile Missing</h3>
            <p>We couldn't find a farm profile linked to your account. You need a farm profile to access the dashboard metrics.</p>
            <div style="margin-top: 20px;">
                <a href="/frontend/settings.php" class="btn">Go to Settings</a>
                <a href="/frontend/logout.php" class="btn secondary">Logout</a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit; // Stop execution here
}

$pageTitle = 'Farmer Dashboard';
require_once __DIR__ . '/includes/header.php';

$farmId = (int) $farm['id'];

// 3. Optimized Metrics Query
// 3. Optimized Metrics Query
$metricsStmt = $pdo->prepare(
    'SELECT 
        (SELECT COUNT(*) FROM crops WHERE farm_id = :f1) AS crop_count,
        (SELECT COUNT(*) FROM livestock WHERE farm_id = :f2) AS livestock_count,
        (SELECT COUNT(*) FROM inventory_items WHERE farm_id = :f3) AS inventory_count,
        (SELECT COUNT(*) FROM farm_tasks WHERE farm_id = :f4 AND status <> "completed") AS open_tasks,
        (SELECT COALESCE(SUM(CASE WHEN fc.type = "income" THEN ft.amount ELSE 0 END),0) FROM financial_transactions ft INNER JOIN financial_categories fc ON fc.id = ft.financial_category_id WHERE ft.farm_id = :f5) AS total_income,
        (SELECT COALESCE(SUM(CASE WHEN fc.type = "expense" THEN ft.amount ELSE 0 END),0) FROM financial_transactions ft INNER JOIN financial_categories fc ON fc.id = ft.financial_category_id WHERE ft.farm_id = :f6) AS total_expense,
        (SELECT COALESCE(SUM(h.yield_qty_kg),0) FROM harvests h INNER JOIN crops c ON c.id = h.crop_id WHERE c.farm_id = :f7) AS total_yield'
);

// We pass 7 unique keys to match the 7 subqueries
$metricsStmt->execute([
    ':f1' => $farmId,
    ':f2' => $farmId,
    ':f3' => $farmId,
    ':f4' => $farmId,
    ':f5' => $farmId,
    ':f6' => $farmId,
    ':f7' => $farmId
]);
$metrics = $metricsStmt->fetch() ?: [];
$metrics = $metricsStmt->fetch() ?: [];

// 4. Fetch Recent Data
$recentTransactions = $pdo->prepare('SELECT ft.title, ft.amount, ft.transaction_date, fc.name AS category_name, fc.type FROM financial_transactions ft INNER JOIN financial_categories fc ON fc.id = ft.financial_category_id WHERE ft.farm_id = :farm_id ORDER BY ft.transaction_date DESC, ft.id DESC LIMIT 5');
$recentTransactions->execute([':farm_id' => $farmId]);
$transactions = fetch_all($recentTransactions);

$lowStockStmt = $pdo->prepare('SELECT item_name, quantity_in_stock, reorder_level, unit FROM inventory_items WHERE farm_id = :farm_id AND quantity_in_stock <= reorder_level ORDER BY quantity_in_stock ASC LIMIT 5');
$lowStockStmt->execute([':farm_id' => $farmId]);
$lowStocks = fetch_all($lowStockStmt);

$tasksStmt = $pdo->prepare('SELECT title, task_type, due_date, priority, status FROM farm_tasks WHERE farm_id = :farm_id ORDER BY CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC, id DESC LIMIT 6');
$tasksStmt->execute([':farm_id' => $farmId]);
$tasks = fetch_all($tasksStmt);

$audience = $user['role'] === 'officer' ? 'officer' : 'farmer';
$notices = get_notices($pdo, $audience, 4);
?>

<section class="hero">
    <div class="hero-box">
        <h3><?= e($farm['farm_name']) ?></h3>
        <p class="muted">Location: <?= e($farm['location'] ?: 'Not specified') ?> | Size: <?= e((string) $farm['farm_size_acres']) ?> acres</p>
        <p style="margin-top: 12px; line-height: 1.7;">This dashboard combines crop production, livestock records, finance tracking, inventory management and the new farm task planner into one operational workspace.</p>
    </div>
    <div class="card">
        <h3>Quick Actions</h3>
        <div class="inline-actions" style="margin-top: 12px;">
            <a class="btn" href="/frontend/crops.php">Add Crop</a>
            <a class="btn secondary" href="/frontend/livestock.php">Add Livestock</a>
            <a class="btn" href="/frontend/finance.php">Record Finance</a>
            <a class="btn secondary" href="/frontend/tasks.php">Plan Task</a>
        </div>
    </div>
</section>

<section class="card-grid">
    <div class="card"><h3>Crop Records</h3><div class="metric"><?= e((string) ($metrics['crop_count'] ?? 0)) ?></div></div>
    <div class="card"><h3>Livestock Records</h3><div class="metric"><?= e((string) ($metrics['livestock_count'] ?? 0)) ?></div></div>
    <div class="card"><h3>Inventory Items</h3><div class="metric"><?= e((string) ($metrics['inventory_count'] ?? 0)) ?></div></div>
    <div class="card"><h3>Open Tasks</h3><div class="metric"><?= e((string) ($metrics['open_tasks'] ?? 0)) ?></div></div>
    <div class="card"><h3>Net Position</h3><div class="metric"><?= e(format_money($pdo, (float) ($metrics['total_income'] ?? 0) - (float) ($metrics['total_expense'] ?? 0))) ?></div></div>
</section>

<section class="card-grid">
    <div class="table-card">
        <div class="section-heading"><h3>Recent Transactions</h3></div>
        <table>
            <thead><tr><th>Title</th><th>Category</th><th>Type</th><th>Date</th><th>Amount</th></tr></thead>
            <tbody>
                <?php if (!$transactions): ?>
                    <tr><td colspan="5">No transactions recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= e($transaction['title']) ?></td>
                            <td><?= e($transaction['category_name']) ?></td>
                            <td><span class="badge"><?= e(ucfirst($transaction['type'])) ?></span></td>
                            <td><?= e($transaction['transaction_date']) ?></td>
                            <td><?= e(format_money($pdo, (float) $transaction['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Low Stock Alerts</h3>
        <div class="kpi-list" style="margin-top: 14px;">
            <?php if (!$lowStocks): ?>
                <div>No low-stock alerts. Inventory levels look healthy.</div>
            <?php else: ?>
                <?php foreach ($lowStocks as $item): ?>
                    <div>
                        <strong><?= e($item['item_name']) ?></strong><br>
                        <span class="muted">Stock: <?= e((string) $item['quantity_in_stock']) ?> <?= e($item['unit']) ?> | Reorder: <?= e((string) $item['reorder_level']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="card-grid">
    <div class="card">
        <h3>Upcoming Task Planner</h3>
        <div class="kpi-list" style="margin-top:14px;">
            <?php if (!$tasks): ?>
                <div>No tasks scheduled yet.</div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div>
                        <strong><?= e($task['title']) ?></strong><br>
                        <span class="muted"><?= e($task['task_type']) ?> | Due: <?= e($task['due_date'] ?: 'N/A') ?> | Priority: <?= e(ucfirst($task['priority'])) ?> | Status: <?= e(ucfirst($task['status'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <h3>System Notices</h3>
        <div class="kpi-list" style="margin-top:14px;">
            <?php if (!$notices): ?>
                <div>No active notices for your account.</div>
            <?php else: ?>
                <?php foreach ($notices as $notice): ?>
                    <div class="notice-item">
                        <strong><?= e($notice['title']) ?></strong>
                        <p class="small-text muted" style="margin-top: 8px;"><?= nl2br(e($notice['message'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>