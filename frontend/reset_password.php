<?php
/* NAME: Reset Password Page
   Description: This page is responsible to handle the password update process after the user clicks the reset link
   02-Apr-2026 v0.1
   Paraskevas Vafeiadis
   Files in use: reset_password.php, ResetPassword.php
*/

$token = trim($_GET['token'] ?? '');
$error = trim($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow p-4" style="width: 420px;">
            <h3 class="text-center mb-3">Reset Password</h3>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($token === ''): ?>
                <div class="alert alert-warning">Missing or invalid reset token.</div>
            <?php else: ?>

                <form method="POST" action="update_password.php">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" required minlength="8">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>