<?php
/*
  NAME: Admin Class
  Description: This Class is responsible for handling the degree/department management
  Paraskevas Vafeiadis
  27-Mar-2026 v1.1
  Inputs: Various inputs for the functions about degrees and departments
  Outputs: Various outputs for the functions about degrees and departments
  Error Messages : if connection fails throw exception with message
  Files in use: AdminStudentClass.php, AdminAdvisorCLass.php, AdminSuperUserClass.php, Admin_dashboard.php
  
*/

declare(strict_types=1);

require_once __DIR__ . '/UsersClass.php';
require_once __DIR__ . '/databaseconnect.php';
require_once __DIR__ . '/AdminStudentClass.php';
require_once __DIR__ . '/AdminAdvisorClass.php';
require_once __DIR__ . '/AdminSuperUserClass.php';

class Admin extends Users
{
    private PDO $conn;
    private AdminStudentClass $studentAdmin;
    private AdminAdvisorClass $advisorAdmin;
    private AdminSuperUserClass $superUserAdmin;

    //connect to the database in XAMPP using the database connection function from databaseconnect.php and initialize the other admin classes for students, advisors and superusers
    public function __construct()
    {
        parent::__construct();
        $this->conn = ConnectToDatabase();
        $this->studentAdmin = new AdminStudentClass();
        $this->advisorAdmin = new AdminAdvisorClass();
        $this->superUserAdmin = new AdminSuperUserClass();
    }

    //get students information for the admin dashboard with the filters provided by the admin in the dashboard
    public function getStudentsByFilters(string $yearInput = '', int $department = 0, int $degree = 0)
    {
        return $this->studentAdmin->getStudentsByFilters($yearInput, $department, $degree);
    }

    //get students information for the admin dashboard with the year filter provided by the admin in the dashboard
    public function getStudentsByYear(string $yearInput)
    {
        return $this->studentAdmin->getStudentsByYear($yearInput);
    }

    //get students information for the admin dashboard with the department filter provided by the admin in the dashboard
    public function getStudentsByDegree(int $degree)
    {
        return $this->studentAdmin->getStudentsByDegree($degree);
    }

    //get students information for the admin dashboard with the degree filter provided by the admin in the dashboard
    public function getStudents()
    {
        return $this->studentAdmin->getStudents();
    }

    //add a student to the database with the information provided by the admin
    public function addStudent(?string $externalid, string $first, string $last, string $email, int $degree, string $year, ?int $advisorID = null): bool
    {
        return $this->studentAdmin->addStudent($externalid, $first, $last, $email, $degree, $year, $advisorID);
    }

    //add students to the database by uploding a CSV file with the information provided by the admin
    public function addStudentByCSV(string $filePath)
    {
        return $this->studentAdmin->addStudentByCSV($filePath);
    }

    //delete a student from the database
    public function deleteStudent(int $student_ID): bool
    {
        return $this->studentAdmin->deleteStudent($student_ID);
    }

    //edit a student information in the database according with the information provided by the admin
    public function editStudent(?string $externalid, string $first, string $last, string $email, int $degree, string $year, ?int $advisorID = null): bool
    {
        return $this->studentAdmin->editStudent($externalid, $first, $last, $email, $degree, $year, $advisorID);
    }

    //get advisors info for the admin dashboard
    public function getAdvisors()
    {
        return $this->advisorAdmin->getAdvisors();
    }

    //add an advisor to the database with the information provided by the admin
    public function addAdvisor(?string $externalId, string $first, string $last, string $email, string $phone, int $department): bool
    {
        return $this->advisorAdmin->addAdvisor($externalId, $first, $last, $email, $phone, $department);
    }

    //delete an advisor from the database
    public function deleteAdvisor(int $advisorID): bool
    {
        return $this->advisorAdmin->deleteAdvisor($advisorID);
    }

    //edit advisor information in the database according with the information provided by the admin
    public function editAdvisor(?string $externalId, string $first, string $last, string $email, string $phone, int $department): bool
    {
        return $this->advisorAdmin->editAdvisor($externalId, $first, $last, $email, $phone, $department);
    }

    //get superusers info for the admin dashboard
    public function getSuperUsers()
    {
        return $this->superUserAdmin->getSuperUsers();
    }

    //add a superuser to the database with the information provided by the admin
    public function addSuperUser(string $email, int $externalId): bool
    {
        return $this->superUserAdmin->addSuperUser($email, $externalId);
    }

    //delete a superuser from the database
    public function deleteSuperUser(int $user_ID): bool
    {
        return $this->superUserAdmin->deleteSuperUser($user_ID);
    }

