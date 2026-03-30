<?php
declare(strict_types=1);

require_once __DIR__ . '/router.php';

$router = new Router();

/*
------------------------------------------------------------
APPOINTMENTS ROUTES
------------------------------------------------------------
*/
$router->add('student_book_appointment', __DIR__ . '/../controllers/StudentBookAppointment.php');
$router->add('advisor_appointment_requests', __DIR__ . '/../controllers/AdvisorAppointmentRequests.php');
$router->add('student_appointment_history', __DIR__ . '/../controllers/StudentAppointmentHistory.php');
$router->add('advisor_appointment_history', __DIR__ . '/../controllers/AdvisorAppointmentHistory.php');
$router->add('student_calendar', __DIR__ . '/../controllers/StudentCalendar.php');
$router->add('advisor_calendar', __DIR__ . '/../controllers/AdvisorCalendar.php');
$router->add('advisor_office_hours', __DIR__ . '/../controllers/AdvisorOfficeHours.php');
$router->add('appointment_dispatcher', __DIR__ . '/../modules/dispatcher.php');
?>