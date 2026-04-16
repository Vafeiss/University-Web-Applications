<?php
require_once('init.php');
require_once("../backend/modules/UsersClass.php");
require_once("../backend/modules/Csrf.php");
require_once("../backend/modules/NotificationsClass.php");
$user = new Users();
$user->Check_Session();

$csrfToken = Csrf::ensureToken();

if (!isset($_SESSION["UserID"])) {
    header("Location: index.php?error=not_logged_in");
    exit();

} ?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Change Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/auth_pages.css">
</head>

<body class="auth-page">
<?php Notifications::createNotification(); ?>
<div class="container d-flex justify-content-center align-items-center vh-100 auth-shell">
<div class="card shadow p-4 auth-card" style="width:400px;">
<h3 class="text-center mb-4">Change Password</h3>

<form method="POST" action="../backend/modules/dispatcher.php">
    <input type="hidden" name="action" value="/password/change">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="alert alert-info small py-2" role="note">
        Password requirements: 10-72 characters, with at least one uppercase letter, one lowercase letter, one number, and one symbol.
    </div>

    <div class="mb-3">
        <label>Current Password</label>
        <div class="input-group">
            <input type="password" class="form-control" name="currentPassword" id="currentPassword" required>
            <button type="button" class="btn btn-outline-secondary" id="toggleCurrentPassword">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="mb-3">
        <label>New Password</label>
        <div class="input-group">
            <input type="password" class="form-control" name="newPassword" id="newPassword" required minlength="10">
            <button type="button" class="btn btn-outline-secondary" id="toggleNewPassword">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="mb-3">
        <label>Confirm New Password</label>
        <div class="input-group">
            <input type="password" class="form-control" name="confirmNewPassword" id="confirmNewPassword" required minlength="10">
            <button type="button" class="btn btn-outline-secondary" id="toggleConfirmNewPassword">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">Update Password</button>

</form>
</div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="js/change-password-toggle.js"></script>
</body>
</html>