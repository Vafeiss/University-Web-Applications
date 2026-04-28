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
StudentAppointmentDashboard.php to test the login of student and AdvisorAppointmentDashboard.php to test the login of the advisor
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
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/databaseconnect.php';
require_once __DIR__ . '/../config/app.php';

class Users {
private PDO $conn;

private function redirectTo(string $path): void {
    $target = preg_match('/^https?:\/\//i', $path)
        ? $path
        : app_url($path);

    header('Location: ' . $target);
    exit();
}

//function to help the userclass to find the right dashboard to redirect the user after login.
//Based on role
private function dashboardPathForRole(string $role): ?string {
    $normalized = strtolower(trim($role));

    if ($normalized === 'student') {
        return 'frontend/StudentAppointmentDashboard.php';
    }
    if ($normalized === 'advisor') {
        return 'frontend/AdvisorAppointmentDashboard.php';
    }
    if ($normalized === 'admin') {
        return 'frontend/admin_dashboard.php';
    }
    if ($normalized === 'superuser') {
        return 'frontend/superuser_reports.php';
    }

    return null;
}

private function getLoginThrottleConfig(): array {
    return [
        'max_attempts' => 5,
        'window_seconds' => 900,
        'lockout_seconds' => 900,
    ];
}

private function getLoginThrottleBucketKey(string $email): string {
    $normalizedEmail = strtolower(trim($email));
    $clientIp = trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    return hash('sha256', $normalizedEmail . '|' . $clientIp);
}

private function isLoginThrottled(string $email): bool {
    $config = $this->getLoginThrottleConfig();
    $key = $this->getLoginThrottleBucketKey($email);
    $now = time();

    if (!isset($_SESSION['login_throttle']) || !is_array($_SESSION['login_throttle'])) {
        $_SESSION['login_throttle'] = [];
    }

    $bucket = $_SESSION['login_throttle'][$key] ?? ['attempts' => [], 'locked_until' => 0];
    $lockedUntil = (int)($bucket['locked_until'] ?? 0);

    if ($lockedUntil > $now) {
        return true;
    }

    $attempts = array_values(array_filter(
        (array)($bucket['attempts'] ?? []),
        static function ($timestamp) use ($now, $config): bool {
            return (int)$timestamp >= ($now - $config['window_seconds']);
        }
    ));

    if (count($attempts) === 0) {
        unset($_SESSION['login_throttle'][$key]);
    } else {
        $_SESSION['login_throttle'][$key] = [
            'attempts' => $attempts,
            'locked_until' => 0,
        ];
    }

    return false;
}

private function recordFailedLoginAttempt(string $email): bool {
    $config = $this->getLoginThrottleConfig();
    $key = $this->getLoginThrottleBucketKey($email);
    $now = time();

    if (!isset($_SESSION['login_throttle']) || !is_array($_SESSION['login_throttle'])) {
        $_SESSION['login_throttle'] = [];
    }

    $bucket = $_SESSION['login_throttle'][$key] ?? ['attempts' => [], 'locked_until' => 0];
    $attempts = array_values(array_filter(
        (array)($bucket['attempts'] ?? []),
        static function ($timestamp) use ($now, $config): bool {
            return (int)$timestamp >= ($now - $config['window_seconds']);
        }
    ));

    $attempts[] = $now;
    $lockedUntil = 0;
    if (count($attempts) >= $config['max_attempts']) {
        $lockedUntil = $now + $config['lockout_seconds'];
        $attempts = [];
    }

    $_SESSION['login_throttle'][$key] = [
        'attempts' => $attempts,
        'locked_until' => $lockedUntil,
    ];

    return $lockedUntil > $now;
}

private function clearLoginThrottle(string $email): void {
    if (!isset($_SESSION['login_throttle']) || !is_array($_SESSION['login_throttle'])) {
        return;
    }

    $key = $this->getLoginThrottleBucketKey($email);
    unset($_SESSION['login_throttle'][$key]);
}

private function isStrongPassword(string $password): bool {
    if (strlen($password) < 10 || strlen($password) > 72) {
        return false;
    }

    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }

    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }

    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return false;
    }

    return true;
}

