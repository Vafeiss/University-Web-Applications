<?php
/*
NAME: AppointmentControllerAction.php
Description: This controller handles advisor actions for approving and declining appointment requests using the AppointmentApproval class. It processes POST/GET requests and uses the Notifications system to display success or error messages.
Panteleimoni Alexandrou
13-Apr-2026 v2.1

Inputs:
- POST: appointment_action (approve/decline), request_id, decline_reason (optional)
- GET: action, id (for testing)

Outputs:
- Updates appointment request status (Approved / Declined)
- Uses Notifications class for success/error messages
- Redirects back to AdvisorAppointmentRequests page

Error Messages:
- Invalid request ID
- Failed to approve/decline appointment
- Database error while processing request

Files in use:
- AppointmentControllerAction.php
- AppointmentApprovalClass.php
- NotificationsClass.php
- dispatcher.php
- AdvisorAppointmentRequests.php
*/
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../modules/AppointmentApprovalClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';
require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/Csrf.php';

class AppointmentControllerAction
{
    private AppointmentApproval $appointmentApproval;

    public function __construct()
    {
        $this->appointmentApproval = new AppointmentApproval();
    }

    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Notifications::error('Invalid request method.');
            $this->redirectToAdvisorRequests();
        }

        if (!Csrf::validateRequestToken()) {
            Notifications::error('Request validation failed.');
            $this->redirectToAdvisorRequests();
        }

        $user = new Users();
        $user->Check_Session('Advisor');

        if (!isset($_SESSION['UserID']) || !is_numeric($_SESSION['UserID'])) {
            Notifications::error('Unauthorized advisor session.');
            $this->redirectToAdvisorRequests();
        }

        $appointmentAction = trim((string)($_POST['appointment_action'] ?? ''));
        $requestId = (int)($_POST['request_id'] ?? 0);
        $advisorId = (int)$_SESSION['UserID'];

        if (!in_array($appointmentAction, ['approve', 'decline'], true)) {
            Notifications::error('Invalid action.');
            $this->redirectToAdvisorRequests();
        }

        if ($requestId <= 0) {
            Notifications::error("Invalid request ID.");
            $this->redirectToAdvisorRequests();
        }

        try {
            if ($appointmentAction === 'approve') {
                $ok = $this->appointmentApproval->approveAppointment($requestId, $advisorId);

                if (!$ok) {
                    Notifications::error("Failed to approve appointment.");
                    $this->redirectToAdvisorRequests();
                }

                Notifications::success("Appointment approved successfully.");
                $this->redirectToAdvisorRequests();
            }

            if ($appointmentAction === 'decline') {
                $reason = trim((string)($_POST['decline_reason'] ?? 'Declined by advisor'));

                if ($reason === '') {
                    Notifications::error("Decline reason is required.");
                    $this->redirectToAdvisorRequests();
                }

                if (mb_strlen($reason, 'UTF-8') > 1000) {
                    Notifications::error("Decline reason is too long.");
                    $this->redirectToAdvisorRequests();
                }

                $ok = $this->appointmentApproval->declineAppointment($requestId, $advisorId, $reason);

                if (!$ok) {
                    Notifications::error("Failed to decline appointment.");
                    $this->redirectToAdvisorRequests();
                }

                Notifications::success("Appointment declined successfully.");
                $this->redirectToAdvisorRequests();
            }

            Notifications::error("Invalid action.");
            $this->redirectToAdvisorRequests();
        } catch (Throwable $e) {
            Notifications::error("Database error while processing request.");
            $this->redirectToAdvisorRequests();
        }
    }

    private function redirectToAdvisorRequests(string $query = ''): void
    {
        header('Location: ../../backend/controllers/AdvisorAppointmentRequests.php' . $query);
        exit();
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $controller = new AppointmentControllerAction();
    $controller->handle();
}