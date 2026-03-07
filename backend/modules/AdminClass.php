<?php
/*Name: AdminClass.php
  Description: This is the class is responsible of handling the management of users (advisors,students,superuser) and
  provide some simple statistic analysis.
  Paraskevas Vafeiadis
  01-Mar-2026 v0.1
  Inputs: Advisor's /Student's/SuperUser's informations
  Outputs: Advisor's /Student's/SuperUser's informations
  Error Messages: Database failure , filehandler error , filepath error.
  Files in Use: Add_Advisor.php / <delete_Advisor / admin_dashboard.php

  03-Mar-2026 v0.2
  Added Student / SuperUser add deletion its the same principle as advisors but with the info of students
  superuser its much simpler as it has only a user account ad we dont store personal information
  Paraskevas Vafeiadis

  03-Mar-2026 v0.3
  Updated Database removed redunduncy and adjusted the code to it. Also removed csv addition for advisors 
  ""NOTES: NEED TO MAKE IT WHEN ADDING A USER FIRST LATTER OF FIRST/LAST NAME TO BE UPPERCASE""
  Paraskevas Vafeiadis

  04-Mar-2026 v0.4
  Finialise CSV addition for the students added Delete/Add supervisor
  Paraskevas Vafeiadis
*/
require_once __DIR__ . '/UsersClass.php';

class Admin extends Users
{
    private $conn;

    public function __construct()
    {
        parent::__construct();
        $this->conn = new mysqli("localhost", "root", "", "advicut");
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
    }

    // Get all the advisors and their details
    public function getAdvisors()
    {
        return $this->conn->query("SELECT User_ID AS Advisor_ID, External_ID, First_name, Last_Name, Department_Name, Phone FROM users WHERE Role = 'Advisor'");
    }

    // Get all the students and their details
    public function getStudents()
    {
        return $this->conn->query("SELECT User_ID AS Student_ID, External_ID AS StuExternal_ID, First_name, Last_Name, Year FROM users WHERE Role = 'Student'");
    }

