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


}