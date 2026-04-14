<?php
/*
   NAME: Student Appointment Dashboard
   Description: This page displays the student dashboard for booking appointments, viewing pending requests, approved appointments and appointment history
   Panteleimoni Alexandrou
   30-Mar-2026 v1.8
   Inputs: Section parameter from URL, session flash messages and database records for available slots, requests, appointments and history
   Outputs: Student dashboard interface with real database data
   Error Messages: If database fetch fails, an error message is displayed inside the relevant section
  Files in use: StudentAppointmentDashboard.php, StudentBookAppointment.php, databaseconnect.php

   30-Mar-2026 v1.8
   Added booking submit integration and appointments fallback logic
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../backend/modules/UsersClass.php';
require_once __DIR__ . '/../backend/modules/StudentClass.php';
require_once __DIR__ . '/../backend/modules/NotificationsClass.php';

$user = new Users();
$user->Check_Session('Student');

require_once __DIR__ . '/../backend/modules/databaseconnect.php';

$pdo = ConnectToDatabase();

/*
login/session of student user
*/
$studentId = isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])
  ? (int)$_SESSION['UserID']
  : 0;
  

$currentStudent = [];
$myAdvisor = null;
$studentExternalId = 0;
$studentDisplayName = 'Student';

if ($studentId > 0) {
  try {
    $studentModule = new StudentClass();
    $currentStudent = $studentModule->getStudentInfo($studentId);
    $myAdvisor = $studentModule->getStudentAdvisor($studentId);
    $studentDisplayName = trim((string)($currentStudent['First_name'] ?? '') . ' ' . (string)($currentStudent['Last_Name'] ?? ''));
    if ($studentDisplayName === '') {
      $studentDisplayName = 'Student';
    }
    $studentExternalId = isset($currentStudent['Student_ID']) && is_numeric($currentStudent['Student_ID'])
      ? (int)$currentStudent['Student_ID']
      : 0;
  } catch (Throwable $e) {
    error_log('Error loading student dashboard info: ' . $e->getMessage());
    $currentStudent = [];
    $myAdvisor = null;
    $studentExternalId = 0;
  }
}

// Get active section from URL
$activeSection = isset($_GET['section']) ? $_GET['section'] : 'calendar';

// Flash messages
$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
$flashType = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success';

unset($_SESSION['flash'], $_SESSION['flash_type']);

// Student advisor mapping
$advisorId = null;
$advisorName = 'Assigned Advisor';

// Available office hours for booking
$availableSlots = [];
$availableSlotsError = '';

// Student pending requests
$studentRequests = [];
$studentRequestsError = '';

// Student appointments
$studentAppointments = [];
$studentAppointmentsError = '';

// Student history
$studentHistory = [];
$studentHistoryError = '';

// Student calendar events
$studentCalendarEvents = [];
$studentCalendarError = '';

/*
------------------------------------------------------------
FETCH STUDENT ADVISOR
------------------------------------------------------------
*/
try {
  if (is_array($myAdvisor) && isset($myAdvisor['User_ID'])) {
    $advisorId = (int)$myAdvisor['User_ID'];
    $advisorName = trim((string)($myAdvisor['First_name'] ?? '') . ' ' . (string)($myAdvisor['Last_Name'] ?? ''));
    if ($advisorName === '') {
      $advisorName = 'Assigned Advisor';
    }
  }
} catch (Throwable $e) {
  $advisorId = null;
}

/*
------------------------------------------------------------
FETCH AVAILABLE OFFICE HOUR SLOTS
------------------------------------------------------------
*/
if ($advisorId !== null) {
    try {
        $sql = "SELECT OfficeHour_ID, Advisor_ID, Day_of_Week, Start_Time, End_Time
                FROM office_hours
                WHERE Advisor_ID = :advisor_id
                ORDER BY
                    FIELD(Day_of_Week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                    Start_Time ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'advisor_id' => $advisorId
        ]);

        $availableSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $availableSlotsError = 'Could not load available office hour slots.';
    }
} else {
    $availableSlotsError = 'No advisor is assigned to this student.';
}

