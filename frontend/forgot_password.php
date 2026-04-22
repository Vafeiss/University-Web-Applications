<?php
/* NAME: forgot_password.php
   Description: This page is responsible for handling the forgot password process, it contains a form to submit the email and send the reset link to the user.
   02-Apr-2026 v0.1
   Paraskevas Vafeiadis
   Files in use: ResetPassword.php
*/
require '../backend/modules/ResetPassword.php';
require '../backend/modules/Csrf.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$csrfToken = Csrf::ensureToken();

$message = '';

$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequestToken()) {
        $message = 'Request validation failed.';
        $isError = true;
    } else {
    $pr     = new PasswordReset();
    $result = $pr->Handle_Forgot_Password(trim($_POST['email'] ?? ''));

    $message = $result['message'];
    $isError = !$result['success'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/auth_pages.css">
</head>
<body class="Log-in body auth-page">
    <div class="container d-flex justify-content-center align-items-center vh-100 auth-shell">
        <div class="card shadow p-4 auth-card" style="width:400px;">
            <h3 class="text-center mb-4">Reset your password</h3>
            <img src="imgs/cut_tepak_image.png" class="card-img-top mb-4" alt="AdviCut Logo">

            <?php if ($message !== ''): ?>
                <div class="alert <?= $isError ? 'alert-danger' : 'alert-success' ?>" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">University Email</label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="you@edu.cut.ac.cy">
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                <a href="index.php" class="d-inline-block mt-3">Back to login</a>
            </form>
        </div>
    </div>
</body>
</html>