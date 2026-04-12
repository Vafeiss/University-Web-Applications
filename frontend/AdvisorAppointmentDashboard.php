<?php
/*
   NAME: Advisor Appointment Dashboard
   Description: This page displays the advisor dashboard for managing appointment requests, office hours, appointments and history
   Panteleimoni Alexandrou
   30-Mar-2026 v2.1
   Inputs: Section parameter from URL, session flash messages and database records for office hours, requests and appointments
   Outputs: Advisor dashboard interface with real database data
   Error Messages: If database fetch fails, an error message is displayed inside the relevant section
  Files in use: AdvisorAppointmentDashboard.php, AdvisorOfficeHours.php, AppointmentController.php, databaseconnect.php
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/modules/databaseconnect.php';
require_once __DIR__ . '/../backend/modules/AdvisorClass.php';
require_once __DIR__ . '/../backend/modules/UsersClass.php';

$user = new Users();
$user->Check_Session('Advisor');

$pdo = ConnectToDatabase();

/*
|--------------------------------------------------------------------------
| ADVISOR IDENTIFICATION
|--------------------------------------------------------------------------
| Use logged-in advisor session if available.
| 
*/

if (isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
    $advisorId = (int) $_SESSION['UserID'];
}

if (!isset($advisorId)) {
  $advisorId = 0;
}

$advisorName = 'Advisor';
if ($advisorId > 0) {
  try {
    $advisorNameStmt = $pdo->prepare('SELECT First_name FROM users WHERE User_ID = :advisor_id AND Role = "Advisor" LIMIT 1');
    $advisorNameStmt->execute(['advisor_id' => $advisorId]);
    $advisorFirstName = trim((string)($advisorNameStmt->fetchColumn() ?: ''));
    if ($advisorFirstName !== '') {
      $advisorName = $advisorFirstName;
    }
  } catch (Throwable $e) {
    if (isset($_SESSION['First_name']) && trim((string)$_SESSION['First_name']) !== '') {
      $advisorName = (string) $_SESSION['First_name'];
    } elseif (isset($_SESSION['email']) && trim((string)$_SESSION['email']) !== '') {
      $advisorName = (string) $_SESSION['email'];
    }
  }
} elseif (isset($_SESSION['First_name']) && trim((string)$_SESSION['First_name']) !== '') {
  $advisorName = (string) $_SESSION['First_name'];
} elseif (isset($_SESSION['email']) && trim((string)$_SESSION['email']) !== '') {
  $advisorName = (string) $_SESSION['email'];
}

/*
|--------------------------------------------------------------------------
| ACTIVE SECTION
|--------------------------------------------------------------------------
*/
$activeSection = isset($_GET['section']) ? (string) $_GET['section'] : 'calendar';

/*
|--------------------------------------------------------------------------
| FLASH MESSAGES
|--------------------------------------------------------------------------
*/
$flash = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : null;
$flashType = isset($_SESSION['flash_type']) ? (string) $_SESSION['flash_type'] : 'success';

unset($_SESSION['flash'], $_SESSION['flash_type']);

/*
|--------------------------------------------------------------------------
| DATA CONTAINERS
|--------------------------------------------------------------------------
*/
$officeHours = [];
$officeHoursError = '';

$requests = [];
$requestsError = '';

$appointments = [];
$appointmentsError = '';

$historyRows = [];
$historyError = '';

$advisorCalendarEvents = [];
$advisorCalendarError = '';

$assignedStudents = [];
$communicationsError = '';

/*
|--------------------------------------------------------------------------
| BASIC DB CHECK
|--------------------------------------------------------------------------
*/
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Database connection is not available.');
}

/*
|--------------------------------------------------------------------------
| FETCH OFFICE HOURS
|--------------------------------------------------------------------------
*/
try {
    $sql = "SELECT OfficeHour_ID, Day_of_Week, Start_Time, End_Time
            FROM office_hours
            WHERE Advisor_ID = :advisor_id
            ORDER BY
                FIELD(Day_of_Week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                Start_Time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'advisor_id' => $advisorId
    ]);

    $officeHours = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $officeHoursError = 'Could not load office hours.';
}

