<?php
/*NAME: User Class
Description: This class is responsible for log_in,log_Out,validation of credentials and signing in(which is the way to reset the
one time password for the user)
Paraskevas Vafeiadis
24-feb-2026 v0.1
Inputs: Email,Password,database advicut
Outputs: None
Error Messages : Database connection failed.
Files in use: authentication.php where the object user is created and the log_in method is called,
student_dashboard.php to test the login of student and advisor_dashboard.php to test the login of the advisor
advicut.sql for the test with the database.

25-feb-2026 v0.2
Added new database schema with Users table send the query to the user table and then based on the role
send the user to the right dashboard.
Paraskevas Vafeiadis

26-feb-2026 v0.3
Added the change password method to the class and created
and a controller to handle the change password process with validation and error handling
Paraskevas Vafeiadis

27-feb-2026 v0.4
Added the log out method to the class and created a controller to handle the log out process
Paraskevas Vafeiadis

28-feb-2026 v1.0
Pre-final version of the class it fully works needs enchans **testing** and review added NEW check_Session for security measures
Paraskevas Vafeiadis

30-Mar-2026 v1.1
Added session management and role-based access control to the Check_Session method.For the communication to be more secure and to prevent 
unauthorized access to the dashboard pages.
Paraskevas Vafeiadis
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
class Users {
private $conn;

//have a base path function to help with redirections in the project
private function appBasePath(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    $backendMarker = '/backend/modules/dispatcher.php';
    $frontendMarker = '/frontend/';

    $backendPos = strpos($scriptName, $backendMarker);
    if ($backendPos !== false) {
        return rtrim(substr($scriptName, 0, $backendPos), '/');
    }

    $frontendPos = strpos($scriptName, $frontendMarker);
    if ($frontendPos !== false) {
        return rtrim(substr($scriptName, 0, $frontendPos), '/');
    }

    return '';
}

private function redirectTo(string $path): void {
    $target = $this->appBasePath() . '/' . ltrim($path, '/');
    header('Location: ' . $target);
    exit();
}

//function to help the userclass to find the right dashboard to redirect the user after login.
//Based on role
private function dashboardPathForRole(string $role): ?string {
    $normalized = strtolower(trim($role));

    if ($normalized === 'student') {
        return '/frontend/student_dashboard.php';
    }
    if ($normalized === 'advisor') {
        return '/frontend/advisor_dashboard.php';
    }
    if ($normalized === 'admin') {
        return '/frontend/admin_dashboard.php';
    }
    if ($normalized === 'superuser') {
        return '/frontend/SuperUser_dashboard.php';
    }

    return null;
}

public function __construct() {
    //creating an obj of the mysql connection and connect to the database 
    $this->conn = new mysqli("localhost", "root", "", "advicut");
if ($this->conn->connect_error) { //if connection fails kill it and print message
    die("Connection failed: " . $this->conn->connect_error);
    $this ->conn->set_charset("utf8mb4");
}}


    //method to log in the user by checking email and password to the advicut database
    public function Log_in(string $email, string $password) {
        //query to get all the students where email and password match the input parameters
        $sql = "SELECT User_ID , Uni_Email , Role , Password FROM users WHERE Uni_Email = ? LIMIT 1";
        $stmt1 = $this->conn->prepare($sql);
        $stmt1->bind_param("s", $email); //make the query as a prepared statement to prevent attacks
        $stmt1->execute();
        $result1 = $stmt1->get_result();

        if ($result1->num_rows !== 1) { //error handling if email not found go back to index
            $this->redirectTo('/frontend/index.php?error=invalid1');
        }

        $row = $result1->fetch_assoc();//error handling if password wrong go bakc to index
        if (!password_verify($password, $row["Password"])) {
            $this->redirectTo('/frontend/index.php?error=invalid2');
        }

        $this->Validate_Credentials($row);
        
    }

//method to kill the session of the user and log them out.
public function Log_out() {
    $_SESSION = [];
    session_destroy();

    $this->redirectTo('/frontend/index.php');
}

public function Check_Session(string $requiredRole = null) {
    // UserID is the primary key for an authenticated session.
    if (!isset($_SESSION['UserID'])) {
        $this->redirectTo('/frontend/index.php?error=unauthorized');
    }

    //userid is numbers and not a invalid number 
    $userId = intval($_SESSION['UserID']);
    if ($userId <= 0) {
        $this->redirectTo('/frontend/index.php?error=unauthorized');
    }

    //query to get the row froim the data base
    $stmt = $this->conn->prepare("SELECT Uni_Email, Role FROM users WHERE User_ID = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        $this->redirectTo('/frontend/index.php?error=unauthorized');
    }
    $result3 = $result->fetch_assoc();

    // Keep session values aligned with DB to avoid unnecessary logouts on refresh.
    $_SESSION['email'] = $result3['Uni_Email'];
    $_SESSION['role'] = $result3['Role'];

    //if not the required role for the page then exit
    if ($requiredRole !== null && strcasecmp(trim((string)$result3['Role']), trim((string)$requiredRole)) !== 0) {
        $this->redirectTo('/frontend/index.php?error=forbidden');
    }
}

public function Validate_Credentials($row) {
    if($row != NULL) {
            $_SESSION['email'] = $row['Uni_Email']; //storing info while user logged in
            $_SESSION['UserID'] = $row['User_ID'];
            $_SESSION['role'] = $row['Role'];}
        else {
        echo "Invalid credentials";
        }
            //redirect them to the right dashboard based on their role if the session it's valid and the credentials are correct
            if ($_SESSION['role'] == 'Student') {
                $this->redirectTo('/frontend/student_dashboard.php');
            }
            else if ($_SESSION['role'] == 'Advisor') {
                $this->redirectTo('/frontend/advisor_dashboard.php');
            }
            else if ($_SESSION['role'] == 'Admin') {
                $this->redirectTo('/frontend/admin_dashboard.php');
            }
            else if ($_SESSION['role'] == 'SuperUser') {
                $this->redirectTo('/frontend/SuperUser_dashboard.php');
            }
        else {
            $fallbackPath = $this->dashboardPathForRole((string)($_SESSION['role'] ?? ''));
            if ($fallbackPath !== null) {
                $this->redirectTo($fallbackPath);
            }
            $this->redirectTo('/frontend/index.php');
        }
}

//method to reset the given password of the user to his own.
public function Change_Password(int $userId, string $currentPassword, string $newPassword): bool
{
    if (strlen($newPassword) < 8) {
        return false;
    }
    $stmt = $this->conn->prepare(
        "SELECT Password FROM users WHERE User_ID = ? LIMIT 1"
    );

    $stmt->bind_param("i", $userId); //get the current of password to verify
    $stmt->execute();


    $result2 = $stmt->get_result();
    if ($result2->num_rows !== 1) {
        return false;
    }

    $row = $result2->fetch_assoc();
    //verify existing password
    if (!password_verify($currentPassword, $row["Password"])) {
        return false;
    }

    //hash password
    $newPasswordhashed = password_hash($newPassword, PASSWORD_DEFAULT);

    //update the database with the new password
    $uploadtodb = $this->conn->prepare(
        "UPDATE users SET Password = ? WHERE User_ID = ?"
    );
    $uploadtodb->bind_param("si", $newPasswordhashed, $userId);
    return $uploadtodb->execute();

}
}
?>