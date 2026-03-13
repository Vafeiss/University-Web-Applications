<?php

$router->post('/login', ['UsersController','Authentication']);
$router->post('/logout', ['UsersController','logout']);
$router->post('/password/change', ['UsersController','changePassword']);

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