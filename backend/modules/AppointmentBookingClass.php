<?php
declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AppointmentBooking
{
    private mysqli $conn;

    public function __construct()
    {
        $db = new DatabaseConnect();
        $this->conn = $db->connect();
    }

    public function getAssignedAdvisorUserIdForStudent(int $studentId): int
    {
        $sql = "SELECT Advisor_ID 
                FROM student_advisors 
                WHERE Student_ID = ? 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows === 0) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)$row['Advisor_ID'];
    }

    private function getWeekdayNumber(string $day): int
    {
        return match (strtolower(trim($day))) {
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7,
            default => 0,
        };
    }

    private function getNextDate(string $dayOfWeek): string
    {
        $today = new DateTime();
        $currentDayNumber = (int)$today->format('N');
        $targetDayNumber = $this->getWeekdayNumber($dayOfWeek);

        if ($targetDayNumber === 0) {
            return $today->format('Y-m-d');
        }

        $difference = $targetDayNumber - $currentDayNumber;

        if ($difference < 0) {
            $difference += 7;
        }

        $nextDate = new DateTime();

        if ($difference > 0) {
            $nextDate->modify("+{$difference} days");
        }

        return $nextDate->format('Y-m-d');
    }

    public function getAvailableSlotsForStudent(int $studentId): array
    {
        $advisorId = $this->getAssignedAdvisorUserIdForStudent($studentId);

        if ($advisorId <= 0) {
            return [];
        }

        $sql = "SELECT * 
                FROM office_hours 
                WHERE Advisor_ID = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $advisorId);
        $stmt->execute();
        $result = $stmt->get_result();

        $slots = [];

        while ($row = $result->fetch_assoc()) {
            $nextDate = $this->getNextDate((string)$row['Day_of_Week']);

            $checkSql = "SELECT Request_ID
                         FROM appointment_requests
                         WHERE OfficeHour_ID = ?
                           AND Appointment_Date = ?
                           AND Status IN ('Pending', 'Approved')
                         LIMIT 1";

            $checkStmt = $this->conn->prepare($checkSql);
            if (!$checkStmt) {
                continue;
            }

            $checkStmt->bind_param("is", $row['OfficeHour_ID'], $nextDate);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult && $checkResult->num_rows > 0) {
                continue;
            }

            $row['Next_Date'] = $nextDate;
            $slots[] = $row;
        }

        return $slots;
    }

    public function bookAppointment(int $studentId, int $slotId, string $reason): bool
    {
        $reason = trim($reason);

        if ($reason === '') {
            return false;
        }

        $advisorId = $this->getAssignedAdvisorUserIdForStudent($studentId);

        if ($advisorId <= 0) {
            return false;
        }

        $slotSql = "SELECT * 
                    FROM office_hours 
                    WHERE OfficeHour_ID = ? 
                      AND Advisor_ID = ?";

        $slotStmt = $this->conn->prepare($slotSql);
        if (!$slotStmt) {
            return false;
        }

        $slotStmt->bind_param("ii", $slotId, $advisorId);
        $slotStmt->execute();
        $slotResult = $slotStmt->get_result();

        if (!$slotResult || $slotResult->num_rows === 0) {
            return false;
        }

        $slot = $slotResult->fetch_assoc();
        $nextDate = $this->getNextDate((string)$slot['Day_of_Week']);

        $insertSql = "INSERT INTO appointment_requests
                      (Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Student_Reason, Status)
                      VALUES (?, ?, ?, ?, ?, 'Pending')";

        $insertStmt = $this->conn->prepare($insertSql);
        if (!$insertStmt) {
            return false;
        }

        $insertStmt->bind_param(
            "iiiss",
            $studentId,
            $advisorId,
            $slotId,
            $nextDate,
            $reason
        );

        return $insertStmt->execute();
    }
}
?>