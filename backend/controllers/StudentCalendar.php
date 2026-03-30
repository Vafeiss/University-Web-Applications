<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/*
TEMP TEST MODE
Use hardcoded student user id until login/session is fully connected.
*/
$studentId = 1;
$studentName = "Student Test User";

$errorMessage = "";
$events = [];

/*
------------------------------------------------------------
FETCH STUDENT NAME
------------------------------------------------------------
*/
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

/*
------------------------------------------------------------
FETCH CALENDAR EVENTS
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
                u.First_name AS Advisor_First_Name,
                u.Last_Name AS Advisor_Last_Name
            FROM appointment_requests ar
            LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
            LEFT JOIN users u ON ar.Advisor_ID = u.User_ID
            WHERE ar.Student_ID = :student_id
            ORDER BY ar.Appointment_Date ASC, oh.Start_Time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'student_id' => $studentId
    ]);

    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $status = (string)($row['Status'] ?? 'Pending');
        $advisorName = trim(
            (string)($row['Advisor_First_Name'] ?? '') . ' ' .
            (string)($row['Advisor_Last_Name'] ?? '')
        );

        $title = $advisorName !== '' ? $advisorName : 'Appointment';

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
                'advisor' => $advisorName,
                'date' => (string)($row['Appointment_Date'] ?? ''),
                'time' => (string)($row['Start_Time'] ?? '') . ' - ' . (string)($row['End_Time'] ?? ''),
                'student_reason' => (string)($row['Student_Reason'] ?? ''),
                'advisor_reason' => (string)($row['Advisor_Reason'] ?? ''),
                'status' => $status
            ]
        ];
    }

} catch (Throwable $e) {
    $errorMessage = "Could not load calendar events.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdviCut - Student Calendar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

    <style>
        .fc .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .fc-event {
            cursor: pointer;
            border-radius: 6px;
            padding: 2px 4px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">

                    <div class="mb-4">
                        <h2 class="fw-bold mb-2">Student Calendar</h2>
                        <h5 class="mb-0"><?= htmlspecialchars($studentName) ?></h5>
                    </div>

                    <?php if ($errorMessage !== ""): ?>
                        <div class="alert alert-danger rounded-3">
                            <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <div id="calendar"></div>

                    <div class="mt-4 text-center">
                        <a href="../../frontend/index.php" class="btn btn-primary px-4">Back</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title">Appointment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Advisor:</strong> <span id="modalAdvisor"></span></p>
                <p><strong>Date:</strong> <span id="modalDate"></span></p>
                <p><strong>Time:</strong> <span id="modalTime"></span></p>
                <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                <p><strong>Your Reason:</strong> <span id="modalStudentReason"></span></p>
                <p><strong>Advisor Reason:</strong> <span id="modalAdvisorReason"></span></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const modalEl = document.getElementById('appointmentModal');
    const modal = new bootstrap.Modal(modalEl);

    const events = <?= json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        events: events,
        eventClick: function(info) {
            const props = info.event.extendedProps;

            document.getElementById('modalAdvisor').textContent = props.advisor || '-';
            document.getElementById('modalDate').textContent = props.date || '-';
            document.getElementById('modalTime').textContent = props.time || '-';
            document.getElementById('modalStatus').textContent = props.status || '-';
            document.getElementById('modalStudentReason').textContent = props.student_reason || '-';
            document.getElementById('modalAdvisorReason').textContent = props.advisor_reason || '-';

            modal.show();
        }
    });

    calendar.render();
});
</script>
</body>
</html>