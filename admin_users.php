<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

$admin = require_admin();
$pdo = getPDO();
$pageTitle = 'User Management';
$users = fetch_all($pdo->query('SELECT u.*, f.farm_name, f.location FROM users u LEFT JOIN farms f ON f.user_id = u.id ORDER BY u.id DESC'));
require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card">
    <h3>Create User</h3>
    <p class="muted">Admins can create farmer and administrator accounts directly.</p>
    <form action="/backend/handlers/admin_users.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create_user">
        <div class="form-row">
            <label>Full Name<input type="text" name="full_name" required></label>
            <label>Email<input type="email" name="email" required></label>
        </div>
        <div class="form-row">
            <label>Phone<input type="text" name="phone"></label>
            <label>Password<input type="password" name="password" minlength="8" required></label>
        </div>
        <div class="form-row three">
            <label>
                Role
                <select name="role">
                    <option value="farmer">Farmer</option>
                    <option value="officer">Officer</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <label>
                Status
                <select name="account_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
            <label>Farm Size (Acres)<input type="number" step="0.01" min="0" name="farm_size_acres"></label>
        </div>
        <div class="form-row">
            <label>Farm Name<input type="text" name="farm_name" placeholder="Optional for admin/officer"></label>
            <label>Location<input type="text" name="location"></label>
        </div>
        <button type="submit">Create User</button>
    </form>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Existing Users</h3></div>
    <table>
        <thead>
            <tr>
                <th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Farm</th><th>Created</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$users): ?><tr><td colspan="7">No users found.</td></tr><?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e($user['full_name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td>
                            <form action="/backend/handlers/admin_users.php" method="POST" class="inline-actions">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">
                                <select name="role">
                                    <?php foreach (['admin', 'farmer', 'officer'] as $role): ?>
                                        <option value="<?= e($role) ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= e(ucfirst($role)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="light" type="submit">Save Role</button>
                            </form>
                        </td>
                        <td><span class="badge <?= e($user['account_status'] === 'active' ? 'success' : 'danger') ?>"><?= e(ucfirst($user['account_status'])) ?></span></td>
                        <td><?= e($user['farm_name'] ?: 'N/A') ?></td>
                        <td><?= e($user['created_at']) ?></td>
                        <td>
                            <div class="inline-actions">
                                <form action="/backend/handlers/admin_users.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">
                                    <input type="hidden" name="new_status" value="<?= e($user['account_status'] === 'active' ? 'inactive' : 'active') ?>">
                                    <button class="<?= e($user['account_status'] === 'active' ? 'danger' : 'secondary') ?>" type="submit"><?= e($user['account_status'] === 'active' ? 'Deactivate' : 'Activate') ?></button>
                                </form>
                                <form action="/backend/handlers/admin_users.php" method="POST" class="inline-actions">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">
                                    <input type="text" name="new_password" placeholder="New password" required>
                                    <button class="warning" type="submit">Reset Password</button>
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