$studentMessageUserId = (int)($currentStudent['User_ID'] ?? $studentId);
$communicationAdvisorLabel = !empty($myAdvisor)
  ? trim((string)($myAdvisor['First_name'] ?? '') . ' ' . (string)($myAdvisor['Last_Name'] ?? ''))
  : $advisorName;

/*
------------------------------------------------------------
FETCH ONLY PENDING STUDENT REQUESTS
------------------------------------------------------------
*/
try {
    $sql = "SELECT Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Student_Reason, Advisor_Reason, Status, Created_At
            FROM appointment_requests
            WHERE Student_ID = :student_id
              AND LOWER(TRIM(Status)) = 'pending'
            ORDER BY Created_At DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'student_id' => $studentId
    ]);

    $studentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $studentRequestsError = 'Could not load appointment requests.';
}

/*
------------------------------------------------------------
FETCH STUDENT APPOINTMENTS
First try appointments table.
If no rows are found, fallback to approved requests.
------------------------------------------------------------
*/
try {
  $sql = "SELECT a.Appointment_ID, a.Request_ID, a.Student_ID, a.Advisor_ID, u.Last_Name AS Advisor_Last_Name, a.OfficeHour_ID, a.Appointment_Date, a.Start_Time, a.End_Time, a.Status, a.Created_At
      FROM appointments a
      LEFT JOIN users u ON a.Advisor_ID = u.User_ID
            WHERE Student_ID = :student_id
            ORDER BY Appointment_Date DESC, Start_Time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'student_id' => $studentId
    ]);

    $studentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($studentAppointments) === 0) {
        $fallbackSql = "SELECT 
                            NULL AS Appointment_ID,
                            Request_ID,
                            Student_ID,
                            Advisor_ID,
                            u.Last_Name AS Advisor_Last_Name,
                            OfficeHour_ID,
                            Appointment_Date,
                            NULL AS Start_Time,
                            NULL AS End_Time,
                            Status,
                            Created_At
                        FROM appointment_requests ar
                        LEFT JOIN users u ON ar.Advisor_ID = u.User_ID
                        WHERE Student_ID = :student_id
                          AND LOWER(TRIM(Status)) = 'approved'
                        ORDER BY Appointment_Date DESC, Created_At DESC";

        $fallbackStmt = $pdo->prepare($fallbackSql);
        $fallbackStmt->execute([
          'student_id' => $studentId
        ]);

        $studentAppointments = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $studentAppointmentsError = 'Could not load student appointments.';
}

/*
------------------------------------------------------------
FETCH STUDENT HISTORY
Use non-pending request records as history for now
------------------------------------------------------------
*/
try {
    $sql = "SELECT ar.Request_ID, ar.Student_ID, ar.Advisor_ID, u.Last_Name AS Advisor_Last_Name, ar.Appointment_Date, ar.Student_Reason, ar.Advisor_Reason, ar.Status, ar.Created_At
            FROM appointment_requests ar
            LEFT JOIN users u ON ar.Advisor_ID = u.User_ID
            WHERE ar.Student_ID = :student_id
              AND LOWER(TRIM(Status)) <> 'pending'
            ORDER BY Created_At DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'student_id' => $studentId
    ]);

    $studentHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $studentHistoryError = 'Could not load appointment history.';
}

