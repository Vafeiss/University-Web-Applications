<?php
/* NAME: StudentClass.php
   Description: Student dashboard data-access methods.
   Paraskevas Vafeiadis
   28-Mar-2026 v0.1
   Files in Use: databaseconnect.php, CommunicationsClass.php
*/

declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';
require_once __DIR__ . '/CommunicationsClass.php';

class StudentClass{
    private PDO $conn;
    private CommunicationsClass $communications;

    //constructor to initialize the database connection
    public function __construct()
    {
        $this->conn = ConnectToDatabase();
        $this->communications = new CommunicationsClass();
    }

    //get the advisor assigned to the student based on the student's User_ID
    public function getAssignedAdvisor(int $studentUserId){
        $stmt = $this->conn->prepare('SELECT a.User_ID, a.First_name, a.Last_Name FROM student_advisors sa JOIN users a ON a.External_ID = sa.Advisor_ID AND a.Role = "Advisor" WHERE sa.Student_ID = (SELECT External_ID FROM users WHERE User_ID = ? AND Role = "Student" LIMIT 1) LIMIT 1');
        $stmt->execute([$studentUserId]);
        $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($advisor === false) {
            return null;
        }
        return $advisor;
    }

    //get student's own information for dashboard needs
    public function getStudentInfo(int $studentUserId): array
    {
        try {
            $stmt = $this->conn->prepare('SELECT User_ID, External_ID AS Student_ID, First_name, Last_Name, Uni_Email AS Email FROM users WHERE User_ID = ? AND Role = "Student" LIMIT 1');
            $stmt->execute([$studentUserId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($student) ? $student : [];
        } catch (Throwable $e) {
            error_log('StudentClass::getStudentInfo error: ' . $e->getMessage());
            return [];
        }
    }

    //keep name aligned with student dashboard usage
    public function getStudentAdvisor(int $studentUserId): ?array
    {
        $advisor = $this->getAssignedAdvisor($studentUserId);
        return is_array($advisor) ? $advisor : null;
    }

    //resolve advisor user id from student user id for student messaging operations
    public function getAdvisorUserIdForStudent(int $studentUserId): ?int
    {
        $advisor = $this->getAssignedAdvisor($studentUserId);
        if (!is_array($advisor) || empty($advisor['User_ID'])) {
            return null;
        }

        return (int)$advisor['User_ID'];
    }

    private function isAdvisorAssignedToStudent(int $advisorId, int $studentId): bool
    {
        $assignedAdvisorId = $this->getAdvisorUserIdForStudent($studentId);
        return $assignedAdvisorId !== null && $assignedAdvisorId === $advisorId;
    }

    //get the message thread between the advisor and student, with informations for each one
    public function getMessageThread(int $advisorId, int $studentId): array
    {
        try {
            if (!$this->isAdvisorAssignedToStudent($advisorId, $studentId)) {
                return [];
            }

            $conversationId = $this->communications->getConversationId($advisorId, $studentId);
            if ($conversationId === null) {
                return [];
            }

            $history = $this->communications->getHistory($conversationId);
            if (!is_array($history)) {
                return [];
            }

            $messages = [];
            foreach ($history as $row) {
                $isStudent = ((int)$row['Sender_ID'] === $studentId);
                $messages[] = [
                    'id' => (int)$row['Message_ID'],
                    'body' => (string)$row['Message_Text'],
                    'sent_at' => $row['Sent_At'],
                    'sender' => $isStudent ? 'student' : 'advisor',
                    'sender_name' => trim(((string)($row['First_name'] ?? '')) . ' ' . ((string)($row['Last_Name'] ?? ''))),
                ];
            }

            return $messages;
        } catch (Throwable $e) {
            error_log('StudentClass::getMessageThread error: ' . $e->getMessage());
            return [];
        }
    }

    //send a message to the advisor from the student and update the database with the message conversation(sender info and timestamp on the message)
    public function sendMessage(int $advisorId, int $studentId, string $messageBody): bool
    {
        try {
            if (!$this->isAdvisorAssignedToStudent($advisorId, $studentId)) {
                return false;
            }

            $conversationId = $this->communications->getOrCreateConversationId($advisorId, $studentId);
            if ($conversationId === null) {
                return false;
            }

            return $this->communications->sendMessage($conversationId, $studentId, $messageBody);
        } catch (Throwable $e) {
            error_log('StudentClass::sendMessage error: ' . $e->getMessage());
            return false;
        }
    }

    //mark messages as read when a student opens the communications tab, update the database with the new status of the messages
    public function markMessagesRead(int $advisorId, int $studentId): bool
    {
        try {
            if (!$this->isAdvisorAssignedToStudent($advisorId, $studentId)) {
                return false;
            }

            $conversationId = $this->communications->getConversationId($advisorId, $studentId);
            if ($conversationId === null) {
                // No conversation means nothing to mark; treat as successful no-op.
                return true;
            }

            return $this->communications->markMessagesRead($conversationId, $studentId);
        } catch (Throwable $e) {
            error_log('StudentClass::markMessagesRead error: ' . $e->getMessage());
            return false;
        }
    }

    // Get blocked additional slots (used in booking validation)
    public function getBlockedAdditionalSlots(): array
    {
        try {
            $sql = "SELECT AdditionalSlot_ID
                    FROM appointment_requests
                    WHERE AdditionalSlot_ID IS NOT NULL
                      AND LOWER(TRIM(Status)) IN ('pending', 'approved')
                    UNION
                    SELECT AdditionalSlot_ID
                    FROM appointments
                    WHERE AdditionalSlot_ID IS NOT NULL
                      AND Status = 'Scheduled'";

            $stmt = $this->conn->query($sql);
            if (!($stmt instanceof PDOStatement)) {
                return [];
            }

            $blockedSlots = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $blockedSlots[(int)$row['AdditionalSlot_ID']] = true;
            }
            return $blockedSlots;
        } catch (Throwable $e) {
            error_log('StudentClass::getBlockedAdditionalSlots error: ' . $e->getMessage());
            return [];
        }
    }

    // Get available recurring office hour slots for student
    public function getAvailableRecurringSlots(int $advisorUserId): array
    {
        try {
            $sql = "SELECT OfficeHour_ID, Advisor_ID, Day_of_Week, Start_Time, End_Time
                    FROM office_hours
                    WHERE Advisor_ID = ?
                    ORDER BY
                        FIELD(Day_of_Week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                        Start_Time ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('StudentClass::getAvailableRecurringSlots error: ' . $e->getMessage());
            return [];
        }
    }

    // Get available additional slots for student booking
    public function getAvailableAdditionalSlots(int $advisorUserId): array
    {
        try {
            $todayDate = date('Y-m-d');
            $sql = "SELECT AdditionalSlot_ID, Slot_Date, Start_Time, End_Time
                    FROM advisor_additional_slots
                    WHERE Advisor_ID = ?
                      AND Is_Active = 1
                      AND Slot_Date >= ?
                    ORDER BY Slot_Date ASC, Start_Time ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId, $todayDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('StudentClass::getAvailableAdditionalSlots error: ' . $e->getMessage());
            return [];
        }
    }

    // Get pending appointment requests for student
    public function getPendingRequests(int $studentUserId): array
    {
        try {
            $sql = "SELECT ar.Request_ID,ar.Student_ID, ar.Advisor_ID,u.First_name AS Advisor_First_Name, u.Last_Name AS Advisor_Last_Name,
                           ar.OfficeHour_ID, ar.Appointment_Date, ar.Student_Reason,ar.Advisor_Reason,ar.Status,ar.Created_At
                    FROM appointment_requests ar
                    LEFT JOIN users u ON ar.Advisor_ID = u.User_ID
                    WHERE ar.Student_ID = ?
                      AND LOWER(TRIM(Status)) = 'pending'
                    ORDER BY ar.Created_At DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$studentUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('StudentClass::getPendingRequests error: ' . $e->getMessage());
            return [];
        }
    }

    // Get approved appointments for student
    public function getApprovedAppointments(int $studentUserId): array
    {
        try {
            $sql = "SELECT a.Appointment_ID, a.Request_ID, a.Student_ID, a.Advisor_ID, u.Last_Name AS Advisor_Last_Name, a.OfficeHour_ID, a.Appointment_Date, a.Start_Time, a.End_Time, a.Status, a.Created_At
                    FROM appointments a
                    LEFT JOIN users u ON a.Advisor_ID = u.User_ID
                    WHERE Student_ID = ?
                    ORDER BY Appointment_Date DESC, Start_Time DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$studentUserId]);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // If no appointments, fallback to approved requests
            if (count($appointments) === 0) {
                $fallbackSql = "SELECT NULL AS Appointment_ID,  Request_ID, Student_ID, Advisor_ID,
                                    u.Last_Name AS Advisor_Last_Name, OfficeHour_ID, Appointment_Date,
                                    NULL AS Start_Time,NULL AS End_Time,Status, Created_At
                                FROM appointment_requests ar
                                LEFT JOIN users u ON ar.Advisor_ID = u.User_ID
                                WHERE Student_ID = ?
                                  AND LOWER(TRIM(Status)) = 'approved'
                                ORDER BY Appointment_Date DESC, Created_At DESC";

                $fallbackStmt = $this->conn->prepare($fallbackSql);
                $fallbackStmt->execute([$studentUserId]);
                $appointments = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            return $appointments;
        } catch (Throwable $e) {
            error_log('StudentClass::getApprovedAppointments error: ' . $e->getMessage());
            return [];
        }
    }

    // Get appointment history for student
    public function getAppointmentHistory(int $studentUserId): array
    {
        try {
            $sql = "SELECT ar.Request_ID, ar.Student_ID, ar.Advisor_ID, u.Last_Name AS Advisor_Last_Name, ar.Appointment_Date, ar.Student_Reason, ar.Advisor_Reason, ar.Status,
                           CASE
                               WHEN ap.Status = 'Completed' THEN 'Attended'
                               WHEN ap.Status = 'Cancelled' THEN 'No Show'
                               ELSE 'Pending'
                           END AS Student_Attendance,
                           ar.Created_At
                    FROM appointment_requests ar
                    LEFT JOIN users u ON ar.Advisor_ID = u.User_ID
                    LEFT JOIN appointments ap ON ap.Request_ID = ar.Request_ID
                    WHERE ar.Student_ID = ?
                      AND LOWER(TRIM(ar.Status)) <> 'pending'
                    ORDER BY Created_At DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$studentUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('StudentClass::getAppointmentHistory error: ' . $e->getMessage());
            return [];
        }
    }

    // Get calendar events for student
    public function getCalendarEvents(int $studentUserId): array
    {
        try {
            $sql = "SELECT
                        ar.Request_ID, ar.Appointment_Date,ar.Student_Reason, ar.Advisor_Reason,ar.Status, oh.Start_Time,
                        oh.End_Time, u.First_name AS Advisor_First_Name, u.Last_Name AS Advisor_Last_Name
                    FROM appointment_requests ar
                    LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
                    LEFT JOIN users u ON ar.Advisor_ID = u.User_ID
                    WHERE ar.Student_ID = ?
                    ORDER BY ar.Appointment_Date ASC, oh.Start_Time ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$studentUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('StudentClass::getCalendarEvents error: ' . $e->getMessage());
            return [];
        }
    }


}
