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

18-Apr-2026 v0.9
Replaced browser confirm popups with custom Bootstrap confirmation modal and preserved existing admin actions and submit logic
Panteleimoni Alexandrou

20-Apr-2026 v1.0
Fixed logout form CSRF submission so logout redirects correctly without dispatcher validation errors
Panteleimoni Alexandrou


*/

require_once 'init.php';
require_once '../backend/modules/AdminClass.php';
require_once '../backend/modules/ParticipantsClass.php';
require_once '../backend/modules/NotificationsClass.php';
require_once '../backend/modules/SelectionClass.php';
require_once '../backend/modules/PromotionClass.php';
require_once '../backend/modules/Csrf.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$csrfToken = Csrf::ensureToken();

if (isset($_GET['set_lang']) && in_array((string)$_GET['set_lang'], ['en', 'el'], true)) {
  $_SESSION['management_dashboard_lang'] = (string)$_GET['set_lang'];
  $redirectParams = $_GET;
  unset($redirectParams['set_lang']);
  $redirectUrl = basename((string)($_SERVER['PHP_SELF'] ?? 'admin_dashboard.php'));
  if ($redirectParams !== []) {
    $redirectUrl .= '?' . http_build_query($redirectParams);
  }
  header('Location: ' . $redirectUrl);
  exit();
}

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

$activeTab = $_GET['tab'] ?? 'advisors';

$adminDisplayName = 'Admin';
if (!empty($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
  try {
    $adminPdo = ConnectToDatabase();
    $adminStmt = $adminPdo->prepare('SELECT First_name FROM users WHERE User_ID = :user_id AND Role = "Admin" LIMIT 1');
    $adminStmt->execute(['user_id' => (int)$_SESSION['UserID']]);
    $adminRow = $adminStmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($adminRow)) {
      $adminFirstName = trim((string)($adminRow['First_name'] ?? ''));
      if ($adminFirstName !== '') {
        $adminDisplayName = $adminFirstName;
      }
    }
  } catch (Throwable $e) {
    $adminDisplayName = 'Admin';
  }
}

$lang = isset($_SESSION['management_dashboard_lang']) && in_array($_SESSION['management_dashboard_lang'], ['en', 'el'], true)
  ? (string)$_SESSION['management_dashboard_lang']
  : 'en';

$buildCurrentUrl = static function (array $overrides = [], array $remove = []): string {
  $params = $_GET;
  foreach ($remove as $param) {
    unset($params[$param]);
  }
  foreach ($overrides as $key => $value) {
    if ($value === null) {
      unset($params[$key]);
    } else {
      $params[$key] = $value;
    }
  }

  $path = basename((string)($_SERVER['PHP_SELF'] ?? 'admin_dashboard.php'));
  return $path . ($params !== [] ? '?' . http_build_query($params) : '');
};

$toggleLang = $lang === 'en' ? 'el' : 'en';
$toggleUrl = $buildCurrentUrl(['set_lang' => $toggleLang]);
$langButtonLabel = $lang === 'en' ? 'EN / EL' : 'EL / EN';

