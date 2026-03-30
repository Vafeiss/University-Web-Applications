<?php
/*
Name: AdvisorController.php
Description: Controller methods for advisor dashboard actions.
Paraskevas Vafeiadis
27-Mar-2026 v0.1
Files in use: UsersClass.php, AdvisorClass.php
*/

declare(strict_types=1);

require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/AdvisorClass.php';

class AdvisorController {

    private Users $advisor;
    private AdvisorClass $advisorModule;

    public function __construct()
    {
        $this->advisor = new Users();
        $this->advisor->Check_Session('Advisor');
        $this->advisorModule = new AdvisorClass();
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit();
    }

    public function getMessageThread(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json([]);
        }

        $advisorId = (int)($_SESSION['UserID'] ?? 0);
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($advisorId <= 0 || $studentId <= 0) {
            $this->json([]);
        }

        $messages = $this->advisorModule->getMessageThread($advisorId, $studentId);
        $this->json($messages);
    }

    public function sendMessage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Invalid request method']);
        }

        $advisorId = (int)($_SESSION['UserID'] ?? 0);
        $studentId = (int)($_POST['student_id'] ?? 0);
        $messageBody = trim((string)($_POST['message_body'] ?? ''));

        if ($advisorId <= 0 || $studentId <= 0 || $messageBody === '') {
            $this->json(['success' => false, 'error' => 'Missing required fields']);
        }

        try {
            $ok = $this->advisorModule->sendMessage($advisorId, $studentId, $messageBody);
            if (!$ok) {
                $this->json(['success' => false, 'error' => 'Failed to send message']);
            }
            $this->json(['success' => true]);
        } catch (Throwable $e) {
            error_log('AdvisorController::sendMessage error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'An error occurred']);
        }
    }

    public function markMessagesRead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false]);
        }

        $advisorId = (int)($_SESSION['UserID'] ?? 0);
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($advisorId <= 0 || $studentId <= 0) {
            $this->json(['success' => false]);
        }

        $ok = $this->advisorModule->markMessagesRead($advisorId, $studentId);
        $this->json(['success' => $ok]);
    }
}
