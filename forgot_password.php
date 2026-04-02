<?php
require_once __DIR__ . '/../backend/config/app.php';
$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
</head>
<body>

<h2>Forgot Password</h2>

<?php if ($flash): ?>
    <p><?= e($flash['message']) ?></p>
<?php endif; ?>

<form action="/backend/handlers/password_reset.php" method="POST">
    <input type="hidden" name="action" value="request_reset">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label>Email</label>
    <input type="email" name="email" required>

    <button type="submit">Send Reset Link</button>
</form>

<a href="/frontend/index.php">Back to Login</a>

</body>
</html>