$translations = [
  'en' => [
    'page_title' => 'Administrator Portal',
    'welcome' => 'Welcome to AdviCut, %s! 👋',
    'appointment_reports' => 'Appointment Reports',
    'manual' => 'Manual',
    'logout' => 'Logout',
    'close' => 'Close',
    'manual_title' => 'Admin Dashboard Manual',
    'manual_intro' => 'Quick instructions:',
    'manual_item_1' => 'Use the top tabs to navigate Advisors, Students, Assignments, Statistics, and Degrees.',
    'manual_item_2' => 'Use Add buttons to create new students and advisors each time it creates an account automatically.',
    'manual_item_3' => 'Use the delete buttons to remove students and advisors (be careful as this action is irreversible).',
    'manual_item_4' => 'Use filter buttons in Students and Assignments to narrow results.',
    'manual_item_5' => 'Use Random Assignment to auto-distribute students to advisors randomly (already assigned students do not get reassigned).',
    'manual_item_6' => 'Use Appointment Reports to view additional statistics and export to CSV or PDF.',
    'manual_item_7' => 'Create new departments and degrees using the Degrees tab (to delete one, you must have 0 associated records).',
    'manual_item_8' => 'For any issues or questions, contact the system administrator.',
    'tab_advisors' => 'Advisors',
    'tab_students' => 'Students',
    'tab_admins' => 'Admins',
    'tab_assignments' => 'Assignments',
    'tab_statistics' => 'Statistics',
    'tab_degrees' => 'Degrees',
    'academic_advisors' => 'Academic Advisors',
    'manage_advisor_accounts' => 'Manage advisor accounts',
    'add_advisor' => 'Add Advisor',
    'search_advisors' => 'Search advisors...',
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'id' => 'ID',
    'email' => 'Email',
    'department' => 'Department',
    'phone_number' => 'Phone Number',
    'delete_selected' => 'Delete Selected',
    'edit_selected' => 'Edit Selected',
    'no_advisors_found' => 'No advisors found',
    'students' => 'Students',
    'manage_enrolled_students' => 'Manage enrolled students',
    'import_csv' => 'Import CSV',
    'add_student' => 'Add Student',
    'year_filter' => 'Year Filter',
    'department_filter' => 'Department Filter',
    'degree_filter' => 'Degree Filter',
    'apply_filters' => 'Apply Filters',
    'reset' => 'Reset',
    'filter_by_year' => 'Filter By Year',
    'all_years' => 'All Years',
    'filter_by_department' => 'Filter By Department',
    'all_departments' => 'All Departments',
    'filter_by_degree' => 'Filter By Degree',
    'all_degrees' => 'All Degrees',
    'search_students' => 'Search students...',
    'degree' => 'Degree',
    'year' => 'Year',
    'advisor_id' => 'Advisor ID',
    'unassigned' => 'Unassigned',
    'admin_control' => 'Admin Control',
    'manage_elevated_access_accounts' => 'Manage elevated access accounts',
    'add_admin' => 'Add Admin',
    'search_admins' => 'Search admins...',
    'assign_students_to_advisors' => 'Assign Students to Advisors',
    'assign_subtitle' => 'Expand an advisor to select which students to assign',
    'run_random_assignment_confirm' => 'Run random assignment for all students?',
    'run_assignment' => 'Run Assignment',
    'random_assignment' => 'Random Assignment',
    'filter_students' => 'Filter students...',
    'save_assignment' => 'Save Assignment',
    'assigned_suffix' => 'assigned',
    'confirm_action' => 'Confirm Action',
    'confirm_continue' => 'Are you sure you want to continue?',
    'cancel' => 'Cancel',
    'confirm' => 'Confirm',
    'year_n' => 'Year %d'
  ],
  'el' => [
    'page_title' => 'Πύλη Διαχειριστή',
    'welcome' => 'Καλώς ήρθες στο AdviCut, %s! 👋',
    'appointment_reports' => 'Αναφορές Ραντεβού',
    'manual' => 'Οδηγός',
    'logout' => 'Αποσύνδεση',
    'close' => 'Κλείσιμο',
    'manual_title' => 'Οδηγός Πίνακα Διαχειριστή',
    'manual_intro' => 'Σύντομες οδηγίες:',
    'manual_item_1' => 'Χρησιμοποιήστε τις επάνω καρτέλες για πλοήγηση σε Συμβούλους, Φοιτητές, Αναθέσεις, Στατιστικά και Πτυχία.',
    'manual_item_2' => 'Χρησιμοποιήστε τα κουμπιά Προσθήκης για δημιουργία νέων φοιτητών και συμβούλων με αυτόματη δημιουργία λογαριασμού.',
    'manual_item_3' => 'Χρησιμοποιήστε τα κουμπιά διαγραφής για αφαίρεση φοιτητών και συμβούλων (η ενέργεια δεν αναιρείται).',
    'manual_item_4' => 'Χρησιμοποιήστε φίλτρα στις ενότητες Φοιτητές και Αναθέσεις για περιορισμό αποτελεσμάτων.',
    'manual_item_5' => 'Χρησιμοποιήστε την Τυχαία Ανάθεση για αυτόματη κατανομή φοιτητών στους συμβούλους (οι ήδη ανατεθειμένοι δεν ανατίθενται ξανά).',
    'manual_item_6' => 'Χρησιμοποιήστε τις Αναφορές Ραντεβού για επιπλέον στατιστικά και εξαγωγή σε CSV ή PDF.',
    'manual_item_7' => 'Δημιουργήστε νέα τμήματα και πτυχία από την καρτέλα Πτυχία (για διαγραφή απαιτούνται 0 συσχετισμένες εγγραφές).',
    'manual_item_8' => 'Για οποιοδήποτε πρόβλημα ή απορία, επικοινωνήστε με τον διαχειριστή συστήματος.',
    'tab_advisors' => 'Σύμβουλοι',
    'tab_students' => 'Φοιτητές',
    'tab_admins' => 'Διαχειριστές',
    'tab_assignments' => 'Αναθέσεις',
    'tab_statistics' => 'Στατιστικά',
    'tab_degrees' => 'Πτυχία',
    'academic_advisors' => 'Ακαδημαϊκοί Σύμβουλοι',
    'manage_advisor_accounts' => 'Διαχείριση λογαριασμών συμβούλων',
    'add_advisor' => 'Προσθήκη Συμβούλου',
    'search_advisors' => 'Αναζήτηση συμβούλων...',
    'first_name' => 'Όνομα',
    'last_name' => 'Επώνυμο',
    'id' => 'Κωδικός',
    'email' => 'Email',
    'department' => 'Τμήμα',
    'phone_number' => 'Τηλέφωνο',
    'delete_selected' => 'Διαγραφή Επιλεγμένων',
    'edit_selected' => 'Επεξεργασία Επιλεγμένου',
    'no_advisors_found' => 'Δεν βρέθηκαν σύμβουλοι',
    'students' => 'Φοιτητές',
    'manage_enrolled_students' => 'Διαχείριση εγγεγραμμένων φοιτητών',
    'import_csv' => 'Εισαγωγή CSV',
    'add_student' => 'Προσθήκη Φοιτητή',
    'year_filter' => 'Φίλτρο Έτους',
    'department_filter' => 'Φίλτρο Τμήματος',
    'degree_filter' => 'Φίλτρο Πτυχίου',
    'apply_filters' => 'Εφαρμογή Φίλτρων',
    'reset' => 'Επαναφορά',
    'filter_by_year' => 'Φιλτράρισμα Ανά Έτος',
    'all_years' => 'Όλα τα Έτη',
    'filter_by_department' => 'Φιλτράρισμα Ανά Τμήμα',
    'all_departments' => 'Όλα τα Τμήματα',
    'filter_by_degree' => 'Φιλτράρισμα Ανά Πτυχίο',
    'all_degrees' => 'Όλα τα Πτυχία',
    'search_students' => 'Αναζήτηση φοιτητών...',
    'degree' => 'Πτυχίο',
    'year' => 'Έτος',
    'advisor_id' => 'Κωδικός Συμβούλου',
    'unassigned' => 'Μη ανατεθειμένος',
    'admin_control' => 'Έλεγχος Διαχειριστών',
    'manage_elevated_access_accounts' => 'Διαχείριση λογαριασμών αυξημένης πρόσβασης',
    'add_admin' => 'Προσθήκη Διαχειριστή',
    'search_admins' => 'Αναζήτηση διαχειριστών...',
    'assign_students_to_advisors' => 'Ανάθεση Φοιτητών σε Συμβούλους',
    'assign_subtitle' => 'Ανοίξτε έναν σύμβουλο για να επιλέξετε φοιτητές προς ανάθεση',
    'run_random_assignment_confirm' => 'Να εκτελεστεί τυχαία ανάθεση για όλους τους φοιτητές;',
    'run_assignment' => 'Εκτέλεση Ανάθεσης',
    'random_assignment' => 'Τυχαία Ανάθεση',
    'filter_students' => 'Φιλτράρισμα φοιτητών...',
    'save_assignment' => 'Αποθήκευση Ανάθεσης',
    'assigned_suffix' => 'ανατεθειμένοι',
    'confirm_action' => 'Επιβεβαίωση Ενέργειας',
    'confirm_continue' => 'Είστε σίγουροι ότι θέλετε να συνεχίσετε;',
    'cancel' => 'Ακύρωση',
    'confirm' => 'Επιβεβαίωση',
    'year_n' => 'Έτος %d'
  ]
];

