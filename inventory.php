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
$categories = fetch_all($pdo->query('SELECT id, name FROM inventory_categories ORDER BY name ASC'));

$editInventory = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM inventory_items WHERE id = :id AND farm_id = :farm_id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit'], ':farm_id' => $farmId]);
    $editInventory = $stmt->fetch() ?: null;
}

$inventoryStmt = $pdo->prepare('SELECT ii.*, ic.name AS category_name, CASE WHEN ii.quantity_in_stock <= ii.reorder_level THEN 1 ELSE 0 END AS low_stock FROM inventory_items ii INNER JOIN inventory_categories ic ON ic.id = ii.inventory_category_id WHERE ii.farm_id = :farm_id ORDER BY ii.id DESC');
$inventoryStmt->execute([':farm_id' => $farmId]);
$items = fetch_all($inventoryStmt);

$pageTitle = 'Inventory Management';
require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card">
    <h3><?= $editInventory ? 'Edit Inventory Item' : 'Add Inventory Item' ?></h3>
    <form action="/backend/handlers/inventory.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_inventory">
        <input type="hidden" name="inventory_id" value="<?= e((string) ($editInventory['id'] ?? 0)) ?>">
        <div class="form-row">
            <label>
                Category
                <select name="inventory_category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>" <?= ((int) ($editInventory['inventory_category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Item Name
                <input type="text" name="item_name" value="<?= e($editInventory['item_name'] ?? '') ?>" required>
            </label>
        </div>
        <div class="form-row">
            <label>
                Unit
                <input type="text" name="unit" value="<?= e($editInventory['unit'] ?? '') ?>" placeholder="kg, bags, litres, pieces">
            </label>
            <label>
                Quantity in Stock
                <input type="number" step="0.01" min="0" name="quantity_in_stock" value="<?= e((string) ($editInventory['quantity_in_stock'] ?? '')) ?>">
            </label>
        </div>
        <div class="form-row">
            <label>
                Reorder Level
                <input type="number" step="0.01" min="0" name="reorder_level" value="<?= e((string) ($editInventory['reorder_level'] ?? '')) ?>">
            </label>
            <label>
                Unit Cost (KES)
                <input type="number" step="0.01" min="0" name="unit_cost" value="<?= e((string) ($editInventory['unit_cost'] ?? '')) ?>">
            </label>
        </div>
        <div class="inline-actions">
            <button type="submit">Save Inventory Item</button>
            <a class="btn secondary" href="/frontend/inventory.php">Reset</a>
        </div>
    </form>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Inventory Items</h3></div>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Item</th>
                <th>Unit</th>
                <th>Quantity</th>
                <th>Reorder Level</th>
                <th>Unit Cost</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$items): ?>
                <tr><td colspan="8">No inventory items found.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['category_name']) ?></td>
                        <td><?= e($item['item_name']) ?></td>
                        <td><?= e($item['unit']) ?></td>
                        <td><?= e((string) $item['quantity_in_stock']) ?></td>
                        <td><?= e((string) $item['reorder_level']) ?></td>
                        <td>KES <?= e(number_format((float) $item['unit_cost'], 2)) ?></td>
                        <td>
                            <?php if ((int) $item['low_stock'] === 1): ?>
                                <span class="badge">Low Stock</span>
                            <?php else: ?>
                                <span class="badge" style="background:#dcfce7;color:#166534;">OK</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="inline-actions">
                                <a class="btn secondary" href="/frontend/inventory.php?edit=<?= e((string) $item['id']) ?>">Edit</a>
                                <form action="/backend/handlers/inventory.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_inventory">
                                    <input type="hidden" name="inventory_id" value="<?= e((string) $item['id']) ?>">
                                    <button class="danger" data-confirm="Delete this inventory item?" type="submit">Delete</button>
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
