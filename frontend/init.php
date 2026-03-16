<?php
/* Name: init.php
   Description: Initializes the session and checks it for expiration.
   Paraskevas Vafeiadis
   Inputs: Session data
   Outputs: Redirects you to the login page if session expired
   Files in use: Included in all of frontend pages
   28-Fev-2026 v0.1
   */

//Auto load class from backend
spl_autoload_register(function($class){
require_once __DIR__ . '/../backend/modules/' . $class . '.php';

});
//check if session is expired and if last activity is more than 30 minutes ago then kill
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    $_SESSION = [];
    session_destroy();
    header("Location: index.php?error=session_expired");
    exit();
}

//keep activity timestamp updated
$_SESSION['LAST_ACTIVITY'] = time();
?>
