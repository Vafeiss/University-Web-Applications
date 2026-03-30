<?php
declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AppointmentHistory
{
    private $conn;

    public function __construct()
    {
        $db = new DatabaseConnect();
        $this->conn = $db->connect();
    }

    public function getStudentHistory(int $studentId): array
    {
        $sql = "SELECT
                    ar.Request_ID,
                    ar.Appointment_Date,
                    ar.Student_Reason,
                    ar.Advisor_Reason,
                    ar.Status,
                    oh.Start_Time,
                    oh.End_Time,
                    u.First_name AS Advisor_First_Name,
                    u.Last_Name AS Advisor_Last_Name
                FROM appointment_requests ar
                LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
                LEFT JOIN users u ON ar.Advisor_ID = u.User_ID
                WHERE ar.Student_ID = ?
                ORDER BY ar.Appointment_Date DESC, oh.Start_Time DESC, ar.Request_ID DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getAdvisorHistory(int $advisorId): array
    {
        $sql = "SELECT
                    ar.Request_ID,
                    ar.Appointment_Date,
                    ar.Student_Reason,
                    ar.Advisor_Reason,
                    ar.Status,
                    oh.Start_Time,
                    oh.End_Time,
                    u.First_name AS Student_First_Name,
                    u.Last_Name AS Student_Last_Name
                FROM appointment_requests ar
                LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
                LEFT JOIN users u ON ar.Student_ID = u.User_ID
                WHERE ar.Advisor_ID = ?
                ORDER BY ar.Appointment_Date DESC, oh.Start_Time DESC, ar.Request_ID DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("i", $advisorId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>