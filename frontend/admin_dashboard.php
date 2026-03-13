<?php
/* Name: Admin_Dashboard
Description: This is the dashboard of the admin.
Paraskevas Vafeiadis
01-Mar-26 v0.1
Inputs: CSV file , Information of advisors
Outputs: Successful messages
Error Messages: If the fields are empty , if not a csv file
Files in Use: AdminClass.php

*/
require_once 'init.php';
require_once '../backend/modules/AdminClass.php';
require_once '../backend/modules/ParticipantsClass.php';

$user = new Admin();
$user->Check_Session("Admin");

$advisors = $user->getAdvisors();
$students = $user->getStudents();
$superusers = $user->getSuperUsers();

$assignAdvisorsResult = $user->getAdvisors();
$assignStudentsResult = $user->getStudents();
$assignAdvisors = $assignAdvisorsResult ? $assignAdvisorsResult->fetch_all(MYSQLI_ASSOC) : [];
$assignStudents = $assignStudentsResult ? $assignStudentsResult->fetch_all(MYSQLI_ASSOC) : [];

$participants = new Participants_Processing();
$assignmentMap = $participants->Get_Student_Advisor();
$studentAssignmentMap = $participants->Assign_Students_Advisors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Administrator Portal</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white shadow-sm">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold">Administrator Portal</span>

    <form action="../backend/modules/dispatcher.php" method="POST">
      <input type="hidden" name="action" value="/logout">
      <button class="btn btn-outline-danger">Logout</button>
    </form>
  </div>
</nav>

