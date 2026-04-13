<?php
/*
   NAME: Admin Appointment Reports Page
   Description: Standalone admin page for displaying appointment reports and advisor request statistics without modifying the existing admin dashboard
   Panteleimoni Alexandrou
   06-Apr-2026 v1.0
   Inputs: Session role validation and internal report data retrieval from AdminAppointmentReportsClass
   Outputs: HTML page showing appointment summary statistics and advisor appointment breakdown
   Error Messages: Redirects unauthorized users back to index page with forbidden access control
   Files in use: init.php, UsersClass.php, AdminAppointmentReportsClass.php, admin_appointment_reports.css

   06-Apr-2026 v1.1
   Improved layout and reporting presentation for standalone appointment statistics page
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

require_once 'init.php';
require_once '../backend/modules/UsersClass.php';
require_once '../backend/modules/AdminAppointmentReportsClass.php';

$user = new Users();
$user->Check_Session();

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
if ($role !== 'admin' && $role !== 'superuser') {
    header('Location: index.php?error=forbidden');
    exit;
}

$backHref = $role === 'superuser'
    ? 'superuser_reports.php'
    : 'admin_dashboard.php?tab=statistics';

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
    <link rel="stylesheet" href="css/admin_appointment_reports.css">

</head>
<body>

<header class="top-navbar">
    <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

    <div class="navbar-center">
        <span class="welcome-text">Appointment Reports 📊</span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#manualInstructionsModal">
            <i class="bi bi-journal-text me-1"></i>Manual
        </button>
        <a href="admin_appointment_reports_export_csv.php" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> Export CSV
        </a>
        <a href="admin_appointment_reports_pdf.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
        <a href="<?= htmlspecialchars($backHref) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</header>

<div class="modal fade" id="manualInstructionsModal" tabindex="-1" aria-labelledby="manualInstructionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="manualInstructionsModalLabel">
                    <i class="bi bi-info-circle me-2 text-primary"></i>Appointment Reports Manual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <ol class="mb-0 ps-3">
                    <li>Use Export CSV to download the appointment report data.</li>
                    <li>Use PDF to generate a printable report.</li>
                    <li>Use Back to return to the main admin dashboard.</li>
                </ol>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>