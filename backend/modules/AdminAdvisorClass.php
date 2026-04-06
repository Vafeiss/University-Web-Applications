<?php
/* NAME: Admin Advisor Class
   Description: This Class is responsible for handling the advisor management
   Paraskevas Vafeiadis
   27-Mar-2026 v1.1
   Inputs: Various inputs for the functions about advisors
   Outputs: Various outputs for the functions about advisors
   Error Messages : if connection fails throw exception with message
   Files in use: AdminClass.php, Admin_dashboard.php
*/

declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AdminAdvisorClass
{
    private PDO $conn;

    //connect to the database in XAMPP using the database connection function from databaseconnect.php
    public function __construct()
    {
        $this->conn = ConnectToDatabase();
    }

    //private function to check if the phone number is valid allows empty string or valid phone formats
    private function isValidPhone(string $phone): bool
    {
        if ($phone === '') {
            return true;
        }

        if (!preg_match('/^[0-9+()\-\s]+$/', $phone)) {
            return false;
        }

        $digitsOnly = preg_replace('/\D/', '', $phone);
        $digitsLength = strlen($digitsOnly);

        return $digitsLength >= 8 && $digitsLength <= 15;
    }

    //private function to resolve the department ID to a degree ID for the advisor
    private function resolveDepartmentToDegreeId(int $departmentId): ?int
    {
        if ($departmentId <= 0) {
            return null;
        }

        $stmt = $this->conn->prepare('SELECT DegreeID FROM degree WHERE DepartmentID = ? ORDER BY DegreeID ASC LIMIT 1');
        $stmt->execute([$departmentId]);
        $degreeId = $stmt->fetchColumn();
        if ($degreeId === false) {
            return null;
        }

        return (int)$degreeId;
    }

    //generate a random temporary password for the advisor account
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

    //get advisors info for the admin dashboard 
    public function getAdvisors()
    {
        return $this->conn->query("SELECT users.User_ID, users.External_ID AS Advisor_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, users.Department_ID, departments.DepartmentID AS DepartmentID, departments.DepartmentName AS Department, users.Phone FROM users JOIN degree ON users.Department_ID = degree.DegreeID JOIN departments ON degree.DepartmentID = departments.DepartmentID WHERE users.Role = 'Advisor'");
    }

    //add an advisor to the database with the information provided by the admin
    public function addAdvisor(?string $externalId, string $first, string $last, string $email, string $phone, int $department): bool
    {
        if ($first === '' || $last === '' || $email === '' || $department <= 0) {
            return false;
        }

        $degreeId = $this->resolveDepartmentToDegreeId($department);
        if ($degreeId === null) {
            return false;
        }

        $first = ucfirst(strtolower($first));
        $last = ucfirst(strtolower($last));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!$this->isValidPhone($phone)) {
            return false;
        }

        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? OR External_ID = ? LIMIT 1');
        $check->execute([$email, (int)$externalId]);
        if ($check->fetch(PDO::FETCH_ASSOC) !== false) {
            return false;
        }

        $tempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        $externalIdInt = (int)$externalId;
        $stmt = $this->conn->prepare('INSERT INTO users (External_ID, Uni_Email, Password, Role, First_name, Last_Name, Phone, Department_ID) VALUES (?, ?, ?, "Advisor", ?, ?, ?, ?)');

        return $stmt->execute([$externalIdInt, $email, $hashedTempPassword, $first, $last, $phone, $degreeId]);
    }

    //delete an advisor from the database
    public function deleteAdvisor(int $advisorID): bool
    {
        if ($advisorID <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM users WHERE External_ID = ? AND Role = "Advisor"');
        return $stmt->execute([$advisorID]);
    }

    //edit advisor information in the database according with the information provided by the admin
    public function editAdvisor(?string $externalId, string $first, string $last, string $email, string $phone, int $department): bool
    {
        if ($first === '' || $last === '' || $email === '' || $department < 0) {
            return false;
        }

        $degreeId = $this->resolveDepartmentToDegreeId($department);
        if ($degreeId === null) {
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!$this->isValidPhone($phone)) {
            return false;
        }

        if ($externalId === null || trim($externalId) === '' || (int)$externalId <= 0) {
            return false;
        }

        $externalIdInt = (int)$externalId;

        $getid = $this->conn->prepare('SELECT User_ID FROM users WHERE External_ID = ? AND Role = "Advisor" LIMIT 1');
        $getid->execute([$externalIdInt]);
        $advisorRow = $getid->fetch(PDO::FETCH_ASSOC);
        if ($advisorRow === false) {
            return false;
        }
        $userId = (int)$advisorRow['User_ID'];

        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? AND User_ID <> ? LIMIT 1');
        $check->execute([$email, $userId]);
        if ($check->fetch(PDO::FETCH_ASSOC) !== false) {
            return false;
        }

        $stmt = $this->conn->prepare('UPDATE users SET Uni_Email = ?, First_name = ?, Last_Name = ?, Phone = ?, Department_ID = ? WHERE User_ID = ? AND Role = "Advisor"');

        return $stmt->execute([$email, $first, $last, $phone, $degreeId, $userId]);
    }
}
