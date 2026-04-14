<?php
/* Name: CommunicationsClass.php
   Description: Class for handling communications between Advisors - Students Asychronous type send a message update db then student reads it when the log in
   Paraskevas Vafeiadis
   26-Mar-2026 v0.1
   Inputs: Conversation ID, Sender ID, Message
   Outputs: Message history, Conversation ID
   Error Messages: If the connection to the database fails || queries fails throw PDO exception with message
   Files in use: databaseconnect.php , students_dashboard.php, advisor_dashboard.php
*/
declare(strict_types=1);

require_once __DIR__ . '/databaseconnect.php';

class CommunicationsClass {

    private PDO $conn;

    public function __construct() {
        $this->conn = ConnectToDatabase();
    }

    //Create conversation between advisor and student if it doesn't exist and return the conversation ID
    public function createConversation(int $advisorID , int $studentID){
    try{
        $sql = "INSERT INTO conversations (Advisor_ID, Student_ID) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$advisorID, $studentID]);

        $conversationId = (int)$this->conn->lastInsertId();
        return $conversationId > 0 ? $conversationId : false;
    } catch(PDOException $e){
        error_log("Error creating conversation: " . $e->getMessage());
        return false;
    }
    }

    //get the conversation id without creating records
    public function getConversationId(int $advisorID, int $studentID): ?int
    {
        try {
            $sql = "SELECT Conversation_ID FROM conversations WHERE Advisor_ID = ? AND Student_ID = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$advisorID, $studentID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || !isset($result['Conversation_ID'])) {
                return null;
            }

            return (int)$result['Conversation_ID'];
        } catch (PDOException $e) {
            error_log("Error getting conversation id: " . $e->getMessage());
            return null;
        }
    }

    //get existing conversation id or create one if none exists
    public function getOrCreateConversationId(int $advisorID, int $studentID): ?int
    {
        $existingId = $this->getConversationId($advisorID, $studentID);
        if ($existingId !== null) {
            return $existingId;
        }

        $createdId = $this->createConversation($advisorID, $studentID);
        if ($createdId === false) {
            return null;
        }

        return (int)$createdId;
    }

    //backward compatible alias
    public function findConversation(int $advisorID, int $studentID)
    {
        $conversationId = $this->getOrCreateConversationId($advisorID, $studentID);
        return $conversationId ?? false;
    }

    //send a message between advisor and student and update the database with the message conversation, sender and timestamp of the message
    public function sendMessage(int $conversationID, int $senderID, string $message){
        try{
            $sql = "INSERT INTO messages (Conversation_ID , Sender_ID, Message_Text) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$conversationID, $senderID, $message]);
            return true;
        } catch(PDOException $e){
            error_log("Error sending message: " . $e->getMessage());
            return false;
        }

    }

    //get the history of the messages between advisor and students 
    public function getHistory(int $conversationID){
        try{
            $sql = "SELECT messages.Message_ID, messages.Conversation_ID, messages.Sender_ID, messages.Message_Text, messages.Sent_At, messages.Is_Read, users.First_name, users.Last_Name FROM messages JOIN users ON messages.Sender_ID = users.User_ID WHERE messages.Conversation_ID = ? ORDER BY messages.Sent_At ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$conversationID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e){
            error_log("Error getting message history: " . $e->getMessage());
            return false;
        }
    }

    //when a students/advisors opens the tab mark it as read in the database.
    public function markMessagesRead(int $conversationID, int $senderID): bool
    {
        try{
            $sql = "UPDATE messages set Is_Read = 1 WHERE Conversation_ID = ? AND Sender_ID != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$conversationID, $senderID]);
            return true;
        }catch(PDOException $e){
            error_log("Error marking messages as read: " . $e->getMessage());
            return false;
        }
    }

    //backward compatible alias
    public function getMessages(int $conversationID, int $senderID)
    {
        return $this->markMessagesRead($conversationID, $senderID);
    }
}