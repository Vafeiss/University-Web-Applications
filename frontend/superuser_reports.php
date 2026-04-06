<?php
/*
  NAME: Super User Reports Page
  Description: Standalone super user reports page with filters, summary cards, chart and tables
  Panteleimoni Alexandrou
  06-Apr-2026 v0.1
*/

declare(strict_types=1);

require_once '../backend/modules/SuperUserReportsClass.php';

$reports = new SuperUserReportsClass();

$selectedDepartment = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$selectedDegree = isset($_GET['degree_id']) ? (int)$_GET['degree_id'] : 0;
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;

$departments = $reports->getDepartments();
$degrees = $reports->getDegrees($selectedDepartment > 0 ? $selectedDepartment : null);

$summary = $reports->getSummary(
    $selectedDepartment > 0 ? $selectedDepartment : null,
    $selectedDegree > 0 ? $selectedDegree : null,
    $selectedYear > 0 ? $selectedYear : null
);

$students = $reports->getFilteredStudents(
    $selectedDepartment > 0 ? $selectedDepartment : null,
    $selectedDegree > 0 ? $selectedDegree : null,
    $selectedYear > 0 ? $selectedYear : null
);

$advisorCounts = $reports->getAdvisorStudentCounts(
    $selectedDepartment > 0 ? $selectedDepartment : null,
    $selectedDegree > 0 ? $selectedDegree : null,
    $selectedYear > 0 ? $selectedYear : null
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super User Reports</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        body {
            background: #f8f9fa;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .page-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 1.25rem 1.5rem;
            height: 100%;
        }

        .stat-label {
            font-size: .8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin: 0 0 .35rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: .25rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: .95rem;
            margin-bottom: 0;
        }

        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .chart-wrap {
            max-width: 360px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<main class="container py-4" style="max-width: 1200px;">

    <div class="page-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="section-title mb-1">Super User Reports</h2>
                <p class="page-subtitle">Students, advisors, assignments, filters and visual overview.</p>
            </div>

            <div class="top-actions">
                <a href="superuser_reports_pdf.php" class="btn btn-outline-dark">Export PDF</a>
            </div>
        </div>
    </div>

    <div class="page-card">
        <h5 class="fw-semibold mb-3">Filters</h5>

        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= htmlspecialchars((string)$department['DepartmentID']) ?>"
                            <?= $selectedDepartment === (int)$department['DepartmentID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($department['DepartmentName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Degree</label>
                <select name="degree_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">All Degrees</option>
                    <?php foreach ($degrees as $degree): ?>
                        <option value="<?= htmlspecialchars((string)$degree['DegreeID']) ?>"
                            <?= $selectedDegree === (int)$degree['DegreeID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($degree['DegreeName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Year</label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <option value="0">All Years</option>
                    <option value="1" <?= $selectedYear === 1 ? 'selected' : '' ?>>Year 1</option>
                    <option value="2" <?= $selectedYear === 2 ? 'selected' : '' ?>>Year 2</option>
                    <option value="3" <?= $selectedYear === 3 ? 'selected' : '' ?>>Year 3</option>
                    <option value="4" <?= $selectedYear === 4 ? 'selected' : '' ?>>Year 4</option>
                    <option value="5" <?= $selectedYear === 5 ? 'selected' : '' ?>>Year 5</option>
                    <option value="6" <?= $selectedYear === 6 ? 'selected' : '' ?>>Year 6</option>
                </select>
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
            <div class="page-card h-100">
                <h5 class="fw-semibold mb-3">Assignment Pie Chart</h5>
                <div class="chart-wrap">
                    <canvas id="assignmentChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="page-card h-100">
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

    <div class="page-card mt-4">
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

</main>

<script>
const assignedStudents = <?= (int)$summary['assigned_students'] ?>;
const unassignedStudents = <?= (int)$summary['unassigned_students'] ?>;

new Chart(document.getElementById('assignmentChart'), {
    type: 'pie',
    data: {
        labels: ['Assigned Students', 'Unassigned Students'],
        datasets: [{
            data: [assignedStudents, unassignedStudents]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

</body>
</html>