<?php
/*Name: dispatcher.php
Description: This file is responsible for routing the actions from the frontend to the appropriate controllers in the backend.
Paraskevas Vafeiadis
27-feb-2026 v0.1
Inputs: action (string)
Outputs: None
Error Messages: None
Files in use: AdminController.php and UsersController.php through the router.
*/

require_once __DIR__ . '/../core/router.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/UsersController.php';
require_once __DIR__ . '/../controllers/AppointmentController.php';
require_once __DIR__ . '/../controllers/AppointmentControllerAction.php';
require_once __DIR__ . '/../controllers/AdvisorController.php';
require_once __DIR__ . '/../controllers/StudentController.php';
require_once __DIR__ . '/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = trim((string)($_POST['action'] ?? ''));
	$resolvedPath = $action !== ''
		? '/' . ltrim($action, '/')
		: (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

	$expectsJson = str_starts_with($resolvedPath, '/message/')
		|| str_starts_with($resolvedPath, '/student/message/');

	if (!Csrf::validateRequestToken()) {
		Csrf::reject($expectsJson);
	}
}


$router = new Router();

require_once __DIR__ . '/../core/routes.php';

$router->resolve();