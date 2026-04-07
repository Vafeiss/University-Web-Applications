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
                        COALESCE(SUM(CASE WHEN m.Sender_ID != ? AND m.Is_Read = 0 THEN 1 ELSE 0 END), 0) AS unread_count
                    FROM student_advisors sa
                    JOIN users s ON s.External_ID = sa.Student_ID AND s.Role = 'Student'
                    LEFT JOIN conversations c ON c.Student_ID = s.User_ID AND c.Advisor_ID = ?
                    LEFT JOIN messages m ON m.Conversation_ID = c.Conversation_ID
                    WHERE sa.Advisor_ID = ?
                    GROUP BY s.User_ID, s.External_ID, s.First_name, s.Last_Name
                    ORDER BY s.First_name ASC, s.Last_Name ASC";

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
            $conversationId = $this->communications->findConversation($advisorId, $studentId);
            if ($conversationId === false) {
                return [];
            }

            $history = $this->communications->getHistory((int)$conversationId);
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
            $conversationId = $this->communications->findConversation($advisorId, $studentId);
            if ($conversationId === false) {
                return false;
            }

            return $this->communications->sendMessage((int)$conversationId, $advisorId, $messageBody);
        } catch (Throwable $e) {
            error_log('AdvisorClass::sendMessage error: ' . $e->getMessage());
            return false;
        }
    }

    //mark read function get the conversdation id and then marks all messages in that conversation as read for the advisor
    public function markMessagesRead(int $advisorId, int $studentId): bool
    {
        try {
            $conversationId = $this->communications->findConversation($advisorId, $studentId);
            if ($conversationId === false) {
                return false;
            }

            return $this->communications->getMessages((int)$conversationId, $advisorId);
        } catch (Throwable $e) {
            error_log('AdvisorClass::markMessagesRead error: ' . $e->getMessage());
            return false;
        }
    }

    
}
