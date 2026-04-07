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

    private function json(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit();
    }

    private function resolveStudentUserId(): int
    {
        return (int)($_SESSION['UserID'] ?? 0);
    }

    public function getMessageThread(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json([]);
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $sessionStudentId = $this->resolveStudentUserId();
        if ($studentId <= 0 || $sessionStudentId <= 0 || $studentId !== $sessionStudentId) {
            $this->json([]);
        }

        $advisorId = $this->studentModule->getAdvisorUserIdForStudent($studentId);
        if ($advisorId === null || $advisorId <= 0) {
            $this->json([]);
        }

        $messages = $this->studentModule->getMessageThread($advisorId, $studentId);
        $this->json($messages);
    }

    public function sendMessage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Invalid request method']);
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $messageBody = trim((string)($_POST['message_body'] ?? ''));
        $sessionStudentId = $this->resolveStudentUserId();

        if ($studentId <= 0 || $sessionStudentId <= 0 || $studentId !== $sessionStudentId || $messageBody === '') {
            $this->json(['success' => false, 'error' => 'Missing required fields']);
        }

        $advisorId = $this->studentModule->getAdvisorUserIdForStudent($studentId);
        if ($advisorId === null || $advisorId <= 0) {
            $this->json(['success' => false, 'error' => 'Advisor not found']);
        }

        try {
            $ok = $this->studentModule->sendMessage($advisorId, $studentId, $messageBody);
            if (!$ok) {
                $this->json(['success' => false, 'error' => 'Failed to send message']);
            }
            $this->json(['success' => true]);
        } catch (Throwable $e) {
            error_log('StudentController::sendMessage error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'An error occurred']);
        }
    }

    public function markMessagesRead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false]);
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $sessionStudentId = $this->resolveStudentUserId();
        if ($studentId <= 0 || $sessionStudentId <= 0 || $studentId !== $sessionStudentId) {
            $this->json(['success' => false]);
        }

        $advisorId = $this->studentModule->getAdvisorUserIdForStudent($studentId);
        if ($advisorId === null || $advisorId <= 0) {
            $this->json(['success' => false]);
        }

        $ok = $this->studentModule->markMessagesRead($advisorId, $studentId);
        $this->json(['success' => $ok]);
    }
}
