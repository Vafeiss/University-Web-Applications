<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/AppointmentController.php';

$controller = new AppointmentController();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'book':
        $controller->bookAppointment();
        break;

    case 'approve':
        $controller->approveAppointment();
        break;

    case 'decline':
        $controller->declineAppointment();
        break;

    case 'attendance':
        $controller->markAttendance();
        break;

    default:
        header('Location: ../../frontend/index.php');
        exit();
}