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

20-Apr-2026 v2.4
Added support for approving additional appointment slots alongside recurring office hours
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
            $requestContextSql = "SELECT ar.OfficeHour_ID,
                                         ar.AdditionalSlot_ID,
                                         ar.Appointment_Date,
                                         oh.Start_Time AS OfficeHour_Start_Time,
                                         oh.End_Time AS OfficeHour_End_Time,
                                         ads.Start_Time AS Additional_Start_Time,
                                         ads.End_Time AS Additional_End_Time
                                  FROM appointment_requests ar
                                  LEFT JOIN office_hours oh ON oh.OfficeHour_ID = ar.OfficeHour_ID
                                  LEFT JOIN advisor_additional_slots ads ON ads.AdditionalSlot_ID = ar.AdditionalSlot_ID
                                  WHERE ar.Request_ID = :request_id
                                    AND ar.Advisor_ID = :advisor_id
                                  LIMIT 1";

            $requestContextStmt = $pdo->prepare($requestContextSql);
            $requestContextStmt->execute([
                'request_id' => $requestId,
                'advisor_id' => $advisorId
            ]);
            $requestContext = $requestContextStmt->fetch(PDO::FETCH_ASSOC);

            if ($requestContext === false) {
                Notifications::error("Invalid request ID.");
                $this->redirectToAdvisorRequests();
            }

            $officeHourId = isset($requestContext['OfficeHour_ID']) ? (int)$requestContext['OfficeHour_ID'] : 0;
            $additionalSlotId = isset($requestContext['AdditionalSlot_ID']) ? (int)$requestContext['AdditionalSlot_ID'] : 0;

            if ($officeHourId > 0) {
                $conflictSql = "SELECT Appointment_ID
                    FROM appointments
                    WHERE Advisor_ID = :advisor_id
                      AND OfficeHour_ID = :office_hour_id
                      AND Appointment_Date = :appointment_date
                    LIMIT 1";

                $conflictStmt = $pdo->prepare($conflictSql);
                $conflictStmt->execute([
                    'advisor_id' => $advisorId,
                    'office_hour_id' => $officeHourId,
                    'appointment_date' => (string)$requestContext['Appointment_Date']
                ]);
            } elseif ($additionalSlotId > 0) {
                $conflictSql = "SELECT Appointment_ID
                    FROM appointments
                    WHERE Advisor_ID = :advisor_id
                      AND Appointment_Date = :appointment_date
                      AND ((Start_Time < :end_time AND End_Time > :start_time))
                    LIMIT 1";

                $conflictStmt = $pdo->prepare($conflictSql);
                $conflictStmt->execute([
                    'advisor_id' => $advisorId,
                    'appointment_date' => (string)$requestContext['Appointment_Date'],
                    'start_time' => (string)$requestContext['Additional_Start_Time'],
                    'end_time' => (string)$requestContext['Additional_End_Time']
                ]);
            } else {
                Notifications::error("Invalid request slot source.");
                $this->redirectToAdvisorRequests();
            }

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
