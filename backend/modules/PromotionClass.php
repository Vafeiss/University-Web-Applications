<?php 
/* Name: PromotionClass.php
   Description: A class that will enable every year at semptember 9 to promote the students to the next year automatically.
   Paraskevas Vafeiadis
   26-Mar-2026
   Inputs: Admin's log in status (When an admin logs in, the system will check the date && year check logs and run the promotion query if its sep 9)
   Outputs: Promoted students to the next year, and updated the logs with the date of promotion and the year of promotion.
   ErrorMessages: Promotion failed, Query failed, Database connection failed, Invalid log in status.
   Files in use: database.php, routes.php , admin_dashboard.php
*/  
require_once __DIR__ . '/databaseconnect.php';

class PromotionClass {
private $conn;

function __construct() {
    $this->conn = ConnectToDatabase();
}

//function to promote students to the next year, this will run every year on Semptember 9th.
function promoteStudents(){
    date_default_timezone_set('Asia/Nicosia');

    $month = (int)date('m');
    $day = (int)date('d');
    $year = (int)date('Y');

    if(!($month == 9 && $day == 9 )){
        return false;
    }

    try{
        //check if the promotion already happened for this year
        $sql = "SELECT * FROM promotion_log WHERE promotion_year = :year";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['year' => $year]);
        $log = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if($log){
            return false;
        }

        //begin transaction for the promotion process
        $this->conn->beginTransaction();

        //delete the students that are in the 6th year
        $sql = "DELETE FROM students WHERE year = 6";
        $deleteStmt = $this->conn->prepare($sql);
        $deleteStmt->execute();

        //update students year by 1
        $sql = "UPDATE students SET year = year + 1";
        $updateStmt = $this->conn->prepare($sql);
        $updateStmt->execute();

        //add a log that the promotion happend the current year so that it wont trigger twice
        $sql = "INSERT INTO promotion_log (promotion_year, executed_at) VALUES (:year, NOW())";
        $insertLogStmt = $this->conn->prepare($sql);
        $insertLogStmt->execute(['year' => $year]);

        $this->conn->commit();
        return true;

        }catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
            }
            die("Promotion failed: " . $e->getMessage());
            }
    }
}






