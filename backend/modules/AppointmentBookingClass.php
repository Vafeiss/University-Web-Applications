<?php
declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class AppointmentBooking
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = ConnectToDatabase();
    }

    public function getAssignedAdvisorUserIdForStudent(int $studentId): int
    {
        try {
            $sql = "SELECT Advisor_ID 
                    FROM student_advisors 
                    WHERE Student_ID = ? 
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$studentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result === false || !isset($result['Advisor_ID'])) {
                return 0;
            }

            return (int)$result['Advisor_ID'];
        } catch (Throwable $e) {
            error_log('AppointmentBooking::getAssignedAdvisorUserIdForStudent error: ' . $e->getMessage());
            return 0;
        }
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
        try {
            $advisorId = $this->getAssignedAdvisorUserIdForStudent($studentId);

            if ($advisorId <= 0) {
                return [];
            }

            $sql = "SELECT * 
                    FROM office_hours 
                    WHERE Advisor_ID = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $slots = [];

            foreach ($result as $row) {
                $nextDate = $this->getNextDate((string)$row['Day_of_Week']);

                $checkSql = "SELECT Request_ID
                             FROM appointment_requests
                             WHERE OfficeHour_ID = ?
                               AND Appointment_Date = ?
                               AND Status IN ('Pending', 'Approved')
                               LIMIT 1";

                $checkStmt = $this->conn->prepare($checkSql);
                if ($checkStmt === false) {
                    continue;
                }

                $checkStmt->execute([(int)$row['OfficeHour_ID'], $nextDate]);
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($checkResult !== false) {
                    continue;
                }

                $row['Next_Date'] = $nextDate;
                $slots[] = $row;
            }

            return $slots;
        } catch (Throwable $e) {
            error_log('AppointmentBooking::getAvailableSlotsForStudent error: ' . $e->getMessage());
            return [];
        }
    }

    public function bookAppointment(int $studentId, int $slotId, string $reason): bool
    {
        try {
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
            $slotStmt->execute([$slotId, $advisorId]);
            $slot = $slotStmt->fetch(PDO::FETCH_ASSOC);

            if ($slot === false) {
                return false;
            }

            $nextDate = $this->getNextDate((string)$slot['Day_of_Week']);

            $insertSql = "INSERT INTO appointment_requests
                          (Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Student_Reason, Status)
                          VALUES (?, ?, ?, ?, ?, 'Pending')";

            $insertStmt = $this->conn->prepare($insertSql);
            return $insertStmt->execute([
                $studentId,
                $advisorId,
                $slotId,
                $nextDate,
                $reason,
            ]);
        } catch (Throwable $e) {
            error_log('AppointmentBooking::bookAppointment error: ' . $e->getMessage());
            return false;
        }
    }
}
?>