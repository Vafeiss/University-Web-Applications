<?php
/* Name: Admin_Dashboard
Description: This is the dashboard of the admin.
Paraskevas Vafeiadis
01-Mar-26 v0.1
Inputs: CSV file , Information of advisors
Outputs: Successful messages
Error Messages: If the fields are empty , if not a csv file
Files in Use: AdminClass.php , ParticipantsClass.php , routes.php , dispatcher.php

13-Mar-2026 v0.2
Made new admin_dashboard using the figma prototype example and bootstrap 5 for styling and added statistics feature
Paraskevas Vafeiadis

15-Mar-2026 v0.3
added random assignment feature that works with a roundrobin function
Paraskevas Vafeiadis

16-Mar-2026 v0.4
added show messages for each action inside the dashboard using the notifications class.
Paraskevas Vafeiadis

20-Mar-2026 v0.5
added filtering for students by year and degree, and added a degrees management section with add/edit features
improved statistics by inserting a pie-chart and filtering the years
Paraskevas Vafeiadis

24-Mar-2026 v0.6
Added department filtering
Paraskevas Vafeiadis

25-Mar-2026 v0.7
Department/Degree add/edit/delete fully functional
Paraskevas Vafeiadis

30-Mar-2026 v0.8
Fixed some UI bugs and added columns to the assigned students list as well as fliters for manual assignment
Paraskevas Vafeiadis


*/

require_once 'init.php';
require_once '../backend/modules/AdminClass.php';
require_once '../backend/modules/ParticipantsClass.php';
require_once '../backend/modules/NotificationsClass.php';
require_once '../backend/modules/SelectionClass.php';
require_once '../backend/modules/PromotionClass.php';

function resultFetchAssoc($result): ?array
{
  if ($result instanceof PDOStatement) {
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
  }

  return null;
}

function resultFetchAllAssoc($result): array
{
  if ($result instanceof PDOStatement) {
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }

  return [];
}

$user = new Admin();
$user->Check_Session('Admin');

$adminDisplayName = 'Admin';
if (!empty($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
  try {
    $adminNamePdo = ConnectToDatabase();
    $adminNameStmt = $adminNamePdo->prepare('SELECT First_name, Last_Name FROM users WHERE User_ID = :user_id AND Role = "Admin" LIMIT 1');
    $adminNameStmt->execute(['user_id' => (int)$_SESSION['UserID']]);
    $adminNameRow = $adminNameStmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($adminNameRow)) {
      $adminDisplayName = trim((string)($adminNameRow['First_name'] ?? '') . ' ' . (string)($adminNameRow['Last_Name'] ?? ''));
      if ($adminDisplayName === '') {
        $adminDisplayName = (string)($_SESSION['email'] ?? 'Admin');
      }
    }
  } catch (Throwable $e) {
    $adminDisplayName = (string)($_SESSION['email'] ?? 'Admin');
  }
}

$activeTab = $_GET['tab'] ?? 'advisors';

//promote students automaticly
$promotion = new PromotionClass();
$promotion->promoteStudents();

//get result sets
$advisors = $user->getAdvisors();
//get students information for filtering
$selectedStudentsYear =  trim((string)($_GET['student_year'] ?? ''));
$selectedStudentsDegree = (int)($_GET['Student_Degree'] ?? 0);
$selectedStudentsDepartment = (int)($_GET['Student_Department'] ?? 0);

//get assign-students filter values
$selectedAssignYear = trim((string)($_GET['assign_student_year'] ?? ''));
$selectedAssignDegree = (int)($_GET['assign_student_degree'] ?? 0);
$selectedAssignDepartment = (int)($_GET['assign_student_department'] ?? 0);

//get function results
$selectionClass = new SelectionClass();
$departments = $selectionClass->getDepartment();
$degrees = $selectionClass->getDegrees();


$DepartmentOptions = [];
if (is_array($departments)) {
  foreach ($departments as $department) {
    $departmentID = (string)($department['DepartmentID'] ?? '');
    $departmentName = (string)($department['DepartmentName'] ?? '');
    if ($departmentID !== '' && $departmentName !== '') {
      $DepartmentOptions[$departmentID] = $departmentName;
    }
  }
}

$availableFilterDegrees = $selectedStudentsDepartment > 0
  ? $selectionClass->getDegrees($selectedStudentsDepartment)
  : $degrees;

$DegreeOptions = [];
if (is_array($availableFilterDegrees)) {
  foreach ($availableFilterDegrees as $degree) {
    $degreeID = (string)($degree['DegreeID'] ?? '');
    $degreeName = (string)($degree['DegreeName'] ?? '');
    if ($degreeID !== '' && $degreeName !== '') {
      $DegreeOptions[$degreeID] = $degreeName;
    }
  }
}

if ($selectedStudentsDegree > 0 && !isset($DegreeOptions[(string)$selectedStudentsDegree])) {
  $selectedStudentsDegree = 0;
}

$hasStudentFilters = $selectedStudentsYear !== '' || $selectedStudentsDepartment > 0 || $selectedStudentsDegree > 0;
if ($hasStudentFilters) {
  $students = $user->getStudentsByFilters($selectedStudentsYear, $selectedStudentsDepartment, $selectedStudentsDegree);
  if ($students === false) {
    $students = $user->getStudents();
  }
} else {
  $students = $user->getStudents();
}

$superusers = $user->getSuperUsers();

//get arrays for assignment tab
$assignAdvisorsResult = $user->getAdvisors();
$availableAssignFilterDegrees = $selectedAssignDepartment > 0
  ? $selectionClass->getDegrees($selectedAssignDepartment)
  : $degrees;

$AssignDegreeOptions = [];
if (is_array($availableAssignFilterDegrees)) {
  foreach ($availableAssignFilterDegrees as $degree) {
    $degreeID = (string)($degree['DegreeID'] ?? '');
    $degreeName = (string)($degree['DegreeName'] ?? '');
    if ($degreeID !== '' && $degreeName !== '') {
      $AssignDegreeOptions[$degreeID] = $degreeName;
    }
  }
}

