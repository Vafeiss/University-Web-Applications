<?php
/*
NAME: Advisor Class
Description: Advisor dashboard data-access methods.
Paraskevas Vafeiadis
27-Mar-2026 v0.1
Files in use: databaseconnect.php, CommunicationsClass.php

*/

declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';
require_once __DIR__ . '/CommunicationsClass.php';

class AdvisorClass
{
    private PDO $conn;
    private CommunicationsClass $communications;

    public function __construct()
    {
        $this->conn = ConnectToDatabase();
        $this->communications = new CommunicationsClass();
    }

    public function isStudentAssignedToAdvisor(int $advisorUserId, int $studentUserId): bool
    {
        try {
            $advisorExternalId = $this->getAdvisorExternalId($advisorUserId);
            if ($advisorExternalId === null) {
                return false;
            }

            $sql = "SELECT 1
                    FROM student_advisors sa
                    INNER JOIN users s ON s.External_ID = sa.Student_ID AND s.Role = 'Student'
                    WHERE sa.Advisor_ID = ?
                      AND s.User_ID = ?
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorExternalId, $studentUserId]);

            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('AdvisorClass::isStudentAssignedToAdvisor error: ' . $e->getMessage());
            return false;
        }
    }

    //get the advisor's external id based on their user_id 
    private function getAdvisorExternalId(int $advisorUserId): ?int
    {
        $stmt = $this->conn->prepare('SELECT External_ID FROM users WHERE User_ID = ? AND Role = "Advisor" LIMIT 1');
        $stmt->execute([$advisorUserId]);
        $externalId = $stmt->fetchColumn();
        if ($externalId === false) {
            return null;
        }

        return (int)$externalId;
    }

    //get the list of students assigned to the advisor, along with their unread message count
    public function getAssignedStudents(int $advisorUserId): array
    {
        try {
            $advisorExternalId = $this->getAdvisorExternalId($advisorUserId);
            if ($advisorExternalId === null) {
                return [];
            }

            $sql = "SELECT
                        s.User_ID,
                        s.External_ID AS StuExternal_ID,
                        s.First_name,
                        s.Last_Name,
                        st.year AS StuYear,
                        COALESCE(SUM(CASE WHEN m.Sender_ID != ? AND m.Is_Read = 0 THEN 1 ELSE 0 END), 0) AS unread_count
                    FROM student_advisors sa
                    JOIN users s ON s.External_ID = sa.Student_ID AND s.Role = 'Student'
                    LEFT JOIN students st ON st.User_ID = s.User_ID
                    LEFT JOIN conversations c ON c.Student_ID = s.User_ID AND c.Advisor_ID = ?
                    LEFT JOIN messages m ON m.Conversation_ID = c.Conversation_ID
                    WHERE sa.Advisor_ID = ?
                    GROUP BY s.User_ID, s.External_ID, s.First_name, s.Last_Name, st.year
                    ORDER BY st.year ASC, s.First_name ASC, s.Last_Name ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId, $advisorUserId, $advisorExternalId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('AdvisorClass::getAssignedStudents error: ' . $e->getMessage());
            return [];
        }
    }

    //get the message thread between the advisor and student, with informations for each one
    public function getMessageThread(int $advisorId, int $studentId): array
    {
        try {
            if (!$this->isStudentAssignedToAdvisor($advisorId, $studentId)) {
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
                $isAdvisor = ((int)$row['Sender_ID'] === $advisorId);
                $messages[] = [
                    'id' => (int)$row['Message_ID'],
                    'body' => (string)$row['Message_Text'],
                    'sent_at' => $row['Sent_At'],
                    'sender' => $isAdvisor ? 'advisor' : 'student',
                    'sender_name' => trim(((string)($row['First_name'] ?? '')) . ' ' . ((string)($row['Last_Name'] ?? ''))),
                ];
            }

            return $messages;
        } catch (Throwable $e) {
            error_log('AdvisorClass::getMessageThread error: ' . $e->getMessage());
            return [];
        }
    }

    //send a message from the advisor to the student. If no conversation exists, it will be created automatically 
    public function sendMessage(int $advisorId, int $studentId, string $messageBody): bool
    {
        try {
            if (!$this->isStudentAssignedToAdvisor($advisorId, $studentId)) {
                return false;
            }

            $conversationId = $this->communications->getOrCreateConversationId($advisorId, $studentId);
            if ($conversationId === null) {
                return false;
            }

            return $this->communications->sendMessage($conversationId, $advisorId, $messageBody);
        } catch (Throwable $e) {
            error_log('AdvisorClass::sendMessage error: ' . $e->getMessage());
            return false;
        }
    }

    //mark read function get the conversdation id and then marks all messages in that conversation as read for the advisor
    public function markMessagesRead(int $advisorId, int $studentId): bool
    {
        try {
            if (!$this->isStudentAssignedToAdvisor($advisorId, $studentId)) {
                return false;
            }

            $conversationId = $this->communications->getConversationId($advisorId, $studentId);
            if ($conversationId === null) {
                // No conversation means nothing to mark; treat as successful no-op.
                return true;
            }

            return $this->communications->markMessagesRead($conversationId, $advisorId);
        } catch (Throwable $e) {
            error_log('AdvisorClass::markMessagesRead error: ' . $e->getMessage());
            return false;
        }
    }

    // Fetch office hours for advisor dashboard
    public function getOfficeHours(int $advisorUserId): array
    {
        try {
            $sql = "SELECT OfficeHour_ID, Day_of_Week, Start_Time, End_Time
                    FROM office_hours
                    WHERE Advisor_ID = ?
                    ORDER BY
                        FIELD(Day_of_Week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                        Start_Time ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('AdvisorClass::getOfficeHours error: ' . $e->getMessage());
            return [];
        }
    }

    // Fetch additional slots for advisor dashboard
    public function getAdditionalSlots(int $advisorUserId): array
    {
        try {
            $sql = "SELECT AdditionalSlot_ID, Slot_Date, Start_Time, End_Time, Is_Active
                    FROM advisor_additional_slots
                    WHERE Advisor_ID = ?
                      AND Is_Active = 1
                    ORDER BY Slot_Date ASC, Start_Time ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('AdvisorClass::getAdditionalSlots error: ' . $e->getMessage());
            return [];
        }
    }

    // Fetch pending appointment requests for advisor
    public function getPendingRequests(int $advisorUserId): array
    {
        try {
            $sql = "SELECT ar.Request_ID, ar.Student_ID, COALESCE(s.External_ID, ar.Student_ID) AS Student_External_ID,
                           ar.Advisor_ID,ar.OfficeHour_ID, ar.Appointment_Date, ar.Student_Reason, ar.Advisor_Reason,
                           ar.Status, ar.Created_At
                    FROM appointment_requests ar
                    INNER JOIN users s ON s.User_ID = ar.Student_ID
                    WHERE ar.Advisor_ID = ?
                      AND LOWER(TRIM(ar.Status)) = 'pending'
                    ORDER BY ar.Created_At DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('AdvisorClass::getPendingRequests error: ' . $e->getMessage());
            return [];
        }
    }

    // Fetch scheduled appointments with pending attendance for advisor
    public function getScheduledAppointmentsWithPendingAttendance(int $advisorUserId): array
    {
        try {
            $sql = "SELECT a.Appointment_ID,a.Request_ID, a.Student_ID, s.External_ID AS Student_External_ID, a.Advisor_ID,
                           a.OfficeHour_ID, a.Appointment_Date, a.Start_Time, a.End_Time, a.Student_Attendance, a.Status,
                           a.Created_At
                    FROM appointments a
                    INNER JOIN users s ON s.User_ID = a.Student_ID
                    WHERE a.Advisor_ID = ?
                      AND a.Status = 'Scheduled'
                      AND COALESCE(a.Student_Attendance, 'Pending') = 'Pending'
                    ORDER BY a.Appointment_Date DESC, a.Start_Time DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('AdvisorClass::getScheduledAppointmentsWithPendingAttendance error: ' . $e->getMessage());
            return [];
        }
    }

    // Fetch appointment history for advisor (completed, declined, cancelled)
    public function getAppointmentHistory(int $advisorUserId): array
    {
        try {
            $sql = "SELECT 
                        ar.Request_ID, COALESCE(s.External_ID, ar.Student_ID) AS Student_External_ID, ar.Status, ap.Student_Attendance,
                        ar.Student_Reason, ar.Advisor_Reason, ar.Appointment_Date, ar.Created_At
                    FROM appointment_requests ar
                    LEFT JOIN users s ON s.User_ID = ar.Student_ID
                    LEFT JOIN appointments ap ON ap.Request_ID = ar.Request_ID
                    WHERE ar.Advisor_ID = ?
                      AND LOWER(TRIM(ar.Status)) IN ('approved', 'declined', 'cancelled')
                    ORDER BY ar.Created_At DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('AdvisorClass::getAppointmentHistory error: ' . $e->getMessage());
            return [];
        }
    }

    // Fetch advisor calendar events for full calendar view
    public function getCalendarEvents(int $advisorUserId): array
    {
        try {
            $sql = "SELECT
                        ar.Request_ID, ar.Appointment_Date, ar.Student_Reason, ar.Advisor_Reason, ar.Status,
                        oh.Start_Time, oh.End_Time, u.First_name AS Student_First_Name, u.Last_Name AS Student_Last_Name
                    FROM appointment_requests ar
                    LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
                    LEFT JOIN users u ON ar.Student_ID = u.User_ID
                    WHERE ar.Advisor_ID = ?
                    ORDER BY ar.Appointment_Date ASC, oh.Start_Time ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('AdvisorClass::getCalendarEvents error: ' . $e->getMessage());
            return [];
        }
    }

    
}
