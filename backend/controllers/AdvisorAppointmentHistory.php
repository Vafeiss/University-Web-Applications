<?php
/*
   NAME: Advisor Appointment History
   Description: Legacy redirect wrapper for advisor appointment history page
   Panteleimoni Alexandrou
   30-Mar-2026 v1.0
   Inputs: Direct URL access
   Outputs: Redirects to advisor appointment dashboard history section
   Error Messages: N/A
   Files in use: AdvisorAppointmentHistory.php, AdvisorAppointmentDashboard.php

   10-Apr-2026 v1.1
   Added dynamic advisor name loading and improved table UI with status badges
   Panteleimoni Alexandrou

   19-Apr-2026 v1.2
   Converted legacy standalone page to redirect wrapper (no HTML rendering)
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

header('Location: ' . frontend_url('AdvisorAppointmentDashboard.php?section=history'));
exit;