    //edit degree information in the database according with the information provided by the admin
    public function editDegree(int $degreeId, string $degreeName, int $departmentId): bool
    {
        if ($degreeName === '' || $degreeId <= 0 || $departmentId <= 0) {
            return false;
        }

        try {
            $this->conn->beginTransaction();
            $stmt2 = $this->conn->prepare('UPDATE degree SET DegreeName = ?, DepartmentID = ? WHERE DegreeID = ?');
            $stmt2->execute([$degreeName, $departmentId, $degreeId]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    
    //add a department to the database with the information provided by the admin
    public function addDepartment(string $departmentName): bool
    {
        if ($departmentName === '') {
            return false;
        }

        try {
            $DepartmentName = ucfirst(strtolower($departmentName));
            $stmt = $this->conn->prepare('INSERT INTO departments (DepartmentName) VALUES (?)');
            $stmt->execute([$DepartmentName]);
            if ($stmt->rowCount() === 0) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    //add a degree to the database with the information provided by the admin
    public function addDegree(string $degreeName, int $departmentId): bool
    {
        if ($degreeName === '' || $departmentId <= 0) {
            return false;
        }

        try {
            $this->conn->beginTransaction();
            //go into the degree table get the max id and the store the next degree as max + 1
            $DegreeName = ucfirst(strtolower($degreeName));
            $nextIdStmt = $this->conn->query('SELECT COALESCE(MAX(DegreeID), 0) + 1 AS NextDegreeID FROM degree');
            $nextId = (int)($nextIdStmt->fetch(PDO::FETCH_ASSOC)['NextDegreeID'] ?? 0);
            if ($nextId <= 0) {
                throw new RuntimeException('Failed to generate DegreeID.');
            }

            $stmt = $this->conn->prepare('INSERT INTO degree (DegreeID, DepartmentID, DegreeName) VALUES (?, ?, ?)');
            $stmt->execute([$nextId, $departmentId, $DegreeName]);
            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return false;
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    //delete a degree from the database and return structured result details
    public function deleteDegreeDetailed(int $degreeId): array
    {
        if ($degreeId <= 0) {
            return ['success' => false, 'code' => 'invalid_id'];
        }

        try {
            // Do not delete a degree that is still assigned to students.
            $inUseStmt = $this->conn->prepare('SELECT 1 FROM studentdegree WHERE DegreeID = ? LIMIT 1');
            $inUseStmt->execute([$degreeId]);
            if ($inUseStmt->fetchColumn() !== false) {
                return ['success' => false, 'code' => 'in_use'];
            }

            $stmt = $this->conn->prepare('DELETE FROM degree WHERE DegreeID = ?');
            $stmt->execute([$degreeId]);
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'code' => 'not_found'];
            }
            return ['success' => true, 'code' => 'deleted'];
        } catch (Exception $e) {
            return ['success' => false, 'code' => 'error'];
        }
    }

    //backward-compatible wrapper used by older codepaths
    public function deleteDegree(int $degreeId): bool
    {
        $result = $this->deleteDegreeDetailed($degreeId);
        return (bool)($result['success'] ?? false);
    }

    //delete a department from the database and return structured result details
    public function deleteDepartmentDetailed(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return ['success' => false, 'code' => 'invalid_id'];
        }

        try {
            // Do not delete a department that still has related degrees/advisors.
            $degreeDepStmt = $this->conn->prepare('SELECT 1 FROM degree WHERE DepartmentID = ? LIMIT 1');
            $degreeDepStmt->execute([$departmentId]);
            if ($degreeDepStmt->fetchColumn() !== false) {
                return ['success' => false, 'code' => 'in_use_degree'];
            }

            $advisorDepStmt = $this->conn->prepare('SELECT 1 FROM advisordepartment WHERE DepartmentID = ? LIMIT 1');
            $advisorDepStmt->execute([$departmentId]);
            if ($advisorDepStmt->fetchColumn() !== false) {
                return ['success' => false, 'code' => 'in_use_advisor'];
            }

            $stmt = $this->conn->prepare('DELETE FROM departments WHERE DepartmentID = ?');
            $stmt->execute([$departmentId]);
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'code' => 'not_found'];
            }
            return ['success' => true, 'code' => 'deleted'];
        } catch (Exception $e) {
            return ['success' => false, 'code' => 'error'];
        }
    }

    //backward-compatible wrapper used by older codepaths
    public function deleteDepartment(int $departmentId): bool
    {
        $result = $this->deleteDepartmentDetailed($departmentId);
        return (bool)($result['success'] ?? false);
    }

    //edit department information in the database according with the information provided by the admin
    public function editDepartment(int $departmentId, string $departmentName)
    {
        if ($departmentId < 0 || $departmentName === '') {
            return false;
        }

        try {
            $this->conn->beginTransaction();
            $stmt2 = $this->conn->prepare('UPDATE departments SET DepartmentName = ? WHERE DepartmentID = ?');
            $stmt2->execute([ucfirst(strtolower($departmentName)), $departmentId]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }
}
