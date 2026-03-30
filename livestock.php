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
$categories = fetch_all($pdo->query('SELECT id, name FROM livestock_categories ORDER BY name ASC'));

$editLivestock = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM livestock WHERE id = :id AND farm_id = :farm_id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit'], ':farm_id' => $farmId]);
    $editLivestock = $stmt->fetch() ?: null;
}

$livestockStmt = $pdo->prepare('SELECT l.*, lc.name AS category_name FROM livestock l INNER JOIN livestock_categories lc ON lc.id = l.livestock_category_id WHERE l.farm_id = :farm_id ORDER BY l.id DESC');
$livestockStmt->execute([':farm_id' => $farmId]);
$livestock = fetch_all($livestockStmt);

$healthStmt = $pdo->prepare('SELECT hr.*, l.tag_number, lc.name AS category_name FROM livestock_health_records hr INNER JOIN livestock l ON l.id = hr.livestock_id INNER JOIN livestock_categories lc ON lc.id = l.livestock_category_id WHERE l.farm_id = :farm_id ORDER BY hr.record_date DESC, hr.id DESC');
$healthStmt->execute([':farm_id' => $farmId]);
$healthRecords = fetch_all($healthStmt);

$pageTitle = 'Livestock Management';
require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card">
    <h3><?= $editLivestock ? 'Edit Livestock Record' : 'Add Livestock Record' ?></h3>
    <p class="muted">Track animal profiles including breed, tag number, gender and status.</p>
    <form action="/backend/handlers/livestock.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_livestock">
        <input type="hidden" name="livestock_id" value="<?= e((string) ($editLivestock['id'] ?? 0)) ?>">
        <div class="form-row">
            <label>
                Category
                <select name="livestock_category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>" <?= ((int) ($editLivestock['livestock_category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Breed
                <input type="text" name="breed" value="<?= e($editLivestock['breed'] ?? '') ?>" placeholder="Friesian">
            </label>
        </div>
        <div class="form-row">
            <label>
                Tag Number
                <input type="text" name="tag_number" value="<?= e($editLivestock['tag_number'] ?? '') ?>" required>
            </label>
            <label>
                Gender
                <select name="gender">
                    <?php foreach (['Male', 'Female', 'Unknown'] as $gender): ?>
                        <option value="<?= e($gender) ?>" <?= (($editLivestock['gender'] ?? 'Unknown') === $gender) ? 'selected' : '' ?>><?= e($gender) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <label>
                Birth Date
                <input type="date" name="birth_date" value="<?= e($editLivestock['birth_date'] ?? '') ?>">
            </label>
            <label>
                Acquisition Date
                <input type="date" name="acquisition_date" value="<?= e($editLivestock['acquisition_date'] ?? '') ?>">
            </label>
        </div>
        <label>
            Status
            <select name="status">
                <?php foreach (['active', 'sold', 'dead', 'quarantined'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= (($editLivestock['status'] ?? 'active') === $status) ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="inline-actions">
            <button type="submit">Save Livestock</button>
            <a class="btn secondary" href="/frontend/livestock.php">Reset</a>
        </div>
    </form>
</section>

<section class="form-card">
    <h3>Add Health / Production Record</h3>
    <p class="muted">Log health status, vaccination, treatment and feed cost entries.</p>
    <form action="/backend/handlers/livestock.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_health">
        <input type="hidden" name="health_id" value="0">
        <div class="form-row">
            <label>
                Animal
                <select name="livestock_id" required>
                    <option value="">Select Animal</option>
                    <?php foreach ($livestock as $animal): ?>
                        <option value="<?= e((string) $animal['id']) ?>"><?= e($animal['category_name'] . ' - ' . $animal['tag_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Record Date
                <input type="date" name="record_date">
            </label>
        </div>
        <div class="form-row">
            <label>
                Weight (KG)
                <input type="number" step="0.01" min="0" name="weight_kg">
            </label>
            <label>
                Feed Cost (KES)
                <input type="number" step="0.01" min="0" name="feed_cost">
            </label>
        </div>
        <div class="form-row">
            <label>
                Health Status
                <input type="text" name="health_status" placeholder="Healthy / Under treatment">
            </label>
            <label>
                Vaccination
                <input type="text" name="vaccination" placeholder="FMD, Newcastle, etc.">
            </label>
        </div>
        <label>
            Treatment
            <textarea name="treatment" placeholder="Medication or intervention details"></textarea>
        </label>
        <label>
            Production Notes
            <textarea name="production_notes" placeholder="Milk yield, egg production, breeding notes"></textarea>
        </label>
        <button type="submit">Save Health Record</button>
    </form>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Livestock Records</h3></div>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Breed</th>
                <th>Tag</th>
                <th>Gender</th>
                <th>Status</th>
                <th>Birth Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$livestock): ?>
                <tr><td colspan="7">No livestock records found.</td></tr>
            <?php else: ?>
                <?php foreach ($livestock as $animal): ?>
                    <tr>
                        <td><?= e($animal['category_name']) ?></td>
                        <td><?= e($animal['breed']) ?></td>
                        <td><?= e($animal['tag_number']) ?></td>
                        <td><?= e($animal['gender']) ?></td>
                        <td><span class="badge"><?= e(ucfirst($animal['status'])) ?></span></td>
                        <td><?= e($animal['birth_date']) ?></td>
                        <td>
                            <div class="inline-actions">
                                <a class="btn secondary" href="/frontend/livestock.php?edit=<?= e((string) $animal['id']) ?>">Edit</a>
                                <form action="/backend/handlers/livestock.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_livestock">
                                    <input type="hidden" name="livestock_id" value="<?= e((string) $animal['id']) ?>">
                                    <button class="danger" data-confirm="Delete this livestock record?" type="submit">Delete</button>
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
    <div class="section-heading"><h3>Health & Production Records</h3></div>
    <table>
        <thead>
            <tr>
                <th>Animal</th>
                <th>Date</th>
                <th>Weight</th>
                <th>Health Status</th>
                <th>Vaccination</th>
                <th>Feed Cost</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$healthRecords): ?>
                <tr><td colspan="8">No health records found.</td></tr>
            <?php else: ?>
                <?php foreach ($healthRecords as $record): ?>
                    <tr>
                        <td><?= e($record['category_name'] . ' - ' . $record['tag_number']) ?></td>
                        <td><?= e($record['record_date']) ?></td>
                        <td><?= e(number_format((float) $record['weight_kg'], 2)) ?> KG</td>
                        <td><?= e($record['health_status']) ?></td>
                        <td><?= e($record['vaccination']) ?></td>
                        <td>KES <?= e(number_format((float) $record['feed_cost'], 2)) ?></td>
                        <td><?= e($record['production_notes']) ?></td>
                        <td>
                            <form action="/backend/handlers/livestock.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_health">
                                <input type="hidden" name="health_id" value="<?= e((string) $record['id']) ?>">
                                <button class="danger" data-confirm="Delete this health record?" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