$t = static function (string $key) use ($translations, $lang): string {
  return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
};

$yearLabel = static function (int $year) use ($t): string {
  return sprintf($t('year_n'), $year);
};

//promote students automaticly
$promotion = new PromotionClass();
$promotion->promoteStudents();

//get result sets
$selectedAdvisorsDepartment = (int)($_GET['Advisor_Department'] ?? 0);
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

if ($selectedAdvisorsDepartment > 0 && !isset($DepartmentOptions[(string)$selectedAdvisorsDepartment])) {
  $selectedAdvisorsDepartment = 0;
}

$advisors = resultFetchAllAssoc($user->getAdvisors());
if ($selectedAdvisorsDepartment > 0) {
  $advisors = array_values(array_filter(
    $advisors,
    static function (array $advisor) use ($selectedAdvisorsDepartment): bool {
      return (int)($advisor['DepartmentID'] ?? 0) === $selectedAdvisorsDepartment;
    }
  ));
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

// Active section (default: advisors)
$activeSection = $_GET['tab'] ?? ($_GET['section'] ?? 'advisors');
Notifications::createNotification();

$YearOptions = [
  '1' => $yearLabel(1),
  '2' => $yearLabel(2),
  '3' => $yearLabel(3),
  '4' => $yearLabel(4),
  '5' => $yearLabel(5),
  '6' => $yearLabel(6),
];

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($t('page_title')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/degreebuttons.css">
  <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>


<!-- navigation bar -->
<header class="top-navbar">

  <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

  <div class="navbar-center">
    <span class="welcome-text"><?= htmlspecialchars(sprintf($t('welcome'), $adminDisplayName)) ?></span>
  </div>

  <div class="d-flex align-items-center gap-3">
    <a href="<?= htmlspecialchars($toggleUrl) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1">
      <i class="bi bi-globe2 me-1"></i><?= htmlspecialchars($langButtonLabel) ?>
    </a>

  <div class="dropdown">
      <button class="btn btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu" style="width: 38px; height: 38px;">
        <i class="bi bi-list fs-4"></i>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 190px;">
        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#manualInstructionsModal">
          <i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($t('manual')) ?>
        </button>
        <a class="dropdown-item" href="admin_appointment_reports.php">
          <i class="bi bi-clipboard-data me-2"></i><?= htmlspecialchars($t('appointment_reports')) ?>
        </a>
        <hr class="dropdown-divider my-2">
        <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0">
          <input type="hidden" name="action" value="/logout">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <button class="dropdown-item text-danger" type="submit">
            <i class="bi bi-box-arrow-right me-2"></i><?= htmlspecialchars($t('logout')) ?>
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
          <i class="bi bi-info-circle me-2 text-primary"></i><?= htmlspecialchars($t('manual_title')) ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-2"><?= htmlspecialchars($t('manual_intro')) ?></p>
        <ol class="mb-0 ps-3">
          <li><?= htmlspecialchars($t('manual_item_1')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_2')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_3')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_4')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_5')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_6')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_7')) ?></li>
          <li><?= htmlspecialchars($t('manual_item_8')) ?></li>
        </ol>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= htmlspecialchars($t('close')) ?></button>
      </div>
    </div>
  </div>
</div>


<!-- tab bar -->
<div class="tab-bar">
  <button class="tab-btn <?= $activeSection === 'advisors'      ? 'active' : '' ?>" data-section="advisors">
    <i class="bi bi-person-badge"></i> <?= htmlspecialchars($t('tab_advisors')) ?>
  </button>
  <button class="tab-btn <?= $activeSection === 'students'      ? 'active' : '' ?>" data-section="students">
    <i class="bi bi-people"></i> <?= htmlspecialchars($t('tab_students')) ?>
  </button>
  <button class="tab-btn <?= $activeSection === 'superusers'    ? 'active' : '' ?>" data-section="superusers">
    <i class="bi bi-shield-lock"></i> <?= htmlspecialchars($t('tab_admins')) ?>
  </button>
  <button class="tab-btn <?= $activeSection === 'assignstudents'? 'active' : '' ?>" data-section="assignstudents">
    <i class="bi bi-diagram-3"></i> <?= htmlspecialchars($t('tab_assignments')) ?>
  </button>
  <button class="tab-btn <?= $activeSection === 'statistics'    ? 'active' : '' ?>" data-section="statistics">
    <i class="bi bi-bar-chart-line"></i> <?= htmlspecialchars($t('tab_statistics')) ?>
  </button>
  <button class="tab-btn <?= $activeSection === 'degrees'    ? 'active' : '' ?>" data-section="degrees">
    <i class="bi bi-mortarboard"></i> <?= htmlspecialchars($t('tab_degrees')) ?>
  </button>
</div>


<!-- main -->
<main class="container-fluid py-4 px-4" style="max-width: 1100px;">


