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

session_start();

require_once __DIR__ . '/../backend/modules/databaseconnect.php';

$pdo = ConnectToDatabase();

/*
TEMP TEST MODE
Use hardcoded student id until login/session is fully connected.
*/
$studentId = 1;

// Get active section from URL
$activeSection = isset($_GET['section']) ? $_GET['section'] : 'book';

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

/*
------------------------------------------------------------
FETCH STUDENT ADVISOR
------------------------------------------------------------
*/
try {
    $advisorSql = "SELECT Advisor_ID
                   FROM student_advisors
                   WHERE Student_ID = :student_id
                   LIMIT 1";

    $advisorStmt = $pdo->prepare($advisorSql);
    $advisorStmt->execute([
        'student_id' => $studentId
    ]);

    $advisorRow = $advisorStmt->fetch(PDO::FETCH_ASSOC);

    if ($advisorRow && isset($advisorRow['Advisor_ID'])) {
        $advisorId = (int)$advisorRow['Advisor_ID'];
        $advisorName = 'Advisor ID: ' . $advisorId;
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
    $sql = "SELECT Appointment_ID, Request_ID, Student_ID, Advisor_ID, OfficeHour_ID, Appointment_Date, Start_Time, End_Time, Status, Created_At
            FROM appointments
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
                            OfficeHour_ID,
                            Appointment_Date,
                            NULL AS Start_Time,
                            NULL AS End_Time,
                            Status,
                            Created_At
                        FROM appointment_requests
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
    $sql = "SELECT Request_ID, Student_ID, Advisor_ID, Appointment_Date, Student_Reason, Advisor_Reason, Status, Created_At
            FROM appointment_requests
            WHERE Student_ID = :student_id
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Appointment Portal</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background-color: #f8f9fa;
      font-family: system-ui, -apple-system, sans-serif;
    }

    .top-navbar {
      background: #fff;
      border-bottom: 1px solid #e5e7eb;
      padding: 0 1.5rem;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }

    .welcome-text {
      font-weight: 750;
      font-size: 28px;
      color: #555;
    }

    .logo {
      height: 70px;
      width: auto;
      object-fit: contain;
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #ede9fe;
      color: #6d28d9;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .9rem;
    }

    .tab-bar {
      background: #fff;
      border-bottom: 1px solid #e5e7eb;
      padding: 0 1.5rem;
      display: flex;
      gap: .25rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .tab-btn {
      border: none;
      background: none;
      padding: 1rem .75rem;
      font-size: .95rem;
      color: #6b7280;
      cursor: pointer;
      border-bottom: 2px solid transparent;
      margin-bottom: -1px;
      display: flex;
      align-items: center;
      gap: .4rem;
      transition: color .15s;
    }

    .tab-btn:hover {
      color: #111827;
    }

    .tab-btn.active {
      color: #4f46e5;
      border-bottom-color: #4f46e5;
      font-weight: 500;
    }

    .section-card {
      background: #fff;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      padding: 1.5rem;
    }

    .section-panel {
      display: none;
    }

    .section-panel.active {
      display: block;
    }

    .flash-toast {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 9999;
      min-width: 280px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,.12);
      padding: .85rem 1.1rem;
      display: flex;
      align-items: center;
      gap: .6rem;
      font-size: .92rem;
    }

    .info-box {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 1rem;
    }

    .info-icon {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #ede9fe;
      color: #4f46e5;
      font-size: 1.1rem;
    }

    @media (max-width: 768px) {
      .welcome-text {
        font-size: 20px;
      }

      .top-navbar {
        padding: 0 1rem;
      }

      .tab-bar {
        justify-content: flex-start;
        overflow-x: auto;
      }
    }
  </style>
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
    <span class="welcome-text">Welcome To Advicut! 👋</span>
  </div>

  <div class="d-flex align-items-center gap-3">
    <i class="bi bi-question-circle text-secondary fs-5" title="Help"></i>
    <div class="user-avatar">S</div>

    <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0">
      <input type="hidden" name="action" value="/logout">
      <button type="submit" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Logout
      </button>
    </form>
  </div>
</header>

<div class="tab-bar">
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
        <div class="col-12 col-md-4">
          <div class="info-box d-flex align-items-center gap-3">
            <div class="info-icon">
              <i class="bi bi-person-badge"></i>
            </div>
            <div>
              <div class="fw-semibold">Assigned Advisor</div>
              <div class="text-muted small"><?= htmlspecialchars($advisorName) ?></div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="info-box d-flex align-items-center gap-3">
            <div class="info-icon">
              <i class="bi bi-calendar-week"></i>
            </div>
            <div>
              <div class="fw-semibold">Available Slots</div>
              <div class="text-muted small">Weekly advisor office hours</div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="info-box d-flex align-items-center gap-3">
            <div class="info-icon">
              <i class="bi bi-send-check"></i>
            </div>
            <div>
              <div class="fw-semibold">Request Status</div>
              <div class="text-muted small">Track pending and approved requests</div>
            </div>
          </div>
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
                  <td><?= htmlspecialchars('Advisor ID: ' . (string)$appointment['Advisor_ID']) ?></td>
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
                <tr>
                  <td><?= htmlspecialchars('Advisor ID: ' . (string)$history['Advisor_ID']) ?></td>
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
                  <td><?= htmlspecialchars((string)($history['Advisor_Reason'] ?? $history['Student_Reason'] ?? '-')) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</main>

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

  const studentRequestSearch = document.getElementById('studentRequestSearch');
  if (studentRequestSearch) {
    studentRequestSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.student-request-row').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  document.querySelectorAll('.open-book-modal-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const slotId = btn.getAttribute('data-slot-id');
      const slotSelect = document.getElementById('bookSlotSelect');

      if (slotSelect && slotId) {
        slotSelect.value = slotId;
      }
    });
  });
});
</script>

</body>
</html>