if ($selectedAssignDegree > 0 && !isset($AssignDegreeOptions[(string)$selectedAssignDegree])) {
  $selectedAssignDegree = 0;
}

$hasAssignStudentFilters = $selectedAssignYear !== '' || $selectedAssignDepartment > 0 || $selectedAssignDegree > 0;
if ($hasAssignStudentFilters) {
  $assignStudentsResult = $user->getStudentsByFilters($selectedAssignYear, $selectedAssignDepartment, $selectedAssignDegree);
  if ($assignStudentsResult === false) {
    $assignStudentsResult = $user->getStudents();
  }
} else {
  $assignStudentsResult = $user->getStudents();
}

$assignAdvisors  = resultFetchAllAssoc($assignAdvisorsResult);
$assignStudents  = resultFetchAllAssoc($assignStudentsResult);

if ($selectedAssignDepartment > 0) {
  $assignAdvisors = array_values(array_filter(
    $assignAdvisors,
    static function (array $advisor) use ($selectedAssignDepartment): bool {
      return (int)($advisor['DepartmentID'] ?? 0) === $selectedAssignDepartment;
    }
  ));
}

//get statistics
$allAdvisors = resultFetchAllAssoc($user->getAdvisors());
$allStudents = resultFetchAllAssoc($user->getStudents());
$superusersArr = $user->getSuperUsers();
$allSuperusers = resultFetchAllAssoc($superusersArr);

$participants = new Participants_Processing();
$assignmentMap = $participants->Get_Student_Advisor();
$studentAssignmentMap = $participants->Assign_Students_Advisors();

//build a set of assigned student IDs for stats
$assignedStudentIds = [];
if ($assignmentMap) {
  foreach ($assignmentMap as $advisorStudents) {
    if (is_array($advisorStudents)) {
      foreach ($advisorStudents as $studentExternalId => $isAssigned) {
        if ($isAssigned) {
          $assignedStudentIds[] = (int)$studentExternalId;
        }
      }
    }
    }
}

$assignedCount   = count(array_unique($assignedStudentIds));
$totalStudents   = count($allStudents);
$totalAdvisors   = count($allAdvisors);
$totalSuperusers = count($allSuperusers);
$unassignedCount = $totalStudents - $assignedCount;

// Flash messages
$flash        = $_SESSION['flash']        ?? null;
$flashType    = $_SESSION['flash_type']   ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

// Active section (default: advisors)
$activeSection = $_GET['section'] ?? 'advisors';
Notifications::createNotification();

