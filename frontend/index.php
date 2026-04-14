<?php
/* NAME: log-In page
Description: This is the login page for the web application without any backend for now.
It contains a simple form with username and password fields and the sumbit button,
It will send the data to the auth.php to check the credintials and log the user in.
Paraskevas Vafeiadis
23-feb-2026
Inputs: Email,Password
Outputs: None
Error Messages : Field not filled. (1)
Files in use: Bootstrap CSS from the web

24-feb-2026: changed the where the form is sent to the backend to validate if the inputs are correct.
Paraskevas Vafeiadis
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/modules/NotificationsClass.php';
require_once __DIR__ . '/../backend/modules/Csrf.php';

$csrfToken = Csrf::ensureToken();

$loginError = (string)($_GET['error'] ?? '');
if ($loginError === 'invalid' || $loginError === 'invalid1') {
    Notifications::error('Incorrect username or password.');
} elseif ($loginError === 'invalid2') {
    Notifications::error('Incorrect username or password.');
} elseif ($loginError === 'database') {
    Notifications::error('A database error occurred while logging in.');
} elseif ($loginError === 'unauthorized') {
    Notifications::error('Please log in to continue.');
} elseif ($loginError === 'forbidden') {
    Notifications::error('You do not have permission to access that page.');
} elseif ($loginError === 'throttled') {
    Notifications::error('Too many login attempts. Please try again later.');
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>AdviCut Login Page</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="css/auth_pages.css">
    </head>

    <body class="Log-in body auth-page">
        <?php Notifications::createNotification(); ?>
        <div class="container d-flex justify-content-center align-items-center vh-100 auth-shell">
        <div class="card shadow p-4 auth-card" style="width:400px;">
        <h3 class="text-center mb-4">Welcome to AdviCUT!</h3>
        <img src="imgs/cut_tepak_image.png" class="card-img-top mb-4" alt="AdviCut Logo">
        <form method="POST" action="../backend/modules/dispatcher.php">
            <input type="hidden" name="action" value="/login">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label for="Email" class="form-label">University Email</label>
                <input type="text" class="form-control" id="Email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="Submit" class="btn btn-primary w-100">Log-in</button>
                <a href="forgot_password.php">Forgot your password?</a>
        </form>
        </div>
        </div>
    </body>
</html>