<?php
/* NAME: Admin Student Class
   Description: This Class is responsible for handling the student management
   Paraskevas Vafeiadis
   27-Mar-2026 v1.1
   Inputs: Various inputs for the functions about students
   Outputs: Various outputs for the functions about students
   Error Messages : if connection fails throw exception with message
   Files in use: AdminClass.php, Admin_dashboard.php
*/

declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AdminStudentClass
{
    private PDO $conn;

    //connect to the database in XAMPP using the database connection function from databaseconnect.php
    public function __construct()
    {
        $this->conn = ConnectToDatabase();
    }

    //use this function to normalizse the year inputs into numeric values for the database queries (TABLE STUDENTS GETS INTEGERS FOR THE YEAR COLUMN)
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

    //generate a random temporary password for the student account
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

    //get students information for the admin dashboard with the filters provided by the admin in the dashboard
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

        $query = 'SELECT users.User_ID AS Student_ID, users.External_ID AS StuExternal_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, sd.DegreeID AS Degree_ID, students.Year, degree.DegreeName AS Degree, sa.Advisor_ID, departments.DepartmentName AS Department
            FROM users
            JOIN studentdegree sd ON users.User_ID = sd.User_ID
            JOIN degree ON sd.DegreeID = degree.DegreeID
            JOIN departments ON degree.DepartmentID = departments.DepartmentID
            JOIN students ON users.User_ID = students.User_ID
            LEFT JOIN student_advisors sa ON sa.Student_ID = users.External_ID
            WHERE users.Role = :role';

        $params = [':role' => 'Student'];

        if ($normalizedYear !== null) {
            $query .= ' AND students.Year = :year';
            $params[':year'] = (int)$normalizedYear;
        }

        if ($department > 0) {
            $query .= ' AND departments.DepartmentID = :department';
            $params[':department'] = $department;
        }

        if ($degree > 0) {
            $query .= ' AND degree.DegreeID = :degree';
            $params[':degree'] = $degree;
        }

        $query .= ' ORDER BY students.Year ASC';

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt;
    }

    //get students information for the admin dashboard by year
    public function getStudentsByYear(string $yearInput)
    {
        $normalizedYear = $this->normalizeYear($yearInput);
        if ($normalizedYear === '') {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT users.User_ID AS Student_ID, users.External_ID AS StuExternal_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, sd.DegreeID AS Degree_ID, students.Year, degree.DegreeName AS Degree, sa.Advisor_ID
            FROM users
            JOIN studentdegree sd ON users.User_ID = sd.User_ID
            JOIN degree ON sd.DegreeID = degree.DegreeID
            JOIN departments ON degree.DepartmentID = departments.DepartmentID
            JOIN students ON users.User_ID = students.User_ID
            LEFT JOIN student_advisors sa ON sa.Student_ID = users.External_ID
            WHERE users.Role = "Student" AND students.Year = ?
            ORDER BY students.Year ASC'
        );

        $stmt->execute([(int)$normalizedYear]);

        return $stmt;
    }

    //get students by degree for the admin dashboard
    public function getStudentsByDegree(int $degree)
    {
        $stmt = $this->conn->prepare(
            'SELECT users.User_ID AS Student_ID, users.External_ID AS StuExternal_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, sd.DegreeID AS Degree_ID, students.Year, degree.DegreeName AS Degree, sa.Advisor_ID
            FROM users
            JOIN studentdegree sd ON users.User_ID = sd.User_ID
            JOIN degree ON sd.DegreeID = degree.DegreeID
            JOIN departments ON degree.DepartmentID = departments.DepartmentID
            JOIN students ON users.User_ID = students.User_ID
            LEFT JOIN student_advisors sa ON sa.Student_ID = users.External_ID
            WHERE users.Role = "Student" AND degree.DegreeID = ?
            ORDER BY students.Year ASC'
        );

        $stmt->execute([$degree]);

        return $stmt;
    }

    //get students information for the admin dashboard
    public function getStudents()
    {
        return $this->conn->query("SELECT users.User_ID AS Student_ID, users.External_ID AS StuExternal_ID, users.First_name, users.Last_Name, users.Uni_Email AS Email, sd.DegreeID AS Degree_ID, students.Year, degree.DegreeName AS Degree, sa.Advisor_ID, departments.DepartmentName AS Department, departments.DepartmentID AS Department_ID FROM users JOIN studentdegree sd ON users.User_ID = sd.User_ID JOIN degree ON sd.DegreeID = degree.DegreeID JOIN departments ON degree.DepartmentID = departments.DepartmentID LEFT JOIN student_advisors sa ON sa.Student_ID = users.External_ID LEFT JOIN students ON users.User_ID = students.User_ID WHERE users.Role = 'Student' ORDER BY students.Year ASC");
    }

    //add students to the database with the information provided by the admin
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

        $stmt1 = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? LIMIT 1');
        $stmt1->execute([$email]);
        if ($stmt1->fetch(PDO::FETCH_ASSOC) !== false) {
            return false;
        }

        $externalIdInt = (int)$externalid;
        $stmt2 = $this->conn->prepare('SELECT User_ID FROM users WHERE External_ID = ? LIMIT 1');
        $stmt2->execute([$externalIdInt]);
        if ($stmt2->fetch(PDO::FETCH_ASSOC) !== false) {
            return false;
        }

        $tempPassword = $this->generateTempPassword(12);
        $hashedTempPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare('INSERT INTO users (Uni_Email, Password, Role, External_ID, First_name, Last_Name) VALUES (?, ?, "Student", ?, ?, ?)');
            if (!$stmt->execute([$email, $hashedTempPassword, $externalIdInt, $first, $last])) {
                throw new RuntimeException('Failed to insert student record.');
            }

            $userId = (int)$this->conn->lastInsertId();

            $degreeStmt = $this->conn->prepare('INSERT INTO studentdegree (User_ID, DegreeID) VALUES (?, ?)');
            if (!$degreeStmt->execute([$userId, $degree])) {
                throw new RuntimeException('Failed to insert student degree mapping.');
            }

            $stmt2 = $this->conn->prepare('INSERT INTO students (User_ID, Year) VALUES (?, ?)');
            if (!$stmt2->execute([$userId, (int)$normalizedYear])) {
                throw new RuntimeException('Failed to insert student info record.');
            }

            if ($advisorID !== null && $advisorID > 0) {
                $advisorCheck = $this->conn->prepare('SELECT External_ID FROM users WHERE External_ID = ? AND Role = "Advisor" LIMIT 1');
                $advisorCheck->execute([$advisorID]);
                if ($advisorCheck->fetch(PDO::FETCH_ASSOC) !== false) {
                    $linkStmt = $this->conn->prepare('INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?) ON DUPLICATE KEY UPDATE Advisor_ID = VALUES(Advisor_ID)');
                    if (!$linkStmt->execute([$externalIdInt, $advisorID])) {
                        throw new RuntimeException('Failed to save student advisor link.');
                    }
                }
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    //add students to the database by uploading a CSV file with the information provided by the admin
    public function addStudentByCSV(string $filePath)
    {
        if (!is_readable($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return false;
        }

        $added = 0;
        $skipped = 0;
        $errors = [];

        $firstRow = fgetcsv($handle);
        if ($firstRow === false) {
            fclose($handle);
            return ['added' => 0, 'skipped' => 0, 'errors' => ['empty_file']];
        }

        $header = [];
        $isHeader = false;
        $lowerRow = array_map(static function ($v) {
            return strtolower((string)$v);
        }, $firstRow);

        if (in_array('first_name', $lowerRow, true) || in_array('first', $lowerRow, true) || in_array('email', $lowerRow, true)) {
            $isHeader = true;
            $header = $lowerRow;
        }

        $rows = $isHeader ? [] : [$firstRow];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

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
            $year = trim((string)$year);
            $advisoridRaw = trim((string)$advisorid);
            $advisorid = ($advisoridRaw === '' ? null : (int)$advisoridRaw);

            if ($first === '' || $last === '' || $email === '' || $external_id === '' || (int)$external_id <= 0 || $degree <= 0) {
                $skipped++;
                continue;
            }

            if (!is_null($advisorid) && $advisorid > 0) {
                $advisorCheck = $this->conn->prepare('SELECT External_ID FROM users WHERE External_ID = ? AND Role = "Advisor"');
                $advisorCheck->execute([$advisorid]);
                if ($advisorCheck->fetch(PDO::FETCH_ASSOC) === false) {
                    $advisorid = null;
                }
            } else {
                $advisorid = null;
            }

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

    //delete students from the database by providing the student ID
    public function deleteStudent(int $student_ID): bool
    {
        if ($student_ID <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare('DELETE FROM users WHERE User_ID = ? AND Role = "Student"');
        return $stmt->execute([$student_ID]);
    }

    //edit student information in the database according with the information provided by the admin
    public function editStudent(?string $externalid, string $first, string $last, string $email, int $degree, string $year, ?int $advisorID = null): bool
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

        $getid = $this->conn->prepare('SELECT User_ID FROM users WHERE External_ID = ? AND Role = "Student" LIMIT 1');
        $getid->execute([$externalIdInt]);
        $studentRow = $getid->fetch(PDO::FETCH_ASSOC);
        if ($studentRow === false) {
            return false;
        }

        $userId = (int)$studentRow['User_ID'];

        $check = $this->conn->prepare('SELECT User_ID FROM users WHERE Uni_Email = ? AND User_ID <> ? LIMIT 1');
        $check->execute([$email, $userId]);
        if ($check->fetch(PDO::FETCH_ASSOC) !== false) {
            return false;
        }

        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare('UPDATE users SET Uni_Email = ?, First_name = ?, Last_Name = ? WHERE User_ID = ? AND Role = "Student"');
            if (!$stmt->execute([$email, $first, $last, $userId])) {
                throw new RuntimeException('Failed to update student user record.');
            }

            $degreeStmt = $this->conn->prepare('INSERT INTO studentdegree (User_ID, DegreeID) VALUES (?, ?) ON DUPLICATE KEY UPDATE DegreeID = VALUES(DegreeID)');
            if (!$degreeStmt->execute([$userId, $degree])) {
                throw new RuntimeException('Failed to update student degree mapping.');
            }

            $yearStmt = $this->conn->prepare('UPDATE students SET Year = ? WHERE User_ID = ?');
            if (!$yearStmt->execute([(int)$normalizedYear, $userId])) {
                throw new RuntimeException('Failed to update student year.');
            }

            if ($advisorID !== null && $advisorID > 0) {
                $advisorCheck = $this->conn->prepare('SELECT External_ID FROM users WHERE External_ID = ? AND Role = "Advisor" LIMIT 1');
                $advisorCheck->execute([$advisorID]);
                if ($advisorCheck->fetch(PDO::FETCH_ASSOC) !== false) {
                    $linkStmt = $this->conn->prepare('INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?) ON DUPLICATE KEY UPDATE Advisor_ID = VALUES(Advisor_ID)');
                    if (!$linkStmt->execute([$externalIdInt, $advisorID])) {
                        throw new RuntimeException('Failed to update student advisor link.');
                    }
                }
            } else {
                $unlinkStmt = $this->conn->prepare('DELETE FROM student_advisors WHERE Student_ID = ?');
                if (!$unlinkStmt->execute([$externalIdInt])) {
                    throw new RuntimeException('Failed to remove student advisor link.');
                }
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }
}