<!-- advisors tab -->
  <div class="section-panel <?= $activeSection === 'advisors' ? 'active' : '' ?>" id="section-advisors">

    <div class="section-card">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('academic_advisors')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('manage_advisor_accounts')) ?></p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdvisorModal">
          <i class="bi bi-person-plus me-1"></i> <?= htmlspecialchars($t('add_advisor')) ?>
        </button>
      </div>

      <form method="GET" class="mb-3">
        <input type="hidden" name="tab" value="advisors">
        <input type="hidden" name="section" value="advisors">

        <div class="d-flex flex-wrap gap-2 mb-3">
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#advisorDepartmentFilterWrap" aria-expanded="false" aria-controls="advisorDepartmentFilterWrap">
            <i class="bi bi-building me-1"></i> <?= htmlspecialchars($t('department_filter')) ?>
          </button>
          <button class="btn btn-primary btn-sm" type="submit">
            <i class="bi bi-funnel-fill me-1"></i> <?= htmlspecialchars($t('apply_filters')) ?>
          </button>
          <a href="admin_dashboard.php?section=advisors" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> <?= htmlspecialchars($t('reset')) ?>
          </a>
        </div>

        <div class="row g-2 align-items-end">
          <div class="col-sm-4 col-md-3 collapse <?= $selectedAdvisorsDepartment > 0 ? 'show' : '' ?>" id="advisorDepartmentFilterWrap">
            <label for="advisorDepartmentFilter" class="form-label mb-1"><?= htmlspecialchars($t('filter_by_department')) ?></label>
            <select class="form-select" id="advisorDepartmentFilter" name="Advisor_Department">
              <option value="" <?= $selectedAdvisorsDepartment === 0 ? 'selected' : '' ?>><?= htmlspecialchars($t('all_departments')) ?></option>
              <?php foreach ($DepartmentOptions as $departmentValue => $departmentLabel): ?>
              <option value="<?= htmlspecialchars($departmentValue) ?>" <?= (string)$selectedAdvisorsDepartment === (string)$departmentValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($departmentLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>

      <input class="form-control mb-3" id="advisorSearch" placeholder="<?= htmlspecialchars($t('search_advisors')) ?>">

      <form action="../backend/modules/dispatcher.php" method="POST" id="advisorForm">
        <input type="hidden" name="action" value="/advisor/delete">

        <div class="table-responsive" id="advisorList">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:36px;"></th>
                <th><?= htmlspecialchars($t('first_name')) ?></th>
                <th><?= htmlspecialchars($t('last_name')) ?></th>
                <th><?= htmlspecialchars($t('id')) ?></th>
                <th><?= htmlspecialchars($t('email')) ?></th>
                <th><?= htmlspecialchars($t('department')) ?></th>
                <th><?= htmlspecialchars($t('phone_number')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($advisors)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4"><?= htmlspecialchars($t('no_advisors_found')) ?></td>
              </tr>
              <?php else: ?>
                <?php foreach ($advisors as $advisor): ?>
                <tr class="advisor-row" data-department-id="<?= htmlspecialchars((string)($advisor['DepartmentID'] ?? '')) ?>">
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
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 mt-3 pt-3 border-top">
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="bi bi-trash me-1"></i> <?= htmlspecialchars($t('delete_selected')) ?>
          </button>

          <button type="button" class="btn btn-primary btn-sm" id="editAdvisorBtn">
            <i class="bi bi-pencil-square me-1"></i> <?= htmlspecialchars($t('edit_selected')) ?>
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
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('students')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('manage_enrolled_students')) ?></p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importStudentsCsvModal">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> <?= htmlspecialchars($t('import_csv')) ?>
          </button>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="bi bi-person-plus me-1"></i> <?= htmlspecialchars($t('add_student')) ?>
          </button>
        </div>
      </div>

      <form method="GET" class="mb-3">
        <input type="hidden" name="tab" value="students">
        <input type="hidden" name="section" value="students">

        <div class="d-flex flex-wrap gap-2 mb-3">
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentYearFilterWrap" aria-expanded="false" aria-controls="studentYearFilterWrap">
            <i class="bi bi-calendar3 me-1"></i> <?= htmlspecialchars($t('year_filter')) ?>
          </button>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentDepartmentFilterWrap" aria-expanded="false" aria-controls="studentDepartmentFilterWrap">
            <i class="bi bi-building me-1"></i> <?= htmlspecialchars($t('department_filter')) ?>
          </button>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#studentDegreeFilterWrap" aria-expanded="false" aria-controls="studentDegreeFilterWrap">
            <i class="bi bi-mortarboard me-1"></i> <?= htmlspecialchars($t('degree_filter')) ?>
          </button>
          <button class="btn btn-primary btn-sm" type="submit">
            <i class="bi bi-funnel-fill me-1"></i> <?= htmlspecialchars($t('apply_filters')) ?>
          </button>
          <a href="admin_dashboard.php?section=students" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> <?= htmlspecialchars($t('reset')) ?>
          </a>
        </div>

        <div class="row g-2 align-items-end">
          <div class="col-sm-4 col-md-3 collapse <?= $selectedStudentsYear !== '' ? 'show' : '' ?>" id="studentYearFilterWrap">
            <label for="studentYearFilter" class="form-label mb-1"><?= htmlspecialchars($t('filter_by_year')) ?></label>
            <select class="form-select" id="studentYearFilter" name="student_year">
              <option value="" <?= $selectedStudentsYear === '' ? 'selected' : '' ?>><?= htmlspecialchars($t('all_years')) ?></option>
              <?php foreach ($YearOptions as $yearValue => $yearLabel): ?>
              <option value="<?= htmlspecialchars($yearValue) ?>" <?= (string)$selectedStudentsYear === (string)$yearValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($yearLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-sm-4 col-md-3 collapse <?= $selectedStudentsDepartment > 0 ? 'show' : '' ?>" id="studentDepartmentFilterWrap">
            <label for="studentDepartmentFilter" class="form-label mb-1"><?= htmlspecialchars($t('filter_by_department')) ?></label>
            <select class="form-select" id="studentDepartmentFilter" name="Student_Department">
              <option value="" <?= $selectedStudentsDepartment === 0 ? 'selected' : '' ?>><?= htmlspecialchars($t('all_departments')) ?></option>
              <?php foreach ($DepartmentOptions as $departmentValue => $departmentLabel): ?>
              <option value="<?= htmlspecialchars($departmentValue) ?>" <?= (string)$selectedStudentsDepartment === (string)$departmentValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($departmentLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-sm-4 col-md-3 collapse <?= $selectedStudentsDegree > 0 ? 'show' : '' ?>" id="studentDegreeFilterWrap">
            <label for="studentDegreeFilter" class="form-label mb-1"><?= htmlspecialchars($t('filter_by_degree')) ?></label>
            <select class="form-select" id="studentDegreeFilter" name="Student_Degree" autocomplete="off">
              <option value="0" <?= $selectedStudentsDegree === 0 ? 'selected' : '' ?>><?= htmlspecialchars($t('all_degrees')) ?></option>
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
      <input class="form-control mb-3" id="studentSearch" placeholder="<?= htmlspecialchars($t('search_students')) ?>">

      <form action="../backend/modules/dispatcher.php" method="POST" id="studentForm">
        <input type="hidden" name="action" value="/student/delete">

        <div class="table-responsive" id="studentList">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:36px;"></th>
                <th><?= htmlspecialchars($t('first_name')) ?></th>
                <th><?= htmlspecialchars($t('last_name')) ?></th>
                <th><?= htmlspecialchars($t('id')) ?></th>
                <th><?= htmlspecialchars($t('email')) ?></th>
                <th><?= htmlspecialchars($t('department')) ?></th>
                <th><?= htmlspecialchars($t('degree')) ?></th>
                <th><?= htmlspecialchars($t('year')) ?></th>
                <th><?= htmlspecialchars($t('advisor_id')) ?></th>
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
                <td><?= htmlspecialchars($student['Advisor_ID'] ?? $t('unassigned')) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 mt-3 pt-3 border-top">
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="bi bi-trash me-1"></i> <?= htmlspecialchars($t('delete_selected')) ?>
          </button>
          
          <button type="button" class="btn btn-primary btn-sm" id="editStudentBtn">
            <i class="bi bi-pencil-square me-1"></i> <?= htmlspecialchars($t('edit_selected')) ?>
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
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('admin_control')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('manage_elevated_access_accounts')) ?></p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSuperUserModal">
          <i class="bi bi-shield-plus me-1"></i> <?= htmlspecialchars($t('add_admin')) ?>
        </button>
      </div>

      <input class="form-control mb-3" id="superuserSearch" placeholder="<?= htmlspecialchars($t('search_admins')) ?>">

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
            <i class="bi bi-trash me-1"></i> <?= htmlspecialchars($t('delete_selected')) ?>
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
          <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($t('assign_students_to_advisors')) ?></h5>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= htmlspecialchars($t('assign_subtitle')) ?></p>
        </div>
        <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0 js-confirm-form"
              data-confirm-message="<?= htmlspecialchars($t('run_random_assignment_confirm')) ?>"
              data-confirm-label="<?= htmlspecialchars($t('run_assignment')) ?>"
              data-confirm-type="primary">
          <input type="hidden" name="action" value="/advisor/students/random">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> <?= htmlspecialchars($t('random_assignment')) ?>
          </button>
        </form>
      </div>

      <form method="GET" class="mb-3">
        <input type="hidden" name="tab" value="assignstudents">
        <input type="hidden" name="section" value="assignstudents">

        <div class="d-flex flex-wrap gap-2 mb-3">
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#assignYearFilterWrap" aria-expanded="false" aria-controls="assignYearFilterWrap">
            <i class="bi bi-calendar3 me-1"></i> <?= htmlspecialchars($t('year_filter')) ?>
          </button>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#assignDepartmentFilterWrap" aria-expanded="false" aria-controls="assignDepartmentFilterWrap">
            <i class="bi bi-building me-1"></i> <?= htmlspecialchars($t('department_filter')) ?>
          </button>
          <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#assignDegreeFilterWrap" aria-expanded="false" aria-controls="assignDegreeFilterWrap">
            <i class="bi bi-mortarboard me-1"></i> <?= htmlspecialchars($t('degree_filter')) ?>
          </button>
          <button class="btn btn-primary btn-sm" type="submit">
            <i class="bi bi-funnel-fill me-1"></i> <?= htmlspecialchars($t('apply_filters')) ?>
          </button>
          <a href="admin_dashboard.php?section=assignstudents" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> <?= htmlspecialchars($t('reset')) ?>
          </a>
        </div>

        <div class="row g-2 align-items-end">
          <div class="col-sm-4 col-md-3 collapse <?= $selectedAssignYear !== '' ? 'show' : '' ?>" id="assignYearFilterWrap">
            <label for="assignYearFilter" class="form-label mb-1"><?= htmlspecialchars($t('filter_by_year')) ?></label>
            <select class="form-select" id="assignYearFilter" name="assign_student_year">
              <option value="" <?= $selectedAssignYear === '' ? 'selected' : '' ?>><?= htmlspecialchars($t('all_years')) ?></option>
              <?php foreach ($YearOptions as $yearValue => $yearLabel): ?>
              <option value="<?= htmlspecialchars($yearValue) ?>" <?= (string)$selectedAssignYear === (string)$yearValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($yearLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-sm-4 col-md-3 collapse <?= $selectedAssignDepartment > 0 ? 'show' : '' ?>" id="assignDepartmentFilterWrap">
            <label for="assignDepartmentFilter" class="form-label mb-1"><?= htmlspecialchars($t('filter_by_department')) ?></label>
            <select class="form-select" id="assignDepartmentFilter" name="assign_student_department">
              <option value="" <?= $selectedAssignDepartment === 0 ? 'selected' : '' ?>><?= htmlspecialchars($t('all_departments')) ?></option>
              <?php foreach ($DepartmentOptions as $departmentValue => $departmentLabel): ?>
              <option value="<?= htmlspecialchars($departmentValue) ?>" <?= (string)$selectedAssignDepartment === (string)$departmentValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($departmentLabel) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-sm-4 col-md-3 collapse <?= $selectedAssignDegree > 0 ? 'show' : '' ?>" id="assignDegreeFilterWrap">
            <label for="assignDegreeFilter" class="form-label mb-1"><?= htmlspecialchars($t('filter_by_degree')) ?></label>
            <select class="form-select" id="assignDegreeFilter" name="assign_student_degree" autocomplete="off">
              <option value="0" <?= $selectedAssignDegree === 0 ? 'selected' : '' ?>><?= htmlspecialchars($t('all_degrees')) ?></option>
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
            <span class="badge bg-secondary ms-auto" style="font-size:.72rem;"><?= $assignedToThisAdvisor ?> <?= htmlspecialchars($t('assigned_suffix')) ?></span>
          </div>
              </button>
          </h2>

          <div id="<?= $collapseId ?>" class="accordion-collapse collapse">
            <div class="accordion-body pt-2 pb-3">
              <form action="../backend/modules/dispatcher.php" method="POST">
                <input type="hidden" name="action" value="/advisor/students/assign">
                <input type="hidden" name="advisor_external_id" value="<?= $advisorExternalId ?>">

                <input class="form-control form-control-sm mb-3 assign-search"
                    placeholder="<?= htmlspecialchars($t('filter_students')) ?>">

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
                  <i class="bi bi-check2-circle me-1"></i> <?= htmlspecialchars($t('save_assignment')) ?>
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
              <canvas id="advisorPieChart"></canvas>
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
 
        <!-- Pass PHP data to JS -->
        <script>
          window.advisorChartData = <?= json_encode(array_values($advisorChartData), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        </script>
 
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
                <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0 js-confirm-form"
                      data-confirm-message="Delete this degree? This cannot be undone."
                      data-confirm-label="Delete Degree"
                      data-confirm-type="danger">
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
                <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0 js-confirm-form"
                      data-confirm-message="Delete this department? This cannot be undone."
                      data-confirm-label="Delete Department"
                      data-confirm-type="danger">
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

<div class="modal fade" id="adminConfirmModal" tabindex="-1" aria-labelledby="adminConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold" id="adminConfirmModalLabel"><?= htmlspecialchars($t('confirm_action')) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-0" id="adminConfirmMessage"><?= htmlspecialchars($t('confirm_continue')) ?></p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t('cancel')) ?></button>
        <button type="button" class="btn btn-danger" id="adminConfirmButton"><?= htmlspecialchars($t('confirm')) ?></button>
      </div>
    </div>
  </div>
</div>


<?php require_once __DIR__ . '/footer/dashboard_footer.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;

function injectCsrfTokenIntoDispatcherForms() {
  document.querySelectorAll('form[action*="dispatcher.php"][method="POST"]').forEach(function (form) {
    if (form.querySelector('input[name="_csrf"]')) {
      return;
    }

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_csrf';
    tokenInput.value = CSRF_TOKEN;
    form.appendChild(tokenInput);
  });
}

let pendingConfirmForm = null;
let pendingConfirmCallback = null;
let adminConfirmModalInstance = null;

function getConfirmButtonClass(confirmType) {
  switch (confirmType) {
    case 'primary':
      return 'btn-primary';
    case 'success':
      return 'btn-success';
    case 'warning':
      return 'btn-warning';
    case 'danger':
    default:
      return 'btn-danger';
  }
}

function openAdminConfirmModal(message, options = {}) {
  const modalElement = document.getElementById('adminConfirmModal');
  const messageElement = document.getElementById('adminConfirmMessage');
  const confirmButton = document.getElementById('adminConfirmButton');

  if (!modalElement || !messageElement || !confirmButton || typeof bootstrap === 'undefined') {
    return;
  }

  if (!adminConfirmModalInstance) {
    adminConfirmModalInstance = new bootstrap.Modal(modalElement);
  }

  const confirmLabel = options.confirmLabel || 'Confirm';
  const confirmType = options.confirmType || 'danger';
  const buttonClasses = ['btn-primary', 'btn-success', 'btn-warning', 'btn-danger'];

  messageElement.textContent = message;
  confirmButton.textContent = confirmLabel;
  confirmButton.classList.remove(...buttonClasses);
  confirmButton.classList.add(getConfirmButtonClass(confirmType));

  pendingConfirmForm = options.form || null;
  pendingConfirmCallback = typeof options.onConfirm === 'function' ? options.onConfirm : null;

  adminConfirmModalInstance.show();
}

function showPageMessage(message, type = 'success') {
  const existing = document.getElementById('pageMessageToast');
  if (existing) {
    existing.remove();
  }

  const box = document.createElement('div');
  box.id = 'pageMessageToast';
  box.className = 'alert alert-' + type + ' position-fixed top-0 end-0 m-3 shadow';
  box.style.zIndex = '9999';
  box.style.minWidth = '260px';
  box.textContent = message;

  document.body.appendChild(box);

  setTimeout(function () {
    box.remove();
  }, 3000);
}

document.addEventListener('DOMContentLoaded', function () {
  injectCsrfTokenIntoDispatcherForms();

  const confirmButton = document.getElementById('adminConfirmButton');
  const modalElement = document.getElementById('adminConfirmModal');

  if (confirmButton) {
    confirmButton.addEventListener('click', function () {
      const formToSubmit = pendingConfirmForm;
      const callbackToRun = pendingConfirmCallback;

      pendingConfirmForm = null;
      pendingConfirmCallback = null;

      if (adminConfirmModalInstance) {
        adminConfirmModalInstance.hide();
      }

      if (formToSubmit) {
        formToSubmit.dataset.skipConfirm = 'true';
        formToSubmit.submit();
        return;
      }

      if (callbackToRun) {
        callbackToRun();
      }
    });
  }

  if (modalElement) {
    modalElement.addEventListener('hidden.bs.modal', function () {
      pendingConfirmForm = null;
      pendingConfirmCallback = null;
    });
  }

  document.querySelectorAll('.js-confirm-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.dataset.skipConfirm === 'true') {
        delete form.dataset.skipConfirm;
        return;
      }

      e.preventDefault();
      openAdminConfirmModal(form.dataset.confirmMessage || 'Are you sure you want to continue?', {
        form: form,
        confirmLabel: form.dataset.confirmLabel || 'Confirm',
        confirmType: form.dataset.confirmType || 'danger'
      });
    });
  });

  document.addEventListener('submit', function (event) {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    if (!form.matches('form[action*="dispatcher.php"][method="POST"]')) {
      return;
    }

    if (form.querySelector('input[name="_csrf"]')) {
      return;
    }

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_csrf';
    tokenInput.value = CSRF_TOKEN;
    form.appendChild(tokenInput);
  }, true);
});

