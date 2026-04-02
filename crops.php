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
$cropCategories = fetch_all($pdo->query('SELECT id, name FROM crop_categories ORDER BY name ASC'));

$editCrop = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM crops WHERE id = :id AND farm_id = :farm_id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit'], ':farm_id' => $farmId]);
    $editCrop = $stmt->fetch() ?: null;
}

$cropsStmt = $pdo->prepare('SELECT c.*, cc.name AS crop_name, COALESCE(SUM(h.yield_qty_kg),0) AS total_yield, COALESCE(SUM(h.total_revenue),0) AS total_revenue FROM crops c INNER JOIN crop_categories cc ON cc.id = c.crop_category_id LEFT JOIN harvests h ON h.crop_id = c.id WHERE c.farm_id = :farm_id GROUP BY c.id ORDER BY c.id DESC');
$cropsStmt->execute([':farm_id' => $farmId]);
$crops = fetch_all($cropsStmt);

$harvestStmt = $pdo->prepare('SELECT h.*, cc.name AS crop_name, c.season_name FROM harvests h INNER JOIN crops c ON c.id = h.crop_id INNER JOIN crop_categories cc ON cc.id = c.crop_category_id WHERE c.farm_id = :farm_id ORDER BY h.harvest_date DESC, h.id DESC');
$harvestStmt->execute([':farm_id' => $farmId]);
$harvests = fetch_all($harvestStmt);

$pageTitle = 'Crop Management';
require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card">
    <h3><?= $editCrop ? 'Edit Crop Record' : 'Add New Crop Record' ?></h3>
    <form action="/backend/handlers/crops.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_crop">
        <input type="hidden" name="crop_id" value="<?= e((string) ($editCrop['id'] ?? 0)) ?>">
        <div class="form-row">
            <label>
                Crop Category
                <select name="crop_category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($cropCategories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>" <?= ((int) ($editCrop['crop_category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Season Name
                <input type="text" name="season_name" value="<?= e($editCrop['season_name'] ?? '') ?>" placeholder="Long Rains 2026" required>
            </label>
        </div>
        <div class="form-row">
            <label>
                Planting Date
                <input type="date" name="planting_date" value="<?= e($editCrop['planting_date'] ?? '') ?>">
            </label>
            <label>
                Expected Harvest Date
                <input type="date" name="expected_harvest_date" value="<?= e($editCrop['expected_harvest_date'] ?? '') ?>">
            </label>
        </div>
        <div class="form-row">
            <label>
                Status
                <select name="status">
                    <?php foreach (['planned', 'planted', 'growing', 'harvested'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= (($editCrop['status'] ?? 'planned') === $status) ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Area Planted (Acres)
                <input type="number" step="0.01" min="0" name="area_planted_acres" value="<?= e((string) ($editCrop['area_planted_acres'] ?? '')) ?>">
            </label>
        </div>
        <label>
            Notes
            <textarea name="notes" placeholder="Any observations about the crop cycle..."><?= e($editCrop['notes'] ?? '') ?></textarea>
        </label>
        <div class="inline-actions">
            <button type="submit">Save Crop</button>
            <a class="btn secondary" href="/frontend/crops.php">Reset</a>
        </div>
    </form>
</section>

<section class="form-card">
    <h3>Add Harvest Record</h3>
    <form action="/backend/handlers/crops.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_harvest">
        <input type="hidden" name="harvest_id" value="0">
        <div class="form-row">
            <label>
                Crop
                <select name="crop_id" required>
                    <option value="">Select Crop</option>
                    <?php foreach ($crops as $crop): ?>
                        <option value="<?= e((string) $crop['id']) ?>"><?= e($crop['crop_name'] . ' - ' . $crop['season_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Harvest Date
                <input type="date" name="harvest_date">
            </label>
        </div>
        <div class="form-row">
            <label>
                Yield (KG)
                <input type="number" step="0.01" min="0" name="yield_qty_kg">
            </label>
            <label>
                Selling Price per KG (KES)
                <input type="number" step="0.01" min="0" name="selling_price_per_kg">
            </label>
        </div>
        <label>
            Total Revenue (KES)
            <input type="number" step="0.01" min="0" name="total_revenue">
        </label>
        <button type="submit">Save Harvest</button>
    </form>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Crop Records</h3></div>
    <table>
        <thead>
            <tr>
                <th>Crop</th>
                <th>Season</th>
                <th>Status</th>
                <th>Area</th>
                <th>Planting</th>
                <th>Expected Harvest</th>
                <th>Total Yield</th>
                <th>Total Revenue</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$crops): ?>
                <tr><td colspan="9">No crop records found.</td></tr>
            <?php else: ?>
                <?php foreach ($crops as $crop): ?>
                    <tr>
                        <td><?= e($crop['crop_name']) ?></td>
                        <td><?= e($crop['season_name']) ?></td>
                        <td><span class="badge"><?= e(ucfirst($crop['status'])) ?></span></td>
                        <td><?= e((string) $crop['area_planted_acres']) ?> acres</td>
                        <td><?= e($crop['planting_date']) ?></td>
                        <td><?= e($crop['expected_harvest_date']) ?></td>
                        <td><?= e(number_format((float) $crop['total_yield'], 2)) ?> KG</td>
                        <td>KES <?= e(number_format((float) $crop['total_revenue'], 2)) ?></td>
                        <td>
                            <div class="inline-actions">
                                <a class="btn secondary" href="/frontend/crops.php?edit=<?= e((string) $crop['id']) ?>">Edit</a>
                                <form action="/backend/handlers/crops.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_crop">
                                    <input type="hidden" name="crop_id" value="<?= e((string) $crop['id']) ?>">
                                    <button class="danger" data-confirm="Delete this crop record?" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Harvest Records</h3></div>
    <table>
        <thead>
            <tr>
                <th>Crop</th>
                <th>Season</th>
                <th>Date</th>
                <th>Yield</th>
                <th>Price/KG</th>
                <th>Revenue</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$harvests): ?>
                <tr><td colspan="7">No harvest records found.</td></tr>
            <?php else: ?>
                <?php foreach ($harvests as $harvest): ?>
                    <tr>
                        <td><?= e($harvest['crop_name']) ?></td>
                        <td><?= e($harvest['season_name']) ?></td>
                        <td><?= e($harvest['harvest_date']) ?></td>
                        <td><?= e(number_format((float) $harvest['yield_qty_kg'], 2)) ?> KG</td>
                        <td>KES <?= e(number_format((float) $harvest['selling_price_per_kg'], 2)) ?></td>
                        <td>KES <?= e(number_format((float) $harvest['total_revenue'], 2)) ?></td>
                        <td>
                            <form action="/backend/handlers/crops.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_harvest">
                                <input type="hidden" name="harvest_id" value="<?= e((string) $harvest['id']) ?>">
                                <button class="danger" data-confirm="Delete this harvest record?" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