/*
------------------------------------------------------------
FETCH STUDENT CALENDAR EVENTS
------------------------------------------------------------
*/
try {
  $calendarSql = "SELECT
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

  $calendarStmt = $pdo->prepare($calendarSql);
  $calendarStmt->execute([
    'student_id' => $studentId
  ]);

  $calendarRows = $calendarStmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($calendarRows as $row) {
    $status = (string)($row['Status'] ?? 'Pending');
    $advisorFullName = trim(
      (string)($row['Advisor_First_Name'] ?? '') . ' ' .
      (string)($row['Advisor_Last_Name'] ?? '')
    );

    $title = $advisorFullName !== '' ? $advisorFullName : 'Appointment';

    $eventColor = '#6c757d';
    if ($status === 'Pending') $eventColor = '#f0ad4e';
    if ($status === 'Approved') $eventColor = '#198754';
    if ($status === 'Declined') $eventColor = '#dc3545';
    if ($status === 'Cancelled') $eventColor = '#212529';

    $studentCalendarEvents[] = [
      'id' => (int)($row['Request_ID'] ?? 0),
      'title' => $title . ' (' . $status . ')',
      'start' => (string)($row['Appointment_Date'] ?? ''),
      'backgroundColor' => $eventColor,
      'borderColor' => $eventColor,
      'extendedProps' => [
        'advisor' => $advisorFullName,
        'date' => (string)($row['Appointment_Date'] ?? ''),
        'time' => (string)($row['Start_Time'] ?? '') . ' - ' . (string)($row['End_Time'] ?? ''),
        'student_reason' => (string)($row['Student_Reason'] ?? ''),
        'advisor_reason' => (string)($row['Advisor_Reason'] ?? ''),
        'status' => $status
      ]
    ];
  }
} catch (Throwable $e) {
  $studentCalendarError = 'Could not load calendar events.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Appointment Portal</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/student_appointment_dashboard.css">

</head>
<body data-student-calendar-events="<?= htmlspecialchars(json_encode($studentCalendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
  data-comm-student-id="<?= (int)$studentMessageUserId ?>">
<?php Notifications::createNotification(); ?>

<header class="top-navbar">
  <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

  <div class="navbar-center">
    <span class="welcome-text">Welcome to AdviCut, <?= htmlspecialchars($studentDisplayName) ?>! 👋</span>
  </div>

  <div class="d-flex align-items-center gap-3">
    <div class="dropdown">
      <button class="btn p-0 border-0 bg-transparent dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="user-avatar">S</div>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px;">
        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#manualInstructionsModal">
          <i class="bi bi-journal-text me-2"></i>Manual
        </button>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="changepassword.php">
          <i class="bi bi-shield-lock me-2"></i>Change Password
        </a>
        <div class="dropdown-divider"></div>
        <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0">
          <input type="hidden" name="action" value="/logout">
          <button type="submit" class="dropdown-item text-danger">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
          </button>
        </form>
      </div>
    </div>
  </div>
</header>

<div class="modal fade" id="manualInstructionsModal" tabindex="-1" aria-labelledby="manualInstructionsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold" id="manualInstructionsModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>Student Dashboard Manual
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <ol class="mb-0 ps-3">
          <li>Use Book Appointment to schedule meetings with your advisor based on the available slots.</li>
          <li>Use My Requests to view the status of your appointment requests.</li>
          <li>Use My Appointments to see upcoming confirmed/denied appointments.</li>
          <li>Use Appointment History to review past appointments and notes.</li>
          <li>Use Communications to message your assigned advisor.</li>
          <li>Use Change Password to update your login password.</li>
          <li>If no advisor is assigned yet, contact the administration office.</li>
        </ol>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="tab-bar">
  <button type="button" class="tab-btn <?= $activeSection === 'calendar' ? 'active' : '' ?>" data-section="calendar">
    <i class="bi bi-calendar3"></i> Calendar
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'book' ? 'active' : '' ?>" data-section="book">
    <i class="bi bi-calendar-plus"></i> Book Appointment
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'requests' ? 'active' : '' ?>" data-section="requests">
    <i class="bi bi-hourglass-split"></i> My Requests
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'appointments' ? 'active' : '' ?>" data-section="appointments">
    <i class="bi bi-calendar-check"></i> My Appointments
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'history' ? 'active' : '' ?>" data-section="history">
    <i class="bi bi-clock-history"></i> History
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'communications' ? 'active' : '' ?>" data-section="communications">
    <i class="bi bi-chat-dots"></i> Communications
  </button>
</div>

<main class="container-fluid py-4 px-4" style="max-width: 1100px;">

  <!-- Book Appointment tab -->
  <div class="section-panel <?= $activeSection === 'book' ? 'active' : '' ?>" id="section-book">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Book Appointment</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Select an available advisor slot and request a meeting</p>
        </div>

        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal">
          <i class="bi bi-plus-circle me-1"></i> New Request
        </button>
      </div>

<div class="row g-3 mb-4">

  <div class="col-12 col-md-6">
    <button type="button"
            class="info-box d-flex align-items-center gap-3 w-100 border-0 text-start"
            onclick="document.querySelector('.tab-btn[data-section=\'communications\']').click();">
      <div class="info-icon">
        <i class="bi bi-person-badge"></i>
      </div>
      <div>
        <div class="fw-semibold">Assigned Advisor</div>
        <div class="text-muted small"><?= htmlspecialchars($advisorName) ?></div>
      </div>
    </button>
  </div>

  <div class="col-12 col-md-6">
    <button type="button"
            class="info-box d-flex align-items-center gap-3 w-100 border-0 text-start"
            onclick="document.querySelector('.tab-btn[data-section=\'requests\']').click();">
      <div class="info-icon">
        <i class="bi bi-send-check"></i>
      </div>
      <div>
        <div class="fw-semibold">Request Status</div>
        <div class="text-muted small">Track pending and approved requests</div>
      </div>
    </button>
  </div>

</div>

      <?php if ($availableSlotsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($availableSlotsError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Advisor</th>
              <th>Day</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Status</th>
              <th style="width:140px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($availableSlots) === 0): ?>
              <tr class="book-row">
                <td colspan="6" class="text-center text-muted">No office hour slots loaded yet</td>
              </tr>
            <?php else: ?>
              <?php foreach ($availableSlots as $slot): ?>
                <tr class="book-row">
                  <td><?= htmlspecialchars($advisorName) ?></td>
                  <td><?= htmlspecialchars((string)$slot['Day_of_Week']) ?></td>
                  <td><?= htmlspecialchars(substr((string)$slot['Start_Time'], 0, 5)) ?></td>
                  <td><?= htmlspecialchars(substr((string)$slot['End_Time'], 0, 5)) ?></td>
                  <td><span class="badge bg-success">Available</span></td>
                  <td>
                    <button type="button"
                            class="btn btn-primary btn-sm open-book-modal-btn"
                            data-slot-id="<?= (int)$slot['OfficeHour_ID'] ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#bookAppointmentModal">
                      Book
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- My Requests tab -->
  <div class="section-panel <?= $activeSection === 'requests' ? 'active' : '' ?>" id="section-requests">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">My Requests</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">View all your pending appointment requests</p>
        </div>
      </div>

      <?php if ($studentRequestsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($studentRequestsError) ?>
        </div>
      <?php endif; ?>

      <input class="form-control mb-3" id="studentRequestSearch" placeholder="Search requests…">

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Advisor</th>
              <th>Date</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Decline Reason</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($studentRequests) === 0): ?>
              <tr class="student-request-row">
                <td colspan="5" class="text-center text-muted">No pending requests loaded yet</td>
              </tr>
            <?php else: ?>
              <?php foreach ($studentRequests as $request): ?>
                <tr class="student-request-row">
                  <td><?= htmlspecialchars('Advisor ID: ' . (string)$request['Advisor_ID']) ?></td>
                  <td><?= htmlspecialchars((string)$request['Appointment_Date']) ?></td>
                  <td><?= htmlspecialchars((string)$request['Student_Reason']) ?></td>
                  <td><span class="badge bg-secondary">Pending</span></td>
                  <td>-</td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- My Appointments tab -->
  <div class="section-panel <?= $activeSection === 'appointments' ? 'active' : '' ?>" id="section-appointments">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">My Appointments</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">View all approved appointments with your advisor</p>
        </div>
      </div>

      <?php if ($studentAppointmentsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($studentAppointmentsError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Advisor</th>
              <th>Date</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($studentAppointments) === 0): ?>
              <tr>
                <td colspan="5" class="text-center text-muted">No approved appointments loaded yet</td>
              </tr>
            <?php else: ?>
              <?php foreach ($studentAppointments as $appointment): ?>
                <tr>
                  <td><?= htmlspecialchars(trim((string)($appointment['Advisor_Last_Name'] ?? '')) !== '' ? (string)$appointment['Advisor_Last_Name'] : 'Advisor') ?></td>
                  <td><?= htmlspecialchars((string)$appointment['Appointment_Date']) ?></td>
                  <td><?= htmlspecialchars($appointment['Start_Time'] ? substr((string)$appointment['Start_Time'], 0, 5) : '-') ?></td>
                  <td><?= htmlspecialchars($appointment['End_Time'] ? substr((string)$appointment['End_Time'], 0, 5) : '-') ?></td>
                  <td>
                    <?php if (strtolower(trim((string)$appointment['Status'])) === 'scheduled'): ?>
                      <span class="badge bg-primary">Scheduled</span>
                    <?php elseif (strtolower(trim((string)$appointment['Status'])) === 'completed'): ?>
                      <span class="badge bg-success">Completed</span>
                    <?php elseif (strtolower(trim((string)$appointment['Status'])) === 'cancelled'): ?>
                      <span class="badge bg-danger">Cancelled</span>
                    <?php elseif (strtolower(trim((string)$appointment['Status'])) === 'approved'): ?>
                      <span class="badge bg-success">Approved</span>
                    <?php else: ?>
                      <span class="badge bg-dark"><?= htmlspecialchars((string)$appointment['Status']) ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- History tab -->
  <div class="section-panel <?= $activeSection === 'history' ? 'active' : '' ?>" id="section-history">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Appointment History</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">View previous appointment actions and decisions</p>
        </div>
      </div>

      <?php if ($studentHistoryError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($studentHistoryError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Advisor</th>
              <th>Status</th>
              <th>Date</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($studentHistory) === 0): ?>
              <tr>
                <td colspan="4" class="text-center text-muted">No history loaded yet</td>
              </tr>
            <?php else: ?>
              <?php foreach ($studentHistory as $history): ?>
                <?php
                  $historyReason = trim((string)($history['Advisor_Reason'] ?? $history['Student_Reason'] ?? ''));
                  $historyAdvisorLastName = trim((string)($history['Advisor_Last_Name'] ?? ''));
                ?>
                <tr>
                  <td><?= htmlspecialchars($historyAdvisorLastName !== '' ? $historyAdvisorLastName : 'Advisor') ?></td>
                  <td>
                    <?php if ($history['Status'] === 'Approved'): ?>
                      <span class="badge bg-success">Approved</span>
                    <?php elseif ($history['Status'] === 'Declined'): ?>
                      <span class="badge bg-danger">Declined</span>
                    <?php elseif ($history['Status'] === 'Cancelled'): ?>
                      <span class="badge bg-dark">Cancelled</span>
                    <?php else: ?>
                      <span class="badge bg-primary"><?= htmlspecialchars((string)$history['Status']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars((string)$history['Appointment_Date']) ?></td>
                  <td>
                    <?php if ($historyReason !== ''): ?>
                      <button type="button"
                              class="btn btn-outline-primary btn-sm calendar-reason-btn history-details-btn"
                              data-history-reason="<?= htmlspecialchars($historyReason) ?>">
                        View details
                      </button>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- Calendar tab -->
  <div class="section-panel <?= $activeSection === 'calendar' ? 'active' : '' ?>" id="section-calendar">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Appointment Calendar</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Track all your requests and appointment decisions by date</p>
        </div>
      </div>

      <?php if ($studentCalendarError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($studentCalendarError) ?>
        </div>
      <?php endif; ?>

      <div id="studentCalendar"></div>
    </div>
  </div>

  <!-- Communications tab -->
  <div class="section-panel <?= $activeSection === 'communications' ? 'active' : '' ?>" id="section-communications">
    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Communications</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Send and receive messages from your academic advisor.</p>
        </div>
      </div>

      <div class="comm-layout">

        <?php if ($advisorId === null): ?>
          <div class="comm-placeholder">
            <i class="bi bi-person-x"></i>
            <p>You don't have an advisor assigned yet.<br>Please contact the administration.</p>
          </div>
        <?php else: ?>

          <div class="comm-messages" id="commMessages">
            <div class="comm-loading">Loading messages...</div>
          </div>

          <div class="comm-compose">
            <label for="commTextarea">Send a message to <?= htmlspecialchars($communicationAdvisorLabel !== '' ? $communicationAdvisorLabel : 'Advisor') ?> <span class="text-muted">(200 words max)</span></label>
            <textarea id="commTextarea"
                      placeholder="Type your question or message here..."
                      maxlength="2000"
                      oninput="commWordCount(this)"></textarea>
            <div class="comm-compose-footer">
              <span class="comm-word-count" id="commWordCount">0 / 200 words</span>
              <button type="button" class="btn-send" id="commSendBtn" onclick="commSend()" disabled>
                <i class="bi bi-send-fill"></i> Send Message
              </button>
            </div>
          </div>

        <?php endif; ?>

      </div>

    </div>
  </div>

</main>

<?php require_once __DIR__ . '/footer/dashboard_footer.php'; ?>

<!-- Book Appointment modal -->
<div class="modal fade" id="bookAppointmentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Book Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form action="../backend/controllers/StudentBookAppointment.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="student_id" value="<?= (int)$studentId ?>">

          <div class="row g-3">

            <div class="col-12">
              <label class="form-label">Advisor <span class="text-danger">*</span></label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($advisorName) ?>" readonly>
            </div>

            <div class="col-12">
              <label class="form-label">Available Slot <span class="text-danger">*</span></label>
              <select name="slot_id" id="bookSlotSelect" class="form-select" required>
                <option value="" selected disabled>Select a slot...</option>
                <?php foreach ($availableSlots as $slot): ?>
                  <option value="<?= (int)$slot['OfficeHour_ID'] ?>">
                    <?= htmlspecialchars((string)$slot['Day_of_Week'] . ' - ' . substr((string)$slot['Start_Time'], 0, 5) . ' to ' . substr((string)$slot['End_Time'], 0, 5)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
              <input type="date" name="appointment_date" class="form-control" required>
            </div>

            <div class="col-12">
              <label class="form-label">Reason for Appointment <span class="text-danger">*</span></label>
              <textarea name="reason" class="form-control" rows="4" placeholder="Write the reason for your appointment request..." required></textarea>
            </div>

          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send me-1"></i> Send Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Student Calendar Details Modal -->
<div class="modal fade" id="studentCalendarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title">Appointment Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Advisor:</strong> <span id="calendarModalAdvisor"></span></p>
        <p><strong>Date:</strong> <span id="calendarModalDate"></span></p>
        <p><strong>Time:</strong> <span id="calendarModalTime"></span></p>
        <p><strong>Status:</strong> <span id="calendarModalStatus"></span></p>

        <div class="calendar-reason-group">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <strong>Your Reason:</strong>
            <button type="button"
                    class="btn btn-outline-primary btn-sm calendar-reason-btn"
                    id="calendarModalStudentReasonBtn"
                    data-bs-toggle="collapse"
                    data-bs-target="#calendarModalStudentReasonWrap"
                    aria-expanded="false"
                    aria-controls="calendarModalStudentReasonWrap">
              View Reason
            </button>
          </div>
          <div class="collapse mt-2" id="calendarModalStudentReasonWrap">
            <div class="calendar-reason-box" id="calendarModalStudentReason"></div>
          </div>
        </div>

        <div class="calendar-reason-group mt-3">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <strong>Advisor Reason:</strong>
            <button type="button"
                    class="btn btn-outline-primary btn-sm calendar-reason-btn"
                    id="calendarModalAdvisorReasonBtn"
                    data-bs-toggle="collapse"
                    data-bs-target="#calendarModalAdvisorReasonWrap"
                    aria-expanded="false"
                    aria-controls="calendarModalAdvisorReasonWrap">
              View Reason
            </button>
          </div>
          <div class="collapse mt-2" id="calendarModalAdvisorReasonWrap">
            <div class="calendar-reason-box" id="calendarModalAdvisorReason"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- History Details Modal -->
<div class="modal fade" id="historyDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title">Appointment Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="calendar-reason-box" id="historyDetailsText"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="js/student-appointment-dashboard.js"></script>

</body>
</html>