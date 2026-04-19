<?php
/*
NAME: Advisor Attendance Page
Description: Legacy redirect wrapper for advisor attendance page
Panteleimoni Alexandrou
23-Mar-2026 v1.0
Inputs:
- Direct URL access
Outputs: Redirects to advisor appointment dashboard appointments section
Error Messages: N/A
Files in use: AdvisorAttendance.php, AdvisorAppointmentDashboard.php

13-Apr-2026 v1.1
Replaced URL-based messages with centralized notification system
Panteleimoni Alexandrou

18-Apr-2026 v1.2
Replaced browser confirm popups with custom Bootstrap confirmation modal for attendance actions
Panteleimoni Alexandrou

19-Apr-2026 v1.3
Converted legacy standalone page to redirect wrapper (no HTML rendering)
Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Location: ../../frontend/AdvisorAppointmentDashboard.php?section=appointments');
exit;
