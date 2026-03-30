<?php
require_once __DIR__ . '/../backend/config/app.php';

if (is_logged_in()) {
    $pdo = getPDO();
    log_action($pdo, (int) current_user()['id'], 'logout', 'users', (int) current_user()['id']);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

session_start();
set_flash('success', 'You have been logged out successfully.');
redirect('/frontend/index.php');
