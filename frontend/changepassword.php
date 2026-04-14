<?php
require_once('init.php');
require_once("../backend/modules/UsersClass.php");
require_once("../backend/modules/Csrf.php");
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
        <input type="password" class="form-control" name="currentPassword" required>
    </div>

    <div class="mb-3">
        <label>New Password</label>
        <input type="password" class="form-control" name="newPassword" required minlength="10">
    </div>

    <div class="mb-3">
        <label>Confirm New Password</label>
        <input type="password" class="form-control" name="confirmNewPassword" required minlength="10">
    </div>

    <button type="submit" class="btn btn-primary w-100">Update Password</button>

</form>
</div>
</div>
</body>
</html>