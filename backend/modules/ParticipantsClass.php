<?php
/* Name: ParicipantsClass
   Description: This class is responsible for handling the processing of the assignment of students to advisors.
   Paraskevas Vafeiadis
   08-Mar-2026 v0.1
   Inputs: Depends on the functions but mostly arrays of IDs
   Outputs: Depends on the functions but mostly arrays of IDs or boolean values
   Files in Use: routes.php, AdminController.php, admin_dashboard.php
   
   15-Mar-2026 v0.2
   added random assignment feature that works with a roundrobin function
   Paraskevas Vafeiadis

   01-Apr-2026 v0.3
   migrated to PDO connection and direct 1-1 random pairing
   Paraskevas Vafeiadis
   */

declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class Participants_Processing
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = ConnectToDatabase();
    }

    public function Get_Student_Advisor(): array
    {
        $stmt = $this->conn->prepare('SELECT Advisor_ID, Student_ID FROM student_advisors');
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $advisorId = (int)($row['Advisor_ID'] ?? 0);
            $studentId = (int)($row['Student_ID'] ?? 0);

            if ($advisorId <= 0 || $studentId <= 0) {
                continue;
            }

            if (!isset($map[$advisorId])) {
                $map[$advisorId] = [];
            }

            $map[$advisorId][$studentId] = true;
        }

        return $map;
    }

    //assignment of students to advisors
    public function Assign_Students_Advisors(): array
    {
        $stmt = $this->conn->prepare('SELECT Student_ID, Advisor_ID FROM student_advisors');
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $studentId = (int)($row['Student_ID'] ?? 0);
            $advisorId = (int)($row['Advisor_ID'] ?? 0);

            if ($studentId <= 0 || $advisorId <= 0) {
                continue;
            }

            if (!isset($map[$studentId])) {
                $map[$studentId] = [];
            }

            $map[$studentId][] = $advisorId;
        }

        return $map;
    }

    //replace students of an advisor with other students
    public function Replace_Advisor_Students(int $advisorId, array $studentIds): bool
    {
        if ($advisorId <= 0) {
            return false;
        }

        $checkIds = [];
        foreach ($studentIds as $studentId) {
            $studentId = (int)$studentId;
            if ($studentId > 0) {
                $checkIds[$studentId] = true;
            }
        }

        $checkIds = array_keys($checkIds);
        $this->conn->beginTransaction();

        try {
            $deleteStmt = $this->conn->prepare('DELETE FROM student_advisors WHERE Advisor_ID = ?');
            $deleteStmt->execute([$advisorId]);

            if (!empty($checkIds)) {
                $insertStmt = $this->conn->prepare('INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?)');
                foreach ($checkIds as $studentId) {
                    $insertStmt->execute([$studentId, $advisorId]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    //random assignment function that pairs students and advisors in a RR fashion
    public function RandomAssignment(): bool
    {
        // Scope assignment per department: group students and advisors by department,
        // then run round-robin within each department.
        $this->conn->beginTransaction();

        try {
            // Prepare statements
            $studentStmt = $this->conn->prepare(
                "SELECT u.External_ID FROM users u
                  JOIN studentdegree sd ON u.User_ID = sd.User_ID
                  JOIN degree d ON sd.DegreeID = d.DegreeID
                  LEFT JOIN student_advisors sa ON sa.Student_ID = u.External_ID
                  WHERE u.Role = 'Student' AND u.External_ID IS NOT NULL AND sa.Student_ID IS NULL AND d.DepartmentID = :dept
                  ORDER BY u.External_ID ASC"
            );

            $advisorStmt = $this->conn->prepare(
                "SELECT u.External_ID FROM users u
                  JOIN advisordepartment ad ON u.User_ID = ad.User_ID
                  WHERE u.Role = 'Advisor' AND u.External_ID IS NOT NULL AND ad.DepartmentID = :dept
                  ORDER BY u.External_ID ASC"
            );

            $insertStmt = $this->conn->prepare('INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?) ON DUPLICATE KEY UPDATE Advisor_ID = VALUES(Advisor_ID)');

            // Get list of departments to consider (departments table)
            $deptRows = $this->conn->query('SELECT DepartmentID FROM departments')->fetchAll(PDO::FETCH_ASSOC);
            $departmentIds = array_map(static fn(array $r) => (int)($r['DepartmentID'] ?? 0), $deptRows);
            $departmentIds = array_values(array_filter($departmentIds, static fn(int $d): bool => $d > 0));

            foreach ($departmentIds as $deptId) {
                // fetch unassigned students in this department
                $studentStmt->bindValue(':dept', $deptId, PDO::PARAM_INT);
                $studentStmt->execute();
                $students = array_map(static fn(array $row): int => (int)($row['External_ID'] ?? 0), $studentStmt->fetchAll(PDO::FETCH_ASSOC));
                $students = array_values(array_filter($students, static fn(int $id): bool => $id > 0));

                // fetch advisors in this department
                $advisorStmt->bindValue(':dept', $deptId, PDO::PARAM_INT);
                $advisorStmt->execute();
                $advisors = array_map(static fn(array $row): int => (int)($row['External_ID'] ?? 0), $advisorStmt->fetchAll(PDO::FETCH_ASSOC));
                $advisors = array_values(array_filter($advisors, static fn(int $id): bool => $id > 0));

                if (empty($students)) {
                    // nothing to do for this department
                    continue;
                }

                if (empty($advisors)) {
                    // Log warning and skip department
                    error_log(sprintf('RandomAssignment: Department %d has %d students but no advisors — skipping', $deptId, count($students)));
                    continue;
                }

                // Shuffle and round-robin assign within department
                shuffle($students);
                shuffle($advisors);
                $advisorCount = count($advisors);
                $studentCount = count($students);

                for ($i = 0; $i < $studentCount; $i++) {
                    $studentId = $students[$i];
                    $advisorId = $advisors[$i % $advisorCount];
                    $insertStmt->execute([$studentId, $advisorId]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log('RandomAssignment failed: ' . $e->getMessage());
            return false;
        }
    }
}