    public function getSuperUsers(){
        return $this->conn->query("SELECT Uni_Email as Email , User_ID FROM users WHERE Role = 'SuperUser'");
    }
    // Add an advisor to the database, also create a user account with a temp password.
    public function addAdvisor(?string $externalId, string $first, string $last, string $email, string $phone, string $department): bool
    {
        if ($first === '' || $last === '' || $email === '' || $department === '') {
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // check if advisor already exists by email
        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $Result = $check->get_result();
        if ($Result && $Result->num_rows > 0) {
            return false;
        }

        // generate temporary password and create users record (store all advisor fields in users table)
        $TempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($TempPassword, PASSWORD_DEFAULT);

        $externalId_int = (int)$externalId;
        $stmt = $this->conn->prepare('INSERT INTO users (Uni_Email, Password, Role, External_ID, First_name, Last_Name, Phone, Department_Name) VALUES (?, ?, "Advisor", ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssissss', $email, $hashedTempPassword, $externalId_int, $first, $last, $phone, $department);
        if (!$stmt->execute()) {
            return false;
        }

        return true;
    }

    // Delete an advisor (deletes user which cascades to advisor_info)
    public function deleteAdvisor(int $advisorID): bool {
        if ($advisorID <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM users WHERE User_ID = ?');
        $stmt->bind_param('i', $advisorID);
        return $stmt->execute();
    
    
        fclose($handle);
        return ['added' => $added, 'skipped' => $skipped, 'errors' => $errors];
    }

    public function addStudent(?string $externalid, string $first, string $last, string $email, string $year, ?int $advisorID = null): bool
    {
        if ($first === '' || $last === '' || $email === '' || $year === '') {
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($externalid === null || trim($externalid) === '' || (int)$externalid <= 0) {
            return false;
        }

        // check if user already exists by email
        $stmt1 = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? LIMIT 1');
        $stmt1->bind_param('s', $email);
        $stmt1->execute();
        $Result = $stmt1->get_result();
        if ($Result && $Result->num_rows > 0) {
            return false;
        }

        // avoid unique key violation on External_ID
        $external_id_int = (int)$externalid;
        $stmt2 = $this->conn->prepare('SELECT User_ID FROM users WHERE External_ID = ? LIMIT 1');
        $stmt2->bind_param('i', $external_id_int);
        $stmt2->execute();
        $resultExternal = $stmt2->get_result();
        if ($resultExternal && $resultExternal->num_rows > 0) {
            return false;
        }

        // generate temporary password and create users record (store all student fields in users table)
        $TempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($TempPassword, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare('INSERT INTO users (Uni_Email, Password, Role, External_ID, First_name, Last_Name, Year) VALUES (?, ?, "Student", ?, ?, ?, ?)');
        $stmt->bind_param('ssisss', $email, $hashedTempPassword, $external_id_int, $first, $last, $year);
        if (!$stmt->execute()) {
            return false;
        }

        return true;
    }

        public function addStudentByCSV(string $filePath){

        //if no file then error
        if (!is_readable($filePath)) {
            return false;
        }

        //open the file and read every line on the csv tehn call addadvisor for each line.
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return false;
        }

        //keep count for added or skipped entries.
        $added = 0;
        $skipped = 0;
        $errors = [];

        //ignore header row
        $firstRow = fgetcsv($handle);
        if ($firstRow === false) {
            fclose($handle);
            return ['added' => 0, 'skipped' => 0, 'errors' => ['empty_file']];
        }
        //make the values lowercase and check if it contains header values.
        $header = [];
        $isHeader = false;
        $lowerRow = array_map(function ($v) { return strtolower((string)$v); }, $firstRow);
        if (in_array('first_name', $lowerRow, true) || in_array('first', $lowerRow, true) || in_array('email', $lowerRow, true)) {
            $isHeader = true;
            $header = $lowerRow;
        }

        //if the first row is not a header procced it as data otherwise skip.
        if (!$isHeader) {
            $rows = [$firstRow];
        } else {
            $rows = [];
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        //get each row read data then create an advisor.
        //  ***NEED TO CHANGE NAMES WITH THE COLUMNS OF MERIMNA THIS IS FOR TESTING***
        foreach ($rows as $r) {
            $r = array_pad($r, 6, '');

            if ($isHeader && !empty($header)) {
                $map = array_combine($header, $r);
                $external_id = $map['student_id'] ?? $map['id'] ?? '';
                $first = $map['first_name'] ?? $map['first'] ?? $map['firstname'] ?? '';
                $last = $map['last_name'] ?? $map['last'] ?? $map['lastname'] ?? '';
                $email = $map['email'] ?? $map['uni_email'] ?? '';
                $year = $map['year'] ?? ' ';
                $advisorid = $map['advisor'] ?? $map['advisor_id'] ?? $map['advisorid'] ?? '';
            } else {
                [$external_id, $first, $last, $email, $year, $advisorid] = $r;
            }
        
            $first = trim((string)$first);
            $last = trim((string)$last);
            $email = trim((string)$email);
            $year = trim((string)$year);
            $advisorid = (int)trim((string)$advisorid);

            if ($first === '' || $last === '' || $email === '' || $external_id <= 0) {
                $skipped++;
                continue;
            }

           // verify advisor if provided
            if (!empty($advisorid) && $advisorid > 0) {

                $advisorCheck = $this->conn->prepare('SELECT User_ID FROM users WHERE User_ID = ? AND Role = "Advisor"');
                $advisorCheck->bind_param('i', $advisorid);
                $advisorCheck->execute();
                $advisorResult = $advisorCheck->get_result();

            // if advisor not found set it to NULL
                if (!$advisorResult || $advisorResult->num_rows === 0) {
                    $advisorid = null;
                }
            }
            else {
                $advisorid = null;
            }

            //call addstudents and start adding each row
            $success = $this->addStudent($external_id, $first, $last, $email, $year, is_null($advisorid) ? null : $advisorid);
            if ($success) {
                $added++;
            } else {
                $skipped++;
                $errors[] = "{$email}";
            }
        }

        fclose($handle);
        return ['added' => $added, 'skipped' => $skipped, 'errors' => $errors];
    }

    //delete student from db using studentid called by delete_student.php
    public function deleteStudent(int $student_ID): bool
    {
        if ($student_ID <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM users WHERE User_ID = ?');
        $stmt->bind_param('i', $student_ID);
        return $stmt->execute();
    }

    public function addSuperUser(string $email): bool  {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // check if advisor already exists by email
        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $Result = $check->get_result();
        if ($Result->num_rows > 0) {
            return false;
        }

        //generate temporary password and create users record
        //HAVE TO SEND THEM THE TempPassword
        $TempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($TempPassword, PASSWORD_DEFAULT);
        $externalId = $this->getNextExternalId();
        $stmt = $this->conn->prepare('INSERT INTO users (External_ID, Uni_Email, Password, Role , First_Name , Last_Name) VALUES (?, ?, ?, "SuperUser", "SuperUser" , "SuperUser")');
        $stmt->bind_param('iss', $externalId, $email, $hashedTempPassword);
        if (!$stmt->execute()) {
            return false;
        }

        return true;
    }

    //Delete SuperUser
    public function deleteSuperUser(int $user_ID): bool   {
        if ($user_ID <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM users WHERE User_ID = ?');
        $stmt->bind_param('i', $user_ID);
        return $stmt->execute();
    }

    // randomly generate a temporary password for new users.
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


    private function getNextExternalId(): int
    {
        $result = $this->conn->query('SELECT COALESCE(MAX(External_ID), 0) + 1 AS next_external_id FROM users');
        if ($result && ($row = $result->fetch_assoc()) && isset($row['next_external_id'])) {
            return (int)$row['next_external_id'];
        }

        return random_int(100000, 999999);
    }
}
