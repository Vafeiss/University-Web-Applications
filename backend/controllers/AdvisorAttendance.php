<?php
/* 
NAME: Advisor Attendance Page
Description: Displays approved appointments for an advisor and allows marking attendance as Present or Absent.
Author: Panteleimoni Alexandrou
Date: 23/03/2026 v1.0
Inputs:
- GET: action (present / absent)
- GET: id (Appointment_ID)
Outputs: HTML page showing approved appointments + attendance actions
Error Messages: Shows database/query error if something fails
Files in use: backend/modules/databaseconnect.php, users table, appointment_history table, Bootstrap CSS from the web
*/

declare(strict_types=1);

require_once __DIR__ . '/../modules/databaseconnect.php';

$pdo = ConnectToDatabase();

$errorMessage = "";
$successMessage = "";
$advisorName = "Advisor Name";

/*
TEMP: hardcoded advisor User_ID for testing
Later this must come from session/login
*/
$advisorId = 2;

/*
------------------------------------------------------------
GET ADVISOR NAME
------------------------------------------------------------
*/
function getAdvisorName(PDO $pdo, int $advisorId): string
{
    $sql = "SELECT First_name, Last_Name
            FROM users
            WHERE User_ID = :advisor_id
              AND Role = 'Advisor'
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'advisor_id' => $advisorId
    ]);

    $advisor = $stmt->fetch();

    if ($advisor) {
        return trim($advisor['First_name'] . ' ' . $advisor['Last_Name']);
    }

    return 'Advisor Name';
}

/*
------------------------------------------------------------
UPDATE ATTENDANCE
Attendance values:
0 = Not marked
1 = Present
2 = Absent
------------------------------------------------------------
*/
function updateAttendance(PDO $pdo, int $appointmentId, int $advisorId, int $attendanceValue): bool
{
    $sql = "UPDATE appointment_history
            SET Attendance = :attendance
            WHERE Appointment_ID = :appointment_id
              AND Advisor_ID = :advisor_id
              AND Status = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'attendance' => $attendanceValue,
        'appointment_id' => $appointmentId,
        'advisor_id' => $advisorId
    ]);

    return $stmt->rowCount() > 0;
}

/*
------------------------------------------------------------
ATTENDANCE LABEL
------------------------------------------------------------
*/
function getAttendanceLabel(int $attendance): string
{
    switch ($attendance) {
        case 1:
            return 'Present';
        case 2:
            return 'Absent';
        default:
            return 'Not marked';
    }
}

/*
------------------------------------------------------------
ATTENDANCE BADGE
------------------------------------------------------------
*/
function getAttendanceBadgeClass(int $attendance): string
{
    switch ($attendance) {
        case 1:
            return 'bg-success';
        case 2:
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

/*
------------------------------------------------------------
HANDLE ATTENDANCE ACTION
------------------------------------------------------------
*/
if (isset($_GET['action'], $_GET['id'])) {
    $action = (string)$_GET['action'];
    $appointmentId = (int)$_GET['id'];

    if ($appointmentId <= 0 || !in_array($action, ['present', 'absent'], true)) {
        header("Location: AdvisorAttendance.php?error=invalid");
        exit;
    }

    $attendanceValue = ($action === 'present') ? 1 : 2;

    try {
        $updated = updateAttendance($pdo, $appointmentId, $advisorId, $attendanceValue);

        if ($updated) {
            $msg = ($action === 'present') ? 'present' : 'absent';
            header("Location: AdvisorAttendance.php?msg=" . $msg);
            exit;
        } else {
            header("Location: AdvisorAttendance.php?error=notfound");
            exit;
        }
    } catch (Throwable $e) {
        header("Location: AdvisorAttendance.php?error=dberror");
        exit;
    }
}

/*
------------------------------------------------------------
MESSAGES AFTER REDIRECT
------------------------------------------------------------
*/
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'present') {
        $successMessage = "Attendance marked as Present.";
    } elseif ($_GET['msg'] === 'absent') {
        $successMessage = "Attendance marked as Absent.";
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $errorMessage = "Invalid action or appointment ID.";
    } elseif ($_GET['error'] === 'notfound') {
        $errorMessage = "No approved appointment found for this advisor.";
    } elseif ($_GET['error'] === 'dberror') {
        $errorMessage = "Database error while updating attendance.";
    }
}

/*
------------------------------------------------------------
GET ADVISOR NAME
------------------------------------------------------------
*/
try {
    $advisorName = getAdvisorName($pdo, $advisorId);
} catch (Throwable $e) {
    $advisorName = "Advisor Name";
}

/*
------------------------------------------------------------
FETCH APPROVED APPOINTMENTS
------------------------------------------------------------
*/
$appointments = [];

try {
    $sql = "SELECT 
                ah.Appointment_ID,
                ah.Student_ID,
                ah.OfficeHour_ID,
                ah.Reason,
                ah.Appointment_Date,
                ah.Attendance,
                u.First_name,
                u.Last_Name
            FROM appointment_history ah
            LEFT JOIN users u
                ON ah.Student_ID = u.User_ID
            WHERE ah.Advisor_ID = :advisor_id
              AND ah.Status = 1
            ORDER BY ah.Appointment_Date ASC, ah.Appointment_ID DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'advisor_id' => $advisorId
    ]);

    $appointments = $stmt->fetchAll();

} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>AdviCut - Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card shadow p-4 rounded-4">

                    <h3 class="text-center mb-3">Advisor Attendance</h3>

                    <div class="mb-4">
                        <h5 class="mb-0"><?= htmlspecialchars($advisorName) ?></h5>
                    </div>

                    <?php if ($errorMessage !== ""): ?>
                        <div class="alert alert-danger text-center">
                            Error: <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($successMessage !== ""): ?>
                        <div class="alert alert-success text-center">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (count($appointments) === 0): ?>
                        <div class="alert alert-secondary text-center">
                            No approved appointments found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle text-center">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Slot ID</th>
                                        <th>Reason</th>
                                        <th>Date</th>
                                        <th>Attendance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)$a['Appointment_ID']) ?></td>
                                            <td><?= htmlspecialchars(trim((string)($a['First_name'] ?? '') . ' ' . (string)($a['Last_Name'] ?? ''))) ?></td>
                                            <td><?= htmlspecialchars((string)$a['Student_ID']) ?></td>
                                            <td><?= htmlspecialchars((string)($a['OfficeHour_ID'] ?? '')) ?></td>
                                            <td class="text-start"><?= htmlspecialchars((string)$a['Reason']) ?></td>
                                            <td><?= htmlspecialchars((string)$a['Appointment_Date']) ?></td>
                                            <td>
                                                <span class="badge <?= htmlspecialchars(getAttendanceBadgeClass((int)$a['Attendance'])) ?> px-3 py-2">
                                                    <?= htmlspecialchars(getAttendanceLabel((int)$a['Attendance'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                    <a href="AdvisorAttendance.php?action=present&id=<?= (int)$a['Appointment_ID'] ?>"
                                                       class="btn btn-success btn-sm"
                                                       onclick="return confirm('Mark this student as Present?');">
                                                        Present
                                                    </a>

                                                    <a href="AdvisorAttendance.php?action=absent&id=<?= (int)$a['Appointment_ID'] ?>"
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Mark this student as Absent?');">
                                                        Absent
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3 text-center">
                        <a href="../../frontend/index.php" class="btn btn-primary">Back</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>