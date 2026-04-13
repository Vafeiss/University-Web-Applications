<?php
/*Name: UsersController.php
  Description: Convertion of all the controllers related to the user management into this one. Paired with the 
  router and the dispatcher, this file is reponsible to be the bridge between the frontend and the backend for the usersclass
  Paraskevas Vafeiadis
  06-Mar-2026 v0.1
  Inputs: Depends on the functions but POST/GET requests
  Outputs: Redirections to the main dashboard
  Files in Uses: UsersClass.php , routes.php , router.php , dispatcher.php*/

declare(strict_types=1);

require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';

class UsersController {

    public function logout(){
        $user = new Users();
        $user->Log_out();
    }

    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Notifications::error("Invalid request method.");
            header('Location: ../../frontend/changepassword.php');
            exit();
        }

        if (!isset($_SESSION['UserID'])) {
            Notifications::error("You must be logged in to change your password.");
            header('Location: ../../frontend/index.php');
            exit();
        }

        $currentPassword = $_POST['currentPassword'] ?? ($_POST['current_password'] ?? '');
        $newPassword = $_POST['newPassword'] ?? ($_POST['new_password'] ?? '');
        $confirmPassword = $_POST['confirmNewPassword'] ?? ($_POST['confirm_password'] ?? '');

        if ($newPassword !== $confirmPassword) {
            Notifications::error("Passwords do not match.");
            header('Location: ../../frontend/changepassword.php');
            exit();
        }

        $user = new Users();
        $result = $user->Change_Password((int)$_SESSION['UserID'], $currentPassword, $newPassword);

        if (!$result) {
            Notifications::error("Invalid current password.");
            header('Location: ../../frontend/changepassword.php');
            exit();
        }

        Notifications::success("Password changed successfully.");
        header('Location: ../../frontend/index.php');
        exit();
    }

    public function Authentication(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/index.php');
            exit();
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = new Users();
        $user->Log_in($email, $password);
    }
}