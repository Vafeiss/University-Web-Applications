<?php
/*
Name: StudentController.php
Description: Controller methods for student dashboard actions.
Paraskevas Vafeiadis
29-Mar-2026 v0.1
Files in use: UsersClass.php, StudentClass.php

*/

declare(strict_types=1);

require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/StudentClass.php';

class StudentController
{
    private Users $student;
    private StudentClass $studentModule;

    public function __construct()
    {
        $this->student = new Users();
        $this->student->Check_Session('Student');
        $this->studentModule = new StudentClass();
    }

    private function jsonResponse(int $statusCode, bool $success, string $message = '', $data = null, array $errors = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    private function resolveStudentUserId(): int
    {
        return (int)($_SESSION['UserID'] ?? 0);
    }

    public function getMessageThread(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(405, false, 'Invalid request method');
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $sessionStudentId = $this->resolveStudentUserId();
        if ($sessionStudentId <= 0) {
            $this->jsonResponse(401, false, 'Unauthorized');
        }

        if ($studentId <= 0) {
            $this->jsonResponse(422, false, 'Invalid student id', null, ['student_id' => 'Student id is required']);
        }

        if ($studentId !== $sessionStudentId) {
            $this->jsonResponse(403, false, 'Forbidden');
        }

        $advisorId = $this->studentModule->getAdvisorUserIdForStudent($studentId);
        if ($advisorId === null || $advisorId <= 0) {
            $this->jsonResponse(404, false, 'Advisor not found');
        }

        try {
            $messages = $this->studentModule->getMessageThread($advisorId, $studentId);
            $this->jsonResponse(200, true, 'Message thread loaded', $messages);
        } catch (Throwable $e) {
            error_log('StudentController::getMessageThread error: ' . $e->getMessage());
            $this->jsonResponse(500, false, 'An error occurred');
        }
    }

    public function sendMessage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(405, false, 'Invalid request method');
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $messageBody = trim((string)($_POST['message_body'] ?? ''));
        $sessionStudentId = $this->resolveStudentUserId();

        if ($sessionStudentId <= 0) {
            $this->jsonResponse(401, false, 'Unauthorized');
        }

        if ($studentId <= 0) {
            $this->jsonResponse(422, false, 'Invalid student id', null, ['student_id' => 'Student id is required']);
        }

        if ($studentId !== $sessionStudentId) {
            $this->jsonResponse(403, false, 'Forbidden');
        }

        if ($messageBody === '') {
            $this->jsonResponse(422, false, 'Message body is required', null, ['message_body' => 'Message body is required']);
        }

        if (mb_strlen($messageBody, 'UTF-8') > 2000) {
            $this->jsonResponse(422, false, 'Message is too long', null, ['message_body' => 'Maximum 2000 characters']);
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $messageBody)) {
            $this->jsonResponse(422, false, 'Message contains invalid characters', null, ['message_body' => 'Contains invalid control characters']);
        }

        $advisorId = $this->studentModule->getAdvisorUserIdForStudent($studentId);
        if ($advisorId === null || $advisorId <= 0) {
            $this->jsonResponse(404, false, 'Advisor not found');
        }

        try {
            $ok = $this->studentModule->sendMessage($advisorId, $studentId, $messageBody);
            if (!$ok) {
                $this->jsonResponse(500, false, 'Failed to send message');
            }

            $this->jsonResponse(200, true, 'Message sent successfully');
        } catch (Throwable $e) {
            error_log('StudentController::sendMessage error: ' . $e->getMessage());
            $this->jsonResponse(500, false, 'An error occurred');
        }
    }

    public function markMessagesRead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(405, false, 'Invalid request method');
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $sessionStudentId = $this->resolveStudentUserId();
        if ($sessionStudentId <= 0) {
            $this->jsonResponse(401, false, 'Unauthorized');
        }

        if ($studentId <= 0) {
            $this->jsonResponse(422, false, 'Invalid student id', null, ['student_id' => 'Student id is required']);
        }

        if ($studentId !== $sessionStudentId) {
            $this->jsonResponse(403, false, 'Forbidden');
        }

        $advisorId = $this->studentModule->getAdvisorUserIdForStudent($studentId);
        if ($advisorId === null || $advisorId <= 0) {
            $this->jsonResponse(404, false, 'Advisor not found');
        }

        try {
            $ok = $this->studentModule->markMessagesRead($advisorId, $studentId);
            if (!$ok) {
                $this->jsonResponse(500, false, 'Failed to mark messages as read');
            }

            $this->jsonResponse(200, true, 'Messages marked as read');
        } catch (Throwable $e) {
            error_log('StudentController::markMessagesRead error: ' . $e->getMessage());
            $this->jsonResponse(500, false, 'An error occurred');
        }
    }
}
