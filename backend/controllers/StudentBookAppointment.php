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

function getNextBookingDate(string $dayOfWeek): string
{
    $dayLookup = [
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        'sunday' => 7,
    ];

    $targetDay = $dayLookup[strtolower(trim($dayOfWeek))] ?? 0;
    $today = new DateTimeImmutable('today');

    if ($targetDay <= 0) {
        return $today->format('Y-m-d');
    }

    $currentDay = (int)$today->format('N');
    $difference = $targetDay - $currentDay;
    if ($difference < 0) {
        $difference += 7;
    }

    return $difference === 0
        ? $today->format('Y-m-d')
        : $today->modify('+' . $difference . ' days')->format('Y-m-d');
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
$slotId = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
$appointmentDate = isset($_POST['appointment_date']) ? trim((string)$_POST['appointment_date']) : '';
$reason = isset($_POST['reason']) ? trim((string)$_POST['reason']) : '';

// Validate basic input
if ($studentId <= 0 || $slotId <= 0 || $appointmentDate === '' || $reason === '') {
    Notifications::error("All booking fields are required.");
    redirectToStudentDashboard('book');
}

if ($studentId !== $sessionStudentId) {
    Notifications::error("Forbidden.");
    redirectToStudentDashboard('book');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate)) {
    Notifications::error("Invalid appointment date.");
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

    /*
    ------------------------------------------------------------
    VALIDATE SLOT BELONGS TO STUDENT'S ADVISOR
    ------------------------------------------------------------
    */
    $slotSql = "SELECT OfficeHour_ID, Advisor_ID
                                        , Day_of_Week
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
        Notifications::error("Selected slot is invalid.");
        redirectToStudentDashboard('book');
    }

    $expectedDate = getNextBookingDate((string)($slotRow['Day_of_Week'] ?? ''));
    if ($appointmentDate !== $expectedDate) {
        Notifications::error("Selected appointment date does not match the chosen slot.");
        redirectToStudentDashboard('book');
    }

    /*
    ------------------------------------------------------------
    CHECK IF SAME STUDENT ALREADY REQUESTED SAME SLOT / DATE PENDING
    ------------------------------------------------------------
    */
    $duplicateSql = "SELECT Request_ID
                     FROM appointment_requests
                     WHERE Student_ID = :student_id
                       AND OfficeHour_ID = :slot_id
                       AND Appointment_Date = :appointment_date
                       AND LOWER(TRIM(Status)) = 'pending'
                     LIMIT 1";

    $duplicateStmt = $pdo->prepare($duplicateSql);
    $duplicateStmt->execute([
        'student_id' => $studentId,
        'slot_id' => $slotId,
        'appointment_date' => $appointmentDate
    ]);

    if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
        Notifications::error("You already have a pending request for this slot and date.");
        redirectToStudentDashboard('book');
    }

    /*
    ------------------------------------------------------------
    INSERT APPOINTMENT REQUEST
    ------------------------------------------------------------
    */
    $insertSql = "INSERT INTO appointment_requests
                  (Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Student_Reason, Advisor_Reason, Status)
                  VALUES
                  (:student_id, :advisor_id, :office_hour_id, :appointment_date, :student_reason, NULL, 'Pending')";

    $insertStmt = $pdo->prepare($insertSql);
    $inserted = $insertStmt->execute([
        'student_id' => $studentId,
        'advisor_id' => $advisorId,
        'office_hour_id' => $slotId,
        'appointment_date' => $appointmentDate,
        'student_reason' => $reason
    ]);

    if (!$inserted) {
        Notifications::error("Failed to submit appointment request.");
        redirectToStudentDashboard('book');
    }

    Notifications::success("Appointment request submitted successfully.");
    redirectToStudentDashboard('requests');

} catch (Throwable $e) {
    Notifications::error("Database error while submitting appointment request.");
    redirectToStudentDashboard('book');
}