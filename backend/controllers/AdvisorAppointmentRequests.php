<?php
/*
   NAME: Advisor Appointment Requests
   Description: This page displays all pending appointment requests for the advisor and allows approval or decline actions
   Panteleimoni Alexandrou
   30-Mar-2026 v1.0
   Inputs: Advisor ID from session or test variable
   Outputs: Displays pending appointment requests and provides approve/decline functionality
   Error Messages: Displays notifications using NotificationsClass for success and error actions
   Files in use: AppointmentApprovalClass.php, dispatcher.php, NotificationsClass.php

   13-Apr-2026 v1.1
   Replaced URL-based messages with centralized notification system and improved UI actions
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../modules/AppointmentApprovalClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';

$appointmentApproval = new AppointmentApproval();

$advisorId = 2;
$advisorName = "Advisor Test User";

$requests = [];
if ($advisorId > 0) {
    $requests = $appointmentApproval->getPendingAppointmentsForAdvisor($advisorId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdviCut - Pending Appointment Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <h2 class="fw-bold mb-2">Pending Appointment Requests</h2>
                        <h5 class="mb-0"><?= htmlspecialchars($advisorName) ?></h5>
                    </div>

                    <?php Notifications::createNotification(); ?>

                    <?php if (count($requests) === 0): ?>
                        <div class="alert alert-secondary rounded-3 mb-0">No pending appointment requests.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-primary text-center">
                                <tr>
                                    <th class="py-3">Request ID</th>
                                    <th class="py-3">Student</th>
                                    <th class="py-3">Student ID</th>
                                    <th class="py-3">Slot ID</th>
                                    <th class="py-3">Date</th>
                                    <th class="py-3">Time</th>
                                    <th class="py-3">Reason</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3" style="min-width: 320px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td class="text-center"><?= htmlspecialchars((string)($request['Request_ID'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars(trim((string)($request['First_name'] ?? '') . ' ' . (string)($request['Last_Name'] ?? ''))) ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string)($request['Student_ID'] ?? '')) ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string)($request['OfficeHour_ID'] ?? '')) ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string)($request['Appointment_Date'] ?? '')) ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string)($request['Start_Time'] ?? '')) ?> - <?= htmlspecialchars((string)($request['End_Time'] ?? '')) ?></td>
                                        <td class="text-start"><?= htmlspecialchars((string)($request['Student_Reason'] ?? '')) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark px-3 py-2">
                                                <?= htmlspecialchars((string)($request['Status'] ?? 'Pending')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <form method="POST" action="../modules/dispatcher.php" class="m-0">
                                                    <input type="hidden" name="action" value="/appointment/action">
                                                    <input type="hidden" name="appointment_action" value="approve">
                                                    <input type="hidden" name="request_id" value="<?= (int)($request['Request_ID'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Approve this appointment request?');">
                                                        Approve
                                                    </button>
                                                </form>

                                                <form method="POST" action="../modules/dispatcher.php" class="m-0">
                                                    <input type="hidden" name="action" value="/appointment/action">
                                                    <input type="hidden" name="appointment_action" value="decline">
                                                    <input type="hidden" name="request_id" value="<?= (int)($request['Request_ID'] ?? 0) ?>">
                                                    <textarea name="decline_reason" class="form-control form-control-sm" rows="2" placeholder="Enter decline reason..." required></textarea>
                                                    <button type="submit" class="btn btn-danger btn-sm w-100 mt-2" onclick="return confirm('Decline this appointment request?');">
                                                        Decline
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 text-center">
                        <a href="../../frontend/index.php" class="btn btn-primary px-4">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>