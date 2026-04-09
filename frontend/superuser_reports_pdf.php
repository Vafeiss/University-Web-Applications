<?php
/*
  NAME: Super User Reports PDF Page
  Description: Print-friendly standalone PDF page for super user reports
  Panteleimoni Alexandrou
  06-Apr-2026 v0.1
*/

declare(strict_types=1);

require_once '../backend/modules/SuperUserReportsClass.php';

$reports = new SuperUserReportsClass();

$selectedDepartment = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$selectedDegree = isset($_GET['degree_id']) ? (int)$_GET['degree_id'] : 0;
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;

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
    <title>Super User Reports PDF</title>

    <link rel="stylesheet" href="css/reports_pdf.css">

</head>
<body>

<div class="toolbar">
    <a href="superuser_reports.php" class="btn">Back</a>
    <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="page">
    <div class="header">
        <h1 class="title">Super User Reports</h1>
        <p class="subtitle">Students, advisors and assignment overview</p>
        <div class="meta">
            Generated report for the Academic Advisor System
        </div>
    </div>

    <h2 class="section-title">Summary</h2>

    <div class="summary-grid">
        <div class="summary-card">
            <p class="summary-label">Total Students</p>
            <p class="summary-value"><?= htmlspecialchars((string)$summary['total_students']) ?></p>
        </div>

        <div class="summary-card">
            <p class="summary-label">Total Advisors</p>
            <p class="summary-value"><?= htmlspecialchars((string)$summary['total_advisors']) ?></p>
        </div>

        <div class="summary-card">
            <p class="summary-label">Assigned Students</p>
            <p class="summary-value"><?= htmlspecialchars((string)$summary['assigned_students']) ?></p>
        </div>

        <div class="summary-card">
            <p class="summary-label">Unassigned Students</p>
            <p class="summary-value"><?= htmlspecialchars((string)$summary['unassigned_students']) ?></p>
        </div>
    </div>

    <h2 class="section-title">Advisor Student Counts</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 18%;">Advisor ID</th>
                <th class="name-cell" style="width: 52%;">Advisor Name</th>
                <th style="width: 30%;">Total Students</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($advisorCounts)): ?>
                <?php foreach ($advisorCounts as $advisor): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($advisor['Advisor_ID'] ?? '')) ?></td>
                        <td class="name-cell">
                            <?= htmlspecialchars(trim(($advisor['First_name'] ?? '') . ' ' . ($advisor['Last_Name'] ?? ''))) ?>
                        </td>
                        <td><?= htmlspecialchars((string)($advisor['Total_Students'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No advisor data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2 class="section-title">Filtered Students</h2>

    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th class="name-cell">Student Name</th>
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
                        <td class="name-cell">
                            <?= htmlspecialchars(trim(($student['First_name'] ?? '') . ' ' . ($student['Last_Name'] ?? ''))) ?>
                        </td>
                        <td><?= htmlspecialchars($student['DepartmentName'] ?? '') ?></td>
                        <td><?= htmlspecialchars($student['DegreeName'] ?? '') ?></td>
                        <td><?= htmlspecialchars((string)($student['Year'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($student['Advisor_ID'] ?? 'Unassigned')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No students found for the selected filters.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-note">
        This report summarizes student and advisor assignment activity for super users.
    </div>
</div>

</body>
</html>