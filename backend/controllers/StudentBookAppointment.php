<?php
/*
   NAME: Student Book Appointment Controller
   Description: This controller handles student appointment request submission
   Panteleimoni Alexandrou
   30-Mar-2026 v1.1
   Inputs: POST inputs for student id, office hour slot, appointment date and reason
   Outputs: Inserts a new record into appointment_requests and redirects back to the student dashboard
   Error Messages: If validation fails or database action fails, a notification message is created
   Files in use: StudentBookAppointment.php, StudentAppointmentDashboard.php, databaseconnect.php, NotificationsClass.php

   13-Apr-2026 v1.2
   Updated notification handling to use NotificationsClass consistently for booking actions
   Panteleimoni Alexandrou

   19-Apr-2026 v1.3
   Added booking validations for past dates, weekday mismatch and duplicate same-slot requests before insert
   Panteleimoni Alexandrou

   19-Apr-2026 v1.4
   Added database notification inserts for appointment request, approve and decline actions
   Panteleimoni Alexandrou

   20-Apr-2026 v1.5
   Changed student booking flow to submit concrete recurring and additional slot selections without free date input
   Panteleimoni Alexandrou

   20-Apr-2026 v1.6
   Updated recurring booking to keep advisor-defined times fixed while requiring only date selection, and kept additional slots fully fixed
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../modules/NotificationsClass.php';
require_once __DIR__ . '/../modules/databaseconnect.php';
require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../modules/Csrf.php';

$pdo = ConnectToDatabase();
$user = new Users();
$user->Check_Session('Student');

/*
Helper function for redirecting back to student dashboard
*/
function redirectToStudentDashboard(string $section = 'book'): void
{
    header("Location: ../../frontend/StudentAppointmentDashboard.php?section=" . urlencode($section));
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Notifications::error("Invalid request method.");
    redirectToStudentDashboard('book');
}

if (!Csrf::validateRequestToken()) {
    Notifications::error("Request validation failed.");
    redirectToStudentDashboard('book');
}

if (!isset($_SESSION['UserID']) || !is_numeric($_SESSION['UserID'])) {
    Notifications::error("Unauthorized student session.");
    redirectToStudentDashboard('book');
}

$sessionStudentId = (int)$_SESSION['UserID'];

// Read form inputs
$studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$slotSource = trim((string)($_POST['slot_source'] ?? ''));
$slotId = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
$slotDate = trim((string)($_POST['slot_date'] ?? ''));
$appointmentDate = trim((string)($_POST['appointment_date'] ?? ''));
$reason = isset($_POST['reason']) ? trim((string)$_POST['reason']) : '';

// Validate basic input
if ($studentId <= 0 || $slotId <= 0 || $reason === '' || !in_array($slotSource, ['recurring', 'additional'], true)) {
    Notifications::error("All booking fields are required.");
    redirectToStudentDashboard('book');
}

if ($studentId !== $sessionStudentId) {
    Notifications::error("Forbidden.");
    redirectToStudentDashboard('book');
}

if (mb_strlen($reason, 'UTF-8') > 2000) {
    Notifications::error("Reason is too long.");
    redirectToStudentDashboard('book');
}

