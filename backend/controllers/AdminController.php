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

  16-Mar-2026 v0.4
  Added error handling and success messages for all functions using the notifications class added new function to normalize the year input for csv
  and phone number validation for the add/edit advisor functions
  Paraskevas Vafeiadis

  21=Mar-2026 v0.5
  Added edit || add functionality for students && advisors && degrees. Also added error handling for the edit functions.
  Paraskevas Vafeiadis

  22-Mar-2026 v0.6
  Added add/edit/delete degree functionality and routes as well as error handling
  Paraskevas Vafeiadis

  25-Mar-2026 v0.7
  Added add/edit/delete department functionality and routes as well as error handling
  Paraskevas Vafeiadis
*/

declare(strict_types=1);

require_once __DIR__ . '/../modules/AdminClass.php';
require_once __DIR__ . '/../modules/ParticipantsClass.php';
require_once __DIR__ . '/../modules/NotificationsClass.php';
require_once __DIR__ . '/../modules/Csrf.php';

class AdminController {

    public $errors = [];
    private $admin;

    public function __construct()
    {
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

    //get an array of values and return an array of positive integers 
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

    private function isValidPhone(string $phone): bool
    {
        if ($phone === '') {
            return true;
        }

        if (!preg_match('/^[0-9+()\-\s]+$/', $phone)) {
            return false;
        }

        $digitsOnly = preg_replace('/\D/', '', $phone);
        $digitsLength = strlen($digitsOnly);

        return $digitsLength >= 8 && $digitsLength <= 15;
    }

    private function requireMutationRequest(string $redirectUrl): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirectUrl);
            exit();
        }

        if (!Csrf::validateRequestToken()) {
            Notifications::error('Request validation failed.');
            header('Location: ' . $redirectUrl);
            exit();
        }
    }

    //get the post request from the frontend and call the function from adminclass
    public function addStudent()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=students');

        $externalId = $_POST['student_external_id'] ?? ($_POST['external_id'] ?? null);
        $first = trim((string)($_POST['first_name'] ?? ''));
        $last = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        $degreeInput = $_POST['degree'] ?? ($_POST['Degree'] ?? null);
        $degree = (int)$degreeInput;
        if ($degree <= 0) {
            Notifications::error("Please select a valid degree.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        $year = trim((string)($_POST['year'] ?? ''));
        
        $advisorinput = trim((string)($_POST['advisor_id'] ?? ($_POST['advisors_id'] ?? '')));
        $advisorID = ($advisorinput === '' ? null : (int)$advisorinput);

        $added = $this->admin->addStudent($externalId, $first, $last, $email, $degree, $year, $advisorID);

        if (!$added) {
            Notifications::error("Failed to add student.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        Notifications::success("Student added successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=students");
        exit();
    }

    public function importStudentsCSV()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=students');

        if (!isset($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            Notifications::error("Failed to upload CSV file.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        $upload = $_FILES['csv_file'];
        $uploadError = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            Notifications::error("CSV upload failed.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        $uploadSize = (int)($upload['size'] ?? 0);
        if ($uploadSize <= 0 || $uploadSize > 2 * 1024 * 1024) {
            Notifications::error("CSV file must be between 1 byte and 2 MB.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        $originalName = (string)($upload['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            Notifications::error("Only .csv files are allowed.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        $tmpPath = (string)$upload['tmp_name'];
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmpPath);
                if (is_string($detected)) {
                    $mime = $detected;
                }
                finfo_close($finfo);
            }
        }

        if ($mime !== '') {
            $allowedMimes = [
                'text/csv',
                'text/plain',
                'application/csv',
                'text/x-csv',
                'application/vnd.ms-excel',
            ];

            if (!in_array($mime, $allowedMimes, true)) {
                Notifications::error("Uploaded file is not a valid CSV.");
                header("Location: ../../frontend/admin_dashboard.php?tab=students");
                exit();
            }
        }

        $result = $this->admin->addStudentByCSV($tmpPath);
        if ($result === false) {
            Notifications::error("Failed to add students.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        Notifications::success("Students added successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=students");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function deleteStudent()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=students');

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

        Notifications::success("Students deleted successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=students");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function addAdvisor()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=advisors');

        $external_id = trim((string)($_POST['external_id'] ?? ($_POST['advisor_external_id'] ?? '')));
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim((string)($_POST['phone'] ?? ''));
        if (!$this->isValidPhone($phone)) {
        $this->errors[] = "Phone number must contain 8 to 15 digits and only valid phone characters.";
        Notifications::error("Invalid phone number. Use 8-15 digits (spaces, +, -, parentheses allowed).");
        header("Location: ../../frontend/admin_dashboard.php?tab=advisors");
        exit();
        }
        $department = (int)trim($_POST['department'] ?? '');

        try {
            $added = $this->admin->addAdvisor($external_id, $first_name, $last_name, $email, $phone, $department);
        } catch (PDOException $e) {
            Notifications::error("Failed to add advisor.");
            header("Location: ../../frontend/admin_dashboard.php?tab=advisors");
            exit();
        }

        if (!$added) {
            Notifications::error("Failed to add advisor.");
            header("Location: ../../frontend/admin_dashboard.php?tab=advisors");
            exit();
        }

        Notifications::success("Advisor added successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=advisors");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function deleteAdvisor()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=advisors');

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

        Notifications::success("Advisor deleted successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=advisors");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function addSuperUser()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=superusers');

        $email      = trim($_POST['email'] ?? '');
        $externalId = (int)($_POST['external_id'] ?? 0);

        $added = $this->admin->addSuperUser($email, $externalId);
        if (!$added) {
           Notifications::error("Failed to add Super user.");
           header("Location: ../../frontend/admin_dashboard.php?tab=superusers");
           exit();
        }

        Notifications::success("Super user added successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=superusers");
        exit();
    }


    //get the post request from the frontend and call the function from adminclass
    public function deleteSuperUser()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=superusers');

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

        Notifications::success("Super user deleted successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=superusers");
        exit();
    }

    //get the post request from the frontend and call the function from adminclass
    public function assignStudentsToAdvisor()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=assignstudents');

        //validate IDs
        $advisorInput = $_POST['advisor_external_id'] ?? null;
        $advisorExternalId = ($advisorInput === null ? 0 : (int)$advisorInput);

        if ($advisorExternalId <= 0) {
           Notifications::error("Failed to assign students to advisor.");
           header("Location: ../../frontend/admin_dashboard.php?tab=assignstudents");
           exit();
        }

        //get student ID
        $studentIds = $_POST['student_external_ids'] ?? [];
        if (!is_array($studentIds)) {
            $studentIds = [];
        }

        try {
            //replace the students assigned to the advisor
            $participants = new Participants_Processing();
            $saved = $participants->Replace_Advisor_Students($advisorExternalId, $studentIds);
        } catch (PDOException $e) {
            Notifications::error("Failed to assign students to advisor.");
            header("Location: ../../frontend/admin_dashboard.php?tab=assignstudents");
            exit();
        }

        if (!$saved) {
            Notifications::error("Failed to assign students to advisor.");
            header("Location: ../../frontend/admin_dashboard.php?tab=assignstudents");
            exit();
        }

        Notifications::success("Students assigned to advisor successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=assignstudents");
        exit();
    }

    public function randomAssignment()
    {
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=assignstudents');

        try {
            $participants = new Participants_Processing();
            $assigned = $participants->RandomAssignment();
        } catch (PDOException $e) {
            Notifications::error("Failed to perform random assignment.");
            header("Location: ../../frontend/admin_dashboard.php?tab=assignstudents");
            exit();
        }

        if (!$assigned) {
            Notifications::error("Failed to perform random assignment.");
            header("Location: ../../frontend/admin_dashboard.php?tab=assignstudents");
            exit();
        }

        Notifications::success("Random assignment completed successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=assignstudents");
        exit();
    }

    public function editAdvisor(){
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=advisors');

        $external_id = trim((string)($_POST['external_id'] ?? ($_POST['advisor_external_id'] ?? '')));
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim((string)($_POST['phone'] ?? ''));
        if (!$this->isValidPhone($phone)) {
        $this->errors[] = "Phone number must contain 8 to 15 digits and only valid phone characters.";
        Notifications::error("Invalid phone number.");
        header("Location: ../../frontend/admin_dashboard.php?tab=advisors");
        exit();}
        $department = (int)trim($_POST['department'] ?? '');

        $saved = $this->admin->editAdvisor($external_id, $first_name, $last_name, $email, $phone, $department);
        if (!$saved) {
            Notifications::error("Failed to edit advisor.");
            header("Location: ../../frontend/admin_dashboard.php?tab=advisors");
            exit();
        }

        Notifications::success("Advisor edited successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=advisors");
        exit();
    }

    public function editStudent(){
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=students');

        $external_id = trim((string)($_POST['student_external_id'] ?? ($_POST['external_id'] ?? '')));
        $first_name = trim((string)($_POST['first_name'] ?? ''));
        $last_name  = trim((string)($_POST['last_name'] ?? ''));
        $email      = trim((string)($_POST['email'] ?? ''));

        $degreeInput = $_POST['degree'] ?? ($_POST['Degree'] ?? null);
        $degree = (int)$degreeInput;
        if ($degree <= 0) {
            Notifications::error("Please select a valid degree.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        $year = trim((string)($_POST['year'] ?? ''));
        $advisorInput = $_POST['advisor_id'] ?? ($_POST['advisors_id'] ?? '');
        $advisorID = ($advisorInput === '' ? null : (int)$advisorInput);

        $saved = $this->admin->editStudent($external_id, $first_name, $last_name, $email, $degree, $year, $advisorID);
        if (!$saved) {
            Notifications::error("Failed to edit student.");
            header("Location: ../../frontend/admin_dashboard.php?tab=students");
            exit();
        }

        Notifications::success("Student edited successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=students");
        exit();
    }

    public function editDegreeController(){
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=degrees');

        $degreeId = (int)($_POST['degree_id'] ?? 0);
        $degreeName = trim((string)($_POST['degree_name'] ?? ''));
        $departmentid = (int)($_POST['department_id'] ?? 0);

        if ($degreeId <= 0 || $departmentid <= 0 || $degreeName === '') {
            Notifications::error("Invalid degree data.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }

        try{
        $saved = $this->admin->editDegree($degreeId, $degreeName , $departmentid);
        if (!$saved) {
            Notifications::error("Failed to edit degree.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
        Notifications::success("Degree edited successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
        exit();
        } catch (Throwable $e) {
            error_log('AdminController::editDegreeController error: ' . $e->getMessage());
            Notifications::error("An error occurred while editing the degree.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
    
    }

    public function addDegreeController(){
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=degrees');

        $degreeName = trim((string)($_POST['degree_name'] ?? ''));
        $departmentId = (int)($_POST['department_id'] ?? 0);

        if ($degreeName === '' || $departmentId <= 0) {
            Notifications::error("Degree name and department are required.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }

        try{
        $added = $this->admin->addDegree($degreeName, $departmentId);
        if (!$added) {
            Notifications::error("Failed to add degree.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
        Notifications::success("Degree added successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
        exit();
        } catch (Throwable $e) {
            error_log('AdminController::addDegreeController error: ' . $e->getMessage());
            Notifications::error("An error occurred while adding the degree.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
    }

    public function addDepartmentController(){
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=degrees');

        $departmentName = trim((string)($_POST['department_name'] ?? ''));

        if ($departmentName === '') {
            Notifications::error("Department name cannot be empty.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }

        try {
            $added = $this->admin->addDepartment($departmentName);
            if (!$added) {
                Notifications::error("Failed to add department.");
                header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
                exit();
            }

            Notifications::success("Department added successfully.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        } catch (Throwable $e) {
            error_log('AdminController::addDepartmentController error: ' . $e->getMessage());
            Notifications::error("An error occurred while adding the department.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
    }

    public function deleteDegreeController(){
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=degrees');

        $degreeId = (int)($_POST['degree_id'] ?? 0);

        if ($degreeId <= 0) {
            Notifications::error("Invalid degree ID.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }

        try{
        $result = $this->admin->deleteDegreeDetailed($degreeId);
        if (!($result['success'] ?? false)) {
            $code = (string)($result['code'] ?? 'error');
            if ($code === 'in_use') {
                Notifications::error("Cannot delete degree because students are still assigned to it.");
            } elseif ($code === 'not_found') {
                Notifications::error("Degree not found.");
            } else {
                Notifications::error("Failed to delete degree.");
            }
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
        Notifications::success("Degree deleted successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
        exit();
        } catch (Throwable $e) {
            error_log('AdminController::deleteDegreeController error: ' . $e->getMessage());
            Notifications::error("An error occurred while deleting the degree.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
    }

    public function deleteDepartmentController(){
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=degrees');

        $departmentid = (int)($_POST['department_id'] ?? 0);

        if ($departmentid <= 0) {
            Notifications::error("Invalid department ID.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }

        try{
        $result = $this->admin->deleteDepartmentDetailed($departmentid);
        if (!($result['success'] ?? false)) {
            $code = (string)($result['code'] ?? 'error');
            if ($code === 'in_use_degree') {
                Notifications::error("Cannot delete department because degrees still belong to it.");
            } elseif ($code === 'in_use_advisor') {
                Notifications::error("Cannot delete department because advisors are still assigned to it.");
            } elseif ($code === 'not_found') {
                Notifications::error("Department not found.");
            } else {
                Notifications::error("Failed to delete department.");
            }
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
        Notifications::success("Department deleted successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
        exit();
        } catch (Throwable $e) {
            error_log('AdminController::deleteDepartmentController error: ' . $e->getMessage());
            Notifications::error("An error occurred while deleting the department.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
    }

    public function editDepartmentController(){
        $this->requireMutationRequest('../../frontend/admin_dashboard.php?tab=degrees');

        $departmentId = (int)($_POST['department_id'] ?? 0);
        $departmentName = trim((string)($_POST['department_name'] ?? ''));

        if ($departmentName === '' || $departmentId <= 0) {
            Notifications::error("Invalid department data.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }

        try{
        $saved = $this->admin->editDepartment($departmentId, $departmentName);
        if (!$saved) {
            Notifications::error("Failed to edit department.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
        Notifications::success("Department edited successfully.");
        header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
        exit();
        } catch (Throwable $e) {
            error_log('AdminController::editDepartmentController error: ' . $e->getMessage());
            Notifications::error("An error occurred while editing the department.");
            header("Location: ../../frontend/admin_dashboard.php?tab=degrees");
            exit();
        }
    }

}