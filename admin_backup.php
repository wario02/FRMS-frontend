<?php
require_once __DIR__ . '/../backend/middleware/auth.php';

$admin = require_admin();
$pdo = getPDO();
$pageTitle = 'Backup & Restore';
$backupLogs = fetch_all($pdo->query('SELECT bl.*, u.full_name FROM backup_logs bl LEFT JOIN users u ON u.id = bl.initiated_by ORDER BY bl.id DESC'));
require_once __DIR__ . '/includes/header.php';
?>

<section class="card-grid">
    <div class="form-card">
        <h3>Create Database Backup</h3>
        <p class="muted">Generate a downloadable SQL snapshot of the current system.</p>
        <form action="/backend/handlers/admin_backup.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_backup">
            <button type="submit">Generate Backup</button>
        </form>
    </div>
    <div class="form-card">
        <h3>Restore Database Backup</h3>
        <p class="muted">Upload a SQL backup generated from this system to restore it.</p>
        <form action="/backend/handlers/admin_backup.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="restore_backup">
            <label>
                SQL Backup File
                <input class="file-input" type="file" name="sql_backup" accept=".sql" required>
            </label>
            <button class="warning" type="submit" data-confirm="Restore database from uploaded SQL file? This will overwrite current data.">Restore Backup</button>
        </form>
    </div>
</section>

<section class="info-box" style="margin-bottom:24px;">
    <strong>Backup location:</strong> <span class="small-text">Generated backups are stored in the project folder under <code>storage/backups/</code> and are also listed below for direct download.</span>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Backup History</h3></div>
    <table>
        <thead><tr><th>File</th><th>Type</th><th>Initiated By</th><th>Timestamp</th><th>Download</th></tr></thead>
        <tbody>
            <?php if (!$backupLogs): ?><tr><td colspan="5">No backup history recorded yet.</td></tr><?php else: ?>
                <?php foreach ($backupLogs as $log): ?>
                    <?php $downloadPath = '/storage/backups/' . basename((string) $log['file_name']); ?>
                    <tr>
                        <td><?= e($log['file_name']) ?></td>
                        <td><?= e($log['backup_type']) ?></td>
                        <td><?= e($log['full_name'] ?: 'System') ?></td>
                        <td><?= e($log['created_at']) ?></td>
                        <td><a class="btn secondary" href="<?= e($downloadPath) ?>">Download</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
