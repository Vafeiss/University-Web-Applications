<?php
/*
   NAME: Appointment Controller
   Description: This controller handles advisor actions for approving and declining appointment requests
   Panteleimoni Alexandrou
   30-Mar-2026 v2.0
   Inputs: GET inputs for action and request id
   Outputs: Updates appointment request status, inserts appointment record, inserts history record and redirects back to the advisor dashboard
   Error Messages: If the request is invalid or a database operation fails, a flash error message is created
   Files in use: AppointmentController.php, AdvisorAppointmentDashboard.php, db.php
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class AppointmentController
{
    public function studentBookAppointment(): void
    {
        header("Location: ../../frontend/StudentAppointmentDashboard.php?section=book");
        exit;
    }

    public function advisorAppointmentRequests(): void
    {
        header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=requests");
        exit;
    }

    public function studentAppointmentHistory(): void
    {
        header("Location: ../../frontend/StudentAppointmentDashboard.php?section=history");
        exit;
    }

    public function advisorAppointmentHistory(): void
    {
        header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=history");
        exit;
    }

    public function studentCalendar(): void
    {
        header("Location: ../../frontend/StudentAppointmentDashboard.php?section=calendar");
        exit;
    }

    public function advisorCalendar(): void
    {
        header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=appointments");
        exit;
    }

    public function advisorOfficeHours(): void
    {
        header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=officehours");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| DIRECT REQUEST HANDLER
|--------------------------------------------------------------------------
| Run approve/decline logic only when this file is accessed directly.
| Do not run this logic when the file is included by dispatcher.php.
*/
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) {
    return;
}

/*
TEMP TEST MODE
Use logged-in advisor user id if available.
Fallback to hardcoded advisor user id until login/session is fully connected.
*/
$advisorId = 2;
if (isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
    $advisorId = (int) $_SESSION['UserID'];
}

// Read action and request id from URL
$action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/*
Helper function for redirecting back to advisor requests tab
*/
function redirectToAdvisorRequestsDashboard(): void
{
    header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=requests");
    exit;
}

// Validate request id
if ($requestId <= 0) {
    $_SESSION['flash'] = "Invalid request ID.";
    $_SESSION['flash_type'] = "error";
    redirectToAdvisorRequestsDashboard();
}

