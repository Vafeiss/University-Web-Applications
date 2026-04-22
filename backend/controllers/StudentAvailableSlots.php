<?php
/*
   NAME: Student Available Slots Page
   Description: Legacy redirect wrapper for student available slots page
   Panteleimoni Alexandrou
   20-Mar-2026 v1.7
   Inputs: Direct URL access
   Outputs: Redirects to student appointment dashboard booking section
   Error Messages: N/A
   Files in use: StudentAvailableSlots.php, StudentAppointmentDashboard.php

   13-Apr-2026 v1.8
   Updated booking form to send all required fields (student_id, slot_id, appointment_date, reason)
   Panteleimoni Alexandrou

   19-Apr-2026 v1.9
   Converted legacy standalone page to redirect wrapper (no HTML rendering)
   Panteleimoni Alexandrou
*/

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

header('Location: ' . frontend_url('StudentAppointmentDashboard.php?section=book'));
exit;