//script to maintain active tab on page reload and handle tab switching with URL
document.addEventListener("DOMContentLoaded", () => {

  const params = new URLSearchParams(window.location.search);
  const tab = params.get("tab");

  if (tab) {
    const btn = document.querySelector(`.tab-btn[data-section="${tab}"]`);
    const panel = document.getElementById("section-" + tab);

    if (btn && panel) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      panel.classList.add('active');
    }
  }

  //Tab switching script
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {

      const section = btn.dataset.section;

      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      document.getElementById('section-' + section).classList.add('active');

      // update URL without reload
      const url = new URL(window.location);
      url.searchParams.set('tab', section);
      window.history.replaceState({}, '', url);

    });
  });

});

//searching advisors script
document.getElementById('advisorSearch').addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.advisor-row').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

//searching students script
document.getElementById('studentSearch').addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.student-row').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

//searching superusers script
document.getElementById('superuserSearch').addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.superuser-row').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

//assignment searching script
document.querySelectorAll('.assign-search').forEach(input => {
  input.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    this.closest('.accordion-body').querySelectorAll('.assign-student-row').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
});

//edit advisor script
const editAdvisorBtn = document.getElementById('editAdvisorBtn');
if (editAdvisorBtn) {
  editAdvisorBtn.addEventListener('click', function () {
    const checked = document.querySelectorAll('input[name="advisor_id[]"]:checked');

    if (checked.length === 0) {
      showPageMessage('Please select one advisor to edit.', 'danger');
      return;
    }

    if (checked.length > 1) {
      showPageMessage('Please select only one advisor to edit.', 'danger');
      return;
    }

    const advisor = checked[0];
    document.getElementById('editAdvisorFirstName').value = advisor.dataset.firstName || '';
    document.getElementById('editAdvisorLastName').value = advisor.dataset.lastName || '';
    document.getElementById('editAdvisorEmail').value = advisor.dataset.email || '';
    document.getElementById('editAdvisorPhone').value = advisor.dataset.phone || '';
    document.getElementById('editAdvisorExternalId').value = advisor.value || '';

    const departmentSelect = document.getElementById('editAdvisorDepartment');
    const departmentId = advisor.dataset.departmentId || '1';
    departmentSelect.value = departmentId;

    if (departmentSelect.value !== departmentId) {
      const option = document.createElement('option');
      option.value = departmentId;
      option.textContent = `Department ${departmentId}`;
      departmentSelect.appendChild(option);
      departmentSelect.value = departmentId;
    }

    const editAdvisorModal = new bootstrap.Modal(document.getElementById('editAdvisorModal'));
    editAdvisorModal.show();
  });
}

