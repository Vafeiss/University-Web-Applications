<?php
/*
   NAME: Student Appointment History
   Description: Legacy redirect wrapper for student appointment history page
   Panteleimoni Alexandrou
   30-Mar-2026 v2.3
   Inputs: Direct URL access
   Outputs: Redirects to student appointment dashboard history section
   Error Messages: N/A
   Files in use: StudentAppointmentHistory.php, StudentAppointmentDashboard.php

   13-Apr-2026 v2.4
   Added dynamic student name loading and improved table UI with status badges
   Panteleimoni Alexandrou

   19-Apr-2026 v2.5
   Converted legacy standalone page to redirect wrapper (no HTML rendering)
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Location: ../../frontend/StudentAppointmentDashboard.php?section=history');
exit;
