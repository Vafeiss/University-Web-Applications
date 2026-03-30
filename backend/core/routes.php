<?php
//User routes
$router->post('/login', ['UsersController','Authentication']);
$router->post('/logout', ['UsersController','logout']);
$router->post('/password/change', ['UsersController','changePassword']);

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

//advisor routes
$router->post('/message/thread', ['AdvisorController','getMessageThread']);
$router->post('/message/send', ['AdvisorController','sendMessage']);
$router->post('/message/read', ['AdvisorController','markMessagesRead']);

//student routes
$router->post('/student/message/thread', ['StudentController','getMessageThread']);
$router->post('/student/message/send', ['StudentController','sendMessage']);
$router->post('/student/message/read', ['StudentController','markMessagesRead']);