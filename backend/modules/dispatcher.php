<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AppointmentController.php';

$controller = new AppointmentController();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'book_appointment':
        $controller->bookAppointment();
        break;

    case 'approve_appointment':
        $controller->approveAppointment();
        break;

    case 'decline_appointment':
        $controller->declineAppointment();
        break;

    case 'mark_attendance':
        $controller->markAttendance();
        break;

    default:
        echo "Invalid action.";
        exit();
}
?>