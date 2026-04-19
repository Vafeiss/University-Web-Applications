<?php
/*
NAME: AppointmentControllerAction.php
Description: This controller handles advisor actions for approving and declining appointment requests using the AppointmentApproval class. It processes POST/GET requests and uses the Notifications system to display success or error messages.
Panteleimoni Alexandrou
13-Apr-2026 v2.1

19-Apr-2026 v2.2
Updated approve/decline redirects to return advisors to the frontend dashboard requests section while keeping notification flow intact and preserving an optional standalone testing redirect target
Panteleimoni Alexandrou

19-Apr-2026 v2.3
Added database notification inserts for appointment request, approve and decline actions
Panteleimoni Alexandrou

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

class AppointmentControllerAction
{
    private AppointmentApproval $appointmentApproval;
    private const REDIRECT_TARGETS = [
        'advisor_dashboard_requests' => '../../frontend/AdvisorAppointmentDashboard.php?section=requests',
        'advisor_requests_controller' => '../../backend/controllers/AdvisorAppointmentRequests.php'
    ];

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
            Notifications::error("Invalid request ID.");
            $this->redirectToAdvisorRequests();
        }

        try {
            require_once __DIR__ . '/../modules/databaseconnect.php';
            $pdo = ConnectToDatabase();

           if ($appointmentAction === 'approve') {
            $conflictSql = "SELECT Appointment_ID
                FROM appointments
                WHERE Advisor_ID = :advisor_id
                  AND OfficeHour_ID = (
                      SELECT OfficeHour_ID
                      FROM appointment_requests
                      WHERE Request_ID = :request_id
                      LIMIT 1
                  )
                  AND Appointment_Date = (
                      SELECT Appointment_Date
                      FROM appointment_requests
                      WHERE Request_ID = :request_id
                      LIMIT 1
                  )
                LIMIT 1";

            $conflictStmt = $pdo->prepare($conflictSql);
            $conflictStmt->execute([
                'advisor_id' => $advisorId,
                'request_id' => $requestId
            ]);

        if ($conflictStmt->fetch(PDO::FETCH_ASSOC)) {
             Notifications::error("An appointment already exists for this slot and date.");
            $this->redirectToAdvisorRequests();
        }
            $ok = $this->appointmentApproval->approveAppointment($requestId, $advisorId);

            if (!$ok) {
             Notifications::error("Failed to approve appointment.");
             $this->redirectToAdvisorRequests();
            }

            try {
                $studentNotificationSql = "SELECT Student_ID
                                           FROM appointment_requests
                                           WHERE Request_ID = :request_id
                                           LIMIT 1";

                $studentNotificationStmt = $pdo->prepare($studentNotificationSql);
                $studentNotificationStmt->execute([
                    'request_id' => $requestId
                ]);

                $studentId = (int)($studentNotificationStmt->fetchColumn() ?: 0);

                if ($studentId > 0) {
                    $notificationSql = "INSERT INTO notifications
                                        (Recipient_ID, Sender_ID, Type, Title, Message, Related_Request_ID, Is_Read)
                                        VALUES
                                        (:recipient_id, :sender_id, :type, :title, :message, :related_request_id, :is_read)";

                    $notificationStmt = $pdo->prepare($notificationSql);
                    $notificationStmt->execute([
                        'recipient_id' => $studentId,
                        'sender_id' => $advisorId,
                        'type' => 'appointment_approved',
                        'title' => 'Appointment Approved',
                        'message' => 'Your appointment request has been approved.',
                        'related_request_id' => $requestId,
                        'is_read' => 0
                    ]);
                }
            } catch (Throwable $e) {
                error_log('AppointmentControllerAction approve notification insert error: ' . $e->getMessage());
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

            try {
                $studentNotificationSql = "SELECT Student_ID
                                           FROM appointment_requests
                                           WHERE Request_ID = :request_id
                                           LIMIT 1";

                $studentNotificationStmt = $pdo->prepare($studentNotificationSql);
                $studentNotificationStmt->execute([
                    'request_id' => $requestId
                ]);

                $studentId = (int)($studentNotificationStmt->fetchColumn() ?: 0);

                if ($studentId > 0) {
                    $notificationSql = "INSERT INTO notifications
                                        (Recipient_ID, Sender_ID, Type, Title, Message, Related_Request_ID, Is_Read)
                                        VALUES
                                        (:recipient_id, :sender_id, :type, :title, :message, :related_request_id, :is_read)";

                    $notificationStmt = $pdo->prepare($notificationSql);
                    $notificationStmt->execute([
                        'recipient_id' => $studentId,
                        'sender_id' => $advisorId,
                        'type' => 'appointment_declined',
                        'title' => 'Appointment Declined',
                        'message' => 'Your appointment request has been declined.',
                        'related_request_id' => $requestId,
                        'is_read' => 0
                    ]);
                }
            } catch (Throwable $e) {
                error_log('AppointmentControllerAction decline notification insert error: ' . $e->getMessage());
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
        $redirectTarget = trim((string)($_POST['redirect_target'] ?? $_GET['redirect_target'] ?? 'advisor_dashboard_requests'));
        $location = self::REDIRECT_TARGETS[$redirectTarget] ?? self::REDIRECT_TARGETS['advisor_dashboard_requests'];

        if ($query !== '') {
            $separator = str_contains($location, '?') ? '&' : '?';
            $location .= $separator . ltrim($query, '?&');
        }

        header('Location: ' . $location);
        exit();
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $controller = new AppointmentControllerAction();
    $controller->handle();
}
