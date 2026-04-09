<?php
/*
  NAME: Admin Appointment Reports PDF Page
  Description: Print-friendly standalone page for saving appointment reports as PDF
  Panteleimoni Alexandrou
  06-Apr-2026 v0.1
*/

declare(strict_types=1);

require_once '../backend/modules/AdminAppointmentReportsClass.php';

$appointmentReports = new AdminAppointmentReportsClass();
$appointmentSummary = $appointmentReports->getAppointmentSummary();
$advisorAppointmentCounts = $appointmentReports->getAdvisorAppointmentCounts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Reports PDF</title>

    <link rel="stylesheet" href="css/reports_pdf.css">

</head>
<body>

<div class="toolbar">
    <a href="admin_appointment_reports.php" class="btn">Back</a>
    <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="page">
    <div class="header">
        <h1 class="title">Appointment Reports</h1>
        <p class="subtitle">Administrative overview of appointment request activity</p>
        <div class="meta">
            Generated report for the Academic Advisor System
        </div>
    </div>

    <h2 class="section-title">Summary</h2>

    <div class="summary-grid">
        <div class="summary-card">
            <p class="summary-label">Total Requests</p>
            <p class="summary-value"><?= htmlspecialchars((string)($appointmentSummary['total_requests'] ?? 0)) ?></p>
        </div>

        <div class="summary-card">
            <p class="summary-label">Pending Requests</p>
            <p class="summary-value"><?= htmlspecialchars((string)($appointmentSummary['pending_requests'] ?? 0)) ?></p>
        </div>

        <div class="summary-card">
            <p class="summary-label">Approved Requests</p>
            <p class="summary-value"><?= htmlspecialchars((string)($appointmentSummary['approved_requests'] ?? 0)) ?></p>
        </div>

        <div class="summary-card">
            <p class="summary-label">Declined Requests</p>
            <p class="summary-value"><?= htmlspecialchars((string)($appointmentSummary['declined_requests'] ?? 0)) ?></p>
        </div>
    </div>

    <h2 class="section-title">Advisor Appointment Breakdown</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Advisor ID</th>
                <th class="name-cell" style="width: 28%;">Advisor Name</th>
                <th style="width: 15%;">Total</th>
                <th style="width: 15%;">Pending</th>
                <th style="width: 15%;">Approved</th>
                <th style="width: 15%;">Declined</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($advisorAppointmentCounts)): ?>
                <?php foreach ($advisorAppointmentCounts as $advisor): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($advisor['Advisor_ID'] ?? '')) ?></td>
                        <td class="name-cell">
                            <?= htmlspecialchars(trim(($advisor['First_name'] ?? '') . ' ' . ($advisor['Last_Name'] ?? ''))) ?>
                        </td>
                        <td><?= htmlspecialchars((string)($advisor['Total_Requests'] ?? 0)) ?></td>
                        <td><?= htmlspecialchars((string)($advisor['Pending_Requests'] ?? 0)) ?></td>
                        <td><?= htmlspecialchars((string)($advisor['Approved_Requests'] ?? 0)) ?></td>
                        <td><?= htmlspecialchars((string)($advisor['Declined_Requests'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No appointment report data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-note">
        This report summarizes appointment request activity for administrators.
    </div>
</div>

</body>
</html>