<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/*
TEMP TEST MODE
*/
$advisorId = 2;
$advisorName = "Advisor Test User";

$errorMessage = "";
$events = [];

/*
------------------------------------------------------------
FETCH ADVISOR NAME
------------------------------------------------------------
*/
try {
    $nameSql = "SELECT First_name, Last_Name
                FROM users
                WHERE User_ID = :advisor_id
                  AND Role = 'Advisor'
                LIMIT 1";

    $nameStmt = $pdo->prepare($nameSql);
    $nameStmt->execute([
        'advisor_id' => $advisorId
    ]);

    $advisor = $nameStmt->fetch();

    if ($advisor) {
        $advisorName = trim((string)$advisor['First_name'] . ' ' . (string)$advisor['Last_Name']);
    }
} catch (Throwable $e) {
    $errorMessage = "Could not load advisor name.";
}

/*
------------------------------------------------------------
FETCH EVENTS
------------------------------------------------------------
*/
try {
    $sql = "SELECT
                ar.Request_ID,
                ar.Appointment_Date,
                ar.Student_Reason,
                ar.Advisor_Reason,
                ar.Status,
                oh.Start_Time,
                oh.End_Time,
                u.First_name AS Student_First_Name,
                u.Last_Name AS Student_Last_Name
            FROM appointment_requests ar
            LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
            LEFT JOIN users u ON ar.Student_ID = u.User_ID
            WHERE ar.Advisor_ID = :advisor_id
            ORDER BY ar.Appointment_Date ASC, oh.Start_Time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'advisor_id' => $advisorId
    ]);

    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $status = (string)($row['Status'] ?? 'Pending');

        $studentName = trim(
            (string)($row['Student_First_Name'] ?? '') . ' ' .
            (string)($row['Student_Last_Name'] ?? '')
        );

        $title = $studentName !== '' ? $studentName : 'Appointment';

        $color = '#6c757d';
        if ($status === 'Pending') $color = '#f0ad4e';
        if ($status === 'Approved') $color = '#198754';
        if ($status === 'Declined') $color = '#dc3545';
        if ($status === 'Cancelled') $color = '#212529';

        $events[] = [
            'id' => (int)$row['Request_ID'],
            'title' => $title . ' (' . $status . ')',
            'start' => (string)$row['Appointment_Date'],
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'student' => $studentName,
                'date' => (string)$row['Appointment_Date'],
                'time' => (string)$row['Start_Time'] . ' - ' . (string)$row['End_Time'],
                'student_reason' => (string)$row['Student_Reason'],
                'advisor_reason' => (string)$row['Advisor_Reason'],
                'status' => $status
            ]
        ];
    }

} catch (Throwable $e) {
    $errorMessage = "Could not load calendar events.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Advisor Calendar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <h3 class="mb-3">Advisor Calendar</h3>
    <h5><?= htmlspecialchars($advisorName) ?></h5>

    <div id="calendar"></div>
</div>

<!-- Modal -->
<div class="modal fade" id="modal">
    <div class="modal-dialog">
        <div class="modal-content p-3">
            <h5>Appointment Details</h5>
            <p><strong>Student:</strong> <span id="mStudent"></span></p>
            <p><strong>Date:</strong> <span id="mDate"></span></p>
            <p><strong>Time:</strong> <span id="mTime"></span></p>
            <p><strong>Status:</strong> <span id="mStatus"></span></p>
            <p><strong>Reason:</strong> <span id="mStudentReason"></span></p>
            <p><strong>Advisor Note:</strong> <span id="mAdvisorReason"></span></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const events = <?= json_encode($events) ?>;
    const modal = new bootstrap.Modal(document.getElementById('modal'));

    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        events: events,
        eventClick: function(info) {
            const p = info.event.extendedProps;

            document.getElementById('mStudent').textContent = p.student;
            document.getElementById('mDate').textContent = p.date;
            document.getElementById('mTime').textContent = p.time;
            document.getElementById('mStatus').textContent = p.status;
            document.getElementById('mStudentReason').textContent = p.student_reason;
            document.getElementById('mAdvisorReason').textContent = p.advisor_reason;

            modal.show();
        }
    });

    calendar.render();
});
</script>
</body>
</html>