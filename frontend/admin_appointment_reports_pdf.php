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

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            margin: 0;
            background: #f5f5f5;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 18mm 16mm;
            box-sizing: border-box;
        }

        .toolbar {
            width: 210mm;
            margin: 20px auto 10px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            border: 1px solid #ccc;
            background: white;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: #222;
        }

        .btn-primary {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        .header {
            border-bottom: 2px solid #1f2937;
            padding-bottom: 12px;
            margin-bottom: 22px;
        }

        .title {
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }

        .subtitle {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .meta {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin: 26px 0 12px 0;
            color: #111827;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .summary-card {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 14px 16px;
            background: #fafafa;
        }

        .summary-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
            margin: 0 0 8px 0;
            letter-spacing: .05em;
        }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }

        thead {
            background: #f3f4f6;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 10px 8px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            font-weight: 700;
        }

        td.name-cell, th.name-cell {
            text-align: left;
        }

        .footer-note {
            margin-top: 28px;
            font-size: 11px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        @media print {
            body {
                background: white;
            }

            .toolbar {
                display: none;
            }

            .page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }

            @page {
                size: A4;
                margin: 16mm;
            }
        }
    </style>
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