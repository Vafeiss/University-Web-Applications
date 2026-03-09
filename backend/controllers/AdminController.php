<?php
/*Name: AdminController.php
  Description: Convertion of all the controllers related to the admin dashboard into this one. Paired with the 
  router and the dispatcher, this file is reponsible to be the bridge between the frontend and the backend for the adminclass
  Paraskevas Vafeiadis
  06-Mar-2026 v0.1
  Inputs: Depends on the functions but POST/GET requests
  Outputs: Redirections to the main dashboard
  Files in Uses: AdminClass.php , routes.php , router.php , dispatcher.php*/

declare(strict_types=1);

require_once __DIR__ . '/../modules/AdminClass.php';

class AdminController {

    public $errors = [];
    private $admin;

    public function __construct()
    {
        session_start();

        $this->admin = new Admin();
        $this->admin->Check_Session('Admin');
    }

    //get the post request from the frontend and call the function from adminclass
    public function addStudent()
    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        // CSV upload
        if (isset($_FILES['csv_file']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {

            $result = $this->admin->addStudentByCSV($_FILES['csv_file']['tmp_name']);
            if ($result === false) {
                header("Location: ../../frontend/admin_dashboard.php?error=student_csv_failed");
                exit();
            }

        } else {

            $externalId = $_POST['external_id'] ?? null;
            $first = $_POST['first_name'] ?? '';
            $last = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $year = $_POST['year'] ?? '';
            $advisorinput = $_POST['advisor_id'] ?? ($_POST['advisors_id'] ?? '');
            $advisorID = ($advisorinput === '' ? null : (int)$advisorinput);

            $added = $this->admin->addStudent($externalId, $first, $last, $email, $year, $advisorID);
            if (!$added) {
                header("Location: ../../frontend/admin_dashboard.php?error=student_add_failed");
                exit();
            }

        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function deleteStudent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        $studentInput = $_POST['student_id'] ?? ($_POST['student_ID'] ?? null);
        $studentId = ($studentInput === null ? null : (int)$studentInput);

        if ($studentId) {
            $this->admin->deleteStudent($studentId);
        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function addAdvisor()
    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

       $external_id = trim($_POST['external_id'] ?? '');
       $first_name = trim($_POST['first_name'] ?? '');
       $last_name  = trim($_POST['last_name'] ?? '');
       $email      = trim($_POST['email'] ?? ''); 
       $phone      = trim($_POST['phone'] ?? '');
       if (strlen($phone) < 8) {
        $this->errors[] = "Phone number must be at least 8 digits long";
        header("Location: ../../frontend/admin_dashboard.php?error=invalid_phone");
        exit();}
       $department = trim($_POST['department'] ?? '');

        $this->admin->addAdvisor($external_id, $first_name, $last_name, $email, $phone, $department);

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function deleteAdvisor()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        $advisorinput = $_POST['advisor_id'] ?? null;
        $advisorId = ($advisorinput === null ? null : (int)$advisorinput);

        if ($advisorId) {
            $this->admin->deleteAdvisor($advisorId);
        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function addSuperUser()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        $email      = trim($_POST['email'] ?? '');

        $added = $this->admin->addSuperUser($email);
        if (!$added) {
            header("Location: ../../frontend/admin_dashboard.php?error=superuser_add_failed");
            exit();
        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }


    //get the post request from the frontend and call the function from adminclass
    public function deleteSuperUser()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        $superUserInput = $_POST['super_user_id'] ?? ($_POST['User_ID'] ?? null);
        $superUserId = ($superUserInput === null ? null : (int)$superUserInput);

        if ($superUserId) {
            $this->admin->deleteSuperUser($superUserId);
        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

}