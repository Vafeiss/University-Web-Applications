<?php
/* NAME: Reset Password Page
   Description: This page is responsible to handle the password update process after the user clicks the reset link
   02-Apr-2026 v0.1
   Paraskevas Vafeiadis
   Files in use: reset_password.php, ResetPassword.php
*/

require_once '../backend/modules/Csrf.php';

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
    <link rel="stylesheet" href="css/auth_pages.css">
</head>
<body class="bg-light auth-page">
    <div class="container d-flex justify-content-center align-items-center min-vh-100 auth-shell">
        <div class="card shadow p-4 auth-card auth-card-md" style="width: 420px;">
            <h3 class="text-center mb-3">Reset Password</h3>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($token === ''): ?>
                <div class="alert alert-warning">Missing or invalid reset token.</div>
            <?php else: ?>

                <form method="POST" action="update_password.php">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::ensureToken(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="alert alert-info small py-2" role="note">
                        Password requirements: 10-72 characters, with at least one uppercase letter, one lowercase letter, one number, and one symbol.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="password" required minlength="10">
                            <button type="button" class="btn btn-outline-secondary" id="toggleResetPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required minlength="10">
                            <button type="button" class="btn btn-outline-secondary" id="toggleResetConfirmPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="js/reset-password-toggle.js"></script>
</body>
</html>