<?php
/*
   NAME: Advisor Office Hours Controller
   Description: This controller handles advisor office hour actions such as add and delete and redirects back to the advisor dashboard
   Panteleimoni Alexandrou
   30-Mar-2026 v2.2
   Inputs: POST and GET inputs for office hour actions
   Outputs: Redirects back to AdvisorAppointmentDashboard.php with flash messages
   Error Messages: If validation fails or database action fails, an error flash message is created
    Files in use: AdvisorOfficeHours.php, AdvisorAppointmentDashboard.php, databaseconnect.php
*/

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../modules/databaseconnect.php';

$pdo = ConnectToDatabase();

/*
TEMP TEST MODE
Use hardcoded advisor user id until login/session is fully connected.
*/
$advisorId = 2;

/*
Helper function for redirecting back to dashboard
*/
function redirectToOfficeHoursDashboard(): void
{
    header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=officehours");
    exit;
}

/*
------------------------------------------------------------
DELETE SLOT
------------------------------------------------------------
*/
if (isset($_GET['delete'])) {
    $deleteId = (int)($_GET['delete']);

    if ($deleteId <= 0) {
        $_SESSION['flash'] = "Invalid slot ID.";
        $_SESSION['flash_type'] = "error";
        redirectToOfficeHoursDashboard();
    }

    try {
        $sql = "DELETE FROM office_hours
                WHERE OfficeHour_ID = :id
                  AND Advisor_ID = :advisor_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $deleteId,
            'advisor_id' => $advisorId
        ]);

        $_SESSION['flash'] = "Office hour slot deleted successfully.";
        $_SESSION['flash_type'] = "success";
        redirectToOfficeHoursDashboard();

    } catch (Throwable $e) {
        $_SESSION['flash'] = "Failed to delete office hour slot.";
        $_SESSION['flash_type'] = "error";
        redirectToOfficeHoursDashboard();
    }
}

/*
------------------------------------------------------------
ADD SLOT
------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action !== 'add') {
        $_SESSION['flash'] = "Invalid action.";
        $_SESSION['flash_type'] = "error";
        redirectToOfficeHoursDashboard();
    }

    $day = trim((string)($_POST['day_of_week'] ?? ''));
    $start = trim((string)($_POST['start_time'] ?? ''));
    $end = trim((string)($_POST['end_time'] ?? ''));

    if ($day === '' || $start === '' || $end === '') {
        $_SESSION['flash'] = "All fields are required.";
        $_SESSION['flash_type'] = "error";
        redirectToOfficeHoursDashboard();
    }

    if ($start >= $end) {
        $_SESSION['flash'] = "End time must be later than start time.";
        $_SESSION['flash_type'] = "error";
        redirectToOfficeHoursDashboard();
    }

    try {
        /*
        Prevent duplicate or overlapping slots on the same day
        */
        $checkSql = "SELECT OfficeHour_ID
                     FROM office_hours
                     WHERE Advisor_ID = :advisor_id
                       AND Day_of_Week = :day
                       AND (:start_time < End_Time AND :end_time > Start_Time)
                     LIMIT 1";

        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            'advisor_id' => $advisorId,
            'day' => $day,
            'start_time' => $start,
            'end_time' => $end
        ]);

        if ($checkStmt->fetch()) {
            $_SESSION['flash'] = "This slot overlaps with an existing office hour.";
            $_SESSION['flash_type'] = "error";
            redirectToOfficeHoursDashboard();
        }

        $sql = "INSERT INTO office_hours (Advisor_ID, Day_of_Week, Start_Time, End_Time)
                VALUES (:advisor_id, :day, :start_time, :end_time)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'advisor_id' => $advisorId,
            'day' => $day,
            'start_time' => $start,
            'end_time' => $end
        ]);

        $_SESSION['flash'] = "Office hour slot added successfully.";
        $_SESSION['flash_type'] = "success";
        redirectToOfficeHoursDashboard();

    } catch (Throwable $e) {
        $_SESSION['flash'] = "Database error while adding office hour.";
        $_SESSION['flash_type'] = "error";
        redirectToOfficeHoursDashboard();
    }
}

/*
Fallback for invalid direct access
*/
$_SESSION['flash'] = "Invalid action.";
$_SESSION['flash_type'] = "error";
redirectToOfficeHoursDashboard();