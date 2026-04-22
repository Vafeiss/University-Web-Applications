<?php declare(strict_types=1);
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

   19-Apr-2026 v2.4
   Fixed strict_types declaration position to resolve fatal error
   Panteleimoni Alexandrou

   19-Apr-2026 v2.5
   Removed duplicate redirectToOfficeHoursDashboard() declaration to resolve fatal error
   Panteleimoni Alexandrou

   20-Apr-2026 v2.6
   Added advisor-side additional one-off slot creation flow with validations and dashboard redirect support
   Panteleimoni Alexandrou
*/

session_start();

require_once __DIR__ . '/../modules/databaseconnect.php';
require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';
require_once __DIR__ . '/../config/app.php';

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
    header('Location: ' . frontend_url('index.php?error=unauthorized'));
    exit;
}

/*
Helper function for redirecting back to dashboard
*/
function redirectToOfficeHoursDashboard(): void
{
    header('Location: ' . frontend_url('AdvisorAppointmentDashboard.php?section=officehours'));
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
DELETE ADDITIONAL SLOT
------------------------------------------------------------
*/
if (isset($_GET['delete_additional'])) {
    $deleteAdditionalId = (int)($_GET['delete_additional']);

    if ($deleteAdditionalId <= 0) {
        Notifications::error("Invalid additional slot ID.");
        redirectToOfficeHoursDashboard();
    }

    try {
        // Keep historical references intact by deactivating the slot.
        $sql = "UPDATE advisor_additional_slots
                SET Is_Active = 0
                WHERE AdditionalSlot_ID = :id
                  AND Advisor_ID = :advisor_id
                  AND Is_Active = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $deleteAdditionalId,
            'advisor_id' => $advisorId
        ]);

        if ($stmt->rowCount() > 0) {
            Notifications::success("Additional slot deleted successfully.");
        } else {
            Notifications::error("Failed to delete additional slot.");
        }

        redirectToOfficeHoursDashboard();
    } catch (Throwable $e) {
        Notifications::error("Failed to delete additional slot.");
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

    if ($action === 'add_additional') {
        $slotDate = trim((string)($_POST['slot_date'] ?? ''));
        $start = trim((string)($_POST['start_time'] ?? ''));
        $end = trim((string)($_POST['end_time'] ?? ''));

        if ($slotDate === '' || $start === '' || $end === '') {
            Notifications::error("All additional slot fields are required.");
            redirectToOfficeHoursDashboard();
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $slotDate)) {
            Notifications::error("Invalid slot date.");
            redirectToOfficeHoursDashboard();
        }

        if ($start >= $end) {
            Notifications::error("End time must be later than start time.");
            redirectToOfficeHoursDashboard();
        }

        $today = date('Y-m-d');
        if ($slotDate < $today) {
            Notifications::error("Additional slot date cannot be in the past.");
            redirectToOfficeHoursDashboard();
        }

        if ($slotDate === $today && $end <= date('H:i')) {
            Notifications::error("Additional slot end time cannot already be in the past.");
            redirectToOfficeHoursDashboard();
        }

        try {
            $additionalOverlapSql = "SELECT AdditionalSlot_ID
                                     FROM advisor_additional_slots
                                     WHERE Advisor_ID = :advisor_id
                                       AND Slot_Date = :slot_date
                                       AND Is_Active = 1
                                       AND (:start_time < End_Time AND :end_time > Start_Time)
                                     LIMIT 1";

            $additionalOverlapStmt = $pdo->prepare($additionalOverlapSql);
            $additionalOverlapStmt->execute([
                'advisor_id' => $advisorId,
                'slot_date' => $slotDate,
                'start_time' => $start,
                'end_time' => $end
            ]);

            if ($additionalOverlapStmt->fetch(PDO::FETCH_ASSOC)) {
                Notifications::error("This additional slot overlaps with another additional slot.");
                redirectToOfficeHoursDashboard();
            }

            $appointmentOverlapSql = "SELECT Appointment_ID
                                      FROM appointments
                                      WHERE Advisor_ID = :advisor_id
                                        AND Appointment_Date = :slot_date
                                        AND LOWER(TRIM(Status)) IN ('scheduled', 'approved', 'completed')
                                        AND (:start_time < End_Time AND :end_time > Start_Time)
                                      LIMIT 1";

            $appointmentOverlapStmt = $pdo->prepare($appointmentOverlapSql);
            $appointmentOverlapStmt->execute([
                'advisor_id' => $advisorId,
                'slot_date' => $slotDate,
                'start_time' => $start,
                'end_time' => $end
            ]);

            if ($appointmentOverlapStmt->fetch(PDO::FETCH_ASSOC)) {
                Notifications::error("This additional slot overlaps with an existing appointment.");
                redirectToOfficeHoursDashboard();
            }

            $slotDayOfWeek = date('l', strtotime($slotDate));

            $recurringOverlapSql = "SELECT OfficeHour_ID
                                    FROM office_hours
                                    WHERE Advisor_ID = :advisor_id
                                      AND Day_of_Week = :day_of_week
                                      AND (:start_time < End_Time AND :end_time > Start_Time)
                                    LIMIT 1";

            $recurringOverlapStmt = $pdo->prepare($recurringOverlapSql);
            $recurringOverlapStmt->execute([
                'advisor_id' => $advisorId,
                'day_of_week' => $slotDayOfWeek,
                'start_time' => $start,
                'end_time' => $end
            ]);

            if ($recurringOverlapStmt->fetch(PDO::FETCH_ASSOC)) {
                Notifications::error("This additional slot overlaps with an existing recurring office hour.");
                redirectToOfficeHoursDashboard();
            }

            $insertSql = "INSERT INTO advisor_additional_slots
                          (Advisor_ID, Slot_Date, Start_Time, End_Time, Is_Active)
                          VALUES
                          (:advisor_id, :slot_date, :start_time, :end_time, 1)";

            $insertStmt = $pdo->prepare($insertSql);
            $inserted = $insertStmt->execute([
                'advisor_id' => $advisorId,
                'slot_date' => $slotDate,
                'start_time' => $start,
                'end_time' => $end
            ]);

            if (!$inserted) {
                Notifications::error("Failed to add additional slot.");
                redirectToOfficeHoursDashboard();
            }

            Notifications::success("Additional slot added successfully.");
            redirectToOfficeHoursDashboard();
        } catch (Throwable $e) {
            Notifications::error("Database error while adding additional slot.");
            redirectToOfficeHoursDashboard();
        }
    }

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
