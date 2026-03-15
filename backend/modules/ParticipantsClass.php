<?php
/* Name: ParicipantsClass
   Description: This class is responsible for handling the processing of the assignment of students to advisors.
   Paraskevas Vafeiadis
   08-Mar-2026 v0.1
   Inputs: Depends on the functions but mostly arrays of IDs
   Outputs: Depends on the functions but mostly arrays of IDs or boolean values
   Files in Use: routes.php, AdminController.php, admin_dashboard.php
   
   15-Mar-2026 v0.2
   added random assignment feature that works with a roundrobin function
   Paraskevas Vafeiadis

   
   */




class Participants_Processing{
    private $conn;

    public function __construct() {
    //creating an obj of the mysql connection and connect to the database
    $this->conn = new mysqli("localhost", "root", "", "advicut");
    if ($this->conn->connect_error) { //if connection fails kill it and print message
    die("Connection failed: " . $this->conn->connect_error);}
    $this ->conn->set_charset("utf8mb4");
    }

        public function Get_Student_Advisor(): array {
            $sql = "SELECT Advisor_ID, Student_ID FROM student_advisors";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $map = [];

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $advisorId = (int)$row['Advisor_ID'];
                    $studentId = (int)$row['Student_ID'];
                    if (!isset($map[$advisorId])) {
                        $map[$advisorId] = [];
                    }
                    $map[$advisorId][$studentId] = true;
                }
            }

            return $map;
        }

        public function Assign_Students_Advisors(): array {
            $sql = "SELECT Student_ID, Advisor_ID FROM student_advisors";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $map = [];

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $studentId = (int)$row['Student_ID'];
                    $advisorId = (int)$row['Advisor_ID'];
                    if (!isset($map[$studentId])) {
                        $map[$studentId] = [];
                    }
                    $map[$studentId][] = $advisorId;
                }
            }

            return $map;
        }

        public function Replace_Advisor_Students(int $advisorId, array $studentIds): bool {
            if ($advisorId <= 0) {
                return false;
            }

            $checkIds = [];
            foreach ($studentIds as $studentId) {
                $studentId = (int)$studentId;
                if ($studentId > 0) {
                    $checkIds[$studentId] = true;
                }
            }
            $checkIds = array_keys($checkIds);
            $this->conn->begin_transaction();

            try {
                $deleteStmt = $this->conn->prepare("DELETE FROM student_advisors WHERE Advisor_ID = ?");
                $deleteStmt->bind_param("i", $advisorId);
                if (!$deleteStmt->execute()) {
                    throw new Exception('Failed to clear previous advisor assignments');
                }

                if (!empty($checkIds)) {
                    $insertStmt = $this->conn->prepare("INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?)");
                    foreach ($checkIds as $studentId) {
                        $insertStmt->bind_param("ii", $studentId, $advisorId);
                        if (!$insertStmt->execute()) {
                            throw new Exception('Failed to save advisor assignments');
                        }
                    }
                }

                $this->conn->commit();
                return true;
            } catch (Throwable $e) {
                $this->conn->rollback();
                return false;
            }
        }

        public function Get_Advisor(int $studentid){
            $sql = "SELECT Advisor_ID FROM student_advisors WHERE Student_ID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $studentid);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['Advisor_ID']; // Return the advisor ID
            } else {
                return null; // Return null if no advisor is assigned to the student
            }
        }


        public function RandomAssignment(): bool {
            $this->conn->begin_transaction();

            try {
                // Select only students that do not have an advisor yet.
                $sql = "SELECT u.External_ID FROM users u LEFT JOIN student_advisors sa ON sa.Student_ID = u.External_ID WHERE u.Role = 'Student' AND u.External_ID IS NOT NULL AND sa.Student_ID IS NULL ORDER BY u.External_ID ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                $students = [];
                while ($row = $result->fetch_assoc()) {
                    $students[] = (int)$row['External_ID'];
                }

                //select all advisors inside the database
                $sql = "SELECT External_ID FROM users WHERE Role = 'Advisor' AND External_ID IS NOT NULL ORDER BY External_ID ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                $advisors = [];
                while ($row = $result->fetch_assoc()) {
                    $advisors[] = (int)$row['External_ID'];
                }

                // If no advisors exist, we cannot assign anybody.
                if (empty($advisors)) {
                    throw new Exception('Missing students or advisors for random assignment');
                }

                // Nothing new to assign 
                if (empty($students)) {
                    $this->conn->commit();
                    return true;
                }

                //shuffle all students and advisors to create a random order
                shuffle($students);
                shuffle($advisors);

                // Assign only unassigned students in round robin fashion.
                $advisorCount = count($advisors);
                $insertStmt = $this->conn->prepare("INSERT INTO student_advisors (Student_ID, Advisor_ID) VALUES (?, ?) ON DUPLICATE KEY UPDATE Advisor_ID = Advisor_ID");
                for ($i = 0; $i < count($students); $i++) {
                    $studentId = $students[$i];
                    $advisorId = $advisors[$i % $advisorCount];
                    $insertStmt->bind_param("ii", $studentId, $advisorId);
                    if (!$insertStmt->execute()) {
                        throw new Exception('Failed to save random assignment row');
                    }
                }

                $this->conn->commit();
                return true;
            } catch (Throwable $e) {
                $this->conn->rollback();
                return false;
            }
        }
}
?>

