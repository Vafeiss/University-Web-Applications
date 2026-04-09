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

$pdo = ConnectToDatabase();

/*
|--------------------------------------------------------------------------
| ADVISOR IDENTIFICATION
|--------------------------------------------------------------------------
| Use logged-in advisor session if available.
| Fallback to hardcoded advisor id only for temporary testing.
*/
$advisorId = 2;

if (isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
    $advisorId = (int) $_SESSION['UserID'];
}

$advisorName = 'Advisor';
if (isset($_SESSION['First_name']) && trim((string)$_SESSION['First_name']) !== '') {
    $advisorName = (string) $_SESSION['First_name'];
} elseif (isset($_SESSION['email']) && trim((string)$_SESSION['email']) !== '') {
    $advisorName = (string) $_SESSION['email'];
}

/*
|--------------------------------------------------------------------------
| ACTIVE SECTION
|--------------------------------------------------------------------------
*/
$activeSection = isset($_GET['section']) ? (string) $_GET['section'] : 'requests';

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
    $sql = "SELECT Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Student_Reason, Advisor_Reason, Status, Created_At
            FROM appointment_requests
            WHERE Advisor_ID = :advisor_id
              AND LOWER(TRIM(Status)) = 'pending'
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
    $sql = "SELECT Appointment_ID, Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Start_Time, End_Time, Status, Created_At
            FROM appointments
            WHERE Advisor_ID = :advisor_id
            ORDER BY Appointment_Date DESC, Start_Time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'advisor_id' => $advisorId
    ]);

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $appointmentsError = 'Could not load appointments.';
}
?>
<head>
    <meta charset="UTF-8">
    <title>Advisor Appointment Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

<div class="tab-bar">
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
                  <td><?= htmlspecialchars((string)$request['Student_ID']) ?></td>
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
              <th>Slot ID</th>
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
                <td colspan="6" class="text-center text-muted">No office hours loaded yet</td>
              </tr>
            <?php else: ?>
              <?php foreach ($officeHours as $slot): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$slot['OfficeHour_ID']) ?></td>
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
                  <td><?= htmlspecialchars((string)$appointment['Student_ID']) ?></td>
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

</main>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
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
});
</script>

</body>
</html>