public function __construct() {
    //connect using the shared PDO connection used by backend modules
    $this->conn = ConnectToDatabase();
} 


    //method to log in the user by checking email and password to the advicut database
    public function Log_in(string $email, string $password) {
        try {
            if ($this->isLoginThrottled($email)) {
                $this->redirectTo('frontend/index.php?error=throttled');
            }

            //query to get all the students where email and password match the input parameters
            $sql = "SELECT User_ID , Uni_Email , Role , Password FROM users WHERE Uni_Email = ? LIMIT 1";
            $stmt1 = $this->conn->prepare($sql);
            $stmt1->execute([$email]); //make the query as a prepared statement to prevent attacks
            $row = $stmt1->fetch(PDO::FETCH_ASSOC);

            if ($row === false) { //error handling if email not found go back to index
                if ($this->recordFailedLoginAttempt($email)) {
                    $this->redirectTo('/frontend/index.php?error=throttled');
                }
                $this->redirectTo('/frontend/index.php?error=invalid');
            }

            //error handling if password wrong go back to index
            if (!password_verify($password, (string)$row["Password"])) {
                if ($this->recordFailedLoginAttempt($email)) {
                    $this->redirectTo('/frontend/index.php?error=throttled');
                }
                $this->redirectTo('/frontend/index.php?error=invalid');
            }

            $this->clearLoginThrottle($email);

            $this->Validate_Credentials($row);
        } catch (PDOException $e) {
            error_log('Users::Log_in PDO error: ' . $e->getMessage());
            $this->redirectTo('frontend/index.php?error=database');
        }
        
    }

//method to kill the session of the user and log them out.
public function Log_out() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
    }

    $_SESSION = [];
    session_destroy();

    $this->redirectTo('frontend/index.php');
}

public function Check_Session(?string $requiredRole = null) {
    // UserID is the primary key for an authenticated session.
    if (!isset($_SESSION['UserID'])) {
        $this->redirectTo('frontend/index.php?error=unauthorized');
    }

    //userid is numbers and not a invalid number 
    $userId = intval($_SESSION['UserID']);
    if ($userId <= 0) {
        $this->redirectTo('frontend/index.php?error=unauthorized');
    }

    try {
        //query to get the row from the database
        $stmt = $this->conn->prepare("SELECT Uni_Email, Role FROM users WHERE User_ID = ? LIMIT 1");
        $stmt->execute([$userId]);
        $result3 = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result3 === false) {
            $this->redirectTo('frontend/index.php?error=unauthorized');
        }

        // Keep session values aligned with DB to avoid unnecessary logouts on refresh.
        $_SESSION['email'] = $result3['Uni_Email'];
        $_SESSION['role'] = $result3['Role'];

        //if not the required role for the page then exit
        if ($requiredRole !== null && strcasecmp(trim((string)$result3['Role']), trim((string)$requiredRole)) !== 0) {
            $this->redirectTo('frontend/index.php?error=forbidden');
        }
    } catch (PDOException $e) {
        error_log('Users::Check_Session PDO error: ' . $e->getMessage());
        $this->redirectTo('frontend/index.php?error=unauthorized');
    }
}

public function Validate_Credentials($row) {
    if($row != NULL) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['email'] = $row['Uni_Email']; //storing info while user logged in
            $_SESSION['UserID'] = $row['User_ID'];
            $_SESSION['role'] = $row['Role'];}
        else {
        $this->redirectTo('frontend/index.php?error=invalid');
        }
            //redirect them to the right dashboard based on their role if the session it's valid and the credentials are correct
            if ($_SESSION['role'] == 'Student') {
                $this->redirectTo('frontend/StudentAppointmentDashboard.php');
            }
            else if ($_SESSION['role'] == 'Advisor') {
                $this->redirectTo('frontend/AdvisorAppointmentDashboard.php');
            }
            else if ($_SESSION['role'] == 'Admin') {
                $this->redirectTo('frontend/admin_dashboard.php');
            }
            else if ($_SESSION['role'] == 'SuperUser') {
                $this->redirectTo('frontend/superuser_reports.php');
            }
        else {
            $fallbackPath = $this->dashboardPathForRole((string)($_SESSION['role'] ?? ''));
            if ($fallbackPath !== null) {
                $this->redirectTo($fallbackPath);
            }
            $this->redirectTo('frontend/index.php');
        }
}

//method to reset the given password of the user to his own.
public function Change_Password(int $userId, string $currentPassword, string $newPassword): bool
{
    if (!$this->isStrongPassword($newPassword)) {
        return false;
    }
    try {
        $stmt = $this->conn->prepare(
            "SELECT Password FROM users WHERE User_ID = ? LIMIT 1"
        );
        $stmt->execute([$userId]); //get the current password to verify

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return false;
        }

        //verify existing password
        if (!password_verify($currentPassword, (string)$row["Password"])) {
            return false;
        }

        if (password_verify($newPassword, (string)$row["Password"])) {
            return false;
        }

        //hash password
        $newPasswordhashed = password_hash($newPassword, PASSWORD_DEFAULT);

        //update the database with the new password
        $uploadtodb = $this->conn->prepare(
            "UPDATE users SET Password = ? WHERE User_ID = ?"
        );
        $uploadtodb->execute([$newPasswordhashed, $userId]);
        return $uploadtodb->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Users::Change_Password PDO error: ' . $e->getMessage());
        return false;
    }

}
}
?>
