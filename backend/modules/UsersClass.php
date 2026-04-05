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
*/

session_start();
class Users {
private $conn;

public function __construct() {
    //creating an obj of the mysql connection and connect to the database 
    $this->conn = new mysqli("localhost", "root", "", "advicut_test");
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
            header("Location: ../../frontend/index.php?error=invalid1");
            exit();
        }

        $row = $result1->fetch_assoc();//error handling if password wrong go bakc to index
        if (!password_verify($password, $row["Password"])) {
            header("Location: ../../frontend/index.php?error=invalid2");
            exit();
        }

        $this->Validate_Credentials($row);
        
    }

//method to kill the session of the user and log them out.
public function Log_out() {
    $_SESSION = [];
    session_destroy();

    header("location: ../../frontend/index.php");
    exit();
}

public function Check_Session(string $requiredRole = null) {
    //check all expected session values exist
    if (!isset($_SESSION['email']) || !isset($_SESSION['UserID']) || !isset($_SESSION['role'])) {
        header("location: ../../frontend/index.php?error=unauthorized");
        exit();
    }

    //userid is numbers and not a invalid number 
    $userId = intval($_SESSION['UserID']);
    if ($userId <= 0) {
        header("location: ../../frontend/index.php?error=unauthorized");
        exit();
    }

    //query to get the row froim the data base
    $stmt = $this->conn->prepare("SELECT Uni_Email, Role FROM users WHERE User_ID = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        header("location: ../../frontend/index.php?error=unauthorized");
        exit();
    }
    $result3 = $result->fetch_assoc();

    //chekc if the email and role in the session match the database
    if ($result3['Uni_Email'] !== $_SESSION['email'] || $result3['Role'] !== $_SESSION['role']) {
        header("location: ../../frontend/index.php?error=unauthorized");
        exit();
    }

    //if not the required role for the page then exit
    if ($requiredRole !== null && $result3['Role'] !== $requiredRole) {
        header("location: ../../frontend/index.php?error=forbidden");
        exit();
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

            if ($_SESSION['role'] == 'Student') {
                header("Location: ../../frontend/student_dashboard.php");
                exit();
            }
            else if ($_SESSION['role'] == 'Advisor') {
                header("Location: ../../frontend/advisor_dashboard.php");
                exit();
            }
            else if ($_SESSION['role'] == 'Admin') {
                header("Location: ../../frontend/admin_dashboard.php");
                exit();
            }
            else if ($_SESSION['role'] == 'SuperUser') {
                header("Location: ../../frontend/SuperUser_dashboard.php");
                exit();
            }
        else {
            
            header("location: ../../frontend/index.php");
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