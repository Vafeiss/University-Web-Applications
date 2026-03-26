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

  13-Mar-2026 v0.5
  Added some error handling and some new validation for the csv inputs in the normalizeYear and editStudent/advisor
  Paraskevas Vafeiadis

  15-Mar-2026 v0.6
  made some changes in the getadvisor/getstudents queries to adjust to the admindashboard to client needs
  Paraskevas Vafeiadis

  16-Mar-2026 v0.7
  added some changes to the addstudent for errorhandling it had some issues with the advisor linking
  added is validphonenumber function and used to for add/edit advisor and also some error handling for it in the admincontroller.
  Paraskevas Vafeiadis

  22-Mar-2026 v0.8
  fixed some bugs in edit student and added some error handling. Added new functions to add/edit/delete degree and also added the routes for them.
  Paraskevas Vafeiadis

  24-Mar-2026 v0.9
  Added department filtering , fixed some bugs because of the new database table
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

    private function normalizeYear(string $yearInput): string
    {
        $value = strtolower(trim($yearInput));
        $map = [
            
            '1' => '1',
            'year 1' => '1',
            'first' => '1',
            '2' => '2',
            'year 2' => '2',
            'second' => '2',
            '3' => '3',
            'year 3' => '3',
            'third' => '3',
            '4' => '4',
            'year 4' => '4',
            'fourth' => '4',
            '5' => '5',
            'year 5' => '5',
            'fifth' => '5',
            '6' => '6',
            'year 6' => '6',
            'sixth' => '6',
        ];

        return $map[$value] ?? '';
    }

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

    //make department --> to degree ID
    private function resolveDepartmentToDegreeId(int $departmentId): ?int
    {
        if ($departmentId <= 0) {
            return null;
        }

        $stmt = $this->conn->prepare('SELECT DegreeID FROM degree WHERE DepartmentID = ? ORDER BY DegreeID ASC LIMIT 1');
        if ($stmt === false) {
            return null;
        }

        $stmt->bind_param('i', $departmentId);
        if (!$stmt->execute()) {
            return null;
        }

        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) {
            return null;
        }

        return (int)$result->fetch_assoc()['DegreeID'];
    }


    public function getStudentsByFilters(string $yearInput = '', int $department = 0, int $degree = 0)
    {
        $normalizedYear = null;
        $trimmedYear = trim($yearInput);
        if ($trimmedYear !== '') {
            $normalizedYear = $this->normalizeYear($trimmedYear);
            if ($normalizedYear === '') {
                return false;
            }
        }

        $query = 'SELECT users.User_ID AS Student_ID, users.External_ID AS StuExternal_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, users.Department_ID AS Degree_ID, students.Year, degree.DegreeName AS Degree, sa.Advisor_ID, departments.DepartmentName AS Department
            FROM users
            JOIN degree ON users.Department_ID = degree.DegreeID
            JOIN departments ON degree.DepartmentID = departments.DepartmentID
            JOIN students ON users.User_ID = students.User_ID
            LEFT JOIN student_advisors sa ON sa.Student_ID = users.External_ID
            WHERE users.Role = "Student"';

        if ($normalizedYear !== null) {
            $query .= ' AND students.Year = ' . (int)$normalizedYear;
        }

        if ($department > 0) {
            $query .= ' AND departments.DepartmentID = ' . (int)$department;
        }

        if ($degree > 0) {
            $query .= ' AND degree.DegreeID = ' . (int)$degree;
        }

        $query .= ' ORDER BY students.Year ASC';

        return $this->conn->query($query);
    }

    public function getStudentsByYear(String $yearInput){

        $normalizedYear = $this->normalizeYear($yearInput);
        if ($normalizedYear === '') {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT users.User_ID AS Student_ID, users.External_ID AS StuExternal_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, users.Department_ID AS Degree_ID, students.Year, degree.DegreeName AS Degree, sa.Advisor_ID
            FROM users
            JOIN degree ON users.Department_ID = degree.DegreeID
            JOIN departments ON degree.DepartmentID = departments.DepartmentID
            JOIN students ON users.User_ID = students.User_ID
            LEFT JOIN student_advisors sa ON sa.Student_ID = users.External_ID
            WHERE users.Role = "Student" AND students.Year = ?
            ORDER BY students.Year ASC'
        );

        if ($stmt === false) {
            return false;
        }

        $stmt->bind_param('s', $normalizedYear);
        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->get_result();

    }

    //get students based on the degree they are in
    public function getStudentsByDegree(int $degree){

        $stmt = $this->conn->prepare(
            'SELECT users.User_ID AS Student_ID, users.External_ID AS StuExternal_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, users.Department_ID AS Degree_ID, students.Year, degree.DegreeName AS Degree, sa.Advisor_ID
            FROM users
            JOIN degree ON users.Department_ID = degree.DegreeID
            JOIN departments ON degree.DepartmentID = departments.DepartmentID
            JOIN students ON users.User_ID = students.User_ID
            LEFT JOIN student_advisors sa ON sa.Student_ID = users.External_ID
            WHERE users.Role = "Student" AND degree.DegreeID = ?
            ORDER BY students.Year ASC'
        );

        if ($stmt === false) {
            return false;
        }

        $stmt->bind_param('i', $degree);
        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->get_result();

    }



    //get all the advisors and their details
    public function getAdvisors()
    {
        return $this->conn->query("SELECT users.User_ID, users.External_ID AS Advisor_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, users.Department_ID, departments.DepartmentID AS DepartmentID, departments.DepartmentName AS Department, users.Phone FROM users JOIN degree ON users.Department_ID = degree.DegreeID JOIN departments ON degree.DepartmentID = departments.DepartmentID WHERE users.Role = 'Advisor'");
    }

    //get all the students and their details
    public function getStudents()
    {
        return $this->conn->query("SELECT users.User_ID AS Student_ID, users.External_ID AS StuExternal_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, users.Department_ID AS Department_ID, students.Year, degree.DegreeName AS Degree, sa.Advisor_ID , departments.DepartmentName as Department FROM users JOIN degree ON users.Department_ID = degree.DegreeID JOIN departments ON degree.DepartmentID = departments.DepartmentID LEFT JOIN student_advisors sa ON sa.Student_ID = users.External_ID LEFT JOIN students ON users.User_ID = students.User_ID WHERE users.Role = 'Student' ORDER BY students.Year ASC");
    }

    public function getSuperUsers(){
        return $this->conn->query("SELECT Uni_Email as Email , User_ID FROM users WHERE Role = 'SuperUser'");
    }
    //add an advisor to the database, also create a user account with a temp password.
    public function addAdvisor(?string $externalId, string $first, string $last, string $email, string $phone, int $department): bool
    {
        if ($first === '' || $last === '' || $email === '' || $department === '') {
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

        //check if advisor already exists by email
        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? OR External_ID = ? LIMIT 1');
        $check->bind_param('si', $email, $externalId);
        $check->execute();
        $Result = $check->get_result();
        if ($Result && $Result->num_rows > 0) {
            return false;
        }

        // generate temporary password and create users record (store all advisor fields in users table)
        $TempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($TempPassword, PASSWORD_DEFAULT);

        $externalId_int = (int)$externalId;
        $stmt = $this->conn->prepare('INSERT INTO users (Uni_Email, Password, Role, External_ID, First_name, Last_Name, Phone, Department_ID) VALUES (?, ?, "Advisor", ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssisssi', $email, $hashedTempPassword, $externalId_int, $first, $last, $phone, $degreeId);
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

        $stmt = $this->conn->prepare('DELETE FROM users WHERE External_ID = ? AND Role = "Advisor"');
        $stmt->bind_param('i', $advisorID);
        return $stmt->execute();
    }

    public function addStudent(?string $externalid, string $first, string $last, string $email, int $degree, string $year, ?int $advisorID = null): bool
    {
        if ($first === '' || $last === '' || $email === '' || $year === '') {
            return false;
        }

        $first = ucfirst(strtolower($first));
        $last = ucfirst(strtolower($last));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($externalid === null || trim($externalid) === '' || (int)$externalid <= 0) {
            return false;
        }

        $normalizedYear = $this->normalizeYear($year);
        
        if ($normalizedYear === '') {
            return false;
        }

        if ($degree <= 0) {
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

        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare('INSERT INTO users (Uni_Email, Password, Role, External_ID, First_name, Last_Name, Department_ID) VALUES (?, ?, "Student", ?, ?, ?, ?)');
            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare student insert statement.');
            }
            $stmt2 = $this->conn->prepare('INSERT INTO students (User_ID, Year) VALUES (LAST_INSERT_ID(), ?)');
            if ($stmt2 === false) {
                throw new RuntimeException('Failed to prepare student info insert statement.');
            }

            $stmt->bind_param('ssissi', $email, $hashedTempPassword, $external_id_int, $first, $last, $degree);
            if (!$stmt->execute()) {
                throw new RuntimeException('Failed to insert student record.');
            }

            $stmt2->bind_param('i', $normalizedYear);
            if (!$stmt2->execute()) {
                throw new RuntimeException('Failed to insert student info record.');
            }

            if ($advisorID !== null && $advisorID > 0) {
                $advisorCheck = $this->conn->prepare('SELECT External_ID FROM users WHERE External_ID = ? AND Role = "Advisor" LIMIT 1');
                if ($advisorCheck === false) {
                    throw new RuntimeException('Failed to prepare advisor lookup statement.');
                }

                $advisorCheck->bind_param('i', $advisorID);
                if (!$advisorCheck->execute()) {
                    throw new RuntimeException('Failed to validate advisor record.');
                }

                $advisorResult = $advisorCheck->get_result();
                if ($advisorResult && $advisorResult->num_rows > 0) {
                    $linkStmt = $this->conn->prepare('INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?) ON DUPLICATE KEY UPDATE Advisor_ID = VALUES(Advisor_ID)');
                    if ($linkStmt === false) {
                        throw new RuntimeException('Failed to prepare advisor link statement.');
                    }

                    $linkStmt->bind_param('ii', $external_id_int, $advisorID);
                    if (!$linkStmt->execute()) {
                        throw new RuntimeException('Failed to save student advisor link.');
                    }
                }
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $exception) {
            $this->conn->rollback();
            return false;
        }
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

        //get each row read data then create students.
        foreach ($rows as $r) {
            $r = array_pad($r, 7, '');

            if ($isHeader && !empty($header)) {
                $map = array_combine($header, $r);
                $external_id = $map['student_id'] ?? $map['student_external_id'] ?? $map['external_id'] ?? $map['id'] ?? '';
                $first = $map['first_name'] ?? $map['first'] ?? $map['firstname'] ?? '';
                $last = $map['last_name'] ?? $map['last'] ?? $map['lastname'] ?? '';
                $email = $map['email'] ?? $map['uni_email'] ?? '';
                $degree = $map['degree'] ?? $map['degree_id'] ?? $map['department'] ?? '1';
                $year = $map['year'] ?? '';
                $advisorid = $map['advisor'] ?? $map['advisor_id'] ?? $map['advisorid'] ?? '';
            } else {
                [$external_id, $first, $last, $email, $degree, $year, $advisorid] = $r;
            }
        
            $external_id = trim((string)$external_id);
            $first = trim((string)$first);
            $last = trim((string)$last);
            $email = trim((string)$email);
            $degree = (int)trim((string)$degree);
            if ($degree <= 0) {
                $degree = 1;
            }
            $year = trim((string)$year);
            $advisoridRaw = trim((string)$advisorid);
            $advisorid = ($advisoridRaw === '' ? null : (int)$advisoridRaw);

            if ($first === '' || $last === '' || $email === '' || $external_id === '' || (int)$external_id <= 0) {
                $skipped++;
                continue;
            }

           // verify advisor if provided
            if (!is_null($advisorid) && $advisorid > 0) {

                $advisorCheck = $this->conn->prepare('SELECT External_ID FROM users WHERE External_ID = ? AND Role = "Advisor"');
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
            $success = $this->addStudent($external_id, $first, $last, $email, $degree, $year, $advisorid);
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

    //function to generate ExtrenalId for superuser bcs its unique
    private function getNextExternalId(): int
    {
        $result = $this->conn->query('SELECT COALESCE(MAX(External_ID), 0) + 1 AS next_external_id FROM users');
        if ($result && ($row = $result->fetch_assoc()) && isset($row['next_external_id'])) {
            return (int)$row['next_external_id'];
        }

        return random_int(100000, 999999);
    }

    //Edit advisor using the advisor id to find the advisor and update the info with the new one provided.
    public function editAdvisor(?string $externalId, string $first, string $last, string $email, string $phone, int $department): bool {
        if ($first === '' || $last === '' || $email === '' || $department === '') {
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

        //get the id of the advisor to update
        $getid = $this->conn->prepare('SELECT User_ID FROM users WHERE External_ID = ? AND Role = "Advisor" LIMIT 1');
        $getid->bind_param('i', $externalIdInt);
        $getid->execute();
        $getidResult = $getid->get_result();
        if (!$getidResult || $getidResult->num_rows === 0) {
            return false;
        }
        $Userid = (int)$getidResult->fetch_assoc()['User_ID'];

        // Prevent collision with another advisor's email.
        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? AND User_ID <> ? LIMIT 1');
        $check->bind_param('si', $email, $Userid);
        $check->execute();
        $Result = $check->get_result();
        if ($Result && $Result->num_rows > 0) {
            return false;
        }

        $stmt = $this->conn->prepare('UPDATE users SET Uni_Email = ?, First_name = ?, Last_Name = ?, Phone = ?, Department_ID = ? WHERE User_ID = ? AND Role = "Advisor"');
        $stmt->bind_param('ssssii', $email, $first, $last, $phone, $degreeId, $Userid);
        if (!$stmt->execute()) {
            return false;
        }

        return true;
    }

    //Edit students using the sudent id to find the student and update the info with the new one provided.
    public function editStudent(?string $externalid, string $first, string $last, string $email, int $degree, string $year, ?int $advisorID = null): bool {
    
        //check if first name, last name, email and year are not empty
        if ($first === '' || $last === '' || $email === '' || $year === '') {
            return false;
        }

        //check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        //check if external id is valid
        if ($externalid === null || trim($externalid) === '' || (int)$externalid <= 0) {
            return false;
        }

        $normalizedYear = $this->normalizeYear($year);
        if ($normalizedYear === '') {
            return false;
        }

        if ($degree <= 0) {
            $degree = 1;
        }

        $first = ucfirst(strtolower($first));
        $last = ucfirst(strtolower($last));

        $externalIdInt = (int)$externalid;

        //get the id of the student to update
        $getid = $this->conn->prepare('SELECT User_ID FROM users WHERE External_ID = ? AND Role = "Student" LIMIT 1');
        $getid->bind_param('i', $externalIdInt);
        $getid->execute();
        $getidResult = $getid->get_result();
        if (!$getidResult || $getidResult->num_rows === 0) {
            return false;
        }

        //get the userid to update the student info
        $Userid = (int)$getidResult->fetch_assoc()['User_ID'];

        // Prevent collision with another student's email.
        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? AND User_ID <> ? LIMIT 1');
        $check->bind_param('si', $email, $Userid);
        $check->execute();
        $Result = $check->get_result();
        if ($Result && $Result->num_rows > 0) {
            return false;
        }

        $this->conn->begin_transaction();

        try {
            // update basic student user fields
            $stmt = $this->conn->prepare('UPDATE users SET Uni_Email = ?, First_name = ?, Last_Name = ?, Department_ID = ? WHERE User_ID = ? AND Role = "Student"');
            if ($stmt === false) {
                throw new RuntimeException('Failed to prepare student update statement.');
            }
            $stmt->bind_param('sssii', $email, $first, $last, $degree, $Userid);
            if (!$stmt->execute()) {
                throw new RuntimeException('Failed to update student user record.');
            }

            // update student-specific data
            $yearStmt = $this->conn->prepare('UPDATE students SET Year = ? WHERE User_ID = ?');
            if ($yearStmt === false) {
                throw new RuntimeException('Failed to prepare student year update statement.');
            }
            $yearStmt->bind_param('ii', $normalizedYear, $Userid);
            if (!$yearStmt->execute()) {
                throw new RuntimeException('Failed to update student year.');
            }

            if ($advisorID !== null && $advisorID > 0) {
                $advisorCheck = $this->conn->prepare('SELECT External_ID FROM users WHERE External_ID = ? AND Role = "Advisor" LIMIT 1');
                if ($advisorCheck === false) {
                    throw new RuntimeException('Failed to prepare advisor lookup statement.');
                }

                $advisorCheck->bind_param('i', $advisorID);
                if (!$advisorCheck->execute()) {
                    throw new RuntimeException('Failed to validate advisor record.');
                }

                $advisorResult = $advisorCheck->get_result();
                if ($advisorResult && $advisorResult->num_rows > 0) {
                    $linkStmt = $this->conn->prepare('INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?) ON DUPLICATE KEY UPDATE Advisor_ID = VALUES(Advisor_ID)');
                    if ($linkStmt === false) {
                        throw new RuntimeException('Failed to prepare advisor link statement.');
                    }

                    $linkStmt->bind_param('ii', $externalIdInt, $advisorID);
                    if (!$linkStmt->execute()) {
                        throw new RuntimeException('Failed to update student advisor link.');
                    }
                }
            } else {
                // remove advisor link when set to no advisor
                $unlinkStmt = $this->conn->prepare('DELETE FROM student_advisors WHERE Student_ID = ?');
                if ($unlinkStmt === false) {
                    throw new RuntimeException('Failed to prepare advisor unlink statement.');
                }
                $unlinkStmt->bind_param('i', $externalIdInt);
                if (!$unlinkStmt->execute()) {
                    throw new RuntimeException('Failed to remove student advisor link.');
                }
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $exception) {
            $this->conn->rollback();
            return false;
        }
    }


    //function to be able to edit any degree / department inside the database
    public function editDegree(int $degreeId, string $degreeName, string $departmentName): bool {

        if ($degreeName === '' || $departmentName === '' || $degreeId <= 0) {
            return false;
        }

        try{
            $DegreeName = ucfirst(strtolower($degreeName));
            $DepartmentName = ucfirst(strtolower($departmentName));
            $stmt = $this->conn->prepare('UPDATE degree SET Department_Name = ?, DegreeName = ? WHERE DegreeID = ?');
            $stmt->bind_param('ssi', $DepartmentName, $DegreeName, $degreeId);
            $stmt->execute();
            if($stmt->affected_rows === 0) {
                return false;
            }
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    //function to be able to add a department inside the database
    public function addDepartment(string $departmentName): bool {
        if ($departmentName === '') {
            return false;
        }

        try{
            $DepartmentName = ucfirst(strtolower($departmentName));
            $stmt = $this->conn->prepare('INSERT INTO departments (DepartmentName) VALUES (?)');
            $stmt->bind_param('s', $DepartmentName);
            $stmt->execute();
            if($stmt->affected_rows === 0) {
                return false;
            }
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    //function to be able to add any degree/department inside the database
    public function addDegree(string $degreeName, int $departmentId): bool {

        if ($degreeName === '' || $departmentId === 0) {
            return false;
        }

        try{
            $DegreeName = ucfirst(strtolower($degreeName));
            $stmt = $this->conn->prepare('INSERT INTO degree (Department_ID, DegreeName) VALUES (?, ?)');
            $stmt->bind_param('is', $departmentId, $DegreeName);
            $stmt->execute();
            if($stmt->affected_rows === 0) {
                return false;
            }
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteDegree(int $degreeId): bool {

        if ($degreeId <= 0) {
            return false;
        }

        try{
            $stmt = $this->conn->prepare('DELETE FROM degree WHERE DegreeID = ?');
            $stmt->bind_param('i', $degreeId);
            $stmt->execute();
            if($stmt->affected_rows === 0) {
                return false;
            }
            return true;

        } catch (Exception $e) {
            return false;
        }

    }

}