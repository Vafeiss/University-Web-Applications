<?php
/*
  NAME: Admin Appointment Reports CSV Export
  Description: Exports appointment report data to CSV
  Panteleimoni Alexandrou
  06-Apr-2026 v0.1
*/

declare(strict_types=1);

require_once '../backend/modules/AdminAppointmentReportsClass.php';

$appointmentReports = new AdminAppointmentReportsClass();
$appointmentSummary = $appointmentReports->getAppointmentSummary();
$advisorAppointmentCounts = $appointmentReports->getAdvisorAppointmentCounts();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=admin_appointment_reports.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['Appointment Reports Summary']);
fputcsv($output, ['Total Requests', $appointmentSummary['total_requests'] ?? 0]);
fputcsv($output, ['Pending Requests', $appointmentSummary['pending_requests'] ?? 0]);
fputcsv($output, ['Approved Requests', $appointmentSummary['approved_requests'] ?? 0]);
fputcsv($output, ['Declined Requests', $appointmentSummary['declined_requests'] ?? 0]);

fputcsv($output, []);
fputcsv($output, ['Advisor Appointment Report']);
fputcsv($output, ['Advisor ID', 'Advisor Name', 'Total Requests', 'Pending', 'Approved', 'Declined']);

foreach ($advisorAppointmentCounts as $advisor) {
    $advisorName = trim(($advisor['First_name'] ?? '') . ' ' . ($advisor['Last_Name'] ?? ''));

    fputcsv($output, [
        $advisor['Advisor_ID'] ?? '',
        $advisorName,
        $advisor['Total_Requests'] ?? 0,
        $advisor['Pending_Requests'] ?? 0,
        $advisor['Approved_Requests'] ?? 0,
        $advisor['Declined_Requests'] ?? 0
    ]);
}

fclose($output);
exit;