try {
    /*
    ------------------------------------------------------------
    FETCH THE REQUEST FIRST
    ------------------------------------------------------------
    */
    $requestSql = "SELECT Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Student_Reason, Advisor_Reason, Status
                   FROM appointment_requests
                   WHERE Request_ID = :request_id
                     AND Advisor_ID = :advisor_id
                   LIMIT 1";

    $requestStmt = $pdo->prepare($requestSql);
    $requestStmt->execute([
        'request_id' => $requestId,
        'advisor_id' => $advisorId
    ]);

    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $_SESSION['flash'] = "Appointment request not found.";
        $_SESSION['flash_type'] = "error";
        redirectToAdvisorRequestsDashboard();
    }

    /*
    ------------------------------------------------------------
    APPROVE REQUEST
    ------------------------------------------------------------
    */
    if ($action === 'approve') {
        if ($request['Status'] !== 'Pending') {
            $_SESSION['flash'] = "Only pending requests can be approved.";
            $_SESSION['flash_type'] = "error";
            redirectToAdvisorRequestsDashboard();
        }

        $slotSql = "SELECT Start_Time, End_Time
                    FROM office_hours
                    WHERE OfficeHour_ID = :office_hour_id
                      AND Advisor_ID = :advisor_id
                    LIMIT 1";

        $slotStmt = $pdo->prepare($slotSql);
        $slotStmt->execute([
            'office_hour_id' => $request['OfficeHour_ID'],
            'advisor_id' => $advisorId
        ]);

        $slot = $slotStmt->fetch(PDO::FETCH_ASSOC);

        if (!$slot) {
            $_SESSION['flash'] = "Office hour slot not found.";
            $_SESSION['flash_type'] = "error";
            redirectToAdvisorRequestsDashboard();
        }

        $pdo->beginTransaction();

        $updateRequestSql = "UPDATE appointment_requests
                             SET Status = 'Approved',
                                 Updated_At = CURRENT_TIMESTAMP
                             WHERE Request_ID = :request_id";

        $updateRequestStmt = $pdo->prepare($updateRequestSql);
        $updateRequestStmt->execute([
            'request_id' => $requestId
        ]);

        $insertAppointmentSql = "INSERT INTO appointments
                                 (Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Start_Time, End_Time, Status)
                                 VALUES
                                 (:request_id, :student_id, :advisor_id, :office_hour_id, :appointment_date, :start_time, :end_time, 'Scheduled')";

        $insertAppointmentStmt = $pdo->prepare($insertAppointmentSql);
        $insertAppointmentStmt->execute([
            'request_id' => $request['Request_ID'],
            'student_id' => $request['Student_ID'],
            'advisor_id' => $request['Advisor_ID'],
            'office_hour_id' => $request['OfficeHour_ID'],
            'appointment_date' => $request['Appointment_Date'],
            'start_time' => $slot['Start_Time'],
            'end_time' => $slot['End_Time']
        ]);

        $appointmentId = (int)$pdo->lastInsertId();

        $insertHistorySql = "INSERT INTO appointment_history
                             (Request_ID, Appointment_ID, Student_ID, Advisor_ID, Action_Type, Action_Reason, Action_By)
                             VALUES
                             (:request_id, :appointment_id, :student_id, :advisor_id, 'Approved', NULL, :action_by)";

        $insertHistoryStmt = $pdo->prepare($insertHistorySql);
        $insertHistoryStmt->execute([
            'request_id' => $request['Request_ID'],
            'appointment_id' => $appointmentId,
            'student_id' => $request['Student_ID'],
            'advisor_id' => $request['Advisor_ID'],
            'action_by' => $advisorId
        ]);

        $pdo->commit();

        $_SESSION['flash'] = "Appointment request approved successfully.";
        $_SESSION['flash_type'] = "success";
        redirectToAdvisorRequestsDashboard();
    }

    /*
    ------------------------------------------------------------
    DECLINE REQUEST
    ------------------------------------------------------------
    */
    if ($action === 'decline') {
        if ($request['Status'] !== 'Pending') {
            $_SESSION['flash'] = "Only pending requests can be declined.";
            $_SESSION['flash_type'] = "error";
            redirectToAdvisorRequestsDashboard();
        }

        $pdo->beginTransaction();

        $updateRequestSql = "UPDATE appointment_requests
                             SET Status = 'Declined',
                                 Advisor_Reason = 'Declined by advisor',
                                 Updated_At = CURRENT_TIMESTAMP
                             WHERE Request_ID = :request_id";

        $updateRequestStmt = $pdo->prepare($updateRequestSql);
        $updateRequestStmt->execute([
            'request_id' => $requestId
        ]);

        $insertHistorySql = "INSERT INTO appointment_history
                             (Request_ID, Appointment_ID, Student_ID, Advisor_ID, Action_Type, Action_Reason, Action_By)
                             VALUES
                             (:request_id, NULL, :student_id, :advisor_id, 'Declined', 'Declined by advisor', :action_by)";

        $insertHistoryStmt = $pdo->prepare($insertHistorySql);
        $insertHistoryStmt->execute([
            'request_id' => $request['Request_ID'],
            'student_id' => $request['Student_ID'],
            'advisor_id' => $request['Advisor_ID'],
            'action_by' => $advisorId
        ]);

        $pdo->commit();

        $_SESSION['flash'] = "Appointment request declined successfully.";
        $_SESSION['flash_type'] = "success";
        redirectToAdvisorRequestsDashboard();
    }

    $_SESSION['flash'] = "Invalid action.";
    $_SESSION['flash_type'] = "error";
    redirectToAdvisorRequestsDashboard();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['flash'] = "Database error while processing appointment request.";
    $_SESSION['flash_type'] = "error";
    redirectToAdvisorRequestsDashboard();
}