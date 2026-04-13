<?php
/*
   NAME: Advisor Office Hours Controller
   Description: This controller handles advisor office hour actions such as add and delete and redirects back to the advisor dashboard
   Panteleimoni Alexandrou
   30-Mar-2026 v2.2
   Inputs: POST and GET inputs for office hour actions
   Outputs: Redirects back to AdvisorAppointmentDashboard.php with notifications
   Error Messages: If validation fails or database action fails, an error notification is created
   Files in use: AdvisorOfficeHours.php, AdvisorAppointmentDashboard.php, databaseconnect.php, NotificationsClass.php

   13-Apr-2026 v2.3
   Replaced flash-based notifications with centralized NotificationsClass
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../modules/databaseconnect.php';
require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';

$user = new Users();
$user->Check_Session('Advisor');

$pdo = ConnectToDatabase();

/*
Resolve advisor user id from authenticated session.
*/
$advisorId = isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])
    ? (int)$_SESSION['UserID']
    : 0;

if ($advisorId <= 0) {
    Notifications::error("Unauthorized advisor session.");
    header("Location: ../../frontend/index.php?error=unauthorized");
    exit;
}

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
        Notifications::error("Invalid slot ID.");
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

        Notifications::success("Office hour slot deleted successfully.");
        redirectToOfficeHoursDashboard();

    } catch (Throwable $e) {
        Notifications::error("Failed to delete office hour slot.");
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
        Notifications::error("Invalid action.");
        redirectToOfficeHoursDashboard();
    }

    $day = trim((string)($_POST['day_of_week'] ?? ''));
    $start = trim((string)($_POST['start_time'] ?? ''));
    $end = trim((string)($_POST['end_time'] ?? ''));

    if ($day === '' || $start === '' || $end === '') {
        Notifications::error("All fields are required.");
        redirectToOfficeHoursDashboard();
    }

    if ($start >= $end) {
        Notifications::error("End time must be later than start time.");
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
            Notifications::error("This slot overlaps with an existing office hour.");
            redirectToOfficeHoursDashboard();
        }

        $sql = "INSERT INTO office_hours (Advisor_ID, Day_of_Week, Start_Time, End_Time)
                VALUES (:advisor_id, :day, :start_time, :end_time)";

        $stmt = $pdo->prepare($sql);
        $inserted = $stmt->execute([
            'advisor_id' => $advisorId,
            'day' => $day,
            'start_time' => $start,
            'end_time' => $end
        ]);

        if (!$inserted) {
            Notifications::error("Failed to add office hour slot.");
            redirectToOfficeHoursDashboard();
        }

        Notifications::success("Office hour slot added successfully.");
        redirectToOfficeHoursDashboard();

    } catch (Throwable $e) {
        Notifications::error("Database error while adding office hour.");
        redirectToOfficeHoursDashboard();
    }
}

/*
Fallback for invalid direct access
*/
Notifications::error("Invalid action.");
redirectToOfficeHoursDashboard();

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../modules/databaseconnect.php';
require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';

$user = new Users();
$user->Check_Session('Advisor');

$pdo = ConnectToDatabase();

/*
Resolve advisor user id from authenticated session.
*/
$advisorId = isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])
    ? (int)$_SESSION['UserID']
    : 0;

if ($advisorId <= 0) {
    Notifications::error("Unauthorized advisor session.");
    header("Location: ../../frontend/index.php?error=unauthorized");
    exit;
}

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
        Notifications::error("Invalid slot ID.");
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

        Notifications::success("Office hour slot deleted successfully.");
        redirectToOfficeHoursDashboard();

    } catch (Throwable $e) {
        Notifications::error("Failed to delete office hour slot.");
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
        Notifications::error("Invalid action.");
        redirectToOfficeHoursDashboard();
    }

    $day = trim((string)($_POST['day_of_week'] ?? ''));
    $start = trim((string)($_POST['start_time'] ?? ''));
    $end = trim((string)($_POST['end_time'] ?? ''));

    if ($day === '' || $start === '' || $end === '') {
        Notifications::error("All fields are required.");
        redirectToOfficeHoursDashboard();
    }

    if ($start >= $end) {
        Notifications::error("End time must be later than start time.");
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
            Notifications::error("This slot overlaps with an existing office hour.");
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

        Notifications::success("Office hour slot added successfully.");
        redirectToOfficeHoursDashboard();

    } catch (Throwable $e) {
        Notifications::error("Database error while adding office hour.");
        redirectToOfficeHoursDashboard();
    }
}

/*
Fallback for invalid direct access
*/
Notifications::error("Invalid action.");
redirectToOfficeHoursDashboard();