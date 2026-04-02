<?php
require_once __DIR__ . '/../backend/config/app.php';

$pdo = getPDO();
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid request.");
}

$tokenHash = hash('sha256', $token);

// Verify token before showing form
$stmt = $pdo->prepare("
    SELECT * FROM password_resets 
    WHERE token = ? AND expires_at > NOW()
");
$stmt->execute([$tokenHash]);

if ($stmt->rowCount() === 0) {
    die("Invalid or expired token.");
}

$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body>

<h2>Reset Password</h2>

<?php if ($flash): ?>
    <p><?= e($flash['message']) ?></p>
<?php endif; ?>

<form action="/backend/handlers/password_reset.php" method="POST">
    <input type="hidden" name="action" value="reset_password">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <label>New Password</label>
    <input type="password" name="password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" required>

    <button type="submit">Reset Password</button>
</form>

</body>
</html>