//student edit script
const editStudentBtn = document.getElementById('editStudentBtn');
if (editStudentBtn) {
  editStudentBtn.addEventListener('click', function () {
    const checked = document.querySelectorAll('input[name="student_ID[]"]:checked');

    if (checked.length === 0) {
      showPageMessage('Please select one student to edit.', 'danger');
      return;
    }

    if (checked.length > 1) {
      showPageMessage('Please select only one student to edit.', 'danger');
      return;
    }

    const student = checked[0];
    document.getElementById('editStudentExternalId').value = student.dataset.externalId || '';
    document.getElementById('editStudentFirstName').value = student.dataset.firstName || '';
    document.getElementById('editStudentLastName').value = student.dataset.lastName || '';
    document.getElementById('editStudentEmail').value = student.dataset.email || '';
    document.getElementById('editStudentYear').value = student.dataset.year || '';
    document.getElementById('editStudentAdvisor').value = student.dataset.advisorId || '';

    const degreeSelect = document.getElementById('editStudentDegree');
    const degreeId = student.dataset.degreeId || '1';
    degreeSelect.value = degreeId;

    if (degreeSelect.value !== degreeId) {
      const option = document.createElement('option');
      option.value = degreeId;
      option.textContent = `Degree ${degreeId}`;
      degreeSelect.appendChild(option);
      degreeSelect.value = degreeId;
    }

    const editStudentModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    editStudentModal.show();
  });
}

