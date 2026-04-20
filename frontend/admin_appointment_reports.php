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

   20-Apr-2026 v1.2
   Added EN/EL translation support for appointment reports page interface text
   Panteleimoni Alexandrou

   20-Apr-2026 v1.3
   Added EN/EL toggle button and fixed translation binding flow for admin appointment reports page
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

require_once 'init.php';
require_once '../backend/modules/UsersClass.php';
require_once '../backend/modules/AdminAppointmentReportsClass.php';

if (isset($_GET['set_lang']) && in_array((string)$_GET['set_lang'], ['en', 'el'], true)) {
    $_SESSION['management_dashboard_lang'] = (string)$_GET['set_lang'];
    $redirectParams = $_GET;
    unset($redirectParams['set_lang']);
    $redirectUrl = basename((string)($_SERVER['PHP_SELF'] ?? 'admin_appointment_reports.php'));
    if ($redirectParams !== []) {
        $redirectUrl .= '?' . http_build_query($redirectParams);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

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

    $path = basename((string)($_SERVER['PHP_SELF'] ?? 'admin_appointment_reports.php'));
    return $path . ($params !== [] ? '?' . http_build_query($params) : '');
};

$toggleLang = $lang === 'en' ? 'el' : 'en';
$toggleUrl = $buildCurrentUrl(['set_lang' => $toggleLang]);
$langButtonLabel = $lang === 'en' ? 'EN / EL' : 'EL / EN';

$translations = [
    'en' => [
        'page_title' => 'Admin Appointment Reports',
        'appointment_reports' => 'Appointment Reports',
        'manual' => 'Manual',
        'export_csv' => 'Export CSV',
        'pdf' => 'PDF',
        'back' => 'Back',
        'manual_title' => 'Appointment Reports Manual',
        'manual_item_1' => 'Use Export CSV to download the appointment report data.',
        'manual_item_2' => 'Use PDF to generate a printable report.',
        'manual_item_3' => 'Use Back to return to the main admin dashboard.',
        'close' => 'Close',
        'overview_title' => 'Appointment System Overview',
        'overview_subtitle' => 'Admin page for appointment request statistics and advisor report overview.',
        'total_requests' => 'Total Requests',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'declined' => 'Declined',
        'advisor_report_title' => 'Advisor Appointment Report',
        'advisor_report_subtitle' => 'Request counts grouped by advisor.',
        'advisor_id' => 'Advisor ID',
        'advisor_name' => 'Advisor Name',
        'no_report_data' => 'No appointment report data found.',
    ],
    'el' => [
        'page_title' => 'Αναφορές Ραντεβού Διαχειριστή',
        'appointment_reports' => 'Αναφορές Ραντεβού',
        'manual' => 'Οδηγός',
        'export_csv' => 'Εξαγωγή CSV',
        'pdf' => 'PDF',
        'back' => 'Πίσω',
        'manual_title' => 'Οδηγός Αναφορών Ραντεβού',
        'manual_item_1' => 'Χρησιμοποιήστε το Εξαγωγή CSV για λήψη των δεδομένων της αναφοράς ραντεβού.',
        'manual_item_2' => 'Χρησιμοποιήστε το PDF για δημιουργία εκτυπώσιμης αναφοράς.',
        'manual_item_3' => 'Χρησιμοποιήστε το Πίσω για να επιστρέψετε στον κύριο πίνακα διαχείρισης.',
        'close' => 'Κλείσιμο',
        'overview_title' => 'Επισκόπηση Συστήματος Ραντεβού',
        'overview_subtitle' => 'Σελίδα διαχειριστή για στατιστικά αιτημάτων ραντεβού και επισκόπηση αναφορών συμβούλων.',
        'total_requests' => 'Σύνολο Αιτημάτων',
        'pending' => 'Εκκρεμή',
        'approved' => 'Εγκεκριμένα',
        'declined' => 'Απορριφθέντα',
        'advisor_report_title' => 'Αναφορά Ραντεβού Συμβούλων',
        'advisor_report_subtitle' => 'Μετρήσεις αιτημάτων ομαδοποιημένες ανά σύμβουλο.',
        'advisor_id' => 'Κωδικός Συμβούλου',
        'advisor_name' => 'Όνομα Συμβούλου',
        'no_report_data' => 'Δεν βρέθηκαν δεδομένα αναφορών ραντεβού.',
    ],
];

$t = static function (string $key) use ($translations, $lang): string {
    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
};

$appointmentReports = new AdminAppointmentReportsClass();
$appointmentSummary = $appointmentReports->getAppointmentSummary();
$advisorAppointmentCounts = $appointmentReports->getAdvisorAppointmentCounts();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t('page_title')) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_appointment_reports.css">

