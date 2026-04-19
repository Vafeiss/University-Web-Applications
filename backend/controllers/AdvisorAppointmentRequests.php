<?php
/*
   NAME: Advisor Appointment Requests
   Description: Legacy redirect wrapper for advisor appointment requests page
   Panteleimoni Alexandrou
   30-Mar-2026 v1.0
   Inputs: Direct URL access
   Outputs: Redirects to advisor appointment dashboard requests section
   Error Messages: N/A
   Files in use: AdvisorAppointmentRequests.php, AdvisorAppointmentDashboard.php

   13-Apr-2026 v1.1
   Replaced URL-based messages with centralized notification system and improved UI actions
   Panteleimoni Alexandrou

   13-Apr-2026 v1.2
   Replaced browser confirm dialogs with custom popup confirmation modal to avoid localhost browser messages
   Panteleimoni Alexandrou

   19-Apr-2026 v1.3
   Added standalone redirect target for approve and decline actions so testing can still return to this controller page when needed
   Panteleimoni Alexandrou

   19-Apr-2026 v1.4
   Converted legacy standalone page to redirect wrapper (no HTML rendering)
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Location: ../../frontend/AdvisorAppointmentDashboard.php?section=requests');
exit;
