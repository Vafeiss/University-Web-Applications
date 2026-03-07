<?php
require_once('init.php');
require_once("../backend/modules/UsersClass.php");
$user = new Users();
$user->Check_Session();

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
</head>

<body>
<div class="container d-flex justify-content-center align-items-center vh-100">
<div class="card shadow p-4" style="width:400px;">
<h3 class="text-center mb-4">Change Password</h3>

<form method="POST" action="../backend/modules/dispatcher.php">
    <input type="hidden" name="action" value="/password/change">

    <div class="mb-3">
        <label>Current Password</label>
        <input type="password" class="form-control" name="currentPassword" required>
    </div>

    <div class="mb-3">
        <label>New Password</label>
        <input type="password" class="form-control" name="newPassword" required minlength="8">
    </div>

    <div class="mb-3">
        <label>Confirm New Password</label>
        <input type="password" class="form-control" name="confirmNewPassword" required minlength="8">
    </div>

    <button type="submit" class="btn btn-primary w-100">Update Password</button>

</form>
</div>
</div>
</body>
</html>