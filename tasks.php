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
$editTask = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM farm_tasks WHERE id = :id AND farm_id = :farm_id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit'], ':farm_id' => $farmId]);
    $editTask = $stmt->fetch() ?: null;
}

$tasksStmt = $pdo->prepare('SELECT * FROM farm_tasks WHERE farm_id = :farm_id ORDER BY CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC, id DESC');
$tasksStmt->execute([':farm_id' => $farmId]);
$tasks = fetch_all($tasksStmt);

$pageTitle = 'Farm Task Planner';
require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card">
    <h3><?= $editTask ? 'Edit Task' : 'Create Task' ?></h3>
    <form action="/backend/handlers/tasks.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_task">
        <input type="hidden" name="task_id" value="<?= e((string) ($editTask['id'] ?? 0)) ?>">
        <div class="form-row">
            <label>
                Task Title
                <input type="text" name="title" value="<?= e($editTask['title'] ?? '') ?>" required>
            </label>
            <label>
                Task Type
                <select name="task_type" required>
                    <?php foreach (['Planting', 'Weeding', 'Harvesting', 'Feeding', 'Vaccination', 'Maintenance', 'Procurement'] as $type): ?>
                        <option value="<?= e($type) ?>" <?= (($editTask['task_type'] ?? '') === $type) ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-row three">
            <label>
                Due Date
                <input type="date" name="due_date" value="<?= e($editTask['due_date'] ?? '') ?>">
            </label>
            <label>
                Priority
                <select name="priority">
                    <?php foreach (['low', 'medium', 'high'] as $priority): ?>
                        <option value="<?= e($priority) ?>" <?= (($editTask['priority'] ?? 'medium') === $priority) ? 'selected' : '' ?>><?= e(ucfirst($priority)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Status
                <select name="status">
                    <?php foreach (['pending', 'in_progress', 'completed', 'cancelled'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= (($editTask['status'] ?? 'pending') === $status) ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>
            Description
            <textarea name="description" placeholder="Describe the planned farm activity..."><?= e($editTask['description'] ?? '') ?></textarea>
        </label>
        <div class="inline-actions">
            <button type="submit">Save Task</button>
            <a class="btn secondary" href="/frontend/tasks.php">Reset</a>
        </div>
    </form>
</section>

<section class="table-card">
    <div class="section-heading"><h3>Planned Tasks</h3></div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Due Date</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$tasks): ?>
                <tr><td colspan="7">No tasks found.</td></tr>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= e($task['title']) ?></td>
                        <td><?= e($task['task_type']) ?></td>
                        <td><?= e($task['due_date']) ?></td>
                        <td><span class="badge <?= e($task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'success')) ?>"><?= e(ucfirst($task['priority'])) ?></span></td>
                        <td><?= e(ucwords(str_replace('_', ' ', $task['status']))) ?></td>
                        <td><?= e($task['description']) ?></td>
                        <td>
                            <div class="inline-actions">
                                <a class="btn secondary" href="/frontend/tasks.php?edit=<?= e((string) $task['id']) ?>">Edit</a>
                                <form action="/backend/handlers/tasks.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="mark_task_status">
                                    <input type="hidden" name="task_id" value="<?= e((string) $task['id']) ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button class="warning" type="submit">Complete</button>
                                </form>
                                <form action="/backend/handlers/tasks.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_task">
                                    <input type="hidden" name="task_id" value="<?= e((string) $task['id']) ?>">
                                    <button class="danger" data-confirm="Delete this task?" type="submit">Delete</button>
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
