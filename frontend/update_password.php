<?php
/* NAME: Update Password Page
   Description: This page is responsible for handling the password update process after the user confirms the passwords
   02-Apr-2026 v0.1
   Paraskevas Vafeiadis
   Files in use: reset_password.php, ResetPassword.php
*/

require_once '../backend/modules/Csrf.php';

require '../backend/modules/ResetPassword.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validateRequestToken()) {
    header('Location: reset_password.php?error=' . urlencode('Request validation failed.'));
    exit();
}

$pr     = new PasswordReset();
$result = $pr->updatePassword(
    trim($_POST['token'] ?? ''),
    $_POST['password'] ?? '',
    $_POST['confirm_password'] ?? ''
);

if ($result['success']) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Updated</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="css/auth_pages.css">
    </head>
    <body class="Log-in body auth-page">
        <div class="container d-flex justify-content-center align-items-center vh-100 auth-shell">
            <div class="card shadow p-4 auth-card" style="width:400px;">
                <h3 class="text-center mb-4">Password Updated</h3>
                <img src="imgs/cut_tepak_image.png" class="card-img-top mb-4" alt="AdviCut Logo">
                <div class="alert alert-success" role="alert">
                    Your password has been updated successfully.
                </div>
                <a href="index.php" class="btn btn-primary w-100">Go to Login</a>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    header('Location: reset_password.php?token=' . urlencode((string)($_POST['token'] ?? '')) . '&error=' . urlencode($result['message']));
    exit();
}