$YearOptions = [
  '1' => 'Year 1',
  '2' => 'Year 2',
  '3' => 'Year 3',
  '4' => 'Year 4',
  '5' => 'Year 5',
  '6' => 'Year 6',
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administrator Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/degreebuttons.css">
  <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>

<?php if ($flash): ?>
<div class="flash-toast alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> mb-0" id="flashToast">
  <span class="flash-content">
    <i class="bi bi-<?= $flashType === 'error' ? 'x-circle' : 'check-circle' ?>-fill"></i>
    <?= htmlspecialchars($flash) ?>
  </span>
</div>
<?php endif; ?>


<!-- navigation bar -->
<header class="top-navbar">

  <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

  <div class="navbar-center">
    <span class="welcome-text">Welcome to AdviCut, <?= htmlspecialchars($adminDisplayName) ?>! 👋</span>
  </div>

  <div class="d-flex align-items-center gap-3">
    <a href="admin_appointment_reports.php" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-clipboard-data me-1"></i>Appointment Reports
    </a>

  <div class="dropdown">
      <button class="btn p-0 border-0 bg-transparent dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="user-avatar">A</div>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 190px;">
        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#manualInstructionsModal">
          <i class="bi bi-journal-text me-2"></i>Manual
        </button>
        <hr class="dropdown-divider my-2">
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

<!-- manual instructions modal -->
<div class="modal fade" id="manualInstructionsModal" tabindex="-1" aria-labelledby="manualInstructionsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold" id="manualInstructionsModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>Admin Dashboard Manual
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-2">Quick instructions:</p>
        <ol class="mb-0 ps-3">
          <li>Use the top tabs to navigate Advisors, Students, Assignments, Statistics, and Degrees.</li>
          <li>Use Add buttons to create new students and advisors each time it creates an account automatically.</li>
          <li>Use the delete buttons to remove students and advisors (be careful as this action is irreversible).</li>
          <li>Use filter buttons in Students and Assignments to narrow results.</li>
          <li>Use Random Assignment to auto-distribute students to advisors Randomly(Those that are already assigned do not get reassigned).</li>
          <li>Tab the Appointment Results to view more statistic analyses and export to CSV , PDF </li>
          <li>Create new departments and degree using the Degress tab(To delete a degree/department you must have 0 associated records)</li>
          <li>For any issues or questions, contact the system administrator.</li>
        </ol>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<!-- tab bar -->
<div class="tab-bar">
  <button class="tab-btn <?= $activeSection === 'advisors'      ? 'active' : '' ?>" data-section="advisors">
    <i class="bi bi-person-badge"></i> Advisors
  </button>
  <button class="tab-btn <?= $activeSection === 'students'      ? 'active' : '' ?>" data-section="students">
    <i class="bi bi-people"></i> Students
  </button>
  <button class="tab-btn <?= $activeSection === 'superusers'    ? 'active' : '' ?>" data-section="superusers">
    <i class="bi bi-shield-lock"></i> Admins
  </button>
  <button class="tab-btn <?= $activeSection === 'assignstudents'? 'active' : '' ?>" data-section="assignstudents">
    <i class="bi bi-diagram-3"></i> Assignments
  </button>
  <button class="tab-btn <?= $activeSection === 'statistics'    ? 'active' : '' ?>" data-section="statistics">
    <i class="bi bi-bar-chart-line"></i> Statistics
  </button>
  <button class="tab-btn <?= $activeSection === 'degrees'    ? 'active' : '' ?>" data-section="degrees">
    <i class="bi bi-graduation-cap"></i> Degrees
  </button>
</div>


<!-- main -->
<main class="container-fluid py-4 px-4" style="max-width: 1100px;">


<!-- advisors tab -->
  <div class="section-panel <?= $activeSection === 'advisors' ? 'active' : '' ?>" id="section-advisors">

    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Academic Advisors</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Manage advisor accounts</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdvisorModal">
          <i class="bi bi-person-plus me-1"></i> Add Advisor
        </button>
      </div>

      <input class="form-control mb-3" id="advisorSearch" placeholder="Search advisors…">

      <form action="../backend/modules/dispatcher.php" method="POST" id="advisorForm">
        <input type="hidden" name="action" value="/advisor/delete">

        <div class="table-responsive" id="advisorList">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:36px;"></th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>ID</th>
                <th>Email</th>
                <th>Department</th>
                <th>Phone Number</th>
              </tr>
            </thead>
            <tbody>
              <?php while (($advisor = resultFetchAssoc($advisors)) !== null): ?>
              <tr class="advisor-row">
                <td>
                  <input class="form-check-input mt-0"
                        type="checkbox"
                        name="advisor_id[]"
                        value="<?= htmlspecialchars($advisor['Advisor_ID']) ?>"
                        data-first-name="<?= htmlspecialchars($advisor['First_name']) ?>"
                        data-last-name="<?= htmlspecialchars($advisor['Last_Name']) ?>"
                        data-email="<?= htmlspecialchars($advisor['Email']) ?>"
                        data-phone="<?= htmlspecialchars($advisor['Phone'] ?? '') ?>"
                        data-department-id="<?= htmlspecialchars((string)($advisor['DepartmentID'] ?? '')) ?>">
                </td>
                <td><?= htmlspecialchars($advisor['First_name']) ?></td>
                <td><?= htmlspecialchars($advisor['Last_Name']) ?></td>
                <td><?= htmlspecialchars($advisor['Advisor_ID']) ?></td>
                <td><?= htmlspecialchars($advisor['Email']) ?></td>
                <td><?= htmlspecialchars($advisor['Department']) ?></td>
                <td><?= htmlspecialchars($advisor['Phone'] ?? '') ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 mt-3 pt-3 border-top">
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="bi bi-trash me-1"></i> Delete Selected
          </button>

          <button type="button" class="btn btn-primary btn-sm" id="editAdvisorBtn">
            <i class="bi bi-pencil-square me-1"></i> Edit Selected
          </button>
        </div>

      </form>
    </div>
  </div>


 <!-- students tab -->
  <div class="section-panel <?= $activeSection === 'students' ? 'active' : '' ?>" id="section-students">

    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Students</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Manage enrolled students</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importStudentsCsvModal">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
          </button>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="bi bi-person-plus me-1"></i> Add Student
          </button>
        </div>
      </div>

      <form method="GET" class="mb-3">
        <input type="hidden" name="tab" value="students">
        <input type="hidden" name="section" value="students">

        <div class="d-flex flex-wrap gap-2 mb-3">
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentYearFilterWrap" aria-expanded="false" aria-controls="studentYearFilterWrap">
            <i class="bi bi-calendar3 me-1"></i> Year Filter
          </button>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentDepartmentFilterWrap" aria-expanded="false" aria-controls="studentDepartmentFilterWrap">
            <i class="bi bi-building me-1"></i> Department Filter
          </button>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentDegreeFilterWrap" aria-expanded="false" aria-controls="studentDegreeFilterWrap">
            <i class="bi bi-mortarboard me-1"></i> Degree Filter
          </button>
          <button class="btn btn-primary btn-sm" type="submit">
            <i class="bi bi-funnel-fill me-1"></i> Apply Filters
          </button>
          <a href="admin_dashboard.php?section=students" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
          </a>
        </div>

        <div class="row g-2 align-items-end">
          <div class="col-sm-4 col-md-3 collapse <?= $selectedStudentsYear !== '' ? 'show' : '' ?>" id="studentYearFilterWrap">
            <label for="studentYearFilter" class="form-label mb-1">Filter By Year</label>
            <select class="form-select" id="studentYearFilter" name="student_year">
              <option value="" <?= $selectedStudentsYear === '' ? 'selected' : '' ?>>All Years</option>
              <?php foreach ($YearOptions as $yearValue => $yearLabel): ?>
              <option value="<?= htmlspecialchars($yearValue) ?>" <?= (string)$selectedStudentsYear === (string)$yearValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($yearLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-sm-4 col-md-3 collapse <?= $selectedStudentsDepartment > 0 ? 'show' : '' ?>" id="studentDepartmentFilterWrap">
            <label for="studentDepartmentFilter" class="form-label mb-1">Filter By Department</label>
            <select class="form-select" id="studentDepartmentFilter" name="Student_Department">
              <option value="" <?= $selectedStudentsDepartment === 0 ? 'selected' : '' ?>>All Departments</option>
              <?php foreach ($DepartmentOptions as $departmentValue => $departmentLabel): ?>
              <option value="<?= htmlspecialchars($departmentValue) ?>" <?= (string)$selectedStudentsDepartment === (string)$departmentValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($departmentLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-sm-4 col-md-3 collapse <?= $selectedStudentsDegree > 0 ? 'show' : '' ?>" id="studentDegreeFilterWrap">
            <label for="studentDegreeFilter" class="form-label mb-1">Filter By Degree</label>
            <select class="form-select" id="studentDegreeFilter" name="Student_Degree" autocomplete="off">
              <option value="0" <?= $selectedStudentsDegree === 0 ? 'selected' : '' ?>>All Degrees</option>
              <?php foreach ($DegreeOptions as $degreeValue => $degreeLabel): ?>
              <option value="<?= htmlspecialchars($degreeValue) ?>" <?= (string)$selectedStudentsDegree === (string)$degreeValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($degreeLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>
  

      <!-- Student tab -->
      <input class="form-control mb-3" id="studentSearch" placeholder="Search students…">

      <form action="../backend/modules/dispatcher.php" method="POST" id="studentForm">
        <input type="hidden" name="action" value="/student/delete">

        <div class="table-responsive" id="studentList">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:36px;"></th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>ID</th>
                <th>Email</th>
                <th>Department</th>
                <th>Degree</th>
                <th>Year</th>
                <th>Advisor ID</th>
              </tr>
            </thead>
            <tbody>
              <?php while (($student = resultFetchAssoc($students)) !== null): ?>
              <tr class="student-row">
                <td>
                  <input class="form-check-input mt-0"
                    type="checkbox"
                    name="student_ID[]"
                    value="<?= htmlspecialchars($student['Student_ID']) ?>"
                    data-external-id="<?= htmlspecialchars($student['StuExternal_ID']) ?>"
                    data-first-name="<?= htmlspecialchars($student['First_name']) ?>"
                    data-last-name="<?= htmlspecialchars($student['Last_Name']) ?>"
                    data-email="<?= htmlspecialchars($student['Email']) ?>"
                    data-department-id="<?= htmlspecialchars((string)($student['Department'] ?? '')) ?>"
                    data-degree-id="<?= htmlspecialchars((string)($student['Degree_ID'] ?? '1')) ?>"
                    data-year="<?= htmlspecialchars((string)($student['Year'] ?? '')) ?>"
                    data-advisor-id="<?= htmlspecialchars((string)($student['Advisor_ID'] ?? '')) ?>">
                </td>
                <td><?= htmlspecialchars($student['First_name']) ?></td>
                <td><?= htmlspecialchars($student['Last_Name']) ?></td>
                <td><?= htmlspecialchars($student['StuExternal_ID']) ?></td>
                <td><?= htmlspecialchars($student['Email']) ?></td>
                <td><?= htmlspecialchars($student['Department'] ?? '') ?></td>
                <td><?= htmlspecialchars($student['Degree']) ?></td>
                <td><?= 'Year ' . htmlspecialchars($student['Year'] ?? '') ?></td>
                <td><?= htmlspecialchars($student['Advisor_ID'] ?? 'Unassigned') ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 mt-3 pt-3 border-top">
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="bi bi-trash me-1"></i> Delete Selected
          </button>
          
          <button type="button" class="btn btn-primary btn-sm" id="editStudentBtn">
            <i class="bi bi-pencil-square me-1"></i> Edit Selected
          </button>
        </div>

      </form>
    </div>
  </div>


  <!-- SuperUsers tab -->
  <div class="section-panel <?= $activeSection === 'superusers' ? 'active' : '' ?>" id="section-superusers">

    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Admin Control</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Manage elevated access accounts</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSuperUserModal">
          <i class="bi bi-shield-plus me-1"></i> Add Admin
        </button>
      </div>

      <input class="form-control mb-3" id="superuserSearch" placeholder="Search admins…">

      <form action="../backend/modules/dispatcher.php" method="POST" id="superuserForm">
        <input type="hidden" name="action" value="/superuser/delete">

        <div id="superuserList">
          <?php while (($superuser = resultFetchAssoc($superusers)) !== null):
            $initials = strtoupper(substr($superuser['Email'], 0, 1));
          ?>
          <div class="list-item superuser-row">
            <input class="form-check-input mt-0 flex-shrink-0"
                   type="checkbox"
                   name="User_ID[]"
                   value="<?= htmlspecialchars($superuser['User_ID']) ?>">
            <div class="item-avatar avatar-amber"><?= $initials ?></div>
            <div class="item-meta">
              <p class="name"><?= htmlspecialchars($superuser['Email']) ?></p>
            </div>
          </div>
          <?php endwhile; ?>
        </div>

        <div class="d-flex gap-2 mt-3 pt-3 border-top">
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="bi bi-trash me-1"></i> Delete Selected
          </button>
        </div>

      </form>
    </div>
  </div>


  <!-- Assignment tab -->
  <div class="section-panel <?= $activeSection === 'assignstudents' ? 'active' : '' ?>" id="section-assignstudents">
    <div class="section-card">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold">Assign Students to Advisors</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Expand an advisor to select which students to assign</p>
        </div>
        <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0" data-confirm="Run random assignment for all students?">
          <input type="hidden" name="action" value="/advisor/students/random">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Random Assignment
          </button>
        </form>
      </div>

      <form method="GET" class="mb-3">
        <input type="hidden" name="tab" value="assignstudents">
        <input type="hidden" name="section" value="assignstudents">

        <div class="d-flex flex-wrap gap-2 mb-3">
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#assignYearFilterWrap" aria-expanded="false" aria-controls="assignYearFilterWrap">
            <i class="bi bi-calendar3 me-1"></i> Year Filter
          </button>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#assignDepartmentFilterWrap" aria-expanded="false" aria-controls="assignDepartmentFilterWrap">
            <i class="bi bi-building me-1"></i> Department Filter
          </button>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#assignDegreeFilterWrap" aria-expanded="false" aria-controls="assignDegreeFilterWrap">
            <i class="bi bi-mortarboard me-1"></i> Degree Filter
          </button>
          <button class="btn btn-primary btn-sm" type="submit">
            <i class="bi bi-funnel-fill me-1"></i> Apply Filters
          </button>
          <a href="admin_dashboard.php?section=assignstudents" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
          </a>
        </div>

        <div class="row g-2 align-items-end">
          <div class="col-sm-4 col-md-3 collapse <?= $selectedAssignYear !== '' ? 'show' : '' ?>" id="assignYearFilterWrap">
            <label for="assignYearFilter" class="form-label mb-1">Filter By Year</label>
            <select class="form-select" id="assignYearFilter" name="assign_student_year">
              <option value="" <?= $selectedAssignYear === '' ? 'selected' : '' ?>>All Years</option>
              <?php foreach ($YearOptions as $yearValue => $yearLabel): ?>
              <option value="<?= htmlspecialchars($yearValue) ?>" <?= (string)$selectedAssignYear === (string)$yearValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($yearLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-sm-4 col-md-3 collapse <?= $selectedAssignDepartment > 0 ? 'show' : '' ?>" id="assignDepartmentFilterWrap">
            <label for="assignDepartmentFilter" class="form-label mb-1">Filter By Department</label>
            <select class="form-select" id="assignDepartmentFilter" name="assign_student_department">
              <option value="" <?= $selectedAssignDepartment === 0 ? 'selected' : '' ?>>All Departments</option>
              <?php foreach ($DepartmentOptions as $departmentValue => $departmentLabel): ?>
              <option value="<?= htmlspecialchars($departmentValue) ?>" <?= (string)$selectedAssignDepartment === (string)$departmentValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($departmentLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-sm-4 col-md-3 collapse <?= $selectedAssignDegree > 0 ? 'show' : '' ?>" id="assignDegreeFilterWrap">
            <label for="assignDegreeFilter" class="form-label mb-1">Filter By Degree</label>
            <select class="form-select" id="assignDegreeFilter" name="assign_student_degree" autocomplete="off">
              <option value="0" <?= $selectedAssignDegree === 0 ? 'selected' : '' ?>>All Degrees</option>
              <?php foreach ($AssignDegreeOptions as $degreeValue => $degreeLabel): ?>
              <option value="<?= htmlspecialchars($degreeValue) ?>" <?= (string)$selectedAssignDegree === (string)$degreeValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($degreeLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>

      <div class="accordion" id="assignAdvisorAccordion">

        <?php foreach ($assignAdvisors as $advisor):
          $advisorUserId    = (int)$advisor['User_ID'];
          $advisorExternalId = (int)$advisor['Advisor_ID'];
          $collapseId       = 'assignAdvisor' . $advisorUserId;
          $advisorName      = htmlspecialchars($advisor['First_name'] . ' ' . $advisor['Last_Name']);
          $initials         = strtoupper(substr($advisor['First_name'], 0, 1) . substr($advisor['Last_Name'], 0, 1));

          //count currently assigned students for badge
          $assignedToThisAdvisor = 0;
          if (isset($assignmentMap[$advisorExternalId]) && is_array($assignmentMap[$advisorExternalId])) {
            $assignedToThisAdvisor = count($assignmentMap[$advisorExternalId]);
          }
        ?>
        <div class="accordion-item border rounded mb-2" style="overflow:hidden;">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
          <div class="d-flex align-items-center gap-2 w-100 me-3">
            <div class="item-avatar avatar-indigo" style="width:32px;height:32px;font-size:.8rem;"><?= $initials ?></div>
            <span class="fw-medium"><?= $advisorName ?></span>
            <span class="badge bg-secondary ms-auto" style="font-size:.72rem;"><?= $assignedToThisAdvisor ?> assigned</span>
          </div>
              </button>
          </h2>

          <div id="<?= $collapseId ?>" class="accordion-collapse collapse">
            <div class="accordion-body pt-2 pb-3">
              <form action="../backend/modules/dispatcher.php" method="POST">
                <input type="hidden" name="action" value="/advisor/students/assign">
                <input type="hidden" name="advisor_external_id" value="<?= $advisorExternalId ?>">

                <input class="form-control form-control-sm mb-3 assign-search"
                       placeholder="Filter students…">

                <div class="assign-student-list" style="max-height:280px;overflow-y:auto;">

                <!-- List for every advisor -->
                <div class="d-flex px-2 py-1 border-bottom" style="font-size:.8rem;color:#6c757d;font-weight:600;">
                  <div style="width:24px;flex-shrink:0;"></div>
                  <div style="flex:2;">First Name</div>
                  <div style="flex:2;">Last Name</div>
                  <div style="flex:1.5;">ID</div>
                  <div style="flex:1;">Year</div>
                </div>

                  <?php foreach ($assignStudents as $student):
                    $sFirstName = htmlspecialchars($student['First_name']);
                    $sLastName  = htmlspecialchars($student['Last_Name']);
                    $sId        = htmlspecialchars($student['StuExternal_ID']);
                    $sYear      = 'Year ' . htmlspecialchars($student['Year'] ?? '');
                  ?>
                <div class="assign-student-row d-flex align-items-center px-2 py-2 border-bottom"
                    style="font-size:.85rem;">
                  <div style="width:24px;flex-shrink:0;">
                    <?php $isChecked = isset($assignmentMap[$advisorExternalId]) && isset($assignmentMap[$advisorExternalId][(int)$sId]); ?>
                    <input class="form-check-input"
                          type="checkbox"
                          name="student_external_ids[]"
                          value="<?= $sId ?>"
                          id="stu_<?= $advisorUserId ?>_<?= $sId ?>"
                          <?= $isChecked ? 'checked' : '' ?>>
                  </div>
                  <label class="d-flex w-100 mb-0" for="stu_<?= $advisorUserId ?>_<?= $sId ?>" style="cursor:pointer;font-size:.85rem;">
                    <div style="flex:2;"><?= $sFirstName ?></div>
                    <div style="flex:2;"><?= $sLastName ?></div>
                    <div style="flex:1.5;color:#6c757d;"><?= $sId ?></div>
                    <div style="flex:1;color:#6c757d;"><?= $sYear ?></div>
                  </label>
                </div>
                <?php endforeach; ?>

              </div>

                <button class="btn btn-primary btn-sm mt-3">
                  <i class="bi bi-check2-circle me-1"></i> Save Assignment
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>


  <!-- Statistics tab -->
  <div class="section-panel <?= $activeSection === 'statistics' ? 'active' : '' ?>" id="section-statistics">
 
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <p class="stat-label">Total Advisors</p>
          <p class="stat-value"><?= $totalAdvisors ?></p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <p class="stat-label">Total Students</p>
          <p class="stat-value"><?= $totalStudents ?></p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <p class="stat-label">Assigned</p>
          <p class="stat-value text-success"><?= $assignedCount ?></p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <p class="stat-label">Unassigned</p>
          <p class="stat-value text-danger"><?= $unassignedCount ?></p>
        </div>
      </div>
    </div>
 
    <div class="section-card">
 
      <!-- Build per-advisor per-year data from existing PHP variables -->
      <?php
        // Build a lookup: student external ID => year
        $studentYearLookup = [];
        foreach ($assignStudents as $stu) {
          $studentYearLookup[(int)$stu['StuExternal_ID']] = (int)($stu['Year'] ?? 0);
        }
 
        // Build chart data: per advisor, total + breakdown by year
        $advisorChartData = [];
        foreach ($allAdvisors as $advisor) {
          $aid  = (int)$advisor['Advisor_ID'];
          $name = $advisor['First_name'] . ' ' . $advisor['Last_Name'];
          $byYear = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0];
          if (isset($assignmentMap[$aid]) && is_array($assignmentMap[$aid])) {
            foreach (array_keys($assignmentMap[$aid]) as $stuId) {
              $yr = $studentYearLookup[(int)$stuId] ?? 0;
              if ($yr >= 1 && $yr <= 6) $byYear[$yr]++;
            }
          }
          $advisorChartData[] = [
            'name'   => $name,
            'total'  => array_sum($byYear),
            'byYear' => $byYear,
          ];
        }
      ?>
 
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h5 class="fw-semibold mb-0">Advisor Statistics</h5>
        <div class="d-flex gap-1 flex-wrap" id="yearFilterBtns">
          <button class="btn btn-primary btn-sm year-filter-btn" data-year="0">All Years</button>
          <?php for ($y = 1; $y <= 6; $y++): ?>
            <button class="btn btn-outline-primary btn-sm year-filter-btn" data-year="<?= $y ?>">Year <?= $y ?></button>
          <?php endfor; ?>
        </div>
      </div>
 
      <?php if (empty($allAdvisors)): ?>
        <p class="text-muted text-center py-4">No advisor data available.</p>
      <?php else: ?>
 
        <div class="row align-items-center g-4">
          <!-- Pie chart -->
          <div class="col-md-5 d-flex justify-content-center">
            <div style="position:relative; width:100%; max-width:300px;">
              <canvas id="advisorPieChart" data-advisor-chart='<?= htmlspecialchars(json_encode(array_values($advisorChartData), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>'></canvas>
              <div id="chartCenterLabel" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                <div style="font-size:1.6rem;font-weight:700;color:#111827;line-height:1;" id="chartCenterCount">0</div>
                <div style="font-size:.75rem;color:#6b7280;">students</div>
              </div>
            </div>
          </div>
 
          <!-- Legend / breakdown table -->
          <div class="col-md-7">
            <div id="advisorLegend"></div>
          </div>
        </div>
 
      <?php endif; ?>
    </div>
  </div>

   
  <!-- Degrees tab -->
  <div class="section-panel <?= $activeSection === 'degrees' ? 'active' : '' ?>" id="section-degrees">
 
    <div class="deg-btn-row">
 
      <!-- ADD DEGREE -->
      <a class="deg-btn add" href="#" data-bs-toggle="modal" data-bs-target="#addDegreeModal">
        <div class="deg-icon"><i class="bi bi-plus-lg"></i></div>
        <div>
          <h5>Add Degree</h5>
          <p>Create a new degree program into the database!</p>
        </div>
      </a>
 
      <!-- EDIT DEGREE -->
      <a class="deg-btn edit" href="#" data-bs-toggle="modal" data-bs-target="#editDegreeModal">
        <div class="deg-icon"><i class="bi bi-pencil-square"></i></div>
        <div>
          <h5>Edit Degree</h5>
          <p>Browse all existing degrees and update their details!</p>
        </div>
      </a>

      <!-- ADD DEPARTMENT -->
      <a class="deg-btn department" href="#" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
        <div class="deg-icon"><i class="bi bi-building-add"></i></div>
        <div>
          <h5>Add Department</h5>
          <p>Create a new department and use it for future degrees.</p>
        </div>
      </a>

      <!-- EDIT DEPARTMENT -->
      <a class="deg-btn department" href="#" data-bs-toggle="modal" data-bs-target="#editDepartmentModal">
        <div class="deg-icon"><i class="bi bi-pencil-square"></i></div>
        <div>
          <h5>Edit Department</h5>
          <p>Browse all existing departments and update their details!</p>
        </div>
      </a>
 
    </div>
  </div>


</main>

<!-- add advisors tab -->
<div class="modal fade" id="addAdvisorModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Add New Advisor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="../backend/modules/dispatcher.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="/advisor/add">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" class="form-control"  required>
            </div>
            <div class="col-12">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" placeholder="ex.example@edu.ac.cy" required>
            </div>
            <div class="col-6">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-control" placeholder="Must be 8 Numbers">
            </div>
            <div class="col-6">
              <label class="form-label">Advisor ID <span class="text-danger">*</span></label>
              <input type="text" name="advisor_external_id" class="form-control" placeholder="20555" required>
            </div>
            <div class="col-12">
              <label class="form-label">Department <span class="text-danger">*</span></label>
              <select name="department" class="form-select" required>
                <option value="" disabled selected>Select a department…</option>
                <?php foreach ($DepartmentOptions as $departmentValue => $departmentLabel): ?>
                  <option value="<?= htmlspecialchars($departmentValue) ?>">
                    <?= htmlspecialchars($departmentLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Advisor
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- edit advisor modal -->
<div class="modal fade" id="editAdvisorModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Edit Advisor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="../backend/modules/dispatcher.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="/advisor/edit">
          <div class="row g-3">
            <div class="col-6">
              
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" id="editAdvisorFirstName" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" id="editAdvisorLastName" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" id="editAdvisorEmail" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" id="editAdvisorPhone" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">Advisor ID</label>
              <input type="text" name="advisor_external_id" id="editAdvisorExternalId" class="form-control" readonly>
            </div>
            <div class="col-12">
              <label class="form-label">Department <span class="text-danger">*</span></label>
              <select name="department" id="editAdvisorDepartment" class="form-select" required>
                <option value="" disabled>Select a department…</option>
                <?php foreach ($DepartmentOptions as $departmentValue => $departmentLabel): ?>
                  <option value="<?= htmlspecialchars($departmentValue) ?>">
                    <?= htmlspecialchars($departmentLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- edit student modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Edit Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="../backend/modules/dispatcher.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="/student/edit">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Student ID</label>
              <input type="text" name="student_external_id" id="editStudentExternalId" class="form-control" readonly>
            </div>
            <div class="col-6">
              <label class="form-label">Year <span class="text-danger">*</span></label>
              <select name="year" id="editStudentYear" class="form-select" required>
                <option value="1">Year 1</option>
                <option value="2">Year 2</option>
                <option value="3">Year 3</option>
                <option value="4">Year 4</option>
                <option value="5">Year 5</option>
                <option value="6">Year 6</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" id="editStudentFirstName" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" id="editStudentLastName" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" id="editStudentEmail" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Degree <span class="text-danger">*</span></label>
              <select name="degree" id="editStudentDegree" class="form-select" required>
                <option value="">Select a degree</option>
                <?php foreach ($degrees as $degree): ?>
                  <option value="<?= htmlspecialchars($degree['DegreeID']) ?>">
                    <?= htmlspecialchars($degree['DegreeName']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Assign Advisor <span class="text-muted">(optional)</span></label>
              <select name="advisor_id" id="editStudentAdvisor" class="form-select">
                <option value="">No advisor</option>
                <?php foreach ($allAdvisors as $adv): ?>
                <option value="<?= htmlspecialchars($adv['Advisor_ID']) ?>">
                  <?= htmlspecialchars($adv['First_name'] . ' ' . $adv['Last_Name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- add students tab -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Add New Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="../backend/modules/dispatcher.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="/student/add">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Student ID <span class="text-danger">*</span></label>
              <input type="text" name="student_external_id" class="form-control" placeholder="27504" required>
            </div>
            <div class="col-6">
              <label class="form-label">Year <span class="text-danger">*</span></label>
              <select name="year" class="form-select" required>
                <option value="1">Year 1</option>
                <option value="2">Year 2</option>
                <option value="3">Year 3</option>
                <option value="4">Year 4</option>
                <option value="5">Year 5</option>
                <option value="6">Year 6</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" class="form-control" placeholder="Andreas" required>
            </div>
            <div class="col-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" class="form-control" placeholder="Kyriakou" required>
            </div>
            <div class="col-12">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" placeholder="a.kyriakou@edu.cut.ac.cy" required>
            </div>
            <div class="col-12">
              <label class="form-label">Degree <span class="text-danger">*</span></label>
              <select name="degree" class="form-select" required>
                <option value="" disabled selected>Select a degree…</option>
                <?php foreach ($degrees as $degree): ?>
                  <option value="<?= htmlspecialchars($degree['DegreeID']) ?>">
                    <?= htmlspecialchars($degree['DegreeName']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Assign Advisor <span class="text-muted">(optional)</span></label>
              <select name="advisor_id" class="form-select">
                <option value="" selected>No advisor</option>
                <?php foreach ($allAdvisors as $adv): ?>
                <option value="<?= htmlspecialchars($adv['Advisor_ID']) ?>">
                  <?= htmlspecialchars($adv['First_name'] . ' ' . $adv['Last_Name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Student
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- import students by csv modal -->
<div class="modal fade" id="importStudentsCsvModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Import Students from CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="../backend/modules/dispatcher.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="action" value="/student/import">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">CSV File <span class="text-danger">*</span></label>
              <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
              <small class="text-muted d-block mt-2">Supported headers: student_id, first_name, last_name, email, degree, year, advisor_id</small>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-upload me-1"></i> Import Students
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- add superusers tab -->
<div class="modal fade" id="addSuperUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">Add New SuperUser</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="../backend/modules/dispatcher.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="/superuser/add">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" placeholder="admin@university.edu" required>
            </div>
            <div class="col-12">
              <label class="form-label">SuperUser ID <span class="text-danger">*</span></label>
              <input type="number" name="external_id" class="form-control" placeholder="12345" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-shield-plus me-1"></i> Add SuperUser
          </button>
        </div>
      </form>
    </div>
  </div>
</div>



<!-- ADD DEGREE MODAL -->
<div class="modal fade" id="addDegreeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-plus-circle-fill me-2 text-primary"></i>Add New Degree
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="../backend/modules/dispatcher.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="/degree/add">
          <div class="row g-3">
            <div class="col-8">
              <label class="form-label">Degree Name <span class="text-danger">*</span></label>
              <input type="text" name="degree_name" class="form-control" placeholder="Computer Science" required>
            </div>
            <div class="col-12">
              <label class="form-label">Department <span class="text-danger">*</span></label>
              <select name="department_id" class="form-select" required>
                <option value="" disabled selected>Select a department...</option>
                <?php foreach ($DepartmentOptions as $departmentValue => $departmentLabel): ?>
                  <option value="<?= htmlspecialchars($departmentValue) ?>">
                    <?= htmlspecialchars($departmentLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> Save Degree
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ADD DEPARTMENT MODAL -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-building-add me-2" style="color:#0369a1"></i>Add New Department
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="../backend/modules/dispatcher.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="/department/add">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Department Name <span class="text-danger">*</span></label>
              <input type="text" name="department_name" class="form-control" placeholder="HMMHY" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> Save Department
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
 
 
<!-- EDIT DEGREE MODAL -->
<div class="modal fade" id="editDegreeModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-pencil-square me-2" style="color:#059669"></i>Edit Degrees
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
 
        <input class="form-control mb-3" id="degreeSearch" placeholder="Search degrees…">
 
        <div id="degreeEditList">
          <?php foreach ($degrees as $degree): ?>
          <div class="deg-list-item" id="degItem-<?= htmlspecialchars($degree['DegreeID']) ?>">
 
            <!-- Row header -->
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div style="flex:1;min-width:0">
                <div class="fw-semibold" style="font-size:.95rem"><?= htmlspecialchars($degree['DegreeName']) ?></div>
                <div class="text-muted" style="font-size:.78rem">
                  <i class="bi bi-building" style="font-size:.7rem"></i>
                  <?= htmlspecialchars($degree['Department_Name'] ?? '') ?>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="degToggleEdit('<?= htmlspecialchars($degree['DegreeID']) ?>')">
                  <i class="bi bi-pencil me-1"></i>Edit
                </button>
                    <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0"
                      data-confirm="Delete this degree? This cannot be undone.">
                  <input type="hidden" name="action" value="/degree/delete">
                  <input type="hidden" name="degree_id" value="<?= htmlspecialchars($degree['DegreeID']) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
            <!-- Inline edit form -->
            <div class="deg-inline-form" id="degForm-<?= htmlspecialchars($degree['DegreeID']) ?>">
              <form action="../backend/modules/dispatcher.php" method="POST">
                <input type="hidden" name="action" value="/degree/edit">
                <input type="hidden" name="degree_id" value="<?= htmlspecialchars($degree['DegreeID']) ?>">
                <div class="row g-2">
                  <div class="col-8">
                    <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#6b7280">Degree Name *</label>
                    <input type="text" name="degree_name" class="form-control form-control-sm"
                          value="<?= htmlspecialchars($degree['DegreeName']) ?>" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#6b7280">Department *</label>
                    <select name="department_id" class="form-select form-select-sm" required>
                    <?php foreach ($departments as $dep): ?>
                      <option value="<?= $dep['DepartmentID'] ?>"<?= $dep['DepartmentID'] == $degree['DepartmentID'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($dep['DepartmentName']) ?></option>
                    <?php endforeach; ?>
                </select>
                  </div>
                  <div class="col-12 d-flex gap-2 justify-content-end mt-1">
                    <button type="button" class="btn btn-sm btn-light" onclick="degToggleEdit('<?= htmlspecialchars($degree['DegreeID']) ?>')">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-check2-circle me-1"></i>Save Changes
                    </button>
                  </div>
                </div>
              </form>
            </div>
 
          </div>
          <?php endforeach; ?>
          <?php if (empty($degrees)): ?>
            <p class="text-muted text-center py-4">No degrees found in the database.</p>
          <?php endif; ?>
        </div>
 
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<!-- EDIT DEPARTMENT MODAL -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-pencil-square me-2" style="color:#0369a1"></i>Edit Departments
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">

        <input class="form-control mb-3" id="departmentSearch" placeholder="Search departments…">

        <div id="departmentEditList">
          <?php foreach ($departments as $department): ?>
          <div class="deg-list-item" id="deptItem-<?= htmlspecialchars($department['DepartmentID']) ?>">

            <!-- Row header -->
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div style="flex:1;min-width:0">
                <div class="fw-semibold" style="font-size:.95rem"><?= htmlspecialchars($department['DepartmentName']) ?></div>
                <div class="text-muted" style="font-size:.78rem">
                  <i class="bi bi-building" style="font-size:.7rem"></i>
                  ID: <?= htmlspecialchars($department['DepartmentID']) ?>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="deptToggleEdit('<?= htmlspecialchars($department['DepartmentID']) ?>')">
                  <i class="bi bi-pencil me-1"></i>Edit
                </button>
                    <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0"
                      data-confirm="Delete this department? This cannot be undone.">
                  <input type="hidden" name="action" value="/department/delete">
                  <input type="hidden" name="department_id" value="<?= htmlspecialchars($department['DepartmentID']) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
            <!-- Inline edit form -->
            <div class="deg-inline-form" id="deptForm-<?= htmlspecialchars($department['DepartmentID']) ?>">
              <form action="../backend/modules/dispatcher.php" method="POST">
                <input type="hidden" name="action" value="/department/edit">
                <input type="hidden" name="department_id" value="<?= htmlspecialchars($department['DepartmentID']) ?>">
                <div class="row g-2">
                  <div class="col-12">
                    <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#6b7280">Department Name *</label>
                    <input type="text" name="department_name" class="form-control form-control-sm"
                          value="<?= htmlspecialchars($department['DepartmentName']) ?>" required>
                  </div>
                  <div class="col-12 d-flex gap-2 justify-content-end mt-1">
                    <button type="button" class="btn btn-sm btn-light" onclick="deptToggleEdit('<?= htmlspecialchars($department['DepartmentID']) ?>')">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-check2-circle me-1"></i>Save Changes
                    </button>
                  </div>
                </div>
              </form>
            </div>

          </div>
          <?php endforeach; ?>
          <?php if (empty($departments)): ?>
            <p class="text-muted text-center py-4">No departments found in the database.</p>
          <?php endif; ?>
        </div>

      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<?php require_once __DIR__ . '/footer/dashboard_footer.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/confirm-dialog.js"></script>
<script src="js/admin-dashboard.js"></script>

</body>
</html>