</head>
<body>

<header class="top-navbar">
    <img src="../documents/tepaklogo.png" alt="Logo" class="logo">

    <div class="navbar-center">
        <span class="welcome-text"><?= htmlspecialchars($t('appointment_reports')) ?> 📊</span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="<?= htmlspecialchars($toggleUrl) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1">
            <i class="bi bi-globe2 me-1"></i><?= htmlspecialchars($langButtonLabel) ?>
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#manualInstructionsModal">
            <i class="bi bi-journal-text me-1"></i><?= htmlspecialchars($t('manual')) ?>
        </button>
        <a href="admin_appointment_reports_export_csv.php" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> <?= htmlspecialchars($t('export_csv')) ?>
        </a>
        <a href="admin_appointment_reports_pdf.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> <?= htmlspecialchars($t('pdf')) ?>
        </a>
        <a href="<?= htmlspecialchars($backHref) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> <?= htmlspecialchars($t('back')) ?>
        </a>
    </div>
</header>

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
                <ol class="mb-0 ps-3">
                    <li><?= htmlspecialchars($t('manual_item_1')) ?></li>
                    <li><?= htmlspecialchars($t('manual_item_2')) ?></li>
                    <li><?= htmlspecialchars($t('manual_item_3')) ?></li>
                </ol>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= htmlspecialchars($t('close')) ?></button>
            </div>
        </div>
    </div>
</div>

<main class="container py-4" style="max-width: 1150px;">

    <div class="page-card mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="section-title mb-1"><?= htmlspecialchars($t('overview_title')) ?></h3>
                <p class="page-subtitle"><?= htmlspecialchars($t('overview_subtitle')) ?></p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <p class="stat-label"><?= htmlspecialchars($t('total_requests')) ?></p>
                <p class="stat-value text-dark">
                    <?= htmlspecialchars((string)($appointmentSummary['total_requests'] ?? 0)) ?>
                </p>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card">
                <p class="stat-label"><?= htmlspecialchars($t('pending')) ?></p>
                <p class="stat-value text-warning">
                    <?= htmlspecialchars((string)($appointmentSummary['pending_requests'] ?? 0)) ?>
                </p>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card">
                <p class="stat-label"><?= htmlspecialchars($t('approved')) ?></p>
                <p class="stat-value text-success">
                    <?= htmlspecialchars((string)($appointmentSummary['approved_requests'] ?? 0)) ?>
                </p>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card">
                <p class="stat-label"><?= htmlspecialchars($t('declined')) ?></p>
                <p class="stat-value text-danger">
                    <?= htmlspecialchars((string)($appointmentSummary['declined_requests'] ?? 0)) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="page-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-semibold"><?= htmlspecialchars($t('advisor_report_title')) ?></h5>
                <p class="text-muted mb-0" style="font-size:.85rem;">
                    <?= htmlspecialchars($t('advisor_report_subtitle')) ?>
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= htmlspecialchars($t('advisor_id')) ?></th>
                        <th><?= htmlspecialchars($t('advisor_name')) ?></th>
                        <th><?= htmlspecialchars($t('total_requests')) ?></th>
                        <th><?= htmlspecialchars($t('pending')) ?></th>
                        <th><?= htmlspecialchars($t('approved')) ?></th>
                        <th><?= htmlspecialchars($t('declined')) ?></th>
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
                                <?= htmlspecialchars($t('no_report_data')) ?>
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
