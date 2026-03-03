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
        return $this->conn->query("SELECT Advisor_ID, External_ID, First_name, Last_Name, Department_Name, Phone FROM advisor_info");
    }

    // Get all the students and their details
    public function getStudents()
    {
        return $this->conn->query("SELECT Student_ID, StuExternal_ID, First_name, Last_Name, Year , Advisor_ID FROM student_info");
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

        // generate temporary password and create users record
        $TempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($TempPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('INSERT INTO users (Uni_Email, Password, Role) VALUES (?, ?, "Advisor")');
        $stmt->bind_param('ss', $email, $hashedTempPassword);
        if (!$stmt->execute()) {
            return false;
        }

        // insert advisor_info row linked to the newly created user id
        $advisorId = $this->conn->insert_id;
        $stmt2 = $this->conn->prepare(
            'INSERT INTO advisor_info (Advisor_ID, External_ID, First_name, Last_Name, Phone, Department_Name) VALUES (?, ?, ?, ?, ?, ?)' );
        
        $extVal = ($externalId === null || $externalId === '') ? null : $externalId;
        $stmt2->bind_param('isssss', $advisorId, $extVal, $first, $last, $phone, $department);
        return $stmt2->execute();
    }

    // Delete an advisor (deletes user which cascades to advisor_info)
    public function deleteAdvisor(int $advisorID): bool
    {
        if ($advisorID <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM users WHERE User_ID = ?');
        $stmt->bind_param('i', $advisorID);
        return $stmt->execute();
    }

    public function addAdvisorByCSV(string $filePath){

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

        //skip the first row if it contains header values
        $header = [];
        $isHeader = false;
        $lowerRow = array_map(function ($v) { return strtolower((string)$v); }, $firstRow);
        if (in_array('first_name', $lowerRow, true) || in_array('first', $lowerRow, true) || in_array('email', $lowerRow, true)) {
            $isHeader = true;
            $header = $lowerRow;
        }

        //if the first row is not a header procced
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
                $external_id = $map['external_id'] ?? $map['id'] ?? '';
                $first = $map['first_name'] ?? $map['first'] ?? $map['firstname'] ?? '';
                $last = $map['last_name'] ?? $map['last'] ?? $map['lastname'] ?? '';
                $email = $map['email'] ?? $map['uni_email'] ?? '';
                $phone = $map['phone'] ?? $map['telephone'] ?? '';
                $department = $map['department'] ?? $map['dept'] ?? '';
            } else {
                [$external_id, $first, $last, $email, $phone, $department] = $r;
            }

            $first = trim((string)$first);
            $last = trim((string)$last);
            $email = trim((string)$email);
            $phone = trim((string)$phone);
            $department = trim((string)$department);

            if ($first === '' || $last === '' || $email === '' || $department === '') {
                $skipped++;
                continue;
            }
            //call addAdvisor and start adding each row
            $success = $this->addAdvisor($external_id, $first, $last, $email, $phone, $department);
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

    public function addStudent(?string $externalId, string $first, string $last, string $email, string $year, string $advisorID): bool
    {
        if ($first === '' || $last === '' || $email === '' || $year === '') {
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // check if advisor already exists by email
        $stmt1 = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? LIMIT 1');
        $stmt1->bind_param('s', $email);
        $stmt1->execute();
        $Result = $stmt1->get_result();
        if ($Result && $Result->num_rows > 0) {
            return false;
        }

        // generate temporary password and create users record
        $TempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($TempPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('INSERT INTO users (Uni_Email, Password, Role) VALUES (?, ?, "Student")');
        $stmt->bind_param('ss', $email, $hashedTempPassword);
        if (!$stmt->execute()) {
            return false;
        }

        // insert student_info row linked to the newly created user id
        $studentID = $this->conn->insert_id;
        $stmt2 = $this->conn->prepare(
            'INSERT INTO student_info (Student_ID, StuExternal_ID, First_name, Last_Name, Year, Advisor_ID) VALUES (?, ?, ?, ?, ?, ?)' );
        //check if errors exists in the external ID 
        $externalValue = ($externalId === null || $externalId === '') ? null : $externalId;
        $stmt2->bind_param('isssss', $studentID, $externalValue, $first, $last, $year, $advisorID);
        return $stmt2->execute();
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
                $external_id = $map['external_id'] ?? $map['id'] ?? '';
                $first = $map['first_name'] ?? $map['first'] ?? $map['firstname'] ?? '';
                $last = $map['last_name'] ?? $map['last'] ?? $map['lastname'] ?? '';
                $email = $map['email'] ?? $map['uni_email'] ?? '';
                $year = $map['year'] ?? ' ';
                $advisorid = $map['advisor'] ?? $map['advisor_id'] ?? $map['advisorid'] ?? '';
            } else {
                [$external_id, $first, $last, $email, $phone, $department] = $r;
            }
        
            $first = trim((string)$first);
            $last = trim((string)$last);
            $email = trim((string)$email);
            $year = trim((string)$year);
            $advisorid = trim((int)$advisorid);

            if ($first === '' || $last === '' || $email === '' || $advisorid == 0) {
                $skipped++;
                continue;
            }
            //call addstudents and start adding each row
            $success = $this->addStudent($external_id, $first, $last, $email, $phone, $department);
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
    public function addSuperUser(string $email): bool
    {
        if ($email === '') {
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

        // generate temporary password and create users record
        $TempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($TempPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('INSERT INTO users (Uni_Email, Password, Role) VALUES (?, ?, "SuperUser")');
        $stmt->bind_param('ss', $email, $hashedTempPassword);
        if (!$stmt->execute()) {
            return false;
        }
    }

    public function deleteSuperUser(int $User_ID): bool   {
        if ($User_ID <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM users WHERE User_ID = ?');
        $stmt->bind_param('i', $User_ID);
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
}
