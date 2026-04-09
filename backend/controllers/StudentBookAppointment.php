<?php
/*
   NAME: Student Book Appointment Controller
   Description: This controller handles student appointment request submission
   Panteleimoni Alexandrou
   30-Mar-2026 v1.0
   Inputs: POST inputs for student id, office hour slot, appointment date and reason
   Outputs: Inserts a new record into appointment_requests and redirects back to the student dashboard
   Error Messages: If validation fails or database action fails, a flash error message is created
    Files in use: StudentBookAppointment.php, StudentAppointmentDashboard.php, databaseconnect.php
*/

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../modules/databaseconnect.php';

$pdo = ConnectToDatabase();

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
    $_SESSION['flash'] = "Invalid request method.";
    $_SESSION['flash_type'] = "error";
    redirectToStudentDashboard('book');
}

// Read form inputs
$studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$slotId = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
$appointmentDate = isset($_POST['appointment_date']) ? trim((string)$_POST['appointment_date']) : '';
$reason = isset($_POST['reason']) ? trim((string)$_POST['reason']) : '';

// Validate basic input
if ($studentId <= 0 || $slotId <= 0 || $appointmentDate === '' || $reason === '') {
    $_SESSION['flash'] = "All booking fields are required.";
    $_SESSION['flash_type'] = "error";
    redirectToStudentDashboard('book');
}

try {
    /*
    ------------------------------------------------------------
    FETCH STUDENT ADVISOR
    ------------------------------------------------------------
    */
    $advisorSql = "SELECT Advisor_ID
                   FROM student_advisors
                   WHERE Student_ID = :student_id
                   LIMIT 1";

    $advisorStmt = $pdo->prepare($advisorSql);
    $advisorStmt->execute([
        'student_id' => $studentId
    ]);

    $advisorRow = $advisorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$advisorRow || !isset($advisorRow['Advisor_ID'])) {
        $_SESSION['flash'] = "No advisor is assigned to this student.";
        $_SESSION['flash_type'] = "error";
        redirectToStudentDashboard('book');
    }

    $advisorId = (int)$advisorRow['Advisor_ID'];

    /*
    ------------------------------------------------------------
    VALIDATE SLOT BELONGS TO STUDENT'S ADVISOR
    ------------------------------------------------------------
    */
    $slotSql = "SELECT OfficeHour_ID, Advisor_ID
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
        $_SESSION['flash'] = "Selected slot is invalid.";
        $_SESSION['flash_type'] = "error";
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
        $_SESSION['flash'] = "You already have a pending request for this slot and date.";
        $_SESSION['flash_type'] = "error";
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
    $insertStmt->execute([
        'student_id' => $studentId,
        'advisor_id' => $advisorId,
        'office_hour_id' => $slotId,
        'appointment_date' => $appointmentDate,
        'student_reason' => $reason
    ]);

    $_SESSION['flash'] = "Appointment request submitted successfully.";
    $_SESSION['flash_type'] = "success";
    redirectToStudentDashboard('requests');

} catch (Throwable $e) {
    $_SESSION['flash'] = "Database error while submitting appointment request.";
    $_SESSION['flash_type'] = "error";
    redirectToStudentDashboard('book');
}