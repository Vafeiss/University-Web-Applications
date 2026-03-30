<?php
declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AppointmentApproval
{
    private mysqli $conn;

    public function __construct()
    {
        $db = new DatabaseConnect();
        $this->conn = $db->connect();
    }

    public function getPendingAppointmentsForAdvisor(int $advisorId): array
    {
        $sql = "SELECT ar.*, u.First_name, u.Last_Name, oh.Start_Time, oh.End_Time
                FROM appointment_requests ar
                LEFT JOIN users u ON ar.Student_ID = u.User_ID
                LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
                WHERE ar.Advisor_ID = ?
                  AND ar.Status = 'Pending'
                ORDER BY ar.Appointment_Date ASC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $advisorId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function approveAppointment(int $requestId, int $advisorId): bool
    {
        $updateSql = "UPDATE appointment_requests
                      SET Status = 'Approved'
                      WHERE Request_ID = ?
                        AND Advisor_ID = ?
                        AND Status = 'Pending'";

        $updateStmt = $this->conn->prepare($updateSql);
        if (!$updateStmt) {
            return false;
        }

        $updateStmt->bind_param("ii", $requestId, $advisorId);
        $ok = $updateStmt->execute();

        if (!$ok || $updateStmt->affected_rows <= 0) {
            return false;
        }

        $fetchSql = "SELECT ar.Student_ID, ar.Advisor_ID, ar.OfficeHour_ID, ar.Appointment_Date,
                            oh.Start_Time, oh.End_Time
                     FROM appointment_requests ar
                     LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
                     WHERE ar.Request_ID = ?
                     LIMIT 1";

        $fetchStmt = $this->conn->prepare($fetchSql);
        if (!$fetchStmt) {
            return false;
        }

        $fetchStmt->bind_param("i", $requestId);
        $fetchStmt->execute();
        $fetchResult = $fetchStmt->get_result();

        if (!$fetchResult || $fetchResult->num_rows === 0) {
            return false;
        }

        $row = $fetchResult->fetch_assoc();

        $insertSql = "INSERT INTO appointments
                      (Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Start_Time, End_Time, Status)
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled')";

        $insertStmt = $this->conn->prepare($insertSql);
        if (!$insertStmt) {
            return false;
        }

        $insertStmt->bind_param(
            "iiiisss",
            $requestId,
            $row['Student_ID'],
            $row['Advisor_ID'],
            $row['OfficeHour_ID'],
            $row['Appointment_Date'],
            $row['Start_Time'],
            $row['End_Time']
        );

        return $insertStmt->execute();
    }

    public function declineAppointment(int $requestId, int $advisorId, string $reason): bool
    {
        $reason = trim($reason);

        if ($reason === '') {
            return false;
        }

        $sql = "UPDATE appointment_requests
                SET Status = 'Declined',
                    Advisor_Reason = ?
                WHERE Request_ID = ?
                  AND Advisor_ID = ?
                  AND Status = 'Pending'";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sii", $reason, $requestId, $advisorId);
        return $stmt->execute();
    }

    public function markAttendance(int $appointmentId, int $advisorId, int $attendance): bool
    {
        $newStatus = match ($attendance) {
            1 => 'Completed',
            2 => 'Cancelled',
            default => ''
        };

        if ($newStatus === '') {
            return false;
        }

        $sql = "UPDATE appointments
                SET Status = ?
                WHERE Appointment_ID = ?
                  AND Advisor_ID = ?
                  AND Status = 'Scheduled'";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sii", $newStatus, $appointmentId, $advisorId);
        return $stmt->execute();
    }
}
?>