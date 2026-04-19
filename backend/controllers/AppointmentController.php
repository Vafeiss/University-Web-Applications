<?php
/*
   NAME: Appointment Controller
   Description: This controller handles advisor actions for approving and declining appointment requests
   Panteleimoni Alexandrou
   30-Mar-2026 v2.0
   Inputs: GET inputs for action and request id
   Outputs: Updates appointment request status, inserts appointment record, inserts history record and redirects back to the advisor dashboard
   Error Messages: If the request is invalid or a database operation fails, a notification message is created
   Files in use: AppointmentController.php, AdvisorAppointmentDashboard.php, databaseconnect.php, NotificationsClass.php

   13-Apr-2026 v2.1
   Updated notification handling to use NotificationsClass consistently for approve and decline actions
   Panteleimoni Alexandrou

   19-Apr-2026 v2.2
   Merged overlapping appointment action handling into AppointmentControllerAction while preserving direct-access compatibility
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AppointmentController
{
    public function studentBookAppointment(): void
    {
        header("Location: ../../frontend/StudentAppointmentDashboard.php?section=book");
        exit;
    }

    public function advisorAppointmentRequests(): void
    {
        header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=requests");
        exit;
    }

    public function studentAppointmentHistory(): void
    {
        header("Location: ../../frontend/StudentAppointmentDashboard.php?section=history");
        exit;
    }

    public function advisorAppointmentHistory(): void
    {
        header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=history");
        exit;
    }

    public function studentCalendar(): void
    {
        header("Location: ../../frontend/StudentAppointmentDashboard.php?section=calendar");
        exit;
    }

    public function advisorCalendar(): void
    {
        header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=appointments");
        exit;
    }

    public function advisorOfficeHours(): void
    {
        header("Location: ../../frontend/AdvisorAppointmentDashboard.php?section=officehours");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| DIRECT REQUEST COMPATIBILITY WRAPPER
|--------------------------------------------------------------------------
| Keep legacy direct access working by delegating approve/decline handling
| to the main AppointmentControllerAction controller.
*/
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) {
    return;
}

require_once __DIR__ . '/AppointmentControllerAction.php';

$controller = new AppointmentControllerAction();
$controller->handle();