try {
    /*
    ------------------------------------------------------------
    FETCH STUDENT ADVISOR
    ------------------------------------------------------------
    */
    $advisorSql = "SELECT advisor.User_ID AS Advisor_User_ID
                   FROM users student
                   INNER JOIN student_advisors sa ON sa.Student_ID = student.External_ID
                   INNER JOIN users advisor ON advisor.External_ID = sa.Advisor_ID AND advisor.Role = 'Advisor'
                   WHERE student.User_ID = :student_id
                     AND student.Role = 'Student'
                   LIMIT 1";

    $advisorStmt = $pdo->prepare($advisorSql);
    $advisorStmt->execute([
        'student_id' => $studentId
    ]);

    $advisorRow = $advisorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$advisorRow || !isset($advisorRow['Advisor_User_ID'])) {
        Notifications::error("No advisor is assigned to this student.");
        redirectToStudentDashboard('book');
    }

    $advisorId = (int)$advisorRow['Advisor_User_ID'];

    $today = date('Y-m-d');

    $inserted = false;

    if ($slotSource === 'recurring') {
        if ($appointmentDate === '') {
            Notifications::error("Appointment date is required for recurring slots.");
            redirectToStudentDashboard('book');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate)) {
            Notifications::error("Invalid appointment date.");
            redirectToStudentDashboard('book');
        }

        if ($appointmentDate < $today) {
            Notifications::error("Appointment date cannot be in the past.");
            redirectToStudentDashboard('book');
        }

        $slotSql = "SELECT OfficeHour_ID, Advisor_ID, Day_of_Week, Start_Time, End_Time
                    FROM office_hours
                    WHERE OfficeHour_ID = :slot_id
                      AND Advisor_ID = :advisor_id
                    LIMIT 1";

        $slotStmt = $pdo->prepare($slotSql);
        $slotStmt->execute([
            'slot_id' => $slotId,
            'advisor_id' => $advisorId
        ]);

        $slotRow = $slotStmt->fetch(PDO::FETCH_ASSOC);

        if (!$slotRow) {
            Notifications::error("Selected recurring slot is invalid.");
            redirectToStudentDashboard('book');
        }

        $slotDayOfWeek = trim((string)($slotRow['Day_of_Week'] ?? ''));
        $submittedDayOfWeek = date('l', strtotime($appointmentDate));

        if ($slotDayOfWeek === '' || strcasecmp($slotDayOfWeek, $submittedDayOfWeek) !== 0) {
            Notifications::error("Selected recurring slot date does not match the office hour weekday.");
            redirectToStudentDashboard('book');
        }

        if ($appointmentDate === $today && (string)($slotRow['End_Time'] ?? '') <= date('H:i:s')) {
            Notifications::error("Selected recurring slot is already in the past.");
            redirectToStudentDashboard('book');
        }

                $duplicateSql = "SELECT Request_ID
                                                 FROM appointment_requests WHERE OfficeHour_ID = :slot_id
                                                     AND Appointment_Date = :appointment_date
                                                     AND LOWER(TRIM(Status)) IN ('pending', 'approved')
                                                 LIMIT 1";

        $duplicateStmt = $pdo->prepare($duplicateSql);
        $duplicateStmt->execute([
            'slot_id' => $slotId,
            'appointment_date' => $appointmentDate
        ]);

        if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            Notifications::error("This recurring slot is already requested by another student for that date.");
            redirectToStudentDashboard('book');
        }

        $scheduledDuplicateSql = "SELECT Appointment_ID FROM appointments WHERE OfficeHour_ID = :slot_id AND Appointment_Date = :appointment_date AND Status = 'Scheduled'
                                  LIMIT 1";

        $scheduledDuplicateStmt = $pdo->prepare($scheduledDuplicateSql);
        $scheduledDuplicateStmt->execute([
            'slot_id' => $slotId,
            'appointment_date' => $appointmentDate
        ]);

        if ($scheduledDuplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            Notifications::error("This recurring slot is already booked for that date.");
            redirectToStudentDashboard('book');
        }

        $insertSql = "INSERT INTO appointment_requests
                      (Student_ID, Advisor_ID, OfficeHour_ID, AdditionalSlot_ID, Appointment_Date, Student_Reason, Advisor_Reason, Status)
                      VALUES
                      (:student_id, :advisor_id, :office_hour_id, NULL, :appointment_date, :student_reason, NULL, 'Pending')";

        $insertStmt = $pdo->prepare($insertSql);
        $inserted = $insertStmt->execute([
            'student_id' => $studentId,
            'advisor_id' => $advisorId,
            'office_hour_id' => $slotId,
            'appointment_date' => $appointmentDate,
            'student_reason' => $reason
        ]);
    }

    if ($slotSource === 'additional') {
        if ($slotDate === '') {
            Notifications::error("Fixed slot date is required for additional slots.");
            redirectToStudentDashboard('book');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $slotDate)) {
            Notifications::error("Invalid appointment date.");
            redirectToStudentDashboard('book');
        }

        if ($slotDate < $today) {
            Notifications::error("Appointment date cannot be in the past.");
            redirectToStudentDashboard('book');
        }

        $slotSql = "SELECT AdditionalSlot_ID, Advisor_ID, Slot_Date, Start_Time, End_Time, Is_Active
                    FROM advisor_additional_slots
                    WHERE AdditionalSlot_ID = :slot_id
                      AND Advisor_ID = :advisor_id
                    LIMIT 1";

        $slotStmt = $pdo->prepare($slotSql);
        $slotStmt->execute([
            'slot_id' => $slotId,
            'advisor_id' => $advisorId
        ]);

        $slotRow = $slotStmt->fetch(PDO::FETCH_ASSOC);

        if (!$slotRow) {
            Notifications::error("Selected additional slot is invalid.");
            redirectToStudentDashboard('book');
        }

        if ((int)($slotRow['Is_Active'] ?? 0) !== 1) {
            Notifications::error("Selected additional slot is no longer active.");
            redirectToStudentDashboard('book');
        }

        if ((string)($slotRow['Slot_Date'] ?? '') !== $slotDate) {
            Notifications::error("Selected additional slot date does not match the stored slot date.");
            redirectToStudentDashboard('book');
        }

        if ($slotDate === $today && (string)($slotRow['End_Time'] ?? '') <= date('H:i:s')) {
            Notifications::error("Selected additional slot is already in the past.");
            redirectToStudentDashboard('book');
        }

                $duplicateSql = "SELECT Request_ID FROM appointment_requests WHERE AdditionalSlot_ID = :slot_id
                AND LOWER(TRIM(Status)) IN ('pending', 'approved')
                LIMIT 1";

        $duplicateStmt = $pdo->prepare($duplicateSql);
        $duplicateStmt->execute([
            'slot_id' => $slotId
        ]);

        if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            Notifications::error("This additional slot is already requested.");
            redirectToStudentDashboard('book');
        }

        $scheduledAdditionalSql = "SELECT Appointment_ID FROM appointments WHERE AdditionalSlot_ID = :slot_id
        AND Status = 'Scheduled'
        LIMIT 1";

        $scheduledAdditionalStmt = $pdo->prepare($scheduledAdditionalSql);
        $scheduledAdditionalStmt->execute([
            'slot_id' => $slotId
        ]);

        if ($scheduledAdditionalStmt->fetch(PDO::FETCH_ASSOC)) {
            Notifications::error("This additional slot is already booked.");
            redirectToStudentDashboard('book');
        }

                $studentDuplicateSql = "SELECT Request_ID FROM appointment_requests WHERE Student_ID = :student_id
                AND AdditionalSlot_ID = :slot_id AND LOWER(TRIM(Status)) IN ('pending', 'approved')
                LIMIT 1";

        $studentDuplicateStmt = $pdo->prepare($studentDuplicateSql);
        $studentDuplicateStmt->execute([
            'student_id' => $studentId,
            'slot_id' => $slotId
        ]);

        if ($studentDuplicateStmt->fetch(PDO::FETCH_ASSOC)) {
            Notifications::error("You already have a pending request for this additional slot.");
            redirectToStudentDashboard('book');
        }

        $insertSql = "INSERT INTO appointment_requests
                      (Student_ID, Advisor_ID, OfficeHour_ID, AdditionalSlot_ID, Appointment_Date, Student_Reason, Advisor_Reason, Status)
                      VALUES
                      (:student_id, :advisor_id, NULL, :additional_slot_id, :appointment_date, :student_reason, NULL, 'Pending')";

        $insertStmt = $pdo->prepare($insertSql);
        $inserted = $insertStmt->execute([
            'student_id' => $studentId,
            'advisor_id' => $advisorId,
            'additional_slot_id' => $slotId,
            'appointment_date' => $slotDate,
            'student_reason' => $reason
        ]);
    }

    if (!$inserted) {
        Notifications::error("Failed to submit appointment request.");
        redirectToStudentDashboard('book');
    }

    $requestId = (int)$pdo->lastInsertId();

    try {
        $notificationSql = "INSERT INTO notifications
                            (Recipient_ID, Sender_ID, Type, Title, Message, Related_Request_ID, Is_Read)
                            VALUES
                            (:recipient_id, :sender_id, :type, :title, :message, :related_request_id, :is_read)";

        $notificationStmt = $pdo->prepare($notificationSql);
        $notificationStmt->execute([
            'recipient_id' => $advisorId,
            'sender_id' => $studentId,
            'type' => 'appointment_requested',
            'title' => 'New Appointment Request',
            'message' => 'A student has requested a new appointment.',
            'related_request_id' => $requestId,
            'is_read' => 0
        ]);
    } catch (Throwable $e) {
        error_log('StudentBookAppointment notification insert error: ' . $e->getMessage());
    }

    Notifications::success("Appointment request submitted successfully.");
    redirectToStudentDashboard('requests');

} catch (Throwable $e) {
    Notifications::error("Database error while submitting appointment request.");
    redirectToStudentDashboard('book');
}
