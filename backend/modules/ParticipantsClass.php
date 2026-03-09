<?php
class Participants_Processing{
    private $conn;

    public function __construct() {
    //creating an obj of the mysql connection and connect to the database
    $this->conn = new mysqli("localhost", "root", "", "advicut");
    if ($this->conn->connect_error) { //if connection fails kill it and print message
    die("Connection failed: " . $this->conn->connect_error);}
    $this ->conn->set_charset("utf8mb4");
    }

    
    public function Assign_Students_Advisors(int $studentId, int $advisorId) {
        $sql = "SELECT * FROM student_advsiors WHERE Student_ID = ? AND Advisor_ID = ?";
        $stmt1 = $this->conn->prepare($sql);
        $stmt1->bind_param("ii", $studentId, $advisorId); //
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        if ($result1->num_rows > 0) {
            while ($row = $result1->fetch_assoc()) {
                if ($row['Student_ID'] == $studentId && $row['Advisor_ID'] == $advisorId) {
                    return false; // Return false if the student is already assigned to the advisor
                }
            }
        }
        //if everythins is fine insert the pair into the table.
        $sql = "INSERT INTO student_advsiors (Student_ID, Advisor_ID) VALUES (?, ?)";
        $stmt2 = $this->conn->prepare($sql);
        $stmt2->bind_param("ii", $studentId, $advisorId);
        if ($stmt2->execute()) {
            return true; // Return true if the update was successful
        } else {
            return false; // Return false if there was an error
        }
    }

}
?>

