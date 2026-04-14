<?php
/* NAME: Admin SuperUser Class
   Description: This Class is responsible for handling SuperUser(Admin) management
   Paraskevas Vafeiadis
   27-Mar-2026 v1.0
   Inputs: Various inputs for the functions about superusers
   Outputs: Various outputs for the functions about superusers
   Error Messages : if connection fails throw exception with message
   Files in use: AdminClass.php, Admin_dashboard.php
*/

declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AdminSuperUserClass
{
    private PDO $conn;

    //connect to the database in XAMPP using the database connection function from databaseconnect.php
    public function __construct()
    {
        $this->conn = ConnectToDatabase();
    }

    //generate a random temporary password for the superuser account
    private function generateTempPassword(int $length = 8): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        $charLen = strlen($chars);
        $bytes = random_bytes($length);
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[ord($bytes[$i]) % $charLen];
        }

        return $password;
    }

    //get superusers information for the admin dashbaord
    public function getSuperUsers()
    {
        return $this->conn->query("SELECT Uni_Email as Email , User_ID FROM users WHERE Role = 'SuperUser'");
    }

    //add superusers to the database with the information provided by the admin
    public function addSuperUser(string $email, int $externalId): bool{
        if ($externalId <= 0) {
            return false;
        }

        $externalCheck = $this->conn->prepare('SELECT User_ID FROM users WHERE External_ID = ? LIMIT 1');
        $externalCheck->execute([$externalId]);
        if ($externalCheck->fetch(PDO::FETCH_ASSOC) !== false) {
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch(PDO::FETCH_ASSOC) !== false) {
            return false;
        }

        $tempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare('INSERT INTO users (External_ID, Uni_Email, Password, Role , First_Name , Last_Name) VALUES (?, ?, ?, "SuperUser", "SuperUser" , "SuperUser")');

        return $stmt->execute([$externalId, $email, $hashedTempPassword]);
    }
 
    //delete superusers from the database by providing the superuser ID
    public function deleteSuperUser(int $user_ID): bool
    {
        if ($user_ID <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM users WHERE User_ID = ? AND Role = "SuperUser"');
        return $stmt->execute([$user_ID]);
    }
}
