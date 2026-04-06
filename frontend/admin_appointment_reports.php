<?php
/*
  NAME: Admin Appointment Reports Page
  Description: Standalone admin page for appointment reports without modifying the existing admin dashboard
  Panteleimoni Alexandrou
  06-Apr-2026 v0.2
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
    <title>Admin Appointment Reports</title>

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

        .logo {
            height: 70px;
            width: auto;
            object-fit: contain;
        }

        .welcome-text {
            font-weight: 750;
            font-size: 26px;
            color: #555;
        }

        .page-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
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

        .page-subtitle {
            color: #6b7280;
            font-size: .95rem;
            margin-bottom: 0;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: .25rem;
        }
    </style>
</head>
<body>

<header class="top-navbar">
    <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

    <div class="navbar-center">
        <span class="welcome-text">Appointment Reports 📊</span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="admin_dashboard.php?tab=statistics" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</header>

<main class="container py-4" style="max-width: 1150px;">

    <div class="page-card mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="section-title mb-1">Appointment System Overview</h3>
                <p class="page-subtitle">Standalone admin page for appointment request statistics and advisor report overview.</p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <p class="stat-label">Total Requests</p>
                <p class="stat-value text-dark">
                    <?= htmlspecialchars((string)($appointmentSummary['total_requests'] ?? 0)) ?>
                </p>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card">
                <p class="stat-label">Pending</p>
                <p class="stat-value text-warning">
                    <?= htmlspecialchars((string)($appointmentSummary['pending_requests'] ?? 0)) ?>
                </p>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card">
                <p class="stat-label">Approved</p>
                <p class="stat-value text-success">
                    <?= htmlspecialchars((string)($appointmentSummary['approved_requests'] ?? 0)) ?>
                </p>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card">
                <p class="stat-label">Declined</p>
                <p class="stat-value text-danger">
                    <?= htmlspecialchars((string)($appointmentSummary['declined_requests'] ?? 0)) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="page-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-semibold">Advisor Appointment Report</h5>
                <p class="text-muted mb-0" style="font-size:.85rem;">
                    Request counts grouped by advisor.
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Advisor ID</th>
                        <th>Advisor Name</th>
                        <th>Total Requests</th>
                        <th>Pending</th>
                        <th>Approved</th>
                        <th>Declined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($advisorAppointmentCounts)): ?>
                        <?php foreach ($advisorAppointmentCounts as $advisor): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($advisor['Advisor_ID'] ?? '')) ?></td>
                                <td>
                                    <?= htmlspecialchars(trim(($advisor['First_name'] ?? '') . ' ' . ($advisor['Last_Name'] ?? ''))) ?>
                                </td>
                                <td><?= htmlspecialchars((string)($advisor['Total_Requests'] ?? 0)) ?></td>
                                <td class="text-warning fw-semibold">
                                    <?= htmlspecialchars((string)($advisor['Pending_Requests'] ?? 0)) ?>
                                </td>
                                <td class="text-success fw-semibold">
                                    <?= htmlspecialchars((string)($advisor['Approved_Requests'] ?? 0)) ?>
                                </td>
                                <td class="text-danger fw-semibold">
                                    <?= htmlspecialchars((string)($advisor['Declined_Requests'] ?? 0)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No appointment report data found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

</body>
</html>