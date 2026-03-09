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

$router = new Router();

require_once __DIR__ . '/../core/routes.php';

$router->resolve();