//delete confirmation script
['advisorForm', 'studentForm', 'superuserForm'].forEach(id => {
  const form = document.getElementById(id);
  if (!form) return;
  form.addEventListener('submit', function (e) {
    if (form.dataset.skipConfirm === 'true') {
      delete form.dataset.skipConfirm;
      return;
    }

    const checked = form.querySelectorAll('input[type=checkbox]:checked');
    if (checked.length === 0) {
      e.preventDefault();
      showPageMessage('Please select at least one item to delete.', 'danger');
      return;
    }

    e.preventDefault();
    openAdminConfirmModal(`Delete ${checked.length} selected item(s)? This cannot be undone.`, {
      form: form,
      confirmLabel: 'Delete Selected',
      confirmType: 'danger'
    });
  });
});

//script for maintaining filter collapse state using localstorage
document.addEventListener("DOMContentLoaded", function () {
  const filter = document.getElementById("filterSection");

  if (!filter) return;

  // Restore state
  if (localStorage.getItem("filtersOpen") === "true") {
    filter.classList.add("show");
  }

  // Listen for open
  filter.addEventListener("shown.bs.collapse", function () {
    localStorage.setItem("filtersOpen", "true");
  });

  // Listen for close
  filter.addEventListener("hidden.bs.collapse", function () {
    localStorage.setItem("filtersOpen", "false");
  });
});