/*
|--------------------------------------------------------------------------
| FETCH ONLY PENDING APPOINTMENT REQUESTS
|--------------------------------------------------------------------------
*/
try {
    $sql = "SELECT ar.Request_ID, ar.Student_ID, COALESCE(s.External_ID, ar.Student_ID) AS Student_External_ID, ar.Advisor_ID, ar.OfficeHour_ID, ar.Appointment_Date, ar.Student_Reason, ar.Advisor_Reason, ar.Status, ar.Created_At
            FROM appointment_requests ar
            INNER JOIN users s ON s.User_ID = ar.Student_ID
            WHERE ar.Advisor_ID = :advisor_id
              AND LOWER(TRIM(ar.Status)) = 'pending'
            ORDER BY Created_At DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'advisor_id' => $advisorId
    ]);

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $requestsError = 'Could not load appointment requests.';
}

/*
|--------------------------------------------------------------------------
| FETCH APPOINTMENTS
|--------------------------------------------------------------------------
*/
try {
        $sql = "SELECT a.Appointment_ID, a.Request_ID, a.Student_ID, s.External_ID AS Student_External_ID, a.Advisor_ID, a.OfficeHour_ID, a.Appointment_Date, a.Start_Time, a.End_Time, a.Status, a.Created_At
          FROM appointments a
          INNER JOIN users s ON s.User_ID = a.Student_ID
          WHERE a.Advisor_ID = :advisor_id
          ORDER BY a.Appointment_Date DESC, a.Start_Time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'advisor_id' => $advisorId
    ]);

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $appointmentsError = 'Could not load appointments.';
}

try {
  $advisorModule = new AdvisorClass();
  $assignedStudents = $advisorModule->getAssignedStudents($advisorId);
} catch (Throwable $e) {
  $communicationsError = 'Could not load assigned students for communications.';
}

