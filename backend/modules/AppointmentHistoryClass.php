<?php
declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AppointmentHistory
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = ConnectToDatabase();
    }

    public function getStudentHistory(int $studentId): array
    {
        try {
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
            $stmt->execute([$studentId]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($result) ? $result : [];
        } catch (Throwable $e) {
            error_log('AppointmentHistory::getStudentHistory error: ' . $e->getMessage());
            return [];
        }
    }

    public function getAdvisorHistory(int $advisorId): array
    {
        try {
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
            $stmt->execute([$advisorId]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($result) ? $result : [];
        } catch (Throwable $e) {
            error_log('AppointmentHistory::getAdvisorHistory error: ' . $e->getMessage());
            return [];
        }
    }
}
?>