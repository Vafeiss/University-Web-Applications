<?php
/*
  NAME: Admin Appointment Reports Class
  Description: Standalone class for appointment report data for the admin dashboard
  Panteleimoni Alexandrou
  06-Apr-2026 v0.2
  Inputs: None
  Outputs: Appointment statistics for admin reports
  Error Messages: Returns safe default values if query fails
*/

declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AdminAppointmentReportsClass
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = ConnectToDatabase();
    }

    public function getAppointmentSummary(): array
    {
        $summary = [
            'total_requests' => 0,
            'pending_requests' => 0,
            'approved_requests' => 0,
            'declined_requests' => 0
        ];

        try {
            $sql = "
                SELECT
                    COUNT(*) AS total_requests,
                    SUM(CASE WHEN LOWER(TRIM(Status)) = 'pending' THEN 1 ELSE 0 END) AS pending_requests,
                    SUM(CASE WHEN LOWER(TRIM(Status)) = 'approved' THEN 1 ELSE 0 END) AS approved_requests,
                    SUM(CASE WHEN LOWER(TRIM(Status)) = 'declined' THEN 1 ELSE 0 END) AS declined_requests
                FROM appointment_requests
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $summary['total_requests'] = (int)($row['total_requests'] ?? 0);
                $summary['pending_requests'] = (int)($row['pending_requests'] ?? 0);
                $summary['approved_requests'] = (int)($row['approved_requests'] ?? 0);
                $summary['declined_requests'] = (int)($row['declined_requests'] ?? 0);
            }
        } catch (Throwable $e) {
            return $summary;
        }

        return $summary;
    }

    public function getAdvisorAppointmentCounts(): array
    {
        try {
            $sql = "
                SELECT
                    u.External_ID AS Advisor_ID,
                    u.First_name,
                    u.Last_Name,
                    COUNT(ar.Request_ID) AS Total_Requests,
                    SUM(CASE WHEN LOWER(TRIM(ar.Status)) = 'pending' THEN 1 ELSE 0 END) AS Pending_Requests,
                    SUM(CASE WHEN LOWER(TRIM(ar.Status)) = 'approved' THEN 1 ELSE 0 END) AS Approved_Requests,
                    SUM(CASE WHEN LOWER(TRIM(ar.Status)) = 'declined' THEN 1 ELSE 0 END) AS Declined_Requests
                FROM users u
                LEFT JOIN appointment_requests ar
                    ON ar.Advisor_ID = u.User_ID
                WHERE u.Role = 'Advisor'
                GROUP BY u.External_ID, u.First_name, u.Last_Name
                ORDER BY Total_Requests DESC, u.Last_Name ASC, u.First_name ASC
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}