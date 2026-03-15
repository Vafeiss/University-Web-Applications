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
*/

require_once 'init.php';
require_once '../backend/modules/AdminClass.php';
require_once '../backend/modules/ParticipantsClass.php';

$user = new Admin();
$user->Check_Session('Admin');

//get result sets
$advisors        = $user->getAdvisors();
$students        = $user->getStudents();
$superusers      = $user->getSuperUsers();

//get arrays for assignment tab
$assignAdvisorsResult = $user->getAdvisors();
$assignStudentsResult = $user->getStudents();
$assignAdvisors  = $assignAdvisorsResult ? $assignAdvisorsResult->fetch_all(MYSQLI_ASSOC) : [];
$assignStudents  = $assignStudentsResult ? $assignStudentsResult->fetch_all(MYSQLI_ASSOC) : [];

//get statistics 
$allAdvisors = $assignAdvisors;
$allStudents = $assignStudents;
$superusersArr = $user->getSuperUsers();
$allSuperusers = $superusersArr ? $superusersArr->fetch_all(MYSQLI_ASSOC) : [];

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administrator Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; }

    /*navbar css*/
    .top-navbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 0 1.5rem; height: 64px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .brand-badge { background: #4f46e5; color: #fff; font-weight: 600; font-size: .8rem; padding: .3rem .7rem; border-radius: 6px; letter-spacing: .05em; }
    .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #ede9fe; color: #6d28d9; font-weight: 600; display: flex; align-items: center; justify-content: center; font-size: .9rem; }

    /*tab bar css*/
    .tab-bar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 0 1.5rem; display: flex; gap: .25rem; justify-content: center; }
    .tab-btn { border: none; background: none; padding: 1rem .75rem; font-size: .95rem; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; display: flex; align-items: center; gap: .4rem; transition: color .15s; }
    .tab-btn:hover { color: #111827; }
    .tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; font-weight: 500; }

    /*card css*/
    .section-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 1.5rem; }

    /*lists item css*/
    .list-item { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    .list-item:last-child { border-bottom: none; }
    .list-item:hover { background: #f9fafb; }
    .item-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: .95rem; flex-shrink: 0; }
    .avatar-indigo { background: #ede9fe; color: #6d28d9; }
    .avatar-green  { background: #d1fae5; color: #065f46; }
    .avatar-amber  { background: #fef3c7; color: #92400e; }
    .item-meta { flex: 1; min-width: 0; }
    .item-meta .name { font-weight: 500; color: #111827; font-size: .95rem; margin: 0; }
    .item-meta .sub  { color: #6b7280; font-size: .82rem; margin: .1rem 0 0; }
    .item-meta .sub-warn { color: #ef4444; font-size: .82rem; margin: .1rem 0 0; }

    /*stat cards*/
    .stat-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 1.25rem 1.5rem; }
    .stat-label { font-size: .8rem; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 .35rem; }
    .stat-value { font-size: 2rem; font-weight: 600; color: #111827; margin: 0; line-height: 1; }

    /*accordion css*/
    .accordion-button:not(.collapsed) { background: #f5f3ff; color: #4f46e5; box-shadow: none; }
    .accordion-button:focus { box-shadow: none; }

    /*section css*/
    .section-panel { display: none; }
    .section-panel.active { display: block; }

    /*flash toast css*/
    .flash-toast { position: fixed; top: 1rem; right: 1rem; z-index: 9999; min-width: 280px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,.12); padding: .85rem 1.1rem; display: flex; align-items: center; gap: .6rem; font-size: .92rem; }
  </style>
</head>
<body>

<?php if ($flash): ?>
<div class="flash-toast alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> mb-0" id="flashToast">
  <i class="bi bi-<?= $flashType === 'error' ? 'x-circle' : 'check-circle' ?>-fill"></i>
  <?= htmlspecialchars($flash) ?>
</div>
<script>setTimeout(() => document.getElementById('flashToast')?.remove(), 3500);</script>
<?php endif; ?>


<!-- navigation bar -->
<header class="top-navbar">

  <span class="brand-badge">ADMIN</span>

  <div class="d-flex align-items-center gap-3">
    <i class="bi bi-question-circle text-secondary fs-5" title="Help"></i>
    <div class="user-avatar">A</div>
    <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0">
      <input type="hidden" name="action" value="/logout">
      <button class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Logout
      </button>
    </form>
  </div>

</header>


<!-- tab bar -->
<div class="tab-bar">
  <button class="tab-btn <?= $activeSection === 'advisors'      ? 'active' : '' ?>" data-section="advisors">
    <i class="bi bi-person-badge"></i> Advisors
  </button>
  <button class="tab-btn <?= $activeSection === 'students'      ? 'active' : '' ?>" data-section="students">
    <i class="bi bi-people"></i> Students
  </button>
  <button class="tab-btn <?= $activeSection === 'superusers'    ? 'active' : '' ?>" data-section="superusers">
    <i class="bi bi-shield-lock"></i> SuperUsers
  </button>
  <button class="tab-btn <?= $activeSection === 'assignstudents'? 'active' : '' ?>" data-section="assignstudents">
    <i class="bi bi-diagram-3"></i> Assignments
  </button>
  <button class="tab-btn <?= $activeSection === 'statistics'    ? 'active' : '' ?>" data-section="statistics">
    <i class="bi bi-bar-chart-line"></i> Statistics
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
              <?php while ($advisor = $advisors->fetch_assoc()): ?>
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
                         data-department-id="<?= htmlspecialchars((string)($advisor['Department_ID'] ?? '1')) ?>">
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
                <th>Degree</th>
                <th>Year</th>
                <th>Advisor ID</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($student = $students->fetch_assoc()): ?>
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
                         data-degree-id="<?= htmlspecialchars((string)($student['Degree_ID'] ?? '1')) ?>"
                         data-year="<?= htmlspecialchars((string)($student['Year'] ?? '')) ?>"
                         data-advisor-id="<?= htmlspecialchars((string)($student['Advisor_ID'] ?? '')) ?>">
                </td>
                <td><?= htmlspecialchars($student['First_name']) ?></td>
                <td><?= htmlspecialchars($student['Last_Name']) ?></td>
                <td><?= htmlspecialchars($student['StuExternal_ID']) ?></td>
                <td><?= htmlspecialchars($student['Email']) ?></td>
                <td><?= htmlspecialchars($student['Degree']) ?></td>
                <td><?= htmlspecialchars($student['Year'] ?? '') ?></td>
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
          <h5 class="mb-0 fw-semibold">SuperUsers</h5>
          <p class="text-muted mb-0" style="font-size:.85rem;">Manage elevated access accounts</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSuperUserModal">
          <i class="bi bi-shield-plus me-1"></i> Add SuperUser
        </button>
      </div>

      <input class="form-control mb-3" id="superuserSearch" placeholder="Search superusers…">

      <form action="../backend/modules/dispatcher.php" method="POST" id="superuserForm">
        <input type="hidden" name="action" value="/superuser/delete">

        <div id="superuserList">
          <?php while ($superuser = $superusers->fetch_assoc()):
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
        <form action="../backend/modules/dispatcher.php" method="POST" class="mb-0" onsubmit="return confirm('Run random assignment for all students?');">
          <input type="hidden" name="action" value="/advisor/students/random">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Random Assignment
          </button>
        </form>
      </div>

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
            <button class="accordion-button collapsed d-flex align-items-center gap-2"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= $collapseId ?>">
              <div class="item-avatar avatar-indigo" style="width:32px;height:32px;font-size:.8rem;"><?= $initials ?></div>
              <div>
                <span class="fw-medium"><?= $advisorName ?></span>
                <span class="badge bg-secondary ms-2" style="font-size:.72rem;"><?= $assignedToThisAdvisor ?> assigned</span>
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
                  <?php foreach ($assignStudents as $student):
                    $sName = htmlspecialchars($student['First_name'] . ' ' . $student['Last_Name']);
                    $sId   = htmlspecialchars($student['StuExternal_ID']);
                    $sYear = htmlspecialchars($student['Year'] ?? '');
                  ?>
                  <div class="form-check assign-student-row py-1 border-bottom">
                        <?php $isChecked = isset($assignmentMap[$advisorExternalId]) && isset($assignmentMap[$advisorExternalId][(int)$sId]); ?>
                    <input class="form-check-input"
                           type="checkbox"
                           name="student_external_ids[]"
                           value="<?= $sId ?>"
                          id="stu_<?= $advisorUserId ?>_<?= $sId ?>"
                          <?= $isChecked ? 'checked' : '' ?>>
                    <label class="form-check-label" for="stu_<?= $advisorUserId ?>_<?= $sId ?>">
                      <span class="fw-medium"><?= $sName ?></span>
                      <span class="text-muted ms-1" style="font-size:.8rem;">(<?= $sId ?>)</span>
                      <?php if ($sYear): ?>
                        <span class="badge bg-secondary ms-2" style="font-size:.72rem;"><?= $sYear ?></span>
                      <?php endif; ?>
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
      <h5 class="fw-semibold mb-4">Advisor Load</h5>
      <?php foreach ($allAdvisors as $advisor):
        $advisorExternalId = (int)$advisor['Advisor_ID'];
        $count = 0;
        if (isset($assignmentMap[$advisorExternalId]) && is_array($assignmentMap[$advisorExternalId])) {
          $count = count($assignmentMap[$advisorExternalId]);
        }
        $pct = $totalStudents > 0 ? round(($count / $totalStudents) * 100) : 0;
        $advisorName = htmlspecialchars($advisor['First_name'] . ' ' . $advisor['Last_Name']);
      ?>
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
          <span style="font-size:.9rem;"><?= $advisorName ?></span>
          <span class="text-muted" style="font-size:.85rem;"><?= $count ?> students (<?= $pct ?>%)</span>
        </div>
        <div class="progress" style="height:8px;border-radius:999px;">
          <div class="progress-bar bg-primary" style="width:<?= $pct ?>%;border-radius:999px;"></div>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (empty($allAdvisors)): ?>
        <p class="text-muted text-center py-4">No advisor data available.</p>
      <?php endif; ?>
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
                <option value="1">HMMHY</option>
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
                <option value="1">HMMHY</option>
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
                <option value="1">Computer Engineer & Informatics</option>
                <option value="2">Electrical Engineering</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Assign Advisor <span class="text-muted">(optional)</span></label>
              <select name="advisor_id" id="editStudentAdvisor" class="form-select">
                <option value=" ">No advisor</option>
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
                <option value="1">Computer Engineer & Informatics</option>
                <option value="2">Electrical Engineering</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Assign Advisor <span class="text-muted">(optional)</span></label>
              <select name="advisor_id" class="form-select">
                <option value=" " selected>No advisor</option>
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

//Tab switching script
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const section = btn.dataset.section;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('section-' + section).classList.add('active');
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
      alert('Please select one advisor to edit.');
      return;
    }

    if (checked.length > 1) {
      alert('Please select only one advisor to edit.');
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
      alert('Please select one student to edit.');
      return;
    }

    if (checked.length > 1) {
      alert('Please select only one student to edit.');
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
    const checked = form.querySelectorAll('input[type=checkbox]:checked');
    if (checked.length === 0) {
      e.preventDefault();
      alert('Please select at least one item to delete.');
      return;
    }
    if (!confirm(`Delete ${checked.length} selected item(s)? This cannot be undone.`)) {
      e.preventDefault();
    }
  });
});
</script>

</body>
</html>