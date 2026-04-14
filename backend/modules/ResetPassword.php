<?php
/* Name: ResetPassword.php
   Description: This file contains the PasswordReset class which handles the forgot password process,
   including generating reset tokens, sending reset emails, validating tokens and updating passwords.
   01 - Apr - 2026 v0.1
   Paraskevas Vafeiadis
   Files in use: forgot_password.php , databaseconnect.php
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/databaseconnect.php';
require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/../config/PHPMailer/Exception.php';
require_once __DIR__ . '/../config/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../config/PHPMailer/SMTP.php';


class PasswordReset{

    private PDO $conn;
    private string $email;
    private string $password;
    private string $baseurl;

    private function isStrongPassword(string $password): bool
    {
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

    public function __construct(?PDO $conn = null)
    {
        Env::loadFromProjectRoot();
        $this->conn = $conn ?? ConnectToDatabase();
        $this->email = (string)(getenv('PASSWORD_RESET_SMTP_USER') ?: '');
        $this->password = (string)(getenv('PASSWORD_RESET_SMTP_PASS') ?: '');
        $this->baseurl = (string)(getenv('APP_BASE_URL') ?: 'http://localhost/University-Web-Applications-System-A/');
    }

    //function to handle the forgot password process
    public function Handle_Forgot_Password(string $email): array {
        $genericMessage = 'Reset link has been sent.';

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => true, 'message' => $genericMessage];
        }

        $stmt = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = :email');
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if($result){
            $token = $this->generateToken($email);
            $sent = $this->sendResetEmail($email, $token);

            if(!$sent){
                return ['success' => true, 'message' => $genericMessage];
            }

            return ['success' => true, 'message' => $genericMessage];
        } else {
            return ['success' => true, 'message' => $genericMessage];
        }
    }

    //function to generate a unique token for password reset
    private function generateToken(string $email): string{
        $token = bin2hex(random_bytes(32)); // Generate a random token
        
        $delete = $this->conn->prepare('DELETE FROM password_resets WHERE email = :email');
        $delete->execute(['email' => $email]);

        $insetnew = $this->conn->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
        $insetnew->execute(['email' => $email, 'token' => $token]);

        return $token;
    }

    //function to send the reset email to the user
    private function sendResetEmail(string $email, string $token): bool {
        if ($this->email === '' || $this->password === '') {
            error_log('Password reset email configuration missing: set PASSWORD_RESET_SMTP_USER and PASSWORD_RESET_SMTP_PASS.');
            return false;
        }

        $resetLink = rtrim($this->baseurl, '/') . '/frontend/reset_password.php?token=' . $token;
        $mail = new PHPMailer(true);

        try{
            $mail -> isSMTP();
            $mail -> Host = 'smtp.gmail.com';
            $mail -> SMTPAuth = true;
            $mail -> Username = $this->email;
            $mail -> Password = $this->password;
            $mail -> SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail -> Port = 587;
            $mail -> setFrom($this->email, 'Advicut System');
            $mail -> addAddress($email);
            $mail -> isHTML(true);
            $mail -> Subject = 'Password Reset Request';
            $mail->Body    = "
                <p>Hello,</p>
                <p>We received a request to reset your password.</p>
                <p>
                    <a href='{$resetLink}' style='
                        background:#0078d4;
                        color:white;
                        padding:10px 20px;
                        text-decoration:none;
                        border-radius:5px;
                    '>
                        Reset My Password
                    </a>
                </p>
                <p>This link expires in <strong>1 hour</strong>.</p>
                <p>If you did not request this, you can safely ignore this email.</p>
                <br>
                <small>Advicut Team — Do not reply to this email.</small>
            ";

            $mail -> AltBody = "Reset your password using the following link: {$resetLink} (This link expires in 1 hour)";
            $mail -> send();
            return true;
            
        } catch (Exception $e) {
            error_log('Password reset email failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

    //function to validate the token and allow the user to reset their password
    public function ValidateToken(string $token): ?array{
        $token = trim($token);
        if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
            return null;
        }

        $stmt = $this->conn->prepare('SELECT email FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['token' => strtolower($token)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

          if(!$result){
            return null;
          }

          return $result;
    }

    //function to update the user's passsword after validating the token
    public function UpdatePassword(string $token , string $newPassword , string $confirmPassword): array{
        $token = strtolower(trim($token));

        if($newPassword !== $confirmPassword){
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        if(!$this->isStrongPassword($newPassword)){
            return ['success' => false, 'message' => 'Password must be 10-72 characters and include upper, lower, number, and symbol.'];
        }

        $email = $this->ValidateToken($token);

        if(!$email){
            return ['success' => false, 'message' => 'Invalid or expired token.'];
        }

        $existingPasswordStmt = $this->conn->prepare('SELECT Password FROM users WHERE Uni_Email = :email LIMIT 1');
        $existingPasswordStmt->execute(['email' => $email['email']]);
        $existingPasswordRow = $existingPasswordStmt->fetch(PDO::FETCH_ASSOC);
        if ($existingPasswordRow !== false && password_verify($newPassword, (string)$existingPasswordRow['Password'])) {
            return ['success' => false, 'message' => 'New password must be different from your current password.'];
        }

        try {
            $this->conn->beginTransaction();

            //updated password in the database
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $this->conn->prepare('UPDATE users SET Password = :password WHERE Uni_Email = :email');
            $update->execute(['password' => $hashedPassword, 'email' => $email['email']]);

            //mark the token as used to prevent reuse
            $used = $this->conn->prepare('UPDATE password_resets SET used = 1 WHERE token = :token AND used = 0');
            $used->execute(['token' => $token]);

            if ($used->rowCount() !== 1) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Invalid or expired token.'];
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Password updated successfully.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Password reset update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not update password. Please try again.'];
        }
    }




}