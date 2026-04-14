<?php
/*
   NAME: Student Appointment History
   Description: This page displays the appointment history for the student including approved, declined, cancelled, and completed appointments
   Panteleimoni Alexandrou
   30-Mar-2026 v2.3
   Inputs: Student ID from session or test variable
   Outputs: Displays student appointment history in a structured table format
   Error Messages: Displays an error message if student data cannot be loaded
   Files in use: AppointmentHistoryClass.php, databaseconnect.php

   13-Apr-2026 v2.4
   Added dynamic student name loading and improved table UI with status badges
   Panteleimoni Alexandrou
*/
declare(strict_types=1);

require_once __DIR__ . '/../modules/databaseconnect.php';
require_once __DIR__ . '/../modules/AppointmentHistoryClass.php';
require_once __DIR__ . '/../modules/UsersClass.php';

$pdo = ConnectToDatabase();

$user = new Users();
$user->Check_Session('Student');

$studentId = isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])
    ? (int)$_SESSION['UserID']
    : 0;
$studentName = trim((string)($_SESSION['email'] ?? 'Student'));

$errorMessage = "";
$history = [];

$appointmentHistory = new AppointmentHistory();

if ($studentId <= 0) {
    $errorMessage = "Unauthorized student session.";
}

try {
    $nameSql = "SELECT First_name, Last_Name
                FROM users
                WHERE User_ID = :student_id
                  AND Role = 'Student'
                LIMIT 1";

    $nameStmt = $pdo->prepare($nameSql);
    $nameStmt->execute([
        'student_id' => $studentId
    ]);

    $student = $nameStmt->fetch();

    if ($student) {
        $studentName = trim((string)$student['First_name'] . ' ' . (string)$student['Last_Name']);
    }
} catch (Throwable $e) {
    $errorMessage = "Could not load student name.";
}

if ($studentId > 0) {
    $history = $appointmentHistory->getStudentHistory($studentId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdviCut - Student Appointment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <h2 class="fw-bold mb-2">Appointment History</h2>
                        <h5 class="mb-0"><?= htmlspecialchars($studentName) ?></h5>
                    </div>

                    <?php if ($errorMessage !== ""): ?>
                        <div class="alert alert-danger rounded-3"><?= htmlspecialchars($errorMessage) ?></div>
                    <?php endif; ?>

                    <?php if (count($history) === 0): ?>
                        <div class="alert alert-secondary rounded-3 mb-0">No appointment history found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-primary text-center">
                                <tr>
                                    <th class="py-3">Request ID</th>
                                    <th class="py-3">Advisor</th>
                                    <th class="py-3">Date</th>
                                    <th class="py-3">Time</th>
                                    <th class="py-3">Your Reason</th>
                                    <th class="py-3">Advisor Reason</th>
                                    <th class="py-3">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($history as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= htmlspecialchars((string)$row['Request_ID']) ?></td>
                                        <td><?= htmlspecialchars(trim((string)($row['Advisor_First_Name'] ?? '') . ' ' . (string)($row['Advisor_Last_Name'] ?? ''))) ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string)($row['Appointment_Date'] ?? '')) ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string)($row['Start_Time'] ?? '')) ?> - <?= htmlspecialchars((string)($row['End_Time'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['Student_Reason'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['Advisor_Reason'] ?? '')) ?></td>
                                        <td class="text-center">
                                            <?php
                                            $status = (string)($row['Status'] ?? '');
                                            $badgeClass = 'bg-secondary';
                                            if ($status === 'Pending') $badgeClass = 'bg-warning text-dark';
                                            if ($status === 'Approved') $badgeClass = 'bg-success';
                                            if ($status === 'Declined') $badgeClass = 'bg-danger';
                                            if ($status === 'Cancelled') $badgeClass = 'bg-dark';
                                            ?>
                                            <span class="badge <?= $badgeClass ?> px-3 py-2"><?= htmlspecialchars($status) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 text-center">
                        <a href="../../frontend/index.php" class="btn btn-primary px-4">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>