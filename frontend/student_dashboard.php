<?php
require_once('init.php');
require_once("../backend/modules/UsersClass.php");
$user = new Users();
$user->Check_Session("Student");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Welcome to the Student Dashboard</h1>
    <button onclick="location.href='changepassword.php'">Change Password</button>
    <form action="../backend/modules/dispatcher.php" method="POST">
        <input type="hidden" name="action" value="/logout">
        <button type="submit">Logout</button>
    </form>
</body>
</html>

   
