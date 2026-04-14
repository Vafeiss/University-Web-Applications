<?php
/*
  NAME: Super User Reports Page
  Description: Super user dashboard with statistics and students tabs
  Panteleimoni Alexandrou
  09-Apr-2026 v0.3
*/

declare(strict_types=1);

require_once 'init.php';
require_once '../backend/modules/UsersClass.php';
require_once '../backend/modules/SuperUserReportsClass.php';
require_once '../backend/modules/NotificationsClass.php';

$user = new Users();
$user->Check_Session('SuperUser');

$superUserDisplayName = 'Super User';
if (!empty($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
  try {
    $superUserPdo = ConnectToDatabase();
    $superUserStmt = $superUserPdo->prepare('SELECT First_name, Last_Name FROM users WHERE User_ID = :user_id AND Role = "SuperUser" LIMIT 1');
    $superUserStmt->execute(['user_id' => (int)$_SESSION['UserID']]);
    $superUserRow = $superUserStmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($superUserRow)) {
      $superUserDisplayName = trim((string)($superUserRow['First_name'] ?? '') . ' ' . (string)($superUserRow['Last_Name'] ?? ''));
      if ($superUserDisplayName === '') {
        $superUserDisplayName = (string)($_SESSION['email'] ?? 'Super User');
      }
    }
  } catch (Throwable $e) {
    $superUserDisplayName = (string)($_SESSION['email'] ?? 'Super User');
  }
}

$reports = new SuperUserReportsClass();

$activeSection = $_GET['section'] ?? 'statistics';

$statsDepartment = isset($_GET['stats_department_id']) ? (int)$_GET['stats_department_id'] : 0;
$statsDegree = isset($_GET['stats_degree_id']) ? (int)$_GET['stats_degree_id'] : 0;
$statsYear = isset($_GET['stats_year']) ? (int)$_GET['stats_year'] : 0;

$selectedDepartment = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$selectedDegree = isset($_GET['degree_id']) ? (int)$_GET['degree_id'] : 0;
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;

$departments = $reports->getDepartments();
$statsDegrees = $reports->getDegrees($statsDepartment > 0 ? $statsDepartment : null);
$statsDepartmentName = 'All Departments';
if ($statsDepartment > 0) {
  foreach ($departments as $department) {
    if ((int)$department['DepartmentID'] === $statsDepartment) {
      $statsDepartmentName = (string)$department['DepartmentName'];
      break;
    }
  }
}

$statsDegreeName = 'All Degrees';
if ($statsDegree > 0) {
  foreach ($statsDegrees as $degree) {
    if ((int)$degree['DegreeID'] === $statsDegree) {
      $statsDegreeName = (string)$degree['DegreeName'];
      break;
    }
  }
}

$degrees = $reports->getDegrees($selectedDepartment > 0 ? $selectedDepartment : null);

$summary = $reports->getSummary(
  $statsDepartment > 0 ? $statsDepartment : null,
  $statsDegree > 0 ? $statsDegree : null,
  $statsYear > 0 ? $statsYear : null
);

$students = $reports->getFilteredStudents(
    $selectedDepartment > 0 ? $selectedDepartment : null,
    $selectedDegree > 0 ? $selectedDegree : null,
    $selectedYear > 0 ? $selectedYear : null
);