/*
|--------------------------------------------------------------------------
| FETCH ADVISOR CALENDAR EVENTS
|--------------------------------------------------------------------------
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
            u.First_name AS Student_First_Name,
            u.Last_Name AS Student_Last_Name
          FROM appointment_requests ar
          LEFT JOIN office_hours oh ON ar.OfficeHour_ID = oh.OfficeHour_ID
          LEFT JOIN users u ON ar.Student_ID = u.User_ID
          WHERE ar.Advisor_ID = :advisor_id
          ORDER BY ar.Appointment_Date ASC, oh.Start_Time ASC";

  $calendarStmt = $pdo->prepare($calendarSql);
  $calendarStmt->execute([
    'advisor_id' => $advisorId
  ]);

  $calendarRows = $calendarStmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($calendarRows as $row) {
    $status = (string)($row['Status'] ?? 'Pending');
    $studentFullName = trim(
      (string)($row['Student_First_Name'] ?? '') . ' ' .
      (string)($row['Student_Last_Name'] ?? '')
    );

    $title = $studentFullName !== '' ? $studentFullName : 'Appointment';

    $eventColor = '#6c757d';
    if ($status === 'Pending') $eventColor = '#f0ad4e';
    if ($status === 'Approved') $eventColor = '#198754';
    if ($status === 'Declined') $eventColor = '#dc3545';
    if ($status === 'Cancelled') $eventColor = '#212529';

    $advisorCalendarEvents[] = [
      'id' => (int)($row['Request_ID'] ?? 0),
      'title' => $title . ' (' . $status . ')',
      'start' => (string)($row['Appointment_Date'] ?? ''),
      'backgroundColor' => $eventColor,
      'borderColor' => $eventColor,
      'extendedProps' => [
        'student' => $studentFullName,
        'date' => (string)($row['Appointment_Date'] ?? ''),
        'time' => (string)($row['Start_Time'] ?? '') . ' - ' . (string)($row['End_Time'] ?? ''),
        'student_reason' => (string)($row['Student_Reason'] ?? ''),
        'advisor_reason' => (string)($row['Advisor_Reason'] ?? ''),
        'status' => $status
      ]
    ];
  }
} catch (Throwable $e) {
  $advisorCalendarError = 'Could not load calendar events.';
}
?>
<head>
    <meta charset="UTF-8">
    <title>Advisor Appointment Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/advisor_appointment_dashboard.css">
</head>
<body>

<?php if ($flash): ?>
<div class="flash-toast alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> mb-0" id="flashToast">
  <span class="flash-content">
    <i class="bi bi-<?= $flashType === 'error' ? 'x-circle' : 'check-circle' ?>-fill"></i>
    <?= htmlspecialchars($flash) ?>
  </span>
</div>
<script>
  setTimeout(function () {
    const toast = document.getElementById('flashToast');
    if (toast) {
      toast.remove();
    }
  }, 3500);
</script>
<?php endif; ?>

<header class="top-navbar">
  <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

  <div class="navbar-center">
    <span class="welcome-text">Welcome to AdviCut, <?= htmlspecialchars($advisorName) ?>! 👋</span>
  </div>

  <div class="d-flex align-items-center gap-3">
    <div class="dropdown">
      <button class="btn p-0 border-0 bg-transparent dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="user-avatar"><?= htmlspecialchars(strtoupper(substr($advisorName, 0, 1))) ?></div>
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
          <i class="bi bi-info-circle me-2 text-primary"></i>Advisor Dashboard Manual
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <ol class="mb-0 ps-3">
          <li>Review appointment requests in Requests.</li>
          <li>Manage your weekly slots in Office Hours.</li>
          <li>Check appointment details in Appointments and History.</li>
          <li>Use Communications to message and see assigned students.</li>
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

  <button type="button" class="tab-btn <?= $activeSection === 'requests' ? 'active' : '' ?>" data-section="requests">
    <i class="bi bi-envelope-paper"></i> Requests
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'officehours' ? 'active' : '' ?>" data-section="officehours">
    <i class="bi bi-clock"></i> Office Hours
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'appointments' ? 'active' : '' ?>" data-section="appointments">
    <i class="bi bi-calendar-check"></i> Appointments
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'history' ? 'active' : '' ?>" data-section="history">
    <i class="bi bi-clock-history"></i> History
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'mystudents' ? 'active' : '' ?>" data-section="mystudents">
    <i class="bi bi-people"></i> My Students
  </button>

  <button type="button" class="tab-btn <?= $activeSection === 'communications' ? 'active' : '' ?>" data-section="communications">
    <i class="bi bi-chat-dots"></i> Communications
  </button>
</div>

<main class="container-fluid py-4 px-4" style="max-width: 1100px;">

  <div class="section-panel <?= $activeSection === 'requests' ? 'active' : '' ?>" id="section-requests">
    <div class="section-card">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Appointment Requests</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Review pending student appointment requests</p>
        </div>
      </div>

      <?php if ($requestsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($requestsError) ?>
        </div>
      <?php endif; ?>

      <input class="form-control mb-3" id="requestSearch" placeholder="Search requests…">

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Student ID</th>
              <th>Date</th>
              <th>Student Reason</th>
              <th>Status</th>
              <th>Advisor Reason</th>
              <th style="width:170px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($requests) === 0): ?>
              <tr class="request-row">
                <td colspan="6" class="text-center text-muted">No pending requests found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($requests as $request): ?>
                <tr class="request-row">
                  <td><?= htmlspecialchars((string)($request['Student_External_ID'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string)$request['Appointment_Date']) ?></td>
                  <td><?= htmlspecialchars((string)$request['Student_Reason']) ?></td>
                  <td><span class="badge bg-secondary">Pending</span></td>
                  <td><?= htmlspecialchars((string)($request['Advisor_Reason'] ?? '-')) ?></td>
                  <td>
                    <a href="../backend/controllers/AppointmentController.php?action=approve&id=<?= (int)$request['Request_ID'] ?>"
                       class="btn btn-success btn-sm">
                      Approve
                    </a>

                    <a href="../backend/controllers/AppointmentController.php?action=decline&id=<?= (int)$request['Request_ID'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Decline this appointment request?');">
                      Decline
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="section-panel <?= $activeSection === 'officehours' ? 'active' : '' ?>" id="section-officehours">
    <div class="section-card">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Office Hours</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Manage your fixed weekly appointment hours</p>
        </div>

        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOfficeHourModal">
          <i class="bi bi-plus-circle me-1"></i> Add Slot
        </button>
      </div>

      <?php if ($officeHoursError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($officeHoursError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Day</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Status</th>
              <th style="width:120px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($officeHours) === 0): ?>
              <tr>
                <td colspan="5" class="text-center text-muted">No office hours loaded yet</td>
              </tr>
            <?php else: ?>
              <?php foreach ($officeHours as $slot): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$slot['Day_of_Week']) ?></td>
                  <td><?= htmlspecialchars(substr((string)$slot['Start_Time'], 0, 5)) ?></td>
                  <td><?= htmlspecialchars(substr((string)$slot['End_Time'], 0, 5)) ?></td>
                  <td><span class="badge bg-success">Active</span></td>
                  <td>
                    <a href="../backend/controllers/AdvisorOfficeHours.php?delete=<?= (int)$slot['OfficeHour_ID'] ?>"
                       class="btn btn-outline-danger btn-sm"
                       onclick="return confirm('Delete this office hour slot?');">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="section-panel <?= $activeSection === 'appointments' ? 'active' : '' ?>" id="section-appointments">
    <div class="section-card">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Approved Appointments</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">View approved and scheduled appointments</p>
        </div>
      </div>

      <?php if ($appointmentsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($appointmentsError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Appointment ID</th>
              <th>Student ID</th>
              <th>Date</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($appointments) === 0): ?>
              <tr>
                <td colspan="6" class="text-center text-muted">No appointments found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($appointments as $appointment): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$appointment['Appointment_ID']) ?></td>
                  <td><?= htmlspecialchars((string)($appointment['Student_External_ID'] ?? $appointment['Student_ID'])) ?></td>
                  <td><?= htmlspecialchars((string)$appointment['Appointment_Date']) ?></td>
                  <td><?= htmlspecialchars(substr((string)$appointment['Start_Time'], 0, 5)) ?></td>
                  <td><?= htmlspecialchars(substr((string)$appointment['End_Time'], 0, 5)) ?></td>
                  <td>
                    <?php if ($appointment['Status'] === 'Scheduled'): ?>
                      <span class="badge bg-primary">Scheduled</span>
                    <?php elseif ($appointment['Status'] === 'Completed'): ?>
                      <span class="badge bg-success">Completed</span>
                    <?php elseif ($appointment['Status'] === 'Cancelled'): ?>
                      <span class="badge bg-danger">Cancelled</span>
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

  <div class="section-panel <?= $activeSection === 'history' ? 'active' : '' ?>" id="section-history">
    <div class="section-card">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Appointment History</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">View all previous appointment actions</p>
        </div>
      </div>

      <?php if ($historyError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($historyError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Request ID</th>
              <th>Student ID</th>
              <th>Status</th>
              <th>Advisor Reason</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($historyRows) === 0): ?>
              <tr>
                <td colspan="5" class="text-center text-muted">No history found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($historyRows as $history): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$history['Request_ID']) ?></td>
                  <td><?= htmlspecialchars((string)$history['Student_ID']) ?></td>
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
                  <td><?= htmlspecialchars((string)($history['Advisor_Reason'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string)$history['Created_At']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="section-panel <?= $activeSection === 'mystudents' ? 'active' : '' ?>" id="section-mystudents">
    <div class="section-card">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">My Students</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">View students currently assigned to you</p>
        </div>
      </div>

      <?php if ($communicationsError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($communicationsError) ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Student ID</th>
              <th>Name</th>
              <th>Last Name</th>
              <th>Year</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($assignedStudents) === 0): ?>
              <tr>
                <td colspan="4" class="text-center text-muted">No assigned students found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($assignedStudents as $student): ?>
                <tr>
                  <td><?= htmlspecialchars((string)($student['StuExternal_ID'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string)($student['First_name'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string)($student['Last_Name'] ?? '-')) ?></td>
                  <td><?= 'Year ' . htmlspecialchars((string)($student['StuYear'] ?? '-')) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="section-panel <?= $activeSection === 'communications' ? 'active' : '' ?>" id="section-communications">
    <div class="section-card">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Communications</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Review and reply to student messages</p>
        </div>
      </div>

      <?php if ($communicationsError !== ''): ?>
        <div class="alert alert-danger mb-3">
          <?= htmlspecialchars($communicationsError) ?>
        </div>
      <?php endif; ?>

      <div class="comm-layout">
        <aside class="comm-sidebar">
          <div class="comm-sidebar-header">
            <h6>Assigned Students</h6>
          </div>

          <div class="comm-student-list" id="commStudentList">
            <?php if (count($assignedStudents) === 0): ?>
              <div class="comm-placeholder">
                <i class="bi bi-people"></i>
                <p>No assigned students found.</p>
              </div>
            <?php else: ?>
              <?php foreach ($assignedStudents as $student):
                $studentUserId = (int)($student['User_ID'] ?? 0);
                $studentName = trim((string)($student['First_name'] ?? '') . ' ' . (string)($student['Last_Name'] ?? ''));
                $studentExternalId = (string)($student['StuExternal_ID'] ?? '');
                $unreadCount = (int)($student['unread_count'] ?? 0);
              ?>
                <div class="comm-student-item"
                     data-student-id="<?= $studentUserId ?>"
                     data-student-name="<?= htmlspecialchars($studentName !== '' ? $studentName : 'Student') ?>"
                     data-student-ext-id="<?= htmlspecialchars($studentExternalId) ?>">
                  <div>
                    <div class="comm-stu-name"><?= htmlspecialchars($studentName !== '' ? $studentName : 'Student') ?></div>
                    <div class="comm-stu-id">ID: <?= htmlspecialchars($studentExternalId !== '' ? $studentExternalId : '-') ?></div>
                  </div>
                  <?php if ($unreadCount > 0): ?>
                    <span class="comm-unread"><?= $unreadCount ?></span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </aside>

        <section class="comm-pane">
          <div class="comm-pane-header">
            <h6 id="commPaneStudentName">Select a student</h6>
            <small id="commPaneStudentMeta">Choose a student from the left to load messages.</small>
          </div>

          <div class="comm-messages" id="commMessages">
            <div class="comm-placeholder">
              <i class="bi bi-chat-dots"></i>
              <p>Select a student to view the conversation.</p>
            </div>
          </div>

          <div class="comm-compose">
            <label for="commTextarea">Reply message <span class="text-muted">(200 words max)</span></label>
            <textarea id="commTextarea"
                      placeholder="Choose a student first..."
                      maxlength="2000"
                      disabled
                      oninput="commWordCount(this)"></textarea>
            <div class="comm-compose-footer">
              <span class="comm-word-count" id="commWordCount">0 / 200 words</span>
              <button type="button" class="btn-send" id="commSendBtn" onclick="commSend()" disabled>
                <i class="bi bi-send-fill"></i> Send Reply
              </button>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <div class="section-panel <?= $activeSection === 'calendar' ? 'active' : '' ?>" id="section-calendar">
    <div class="section-card">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Appointment Calendar</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">See student appointments and request statuses by date</p>
        </div>
      </div>

      <?php if ($advisorCalendarError !== ''): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($advisorCalendarError) ?>
        </div>
      <?php endif; ?>

      <div id="advisorCalendar"></div>
    </div>
  </div>

</main>

<?php require_once __DIR__ . '/footer/dashboard_footer.php'; ?>

<div class="modal fade" id="addOfficeHourModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Add Office Hour Slot</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form action="../backend/controllers/AdvisorOfficeHours.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Day of Week <span class="text-danger">*</span></label>
              <select name="day_of_week" class="form-select" required>
                <option value="" disabled selected>Select day...</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
              </select>
            </div>

            <div class="col-6">
              <label class="form-label">Start Time <span class="text-danger">*</span></label>
              <input type="time" name="start_time" class="form-control" required>
            </div>

            <div class="col-6">
              <label class="form-label">End Time <span class="text-danger">*</span></label>
              <input type="time" name="end_time" class="form-control" required>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Slot
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="advisorCalendarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title">Appointment Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Student:</strong> <span id="advisorCalendarModalStudent"></span></p>
        <p><strong>Date:</strong> <span id="advisorCalendarModalDate"></span></p>
        <p><strong>Time:</strong> <span id="advisorCalendarModalTime"></span></p>
        <p><strong>Status:</strong> <span id="advisorCalendarModalStatus"></span></p>

        <div class="calendar-reason-group">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <strong>Student Reason:</strong>
            <button type="button"
                    class="btn btn-outline-primary btn-sm calendar-reason-btn"
                    id="advisorCalendarModalStudentReasonBtn"
                    data-bs-toggle="collapse"
                    data-bs-target="#advisorCalendarModalStudentReasonWrap"
                    aria-expanded="false"
                    aria-controls="advisorCalendarModalStudentReasonWrap">
              View Reason
            </button>
          </div>
          <div class="collapse mt-2" id="advisorCalendarModalStudentReasonWrap">
            <div class="calendar-reason-box" id="advisorCalendarModalStudentReason"></div>
          </div>
        </div>

        <div class="calendar-reason-group mt-3">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <strong>Advisor Note:</strong>
            <button type="button"
                    class="btn btn-outline-primary btn-sm calendar-reason-btn"
                    id="advisorCalendarModalAdvisorReasonBtn"
                    data-bs-toggle="collapse"
                    data-bs-target="#advisorCalendarModalAdvisorReasonWrap"
                    aria-expanded="false"
                    aria-controls="advisorCalendarModalAdvisorReasonWrap">
              View Reason
            </button>
          </div>
          <div class="collapse mt-2" id="advisorCalendarModalAdvisorReasonWrap">
            <div class="calendar-reason-box" id="advisorCalendarModalAdvisorReason"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
const COMM_MAX_WORDS = 200;
let commActiveStudentId = 0;
let commLoadedForStudent = 0;
let advisorCalendarLoaded = false;
let advisorCalendarInstance = null;

const advisorCalendarEvents = <?= json_encode($advisorCalendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function setCalendarReason(buttonId, wrapId, contentId, value) {
  const button = document.getElementById(buttonId);
  const wrap = document.getElementById(wrapId);
  const content = document.getElementById(contentId);

  if (!button || !wrap || !content) return;

  const text = String(value ?? '').trim();
  const hasValue = text !== '' && text !== '-';

  content.textContent = hasValue ? text : '';
  button.style.display = hasValue ? 'inline-flex' : 'none';

  if (!hasValue) {
    const collapse = bootstrap.Collapse.getOrCreateInstance(wrap, { toggle: false });
    collapse.hide();
  }
}

function resetCalendarReasonState(wrapId) {
  const wrap = document.getElementById(wrapId);
  if (!wrap) return;

  const collapse = bootstrap.Collapse.getOrCreateInstance(wrap, { toggle: false });
  collapse.hide();
}

function renderAdvisorCalendar() {
  if (advisorCalendarLoaded) return;

  const calendarEl = document.getElementById('advisorCalendar');
  const modalEl = document.getElementById('advisorCalendarModal');
  if (!calendarEl || !modalEl) return;

  const detailsModal = new bootstrap.Modal(modalEl);

  advisorCalendarInstance = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 'auto',
    events: advisorCalendarEvents,
    eventClick: function (info) {
      const props = info.event.extendedProps || {};
      document.getElementById('advisorCalendarModalStudent').textContent = props.student || '-';
      document.getElementById('advisorCalendarModalDate').textContent = props.date || '-';
      document.getElementById('advisorCalendarModalTime').textContent = props.time || '-';
      document.getElementById('advisorCalendarModalStatus').textContent = props.status || '-';
      setCalendarReason('advisorCalendarModalStudentReasonBtn', 'advisorCalendarModalStudentReasonWrap', 'advisorCalendarModalStudentReason', props.student_reason);
      setCalendarReason('advisorCalendarModalAdvisorReasonBtn', 'advisorCalendarModalAdvisorReasonWrap', 'advisorCalendarModalAdvisorReason', props.advisor_reason);
      resetCalendarReasonState('advisorCalendarModalStudentReasonWrap');
      resetCalendarReasonState('advisorCalendarModalAdvisorReasonWrap');
      detailsModal.show();
    }
  });

  advisorCalendarInstance.render();
  advisorCalendarLoaded = true;
}

function commEsc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function commSetActiveStudent(item) {
  document.querySelectorAll('.comm-student-item').forEach(function (el) {
    el.classList.remove('active');
  });
  item.classList.add('active');

  const name = item.getAttribute('data-student-name') || 'Student';
  const extId = item.getAttribute('data-student-ext-id') || '-';
  document.getElementById('commPaneStudentName').textContent = name;
  document.getElementById('commPaneStudentMeta').textContent = 'Student ID: ' + extId;

  const textarea = document.getElementById('commTextarea');
  textarea.disabled = false;
  textarea.placeholder = 'Type your message here...';
}

function commLoadThread(studentId) {
  const box = document.getElementById('commMessages');
  if (!box || studentId <= 0) return;

  box.innerHTML = '<div class="comm-loading">Loading messages...</div>';

  const fd = new FormData();
  fd.append('action', '/message/thread');
  fd.append('student_id', String(studentId));

  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (messages) {
      if (!Array.isArray(messages) || messages.length === 0) {
        box.innerHTML = '<div class="comm-placeholder"><i class="bi bi-chat"></i><p>No messages yet. Send the first reply.</p></div>';
      } else {
        box.innerHTML = messages.map(function (m) {
          const side = m.sender === 'advisor' ? 'from-advisor' : 'from-student';
          const senderLabel = m.sender === 'advisor' ? 'You' : (m.sender_name || 'Student');
          const time = m.sent_at ? new Date(m.sent_at).toLocaleString() : '';
          return [
            '<div class="msg-bubble-wrap ' + side + '">',
            '<div class="msg-meta">',
            '<span class="msg-sender">' + commEsc(senderLabel) + '</span>',
            '<span>' + commEsc(time) + '</span>',
            '</div>',
            '<div class="msg-bubble">' + commEsc(m.body) + '</div>',
            '</div>'
          ].join('');
        }).join('');
      }

      box.scrollTop = box.scrollHeight;

      const readFd = new FormData();
      readFd.append('action', '/message/read');
      readFd.append('student_id', String(studentId));
      fetch('../backend/modules/dispatcher.php', { method: 'POST', body: readFd }).catch(function () {});

      const activeItem = document.querySelector('.comm-student-item.active .comm-unread');
      if (activeItem) {
        activeItem.remove();
      }
    })
    .catch(function () {
      box.innerHTML = '<div class="comm-placeholder" style="color:#ef4444"><i class="bi bi-exclamation-circle"></i><p>Failed to load messages. Please try again.</p></div>';
    });
}

function commWordCount(textarea) {
  const words = textarea.value.trim() === '' ? 0 : textarea.value.trim().split(/\s+/).length;
  const counter = document.getElementById('commWordCount');
  const sendBtn = document.getElementById('commSendBtn');

  counter.textContent = words + ' / ' + COMM_MAX_WORDS + ' words';
  counter.classList.toggle('over', words > COMM_MAX_WORDS);
  sendBtn.disabled = (commActiveStudentId <= 0 || words === 0 || words > COMM_MAX_WORDS);
}

function commSend() {
  const textarea = document.getElementById('commTextarea');
  const sendBtn = document.getElementById('commSendBtn');
  const messageBody = textarea.value.trim();

  if (commActiveStudentId <= 0 || messageBody === '') return;

  sendBtn.disabled = true;
  sendBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';

  const fd = new FormData();
  fd.append('action', '/message/send');
  fd.append('student_id', String(commActiveStudentId));
  fd.append('message_body', messageBody);

  fetch('../backend/modules/dispatcher.php', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data && data.success) {
        textarea.value = '';
        commWordCount(textarea);
        commLoadThread(commActiveStudentId);
      } else {
        alert((data && data.error) ? data.error : 'Failed to send message.');
      }
    })
    .catch(function () {
      alert('Network error while sending message.');
    })
    .finally(function () {
      sendBtn.innerHTML = '<i class="bi bi-send-fill"></i> Send Reply';
      commWordCount(textarea);
    });
}

document.addEventListener("DOMContentLoaded", function () {
  const params = new URLSearchParams(window.location.search);
  const section = params.get("section");

  if (section) {
    const btn = document.querySelector('.tab-btn[data-section="' + section + '"]');
    const panel = document.getElementById('section-' + section);

    if (btn && panel) {
      document.querySelectorAll('.tab-btn').forEach(function (b) {
        b.classList.remove('active');
      });

      document.querySelectorAll('.section-panel').forEach(function (p) {
        p.classList.remove('active');
      });

      btn.classList.add('active');
      panel.classList.add('active');
    }
  }

  document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const sectionName = btn.getAttribute('data-section');

      document.querySelectorAll('.tab-btn').forEach(function (b) {
        b.classList.remove('active');
      });

      document.querySelectorAll('.section-panel').forEach(function (p) {
        p.classList.remove('active');
      });

      btn.classList.add('active');

      const targetPanel = document.getElementById('section-' + sectionName);
      if (targetPanel) {
        targetPanel.classList.add('active');
      }

      const url = new URL(window.location);
      url.searchParams.set('section', sectionName);
      window.history.replaceState({}, '', url);

      if (sectionName === 'communications') {
        const firstStudent = document.querySelector('.comm-student-item');
        if (firstStudent && commLoadedForStudent === 0) {
          firstStudent.click();
        }
      }

      if (sectionName === 'calendar') {
        renderAdvisorCalendar();
      }
    });
  });

  const requestSearch = document.getElementById('requestSearch');
  if (requestSearch) {
    requestSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.request-row').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  document.querySelectorAll('.comm-student-item').forEach(function (item) {
    item.addEventListener('click', function () {
      const studentId = parseInt(item.getAttribute('data-student-id') || '0', 10);
      if (!studentId) return;

      commActiveStudentId = studentId;
      commLoadedForStudent = studentId;

      commSetActiveStudent(item);
      commLoadThread(studentId);
      commWordCount(document.getElementById('commTextarea'));
    });
  });

  if (document.getElementById('section-communications')?.classList.contains('active')) {
    const firstStudent = document.querySelector('.comm-student-item');
    if (firstStudent) {
      firstStudent.click();
    }
  }

  if (document.getElementById('section-calendar')?.classList.contains('active')) {
    renderAdvisorCalendar();
  }
});
</script>

</body>
</html>