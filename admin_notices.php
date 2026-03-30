<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

$admin = require_admin();
$pdo = getPDO();
$pageTitle = 'Notice Center';
$notices = fetch_all($pdo->query('SELECT sn.*, u.full_name FROM system_notices sn LEFT JOIN users u ON u.id = sn.created_by ORDER BY sn.id DESC'));
require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card">
    <h3>Publish Notice</h3>
    <p class="muted">Share system messages, farming reminders, maintenance windows or administrative announcements.</p>
    <form action="/backend/handlers/admin_notices.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create_notice">
        <div class="form-row">
            <label>Title<input type="text" name="title" required></label>
            <label>
                Audience
                <select name="audience">
                    <option value="all">All Users</option>
                    <option value="farmer">Farmers</option>
                    <option value="officer">Officers</option>
                </select>
            </label>
        </div>
        <label>Message<textarea name="message" required></textarea></label>
        <button type="submit">Publish Notice</button>
    </form>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Existing Notices</h3></div>
    <table>
        <thead><tr><th>Title</th><th>Audience</th><th>Status</th><th>Message</th><th>Created By</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (!$notices): ?><tr><td colspan="7">No notices found.</td></tr><?php else: ?>
                <?php foreach ($notices as $notice): ?>
                    <tr>
                        <td><?= e($notice['title']) ?></td>
                        <td><?= e(ucfirst($notice['audience'])) ?></td>
                        <td><span class="badge <?= e($notice['status'] === 'active' ? 'success' : 'danger') ?>"><?= e(ucfirst($notice['status'])) ?></span></td>
                        <td><?= e($notice['message']) ?></td>
                        <td><?= e($notice['full_name'] ?: 'Admin') ?></td>
                        <td><?= e($notice['created_at']) ?></td>
                        <td>
                            <div class="inline-actions">
                                <form action="/backend/handlers/admin_notices.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="toggle_notice">
                                    <input type="hidden" name="notice_id" value="<?= e((string) $notice['id']) ?>">
                                    <input type="hidden" name="new_status" value="<?= e($notice['status'] === 'active' ? 'inactive' : 'active') ?>">
                                    <button class="<?= e($notice['status'] === 'active' ? 'warning' : 'secondary') ?>" type="submit"><?= e($notice['status'] === 'active' ? 'Deactivate' : 'Activate') ?></button>
                                </form>
                                <form action="/backend/handlers/admin_notices.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_notice">
                                    <input type="hidden" name="notice_id" value="<?= e((string) $notice['id']) ?>">
                                    <button class="danger" type="submit" data-confirm="Delete this notice?">Delete</button>
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
