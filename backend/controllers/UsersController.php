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

class UsersController {

    public function logout(){
        $user = new Users();
        $user->Log_out();
    }

    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/changepassword.php');
            exit();
        }

        if (!isset($_SESSION['UserID'])) {
            header('Location: ../../frontend/index.php?error=not_logged_in');
            exit();
        }

        $currentPassword = $_POST['currentPassword'] ?? ($_POST['current_password'] ?? '');
        $newPassword = $_POST['newPassword'] ?? ($_POST['new_password'] ?? '');
        $confirmPassword = $_POST['confirmNewPassword'] ?? ($_POST['confirm_password'] ?? '');

        if ($newPassword !== $confirmPassword) {
            header('Location: ../../frontend/changepassword.php?error=passwords_do_not_match');
            exit();
        }


        $user = new Users();
        $result = $user->Change_Password((int)$_SESSION['UserID'], $currentPassword, $newPassword);

        if ($result) {
            header('Location: ../../frontend/index.php?success=password_changed');
        } else {
            header('Location: ../../frontend/changepassword.php?error=invalid_current_password');
        }
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

   