<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

$user = require_login();
$pdo = getPDO();
$farm = get_user_farm($pdo, (int) $user['id']);

if (!$farm) {
    set_flash('error', 'Farm profile not found.');
    redirect('/frontend/dashboard.php');
}

$farmId = (int) $farm['id'];
$categories = fetch_all($pdo->query('SELECT id, name, type FROM financial_categories ORDER BY type ASC, name ASC'));

$editTransaction = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM financial_transactions WHERE id = :id AND farm_id = :farm_id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit'], ':farm_id' => $farmId]);
    $editTransaction = $stmt->fetch() ?: null;
}

$transactionsStmt = $pdo->prepare('SELECT ft.*, fc.name AS category_name, fc.type FROM financial_transactions ft INNER JOIN financial_categories fc ON fc.id = ft.financial_category_id WHERE ft.farm_id = :farm_id ORDER BY ft.transaction_date DESC, ft.id DESC');
$transactionsStmt->execute([':farm_id' => $farmId]);
$transactions = fetch_all($transactionsStmt);

$summaryStmt = $pdo->prepare('SELECT COALESCE(SUM(CASE WHEN fc.type = "income" THEN ft.amount END), 0) AS income_total, COALESCE(SUM(CASE WHEN fc.type = "expense" THEN ft.amount END), 0) AS expense_total FROM financial_transactions ft INNER JOIN financial_categories fc ON fc.id = ft.financial_category_id WHERE ft.farm_id = :farm_id');
$summaryStmt->execute([':farm_id' => $farmId]);
$summary = $summaryStmt->fetch() ?: ['income_total' => 0, 'expense_total' => 0];

$pageTitle = 'Financial Management';
require_once __DIR__ . '/includes/header.php';
?>

<section class="card-grid">
    <div class="card">
        <h3>Total Income</h3>
        <div class="metric">KES <?= e(number_format((float) $summary['income_total'], 2)) ?></div>
    </div>
    <div class="card">
        <h3>Total Expense</h3>
        <div class="metric">KES <?= e(number_format((float) $summary['expense_total'], 2)) ?></div>
    </div>
    <div class="card">
        <h3>Net Position</h3>
        <div class="metric">KES <?= e(number_format((float) $summary['income_total'] - (float) $summary['expense_total'], 2)) ?></div>
    </div>
</section>

<section class="form-card">
    <h3><?= $editTransaction ? 'Edit Transaction' : 'Add Transaction' ?></h3>
    <form action="/backend/handlers/finance.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_transaction">
        <input type="hidden" name="transaction_id" value="<?= e((string) ($editTransaction['id'] ?? 0)) ?>">
        <div class="form-row">
            <label>
                Category
                <select name="financial_category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>" <?= ((int) ($editTransaction['financial_category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e($category['name'] . ' (' . ucfirst($category['type']) . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Transaction Date
                <input type="date" name="transaction_date" value="<?= e($editTransaction['transaction_date'] ?? '') ?>">
            </label>
        </div>
        <div class="form-row">
            <label>
                Title
                <input type="text" name="title" value="<?= e($editTransaction['title'] ?? '') ?>" placeholder="Milk sales for week 1" required>
            </label>
            <label>
                Amount (KES)
                <input type="number" step="0.01" min="0" name="amount" value="<?= e((string) ($editTransaction['amount'] ?? '')) ?>" required>
            </label>
        </div>
        <label>
            Notes
            <textarea name="notes" placeholder="Optional remarks or references"><?= e($editTransaction['notes'] ?? '') ?></textarea>
        </label>
        <div class="inline-actions">
            <button type="submit">Save Transaction</button>
            <a class="btn secondary" href="/frontend/finance.php">Reset</a>
        </div>
    </form>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Financial Transactions</h3></div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Title</th>
                <th>Category</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$transactions): ?>
                <tr><td colspan="7">No transactions found.</td></tr>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= e($transaction['transaction_date']) ?></td>
                        <td><?= e($transaction['title']) ?></td>
                        <td><?= e($transaction['category_name']) ?></td>
                        <td><span class="badge"><?= e(ucfirst($transaction['type'])) ?></span></td>
                        <td>KES <?= e(number_format((float) $transaction['amount'], 2)) ?></td>
                        <td><?= e($transaction['notes']) ?></td>
                        <td>
                            <div class="inline-actions">
                                <a class="btn secondary" href="/frontend/finance.php?edit=<?= e((string) $transaction['id']) ?>">Edit</a>
                                <form action="/backend/handlers/finance.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_transaction">
                                    <input type="hidden" name="transaction_id" value="<?= e((string) $transaction['id']) ?>">
                                    <button class="danger" data-confirm="Delete this transaction?" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
