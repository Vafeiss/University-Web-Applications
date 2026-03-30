<?php

require_once __DIR__ . '/../config/db.php';

class AdminClass
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = getDBConnection();
    }

    // =========================================
    // DASHBOARD / STATS
    // =========================================
    public function getDashboardStats(): array
    {
        $stats = [
            'students' => 0,
            'advisors' => 0,
            'unassigned_students' => 0,
            'appointments' => 0,
            'pending_requests' => 0
        ];

        $queries = [
            'students' => "SELECT COUNT(*) AS total FROM students",
            'advisors' => "SELECT COUNT(*) AS total FROM advisors",
            'unassigned_students' => "SELECT COUNT(*) AS total FROM students WHERE advisor_id IS NULL",
            'appointments' => "SELECT COUNT(*) AS total FROM appointments",
            'pending_requests' => "SELECT COUNT(*) AS total FROM appointment_requests WHERE status = 0"
        ];

        foreach ($queries as $key => $sql) {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats[$key] = (int)($row['total'] ?? 0);
        }

        return $stats;
    }

    // =========================================
    // STUDENTS
    // =========================================
    public function getAllStudents(): array
    {
        $sql = "
            SELECT
                s.id AS student_id,
                s.user_id,
                u.username AS student_name,
                u.email AS student_email,
                s.advisor_id
            FROM students s
            INNER JOIN users u ON s.user_id = u.id
            ORDER BY u.username ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentById(int $studentId): ?array
    {
        $sql = "
            SELECT
                s.id AS student_id,
                s.user_id,
                u.username AS student_name,
                u.email AS student_email,
                s.advisor_id
            FROM students s
            INNER JOIN users u ON s.user_id = u.id
            WHERE s.id = :student_id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->execute();

        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        return $student ?: null;
    }

    public function getUnassignedStudents(): array
    {
        $sql = "
            SELECT
                s.id AS student_id,
                s.user_id,
                u.username AS student_name,
                u.email AS student_email
            FROM students s
            INNER JOIN users u ON s.user_id = u.id
            WHERE s.advisor_id IS NULL
            ORDER BY u.username ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchStudents(string $keyword): array
    {
        $sql = "
            SELECT
                s.id AS student_id,
                s.user_id,
                u.username AS student_name,
                u.email AS student_email,
                s.advisor_id
            FROM students s
            INNER JOIN users u ON s.user_id = u.id
            WHERE u.username LIKE :keyword
               OR u.email LIKE :keyword
            ORDER BY u.username ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================
    // ADVISORS
    // =========================================
    public function getAllAdvisors(): array
    {
        $sql = "
            SELECT
                a.id AS advisor_id,
                a.user_id,
                u.username AS advisor_name,
                u.email AS advisor_email
            FROM advisors a
            INNER JOIN users u ON a.user_id = u.id
            ORDER BY u.username ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdvisorById(int $advisorId): ?array
    {
        $sql = "
            SELECT
                a.id AS advisor_id,
                a.user_id,
                u.username AS advisor_name,
                u.email AS advisor_email
            FROM advisors a
            INNER JOIN users u ON a.user_id = u.id
            WHERE a.id = :advisor_id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':advisor_id', $advisorId, PDO::PARAM_INT);
        $stmt->execute();

        $advisor = $stmt->fetch(PDO::FETCH_ASSOC);

        return $advisor ?: null;
    }

    public function searchAdvisors(string $keyword): array
    {
        $sql = "
            SELECT
                a.id AS advisor_id,
                a.user_id,
                u.username AS advisor_name,
                u.email AS advisor_email
            FROM advisors a
            INNER JOIN users u ON a.user_id = u.id
            WHERE u.username LIKE :keyword
               OR u.email LIKE :keyword
            ORDER BY u.username ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================
    // ASSIGN STUDENTS TO ADVISORS
    // =========================================
    public function assignAdvisorToStudent(int $studentId, int $advisorId): bool
    {
        $sql = "
            UPDATE students
            SET advisor_id = :advisor_id
            WHERE id = :student_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':advisor_id', $advisorId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function removeAdvisorFromStudent(int $studentId): bool
    {
        $sql = "
            UPDATE students
            SET advisor_id = NULL
            WHERE id = :student_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function getStudentsWithAdvisors(): array
    {
        $sql = "
            SELECT
                s.id AS student_id,
                su.username AS student_name,
                su.email AS student_email,
                a.id AS advisor_id,
                au.username AS advisor_name,
                au.email AS advisor_email
            FROM students s
            INNER JOIN users su ON s.user_id = su.id
            LEFT JOIN advisors a ON s.advisor_id = a.id
            LEFT JOIN users au ON a.user_id = au.id
            ORDER BY su.username ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================
    // SYSTEM DATA / REPORTS
    // =========================================
    public function getAppointmentOverview(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_appointments,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS declined
            FROM appointment_requests
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: [
            'total_appointments' => 0,
            'pending' => 0,
            'approved' => 0,
            'declined' => 0
        ];
    }
}