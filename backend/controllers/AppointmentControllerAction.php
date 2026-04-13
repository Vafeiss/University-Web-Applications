<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../modules/AppointmentApprovalClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';

class AppointmentControllerAction
{
    private AppointmentApproval $appointmentApproval;

    public function __construct()
    {
        $this->appointmentApproval = new AppointmentApproval();
    }

    public function handle(): void
    {
        $appointmentAction = trim((string)($_POST['appointment_action'] ?? $_GET['action'] ?? ''));
        $requestId = (int)($_POST['request_id'] ?? $_GET['id'] ?? 0);
        $advisorId = isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : 2;

        if ($requestId <= 0) {
            $this->redirectToAdvisorRequests('?error=invalid');
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
                 $ok = $this->appointmentApproval->declineAppointment($requestId, $advisorId, $reason);

            if (!$ok) {
                Notifications::error("Failed to decline appointment.");
                $this->redirectToAdvisorRequests();
            }

            Notifications::success("Appointment declined successfully.");
            $this->redirectToAdvisorRequests();
        }

            $this->redirectToAdvisorRequests('?error=invalid');
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