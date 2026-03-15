<?php
/*Name: AdminController.php
  Description: Convertion of all the controllers related to the admin dashboard into this one. Paired with the 
  router and the dispatcher, this file is reponsible to be the bridge between the frontend and the backend for the adminclass
  Paraskevas Vafeiadis
  06-Mar-2026 v0.1
  Inputs: Depends on the functions but POST/GET requests
  Outputs: Redirections to the main dashboard
  Files in Uses: AdminClass.php , routes.php , router.php , dispatcher.php
  
  08-Mar-2026 v0.2 
  Added new function to call the participantclass and begin the replacement of students
  
  13-Mar-2026 v0.3
  CSV import functionality for students
  Paraskevas Vafeiadis
*/

declare(strict_types=1);

require_once __DIR__ . '/../modules/AdminClass.php';
require_once __DIR__ . '/../modules/ParticipantsClass.php';

class AdminController {

    public $errors = [];
    private $admin;

    public function __construct()
    {
        session_start();

        $this->admin = new Admin();
        $this->admin->Check_Session('Admin');
    }

    private function normalizeYear(string $yearInput): string
    {
        $value = strtolower(trim($yearInput));
        $map = [
            '1' => 'First',
            'year 1' => 'First',
            'first' => 'First',
            '2' => 'Second',
            'year 2' => 'Second',
            'second' => 'Second',
            '3' => 'Third',
            'year 3' => 'Third',
            'third' => 'Third',
            '4' => 'Fourth',
            'year 4' => 'Fourth',
            'fourth' => 'Fourth',
            '5' => 'Fifth',
            'year 5' => 'Fifth',
            'fifth' => 'Fifth',
        ];

        return $map[$value] ?? '';
    }

    private function toIntList($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        return array_keys($ids);
    }