$advisorCounts = $reports->getAdvisorStudentCounts(
  $statsDepartment > 0 ? $statsDepartment : null,
  $statsDegree > 0 ? $statsDegree : null,
  $statsYear > 0 ? $statsYear : null
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super User Reports</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/admin_dashboard.css">
  <link rel="stylesheet" href="css/superuser_reports.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body data-assigned-students="<?= (int)$summary['assigned_students'] ?>"
  data-unassigned-students="<?= (int)$summary['unassigned_students'] ?>">

<?php Notifications::createNotification(); ?>

<header class="top-navbar">
  <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

  <div class="navbar-center">
    <span class="welcome-text">Welcome to AdviCut, <?= htmlspecialchars($superUserDisplayName) ?>!👋</span>
  </div>

  <div class="d-flex align-items-center gap-3">
    <a href="admin_appointment_reports.php" class="btn btn-outline-success btn-sm">
      <i class="bi bi-clipboard-data me-1"></i> Appointment Reports
    </a>
    <a href="superuser_reports_pdf.php" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-file-earmark-pdf me-1"></i> Superuser Reports PDF
    </a>
    <div class="dropdown">
      <button class="btn p-0 border-0 bg-transparent dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="user-avatar">S</div>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 190px;">
        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#manualInstructionsModal">
          <i class="bi bi-journal-text me-2"></i>Manual
        </button>
        <div class="dropdown-divider"></div>
        <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0">
          <input type="hidden" name="action" value="/logout">
          <button class="dropdown-item text-danger" type="submit">
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
          <i class="bi bi-info-circle me-2 text-primary"></i>Super User Reports Manual
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <ol class="mb-0 ps-3">
          <li>Use Statistics to filter the overview by department, degree, and year.</li>
          <li>Use Students to review the filtered student list.</li>
          <li>Use the PDF report to export the current view.</li>
        </ol>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="tab-bar">
  <button type="button" class="tab-btn <?= $activeSection === 'statistics' ? 'active' : '' ?>" data-section="statistics">
    <i class="bi bi-bar-chart-line"></i> Statistics
  </button>
  <button type="button" class="tab-btn <?= $activeSection === 'students' ? 'active' : '' ?>" data-section="students">
    <i class="bi bi-people"></i> Students
  </button>
</div>

<main class="container-fluid py-4 px-4" style="max-width: 1100px;">

  <div class="section-panel <?= $activeSection === 'statistics' ? 'active' : '' ?>" id="section-statistics">

    <div class="section-card mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <h5 class="mb-0 fw-semibold">Super User Statistics</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Students, advisors and assignment overview.</p>
        </div>
      </div>

      <form method="GET" class="mt-3">
        <input type="hidden" name="section" value="statistics">

        <div class="d-flex flex-wrap gap-2 mb-3">
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#statsDepartmentFilter" aria-expanded="false" aria-controls="statsDepartmentFilter">
            <i class="bi bi-building me-1"></i> Department Filter
          </button>

          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#statsDegreeFilter" aria-expanded="false" aria-controls="statsDegreeFilter">
            <i class="bi bi-mortarboard me-1"></i> Degree Filter
          </button>

          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#statsYearFilter" aria-expanded="false" aria-controls="statsYearFilter">
            <i class="bi bi-calendar3 me-1"></i> Year Filter
          </button>

          <button class="btn btn-primary btn-sm" type="submit">
            <i class="bi bi-funnel-fill me-1"></i> Apply Filters
          </button>

          <a href="superuser_reports.php?section=statistics" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
          </a>
        </div>

        <div class="row g-3 align-items-end">
          <div class="col-md-4 collapse <?= $statsDepartment > 0 ? 'show' : '' ?>" id="statsDepartmentFilter">
            <label class="form-label">Department</label>
            <select name="stats_department_id" class="form-select">
              <option value="0">All Departments</option>
              <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars((string)$department['DepartmentID']) ?>"
                  <?= $statsDepartment === (int)$department['DepartmentID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($department['DepartmentName']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4 collapse <?= $statsDegree > 0 ? 'show' : '' ?>" id="statsDegreeFilter">
            <label class="form-label">Degree</label>
            <select name="stats_degree_id" class="form-select">
              <option value="0">All Degrees</option>
              <?php foreach ($statsDegrees as $degree): ?>
                <option value="<?= htmlspecialchars((string)$degree['DegreeID']) ?>"
                  <?= $statsDegree === (int)$degree['DegreeID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($degree['DegreeName']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4 collapse <?= $statsYear > 0 ? 'show' : '' ?>" id="statsYearFilter">
            <label class="form-label">Year</label>
            <select name="stats_year" class="form-select">
              <option value="0">All Years</option>
              <option value="1" <?= $statsYear === 1 ? 'selected' : '' ?>>Year 1</option>
              <option value="2" <?= $statsYear === 2 ? 'selected' : '' ?>>Year 2</option>
              <option value="3" <?= $statsYear === 3 ? 'selected' : '' ?>>Year 3</option>
              <option value="4" <?= $statsYear === 4 ? 'selected' : '' ?>>Year 4</option>
              <option value="5" <?= $statsYear === 5 ? 'selected' : '' ?>>Year 5</option>
              <option value="6" <?= $statsYear === 6 ? 'selected' : '' ?>>Year 6</option>
            </select>
          </div>
        </div>
      </form>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <p class="stat-label">Total Students</p>
          <p class="stat-value text-dark"><?= htmlspecialchars((string)$summary['total_students']) ?></p>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card">
          <p class="stat-label">Total Advisors</p>
          <p class="stat-value text-primary"><?= htmlspecialchars((string)$summary['total_advisors']) ?></p>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card">
          <p class="stat-label">Assigned Students</p>
          <p class="stat-value text-success"><?= htmlspecialchars((string)$summary['assigned_students']) ?></p>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card">
          <p class="stat-label">Unassigned Students</p>
          <p class="stat-value text-danger"><?= htmlspecialchars((string)$summary['unassigned_students']) ?></p>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-5">
        <div class="section-card h-100">
          <h5 class="fw-semibold mb-3">Assignment Pie Chart</h5>
          <p class="text-muted mb-3" style="font-size:.85rem;">
            Department: <?= htmlspecialchars($statsDepartmentName) ?>,
            Degree: <?= htmlspecialchars($statsDegreeName) ?>,
            Year: <?= htmlspecialchars($statsYear > 0 ? 'Year ' . (string)$statsYear : 'All Years') ?>
          </p>
          <div class="chart-wrap">
            <canvas id="assignmentChart"></canvas>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="section-card h-100">
          <h5 class="fw-semibold mb-3">Advisor Student Counts</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Advisor ID</th>
                  <th>Advisor Name</th>
                  <th>Total Students</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($advisorCounts)): ?>
                  <?php foreach ($advisorCounts as $advisor): ?>
                    <tr>
                      <td><?= htmlspecialchars((string)($advisor['Advisor_ID'] ?? '')) ?></td>
                      <td><?= htmlspecialchars(trim(($advisor['First_name'] ?? '') . ' ' . ($advisor['Last_Name'] ?? ''))) ?></td>
                      <td><?= htmlspecialchars((string)($advisor['Total_Students'] ?? 0)) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No advisor data found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="section-panel <?= $activeSection === 'students' ? 'active' : '' ?>" id="section-students">

    <div class="section-card mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
          <h5 class="mb-0 fw-semibold">Students</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Filtered students list with department, degree and year.</p>
        </div>
      </div>

      <form method="GET">
        <input type="hidden" name="section" value="students">

        <div class="d-flex flex-wrap gap-2 mb-3">
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentDepartmentFilterWrap" aria-expanded="false" aria-controls="studentDepartmentFilterWrap">
            <i class="bi bi-building me-1"></i> Department Filter
          </button>

          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentDegreeFilterWrap" aria-expanded="false" aria-controls="studentDegreeFilterWrap">
            <i class="bi bi-mortarboard me-1"></i> Degree Filter
          </button>

          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentYearFilterWrap" aria-expanded="false" aria-controls="studentYearFilterWrap">
            <i class="bi bi-calendar3 me-1"></i> Year Filter
          </button>

          <button class="btn btn-primary btn-sm" type="submit">
            <i class="bi bi-funnel-fill me-1"></i> Apply Filters
          </button>

          <a href="superuser_reports.php?section=students" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
          </a>
        </div>

        <div class="row g-3 align-items-end">
          <div class="col-md-4 collapse <?= $selectedDepartment > 0 ? 'show' : '' ?>" id="studentDepartmentFilterWrap">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
              <option value="0">All Departments</option>
              <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars((string)$department['DepartmentID']) ?>"
                  <?= $selectedDepartment === (int)$department['DepartmentID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($department['DepartmentName']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4 collapse <?= $selectedDegree > 0 ? 'show' : '' ?>" id="studentDegreeFilterWrap">
            <label class="form-label">Degree</label>
            <select name="degree_id" class="form-select">
              <option value="0">All Degrees</option>
              <?php foreach ($degrees as $degree): ?>
                <option value="<?= htmlspecialchars((string)$degree['DegreeID']) ?>"
                  <?= $selectedDegree === (int)$degree['DegreeID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($degree['DegreeName']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4 collapse <?= $selectedYear > 0 ? 'show' : '' ?>" id="studentYearFilterWrap">
            <label class="form-label">Year</label>
            <select name="year" class="form-select">
              <option value="0">All Years</option>
              <option value="1" <?= $selectedYear === 1 ? 'selected' : '' ?>>Year 1</option>
              <option value="2" <?= $selectedYear === 2 ? 'selected' : '' ?>>Year 2</option>
              <option value="3" <?= $selectedYear === 3 ? 'selected' : '' ?>>Year 3</option>
              <option value="4" <?= $selectedYear === 4 ? 'selected' : '' ?>>Year 4</option>
              <option value="5" <?= $selectedYear === 5 ? 'selected' : '' ?>>Year 5</option>
              <option value="6" <?= $selectedYear === 6 ? 'selected' : '' ?>>Year 6</option>
            </select>
          </div>
        </div>
      </form>
    </div>

    <div class="section-card">
      <h5 class="fw-semibold mb-3">Filtered Students</h5>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Student ID</th>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Email</th>
              <th>Department</th>
              <th>Degree</th>
              <th>Year</th>
              <th>Advisor ID</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($students)): ?>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td><?= htmlspecialchars((string)($student['Student_ID'] ?? '')) ?></td>
                  <td><?= htmlspecialchars($student['First_name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($student['Last_Name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($student['Uni_Email'] ?? '') ?></td>
                  <td><?= htmlspecialchars($student['DepartmentName'] ?? '') ?></td>
                  <td><?= htmlspecialchars($student['DegreeName'] ?? '') ?></td>
                  <td><?= htmlspecialchars((string)($student['Year'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($student['Advisor_ID'] ?? 'Unassigned')) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">No students found for the selected filters.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</main>

<?php require_once __DIR__ . '/footer/dashboard_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/superuser-reports.js"></script>

</body>
</html>