//script for maintaining assign filter collapse state using localstorage
document.addEventListener("DOMContentLoaded", function () {
  const assignFilter = document.getElementById("assignFilterSection");

  if (!assignFilter) return;

  if (localStorage.getItem("assignFiltersOpen") === "true") {
    assignFilter.classList.add("show");
  }

  assignFilter.addEventListener("shown.bs.collapse", function () {
    localStorage.setItem("assignFiltersOpen", "true");
  });

  assignFilter.addEventListener("hidden.bs.collapse", function () {
    localStorage.setItem("assignFiltersOpen", "false");
  });
});


// degree edit toggle
function degToggleEdit(id) {
  const item = document.getElementById('degItem-' + id);
  const form = document.getElementById('degForm-' + id);
  const isOpen = item.classList.contains('editing');
  document.querySelectorAll('.deg-list-item.editing').forEach(el => {
    el.classList.remove('editing');
    el.querySelector('.deg-inline-form').style.display = 'none';
  });
  if (!isOpen) {
    item.classList.add('editing');
    form.style.display = 'flex';
    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}
 
// degree search
const degreeSearchInput = document.getElementById('degreeSearch');
if (degreeSearchInput) {
  degreeSearchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.deg-list-item').forEach(item => {
      item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// department edit toggle
function deptToggleEdit(id) {
  const item = document.getElementById('deptItem-' + id);
  const form = document.getElementById('deptForm-' + id);
  const isOpen = item.classList.contains('editing');
  document.querySelectorAll('.deg-list-item.editing').forEach(el => {
    el.classList.remove('editing');
    el.querySelector('.deg-inline-form').style.display = 'none';
  });
  if (!isOpen) {
    item.classList.add('editing');
    form.style.display = 'flex';
    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

// department search
const departmentSearchInput = document.getElementById('departmentSearch');
if (departmentSearchInput) {
  departmentSearchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#departmentEditList .deg-list-item').forEach(item => {
      item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// Advisor Pie Chart
(function () {
  const canvas  = document.getElementById('advisorPieChart');
  if (!Array.isArray(window.advisorChartData) || !canvas) return;

  if (typeof Chart === 'undefined') {
    console.error('Chart.js failed to load. Advisor pie chart cannot render.');
    return;
  }
 
  const COLORS = [
    '#4f46e5','#06b6d4','#10b981','#f59e0b','#ef4444',
    '#8b5cf6','#ec4899','#14b8a6','#f97316','#6366f1'
  ];
 
  const legend  = document.getElementById('advisorLegend');
  const center  = document.getElementById('chartCenterCount');
  const buttons = document.querySelectorAll('.year-filter-btn');
 
  let currentYear = 0; // 0 = all
  let chartInstance = null;
 
  function getCounts(year) {
    return window.advisorChartData.map(a =>
      year === 0 ? a.total : (a.byYear[year] || 0)
    );
  }
 
  function buildLegend(counts, total) {
    if (!legend) return;
    legend.innerHTML = '';

    counts.forEach((c, i) => {
      const a   = window.advisorChartData[i];
      const pct = total > 0 ? Math.round((c / total) * 100) : 0;

      const row = document.createElement('div');
      row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;';

      const left = document.createElement('div');
      left.style.cssText = 'display:flex;align-items:center;gap:10px;flex:1;min-width:0;';

      const swatch = document.createElement('span');
      swatch.style.cssText = `width:12px;height:12px;border-radius:3px;background:${COLORS[i % COLORS.length]};flex-shrink:0;display:inline-block;`;

      const name = document.createElement('span');
      name.style.cssText = 'font-size:.875rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
      name.textContent = String(a?.name ?? '');

      const right = document.createElement('span');
      right.style.cssText = 'font-size:.82rem;color:#6b7280;white-space:nowrap;margin-left:12px;';
      right.textContent = `${c} student${c !== 1 ? 's' : ''} (${pct}%)`;

      left.appendChild(swatch);
      left.appendChild(name);
      row.appendChild(left);
      row.appendChild(right);
      legend.appendChild(row);
    });
  }
 
  function renderChart(year) {
    const counts = getCounts(year);
    const total  = counts.reduce((s, v) => s + v, 0);
    const labels = window.advisorChartData.map(a => a.name);
    const colors = COLORS.slice(0, counts.length);
 
    if (center) center.textContent = total;
    buildLegend(counts, total);
 
    // All-zero → show placeholder
    const displayCounts = total === 0 ? [1] : counts;
    const displayColors = total === 0 ? ['#e5e7eb'] : colors;
    const displayLabels = total === 0 ? ['No data'] : labels;
 
    if (chartInstance) {
      chartInstance.data.labels             = displayLabels;
      chartInstance.data.datasets[0].data   = displayCounts;
      chartInstance.data.datasets[0].backgroundColor = displayColors;
      chartInstance.update();
      return;
    }
 
    chartInstance = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: displayLabels,
        datasets: [{
          data: displayCounts,
          backgroundColor: displayColors,
          borderWidth: 2,
          borderColor: '#fff',
          hoverOffset: 6,
        }]
      },
      options: {
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => {
                if (total === 0) return ' No students assigned';
                const val = counts[ctx.dataIndex];
                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                return ` ${val} student${val !== 1 ? 's' : ''} (${pct}%)`;
              }
            }
          }
        },
        animation: { animateRotate: true, duration: 500 }
      }
    });
  }
 
  // Init
  renderChart(0);
 
  // Year filter buttons
  buttons.forEach(btn => {
    btn.addEventListener('click', function () {
      buttons.forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline-primary');
      });
      this.classList.remove('btn-outline-primary');
      this.classList.add('btn-primary');
      currentYear = parseInt(this.dataset.year, 10) || 0;
      renderChart(currentYear);
    });
  });
})();

</script>

</body>
</html>
