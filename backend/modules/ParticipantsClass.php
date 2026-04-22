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
    
    public function RandomAssignment(): bool
    {
        $this->conn->beginTransaction();

        try {
            $studentStmt = $this->conn->prepare(
                "SELECT u.External_ID FROM users u LEFT JOIN student_advisors sa ON sa.Student_ID = u.External_ID WHERE u.Role = 'Student'
                   AND u.External_ID IS NOT NULL AND sa.Student_ID IS NULL ORDER BY u.External_ID ASC"
            );
            $studentStmt->execute();
            $students = array_map(
                static fn (array $row): int => (int)($row['External_ID'] ?? 0),
                $studentStmt->fetchAll(PDO::FETCH_ASSOC)
            );
            $students = array_values(array_filter($students, static fn (int $studentId): bool => $studentId > 0));

            $advisorStmt = $this->conn->prepare(
                "SELECT External_ID FROM users WHERE Role = 'Advisor' AND External_ID IS NOT NULL ORDER BY External_ID ASC"
            );
            $advisorStmt->execute();
            $advisors = array_map(
                static fn (array $row): int => (int)($row['External_ID'] ?? 0),
                $advisorStmt->fetchAll(PDO::FETCH_ASSOC)
            );
            $advisors = array_values(array_filter($advisors, static fn (int $advisorId): bool => $advisorId > 0));

            if (empty($advisors)) {
                throw new PDOException('Missing advisors for random assignment');
            }

            if (empty($students)) {
                $this->conn->commit();
                return true;
            }

            shuffle($students);
            shuffle($advisors);

            $pairCount = min(count($students), count($advisors));
            $insertStmt = $this->conn->prepare('INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?) ON DUPLICATE KEY UPDATE Advisor_ID = VALUES(Advisor_ID)');

            for ($i = 0; $i < $pairCount; $i++) {
                $insertStmt->execute([$students[$i], $advisors[$i]]);
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}


