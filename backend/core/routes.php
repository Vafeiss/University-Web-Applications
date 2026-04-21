<?php
//User routes
$router->post('/login', ['UsersController','Authentication']);
$router->post('/logout', ['UsersController','logout']);
$router->post('/password/change', ['UsersController','changePassword']);
$router->post('/notification/delete', ['UsersController','deleteNotification']);

//admin routes
$router->post('/student/add', ['AdminController','addStudent']);
$router->post('/student/import', ['AdminController','importStudentsCSV']);
$router->post('/student/delete', ['AdminController','deleteStudent']);
$router->post('/student/edit', ['AdminController','editStudent']);
$router->post('/advisor/add', ['AdminController','addAdvisor']);
$router->post('/advisor/delete', ['AdminController','deleteAdvisor']);
$router->post('/advisor/edit', ['AdminController','editAdvisor']);
$router->post('/superuser/add', ['AdminController','addSuperUser']);
$router->post('/superuser/delete', ['AdminController','deleteSuperUser']);
$router->post('/advisor/students/assign', ['AdminController','assignStudentsToAdvisor']);
$router->post('/advisor/students/random', ['AdminController','randomAssignment']);
$router->post('/department/add', ['AdminController','addDepartmentController']);
$router->post('/degree/add', ['AdminController','addDegreeController']);
$router->post('/degree/delete', ['AdminController','deleteDegreeController']);
$router->post('/degree/edit', ['AdminController','editDegreeController']);
$router->post('/department/delete', ['AdminController','deleteDepartmentController']);
$router->post('/department/edit', ['AdminController','editDepartmentController']);

//communications routes
$router->post('/message/thread', ['AdvisorController','getMessageThread']);
$router->post('/message/send', ['AdvisorController','sendMessage']);
$router->post('/message/read', ['AdvisorController','markMessagesRead']);

$router->post('/student/message/thread', ['StudentController','getMessageThread']);
$router->post('/student/message/send', ['StudentController','sendMessage']);
$router->post('/student/message/read', ['StudentController','markMessagesRead']);

/*
|--------------------------------------------------------------------------
| Appointments Routes
|--------------------------------------------------------------------------
*/
$router->get('/student/book-appointment', ['AppointmentController','studentBookAppointment']);
$router->post('/student/book-appointment', ['AppointmentController','studentBookAppointment']);

$router->get('/advisor/appointment-requests', ['AppointmentController','advisorAppointmentRequests']);
$router->post('/advisor/appointment-requests', ['AppointmentController','advisorAppointmentRequests']);

$router->get('/student/appointment-history', ['AppointmentController','studentAppointmentHistory']);
$router->post('/student/appointment-history', ['AppointmentController','studentAppointmentHistory']);

$router->get('/advisor/appointment-history', ['AppointmentController','advisorAppointmentHistory']);
$router->post('/advisor/appointment-history', ['AppointmentController','advisorAppointmentHistory']);

$router->get('/student/calendar', ['AppointmentController','studentCalendar']);
$router->post('/student/calendar', ['AppointmentController','studentCalendar']);

$router->get('/advisor/calendar', ['AppointmentController','advisorCalendar']);
$router->post('/advisor/calendar', ['AppointmentController','advisorCalendar']);

$router->get('/advisor/office-hours', ['AppointmentController','advisorOfficeHours']);
$router->post('/advisor/office-hours', ['AppointmentController','advisorOfficeHours']);

$router->post('/appointment/action', ['AppointmentControllerAction','handle']);