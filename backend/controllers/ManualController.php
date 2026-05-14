<?php
/*Description: this is a controller to serve as the authenticated endpoint for the manuals in each dashboards
  13 - 05 - 2025: Paraskevas Vafeiadis
  File in use: manuals/* , userclass.php , app.php
  Output: redirects to the manual pdfs in the frontend folder, after validating the session and role of the user
  Input: role parameter

*/
declare(strict_types=1);

require_once __DIR__ . '/../modules/UsersClass.php';
require_once __DIR__ . '/../config/app.php';

class ManualController
{
    public function download(): void
    {
        $roleParam = isset($_GET['role']) ? trim((string)$_GET['role']) : '';
        $roleMap = [
            'admin' => 'Admin',
            'advisor' => 'Advisor',
            'student' => 'Student',
            'superuser' => 'SuperUser',
        ];

        $normalized = strtolower($roleParam);
        if (!isset($roleMap[$normalized])) {
            http_response_code(400);
            echo 'Invalid role';
            return;
        }

        $requiredRole = $roleMap[$normalized];

        //Check_Session(from users class) to validate session and role.
        $user = new Users();
        try {
            $user->Check_Session($requiredRole);
        } catch (Throwable $e) {
            //Check_Session will redirect on failure;
            http_response_code(403); //just in case it doesnt work throw 403 unauthorised
            echo 'Forbidden';
            return;
        }

        //map role to manual filename
        $manualFiles = [
            'Admin' => 'manuals/AdviCut_Manual_ADMIN.pdf',
            'Advisor' => 'manuals/AdviCut_Manual_ADVISOR.pdf',
            'Student' => 'manuals/AdviCut_Manual_STUDENT.pdf',
            'SuperUser' => 'manuals/AdviCut_Manual_SUPERUSER.pdf',
        ];

        $file = $manualFiles[$requiredRole] ?? null;
        if ($file === null) {
            http_response_code(404);
            echo 'Manual not found';
            return;
        }

        //redirect to frontend PDF path (uses configured base URL)
        $redirect = frontend_url($file);
        header('Location: ' . $redirect);
        exit();
    }
}
