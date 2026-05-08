<?php
/*Name: UsersController.php
  Description: Convertion of all the controllers related to the user management into this one. Paired with the 
  router and the dispatcher, this file is reponsible to be the bridge between the frontend and the backend for the usersclass
  Paraskevas Vafeiadis
  06-Mar-2026 v0.1
  Inputs: Depends on the functions but POST/GET requests
  Outputs: Redirections to the main dashboard
  Files in Uses: UsersClass.php , routes.php , router.php , dispatcher.php
  */
  
declare(strict_types=1);
require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';
require_once __DIR__ . '/../modules/Csrf.php';
require_once __DIR__ . '/../modules/databaseconnect.php';
require_once __DIR__ . '/../config/app.php';

class UsersController {

    public function deleteNotification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Notifications::error("Invalid request method.");
            header('Location: ' . frontend_url('index.php'));
            exit();
        }

        if (!Csrf::validateRequestToken()) {
            Notifications::error("Request validation failed.");
            header('Location: ' . frontend_url('index.php'));
            exit();
        }

        if (!isset($_SESSION['UserID']) || !is_numeric($_SESSION['UserID'])) {
            Notifications::error("Unauthorized session.");
            header('Location: ' . frontend_url('index.php'));
            exit();
        }

        $notificationId = (int)($_POST['notification_id'] ?? 0);
        $recipientId = (int)$_SESSION['UserID'];
        $redirectTo = $this->safeFrontendRedirect((string)($_POST['redirect_to'] ?? 'index.php'));

        if ($notificationId <= 0) {
            Notifications::error("Invalid notification.");
            header('Location: ' . frontend_url($redirectTo));
            exit();
        }

        try {
            $pdo = ConnectToDatabase();
            $deleteSql = "DELETE FROM notifications WHERE Notification_ID = :notification_id AND Recipient_ID = :recipient_id
                          LIMIT 1";

            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([
                'notification_id' => $notificationId,
                'recipient_id' => $recipientId,
            ]);

            if ($deleteStmt->rowCount() > 0) {
                Notifications::success("Notification deleted.");
            } else {
                Notifications::error("Notification not found.");
            }
        } catch (Throwable $e) {
            Notifications::error("Failed to delete notification.");
        }

        header('Location: ' . frontend_url($redirectTo));
        exit();
    }

    public function logout(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . frontend_url('index.php'));
            exit();
        }

        if (!Csrf::validateRequestToken()) {
            header('Location: ' . frontend_url('index.php?error=unauthorized'));
            exit();
        }

        $user = new Users();
        $user->Log_out();
    }

    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Notifications::error("Invalid request method.");
            header('Location: ' . frontend_url('changepassword.php'));
            exit();
        }

        if (!Csrf::validateRequestToken()) {
            Notifications::error("Request validation failed.");
            header('Location: ' . frontend_url('changepassword.php'));
            exit();
        }

        if (!isset($_SESSION['UserID'])) {
            Notifications::error("You must be logged in to change your password.");
            header('Location: ' . frontend_url('index.php'));
            exit();
        }

        $currentPassword = $_POST['currentPassword'] ?? ($_POST['current_password'] ?? '');
        $newPassword = $_POST['newPassword'] ?? ($_POST['new_password'] ?? '');
        $confirmPassword = $_POST['confirmNewPassword'] ?? ($_POST['confirm_password'] ?? '');

        if (
            $currentPassword === '' ||
            $newPassword === '' ||
            $confirmPassword === '' ||
            strlen($currentPassword) > 255 ||
            strlen($newPassword) > 255 ||
            strlen($confirmPassword) > 255
        ) {
            Notifications::error("Invalid password input.");
            header('Location: ' . frontend_url('changepassword.php'));
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            Notifications::error("Passwords do not match.");
            header('Location: ' . frontend_url('changepassword.php'));
            exit();
        }

        // Validate password strength before attempting to change
        if (!$this->isStrongPassword($newPassword)) {
            Notifications::error("Password does not meet requirements.");
            header('Location: ' . frontend_url('changepassword.php'));
            exit();
        }

        $user = new Users();
        $result = $user->Change_Password((int)$_SESSION['UserID'], $currentPassword, $newPassword);

        if (!$result) {
            Notifications::error("Your current password is incorrect.");
            header('Location: ' . frontend_url('changepassword.php'));
            exit();
        }

        Notifications::success("Password changed successfully.");
        header('Location: ' . frontend_url('index.php'));
        exit();
    }

    private function isStrongPassword(string $password): bool {
        if (strlen($password) < 10 || strlen($password) > 72) {
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    private function safeFrontendRedirect(string $target): string
    {
        $target = trim($target);

        if ($target === '' || str_contains($target, '://') || str_starts_with($target, '/')) {
            return 'index.php';
        }

        if (str_contains($target, '..') || str_contains($target, "\\")) {
            return 'index.php';
        }

        $allowed = [
            'StudentAppointmentDashboard.php',
            'AdvisorAppointmentDashboard.php',
            'admin_dashboard.php',
            'superuser_reports.php',
            'changepassword.php',
            'index.php',
        ];

        $path = (string)parse_url($target, PHP_URL_PATH);
        $basename = basename($path);

        if (!in_array($basename, $allowed, true)) {
            return 'index.php';
        }

        $query = (string)parse_url($target, PHP_URL_QUERY);
        return $query !== '' ? ($basename . '?' . $query) : $basename;
    }

    public function Authentication(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . frontend_url('index.php'));
            exit();
        }

        if (!Csrf::validateRequestToken()) {
            header('Location: ' . frontend_url('index.php?error=unauthorized'));
            exit();
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (
            $email === '' ||
            $password === '' ||
            strlen($email) > 254 ||
            strlen($password) > 255 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            header('Location: ' . frontend_url('index.php?error=invalid'));
            exit();
        }

        $user = new Users();
        $user->Log_in($email, $password);
    }
}