    //get the post request from the frontend and call the function from adminclass
    public function addStudent()
    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        if (isset($_FILES['csv_file']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $result = $this->admin->addStudentByCSV($_FILES['csv_file']['tmp_name']);
            if ($result === false) {
                header("Location: ../../frontend/admin_dashboard.php?error=student_csv_failed");
                exit();
            }

            header("Location: ../../frontend/admin_dashboard.php");
            exit();
        }

        $externalId = $_POST['student_external_id'] ?? ($_POST['external_id'] ?? null);
        $first = trim((string)($_POST['first_name'] ?? ''));
        $last = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        $degreeInput = $_POST['degree'] ?? ($_POST['Degree'] ?? null);
        $degree = (int)$degreeInput;
        if ($degree <= 0) {
            $degree = 1;
        }

        $year = $this->normalizeYear((string)($_POST['year'] ?? ''));
        $advisorinput = $_POST['advisor_id'] ?? ($_POST['advisors_id'] ?? '');
        $advisorID = ($advisorinput === '' ? null : (int)$advisorinput);

        $added = $this->admin->addStudent($externalId, $first, $last, $email, $degree, $year, $advisorID);
        if (!$added) {
            header("Location: ../../frontend/admin_dashboard.php?error=student_add_failed");
            exit();
        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

    public function importStudentsCSV()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        if (!isset($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            header("Location: ../../frontend/admin_dashboard.php?error=missing_csv_file");
            exit();
        }

        $result = $this->admin->addStudentByCSV($_FILES['csv_file']['tmp_name']);
        if ($result === false) {
            header("Location: ../../frontend/admin_dashboard.php?error=student_csv_failed");
            exit();
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

        $studentIds = [];
        $bulk = $this->toIntList($_POST['student_ID'] ?? []);
        if (!empty($bulk)) {
            $studentIds = $bulk;
        } else {
            $studentInput = $_POST['student_id'] ?? ($_POST['student_ID'] ?? null);
            $studentId = ($studentInput === null ? 0 : (int)$studentInput);
            if ($studentId > 0) {
                $studentIds[] = $studentId;
            }
        }

        foreach ($studentIds as $studentId) {
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

        $external_id = trim((string)($_POST['external_id'] ?? ($_POST['advisor_external_id'] ?? '')));
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        if ($phone !== '' && strlen($phone) < 8) {
        $this->errors[] = "Phone number must be at least 8 digits long";
        header("Location: ../../frontend/admin_dashboard.php?error=invalid_phone");
        exit();}
        $department = (int)trim($_POST['department'] ?? '');

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

        $advisorIds = [];
        $bulkExternalIds = $this->toIntList($_POST['advisor_id'] ?? []);
        if (!empty($bulkExternalIds)) {
            $advisorIds = $bulkExternalIds;
        } else {
            $advisorinput = $_POST['advisor_id'] ?? null;
            $advisorId = ($advisorinput === null ? 0 : (int)$advisorinput);
            if ($advisorId > 0) {
                $advisorIds[] = $advisorId;
            }
        }

        foreach ($advisorIds as $advisorId) {
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

        $superUserIds = [];
        $bulk = $this->toIntList($_POST['User_ID'] ?? []);
        if (!empty($bulk)) {
            $superUserIds = $bulk;
        } else {
            $superUserInput = $_POST['super_user_id'] ?? ($_POST['User_ID'] ?? null);
            $superUserId = ($superUserInput === null ? 0 : (int)$superUserInput);
            if ($superUserId > 0) {
                $superUserIds[] = $superUserId;
            }
        }

        foreach ($superUserIds as $superUserId) {
            $this->admin->deleteSuperUser($superUserId);
        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function assignStudentsToAdvisor()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        //validate IDs
        $advisorInput = $_POST['advisor_external_id'] ?? null;
        $advisorExternalId = ($advisorInput === null ? 0 : (int)$advisorInput);

        if ($advisorExternalId <= 0) {
            header("Location: ../../frontend/admin_dashboard.php?error=invalid_advisor");
            exit();
        }

        //get student ID
        $studentIds = $_POST['student_external_ids'] ?? [];
        if (!is_array($studentIds)) {
            $studentIds = [];
        }

        //replace the students assigned to the advisor
        $participants = new Participants_Processing();
        $saved = $participants->Replace_Advisor_Students($advisorExternalId, $studentIds);

        if (!$saved) {
            header("Location: ../../frontend/admin_dashboard.php?error=assign_students_failed");
            exit();
        }

        header("Location: ../../frontend/admin_dashboard.php?success=students_assigned");
        exit();
    }

    public function randomAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        $participants = new Participants_Processing();
        $assigned = $participants->RandomAssignment();

        if (!$assigned) {
            header("Location: ../../frontend/admin_dashboard.php?error=random_assignment_failed&section=assignstudents");
            exit();
        }

        header("Location: ../../frontend/admin_dashboard.php?success=random_assignment_done&section=assignstudents");
        exit();
    }

    public function editAdvisor(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        $external_id = trim((string)($_POST['external_id'] ?? ($_POST['advisor_external_id'] ?? '')));
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        if ($phone !== '' && strlen($phone) < 8) {
        $this->errors[] = "Phone number must be at least 8 digits long";
        header("Location: ../../frontend/admin_dashboard.php?error=invalid_phone");
        exit();}
        $department = (int)trim($_POST['department'] ?? '');

        $saved = $this->admin->editAdvisor($external_id, $first_name, $last_name, $email, $phone, $department);
        if (!$saved) {
            header("Location: ../../frontend/admin_dashboard.php?error=advisor_edit_failed");
            exit();
        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

    public function editStudent(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../../frontend/admin_dashboard.php');
            exit();
        }

        $external_id = trim((string)($_POST['student_external_id'] ?? ($_POST['external_id'] ?? '')));
        $first_name = trim((string)($_POST['first_name'] ?? ''));
        $last_name  = trim((string)($_POST['last_name'] ?? ''));
        $email      = trim((string)($_POST['email'] ?? ''));

        $degreeInput = $_POST['degree'] ?? ($_POST['Degree'] ?? null);
        $degree = (int)$degreeInput;
        if ($degree <= 0) {
            $degree = 1;
        }

        $year = $this->normalizeYear((string)($_POST['year'] ?? ''));
        $advisorInput = $_POST['advisor_id'] ?? ($_POST['advisors_id'] ?? '');
        $advisorID = ($advisorInput === '' ? null : (int)$advisorInput);

        $saved = $this->admin->editStudent($external_id, $first_name, $last_name, $email, $degree, $year, $advisorID);
        if (!$saved) {
            header("Location: ../../frontend/admin_dashboard.php?error=student_edit_failed");
            exit();
        }

        header("Location: ../../frontend/admin_dashboard.php");
        exit();
    }

    
}