<div class="container mt-4">
  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
      <?= htmlspecialchars($_GET['error']) ?>
    </div>
  <?php elseif (isset($_GET['success'])): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($_GET['success']) ?>
    </div>
  <?php endif; ?>


  <ul class="nav nav-tabs" id="adminTabs">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#advisors">
        Manage Advisors
      </button>
    </li>

    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#students">
        Manage Students
      </button>
    </li>

    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#superusers">
        Manage SuperUsers
      </button>
    </li>

    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#assignstudents">
        Assign Students
      </button>
    </li>
  </ul>

  <div class="tab-content mt-3">
    <div class="tab-pane fade show active" id="advisors">

      <div class="row">
        <div class="col-md-6">
          <div class="card mb-3 shadow-sm">
            <div class="card-body">
              <h5>Total Advisors</h5>
              <h2><?= $advisors->num_rows ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card mb-3 shadow-sm">
            <div class="card-body">
              <h5>Total Students</h5>
              <h2><?= $students->num_rows ?></h2>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">Add Advisor</div>
        <div class="card-body">

          <form action="../backend/modules/dispatcher.php" method="post" class="row g-3" enctype="multipart/form-data">
            <input type="hidden" name="action" value="/advisor/add">


            <div class="col-md-6">
              <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
            </div>

            <div class="col-md-6">
              <input type="text" name="external_id" class="form-control" placeholder="Advisor ID">
            </div>
            <div class="col-md-6">
              <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
            </div>

            <div class="col-md-6">
              <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>

            <div class="col-md-6">
              <input type="text" name="phone" class="form-control" placeholder="Phone number (optional)">
            </div>

            <div class="col-md-6">
              <select  name="department" class="form-control" required>
                <option value selected disabled> Select Department</option>
                <option value = "1" > ΗΜΜΗΥ </option>
              </select>
            </div>

            <div class="col-12">
              <button class="btn btn-primary">Register Advisor</button>
            
          </form>
        </div>
      </div>
    </div>

      <div class="card shadow-sm">
        <div class="card-header">Advisors Information</div>

        <ul class="list-group list-group-flush">

          <?php while ($advisor = $advisors->fetch_assoc()): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">

              <div>
                <strong><?= htmlspecialchars($advisor['Advisor_ID'] . ' ' . $advisor['First_name'] . ' ' . $advisor['Last_Name']) ?></strong>
                <br>
                <small><?= htmlspecialchars($advisor['Department_Name']) ?></small>
                <?php if (!empty($advisor['Phone'])): ?>
                  <br>
                  <small>Phone Number: <?= htmlspecialchars($advisor['Phone']) ?></small>
                <?php endif; ?>
              </div>

              <form action="../backend/modules/dispatcher.php" method="post">
                <input type="hidden" name="action" value="/advisor/delete">
                <input type="hidden" name="advisor_id" value="<?= $advisor['Advisor_ID'] ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>

            </li>
          <?php endwhile; ?>

        </ul>

      </div>

    </div>

    <div class="tab-pane fade" id="students">

      <div class="card shadow-sm">
        <div class="card-header">Students</div>

        <ul class="list-group list-group-flush">

          <?php while ($student = $students->fetch_assoc()): ?>
            <li class="list-group-item">
              <?= htmlspecialchars($student['StuExternal_ID'] . ' ' . $student['First_name'] . ' ' . $student['Last_Name']) ?>
              <?php
                $studentId = (int)$student['StuExternal_ID'];
                $advisorIds = $studentAssignmentMap[$studentId] ?? [];
              ?>
              Advisor's ID:
              <?= htmlspecialchars(!empty($advisorIds) ? implode(', ', $advisorIds) : 'Not assigned') ?>

              <form action="../backend/modules/dispatcher.php" method="post">
                <input type="hidden" name="action" value="/student/delete">
                <input type="hidden" name="student_ID" value="<?= $student['Student_ID'] ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>

            </li>
          <?php endwhile; ?>

        </ul>

      </div>

      <div class="card mb-3">
        <div class="card-header">Add Students</div>
          <div class="card-body">

          <form action="../backend/modules/dispatcher.php" method="post" class="row g-3" enctype="multipart/form-data">
            <input type="hidden" name="action" value="/student/add">


            <div class="col-md-6">
              <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
            </div>

            <div class="col-md-6">
              <input type="text" name="external_id" class="form-control" placeholder="Student ID" required>
            </div>
            <div class="col-md-6">
              <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
            </div>

            <div class="col-md-6">
              <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>

            <div class="col-md-6">
              <select name="Degree" class="form-control" required>
                <option value="" selected disabled>Degree</option>
                <option value="1"> Computer Engineer And Infomartics</option>
              </select>
            </div>

            <div class="col-md-6">
              <select name="year" class="form-control" required>
                <option value="" selected disabled>Select Year</option>
                <option value="First">First</option>
                <option value="Second">Second</option>
                <option value="Third">Third</option>
                <option value="Fourth">Fourth</option>
                <option value="Fifth">Fifth</option>
              </select>
            </div>

            <div class="col-md-6">
              <input type="text" name="advisors_id" class="form-control" placeholder="Advisor ID (optional)">
            </div>

            <div class="col-12">
              <button class="btn btn-primary">Register Student</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">Students' CSV File</div>
        <div class="card-body">
          <form action="../backend/modules/dispatcher.php" method="post" class="row g-3" enctype="multipart/form-data">
            <input type="hidden" name="action" value="/student/add">
            <div class="col-12">
              <label for="csv_file" class="form-label">Upload Multiple Students in a .csv Format</label>
              <small class="form-text text-muted d-block mb-2">CSV columns: student_id, first_name, last_name, email, year (required); advisor_id (optional)</small>
              <input type="file" name="csv_file" id="csv_file" accept="text/csv,application/vnd.ms-excel" class="form-control">
            </div>
            <div class="col-12">
              <button class="btn btn-primary">Register Students</button>
            </div>
          </form>
        </div>
      </div>

    </div>

    <div class="tab-pane fade" id="superusers">
      <div class="card mb-3">
            <div class="card-header">Add SuperUser</div>
              <div class="card-body">

            <form action="../backend/modules/dispatcher.php" method="post" class="row g-3" enctype="multipart/form-data">
            <input type="hidden" name="action" value="/superuser/add">

            <div class="col-12">
              <input type="text" name="email" class="form-control" placeholder="Enter Email" required>
              <p>   </p>
              <button class="btn btn-primary">Register SuperUser</button>
            </div>
          </form>
        </div>
      </div>
      
      <div class="card shadow-sm">
        <div class="card-header">SuperUsers</div>
        <ul class="list-group list-group-flush">

          <?php while ($superuser = $superusers->fetch_assoc()): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">

              <div>
                <strong><?= htmlspecialchars($superuser['Email']) ?></strong>
              </div>

              <form action="../backend/modules/dispatcher.php" method="post">
                <input type="hidden" name="action" value="/superuser/delete">
                <input type="hidden" name="User_ID" value="<?= $superuser['User_ID'] ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>

            </li>
          <?php endwhile; ?>
        </ul>
      </div>
    </div>
 
    <div class="tab-pane fade" id="assignstudents">
      <div class="card shadow-sm">
        <div class="card-header">Assign Students to Advisors</div>
        <div class="card-body">
          <?php if (empty($assignAdvisors)): ?>
            <p class="text-muted mb-0">No advisors found.</p>
          <?php else: ?>
            <div class="accordion" id="assignAdvisorAccordion">
              <?php foreach ($assignAdvisors as $advisor): ?>
                <?php
                  $advisorUserId = (int)$advisor['User_ID'];
                  $advisorExternalId = (int)$advisor['Advisor_ID'];
                  $collapseId = 'assignAdvisor' . $advisorUserId;
                  $headingId = 'assignHeading' . $advisorUserId;
                ?>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="<?= $headingId ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                      <?= htmlspecialchars($advisor['Advisor_ID'] . ' ' . $advisor['First_name'] . ' ' . $advisor['Last_Name']) ?>
                    </button>
                  </h2>
                  <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="<?= $headingId ?>" data-bs-parent="#assignAdvisorAccordion">
                    <div class="accordion-body">
                      <form action="../backend/modules/dispatcher.php" method="post">
                        <input type="hidden" name="action" value="/advisor/students/assign">
                        <input type="hidden" name="advisor_external_id" value="<?= $advisorExternalId ?>">

                        <div class="row g-2">
                          <?php foreach ($assignStudents as $student): ?>
                            <?php
                              $studentExternalId = (int)$student['StuExternal_ID'];
                              $checkboxId = 'advisor' . $advisorUserId . 'student' . (int)$student['Student_ID'];
                              $isChecked = isset($assignmentMap[$advisorExternalId]) && isset($assignmentMap[$advisorExternalId][$studentExternalId]);
                            ?>
                            <div class="col-md-6">
                              <div class="form-check border rounded p-2">
                                <input class="form-check-input" type="checkbox" name="student_external_ids[]" value="<?= $studentExternalId ?>" id="<?= $checkboxId ?>" <?= $isChecked ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $checkboxId ?>">
                                  <?= htmlspecialchars($student['StuExternal_ID'] . ' ' . $student['First_name'] . ' ' . $student['Last_Name']) ?>
                                </label>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>

                        <div class="mt-3">
                          <button class="btn btn-primary">Save Assignment</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>