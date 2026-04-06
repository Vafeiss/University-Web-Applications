<?php
/*
  NAME: Super User Reports Class
  Description: Standalone class for super user reports and statistics
  Panteleimoni Alexandrou
  06-Apr-2026 v0.1
  Inputs: Optional filters for department, degree and year
  Outputs: Summary statistics, filtered students, advisor assignment counts
*/

declare(strict_types=1);

class SuperUserReportsClass
{
    private PDO $conn;

    public function __construct()
    {
        $host = "localhost";
        $dbname = "advicut";
        $username = "root";
        $password = "";

        $this->conn = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password
        );

        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function getDepartments(): array
    {
        try {
            $sql = "SELECT DepartmentID, DepartmentName FROM departments ORDER BY DepartmentName ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getDegrees(?int $departmentId = null): array
    {
        try {
            if ($departmentId !== null && $departmentId > 0) {
                $sql = "SELECT DegreeID, DegreeName, DepartmentID FROM degree WHERE DepartmentID = :department_id ORDER BY DegreeName ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll();
            }

            $sql = "SELECT DegreeID, DegreeName, DepartmentID FROM degree ORDER BY DegreeName ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getSummary(?int $departmentId = null, ?int $degreeId = null, ?int $year = null): array
    {
        $summary = [
            'total_students' => 0,
            'total_advisors' => 0,
            'assigned_students' => 0,
            'unassigned_students' => 0
        ];

        try {
            $studentWhere = ["u.Role = 'Student'"];
            $studentParams = [];

            if ($departmentId !== null && $departmentId > 0) {
                $studentWhere[] = "d.DepartmentID = :department_id";
                $studentParams[':department_id'] = $departmentId;
            }

            if ($degreeId !== null && $degreeId > 0) {
                $studentWhere[] = "deg.DegreeID = :degree_id";
                $studentParams[':degree_id'] = $degreeId;
            }

            if ($year !== null && $year > 0) {
                $studentWhere[] = "s.Year = :year";
                $studentParams[':year'] = $year;
            }

            $studentWhereSql = implode(' AND ', $studentWhere);

            $sqlStudents = "
                SELECT COUNT(*) AS total_students
                FROM users u
                INNER JOIN students s ON u.User_ID = s.User_ID
                INNER JOIN degree deg ON u.Department_ID = deg.DegreeID
                INNER JOIN departments d ON deg.DepartmentID = d.DepartmentID
                WHERE $studentWhereSql
            ";
            $stmtStudents = $this->conn->prepare($sqlStudents);
            foreach ($studentParams as $key => $value) {
                $stmtStudents->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmtStudents->execute();
            $summary['total_students'] = (int)($stmtStudents->fetch()['total_students'] ?? 0);

            $sqlAdvisors = "SELECT COUNT(*) AS total_advisors FROM users WHERE Role = 'Advisor'";
            $stmtAdvisors = $this->conn->prepare($sqlAdvisors);
            $stmtAdvisors->execute();
            $summary['total_advisors'] = (int)($stmtAdvisors->fetch()['total_advisors'] ?? 0);

            $sqlAssigned = "
                SELECT COUNT(DISTINCT u.External_ID) AS assigned_students
                FROM users u
                INNER JOIN students s ON u.User_ID = s.User_ID
                INNER JOIN degree deg ON u.Department_ID = deg.DegreeID
                INNER JOIN departments d ON deg.DepartmentID = d.DepartmentID
                INNER JOIN student_advisors sa ON sa.Student_ID = u.External_ID
                WHERE $studentWhereSql
            ";
            $stmtAssigned = $this->conn->prepare($sqlAssigned);
            foreach ($studentParams as $key => $value) {
                $stmtAssigned->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmtAssigned->execute();
            $summary['assigned_students'] = (int)($stmtAssigned->fetch()['assigned_students'] ?? 0);

            $summary['unassigned_students'] = max(0, $summary['total_students'] - $summary['assigned_students']);
        } catch (Throwable $e) {
            return $summary;
        }

        return $summary;
    }

    public function getFilteredStudents(?int $departmentId = null, ?int $degreeId = null, ?int $year = null): array
    {
        try {
            $where = ["u.Role = 'Student'"];
            $params = [];

            if ($departmentId !== null && $departmentId > 0) {
                $where[] = "d.DepartmentID = :department_id";
                $params[':department_id'] = $departmentId;
            }

            if ($degreeId !== null && $degreeId > 0) {
                $where[] = "deg.DegreeID = :degree_id";
                $params[':degree_id'] = $degreeId;
            }

            if ($year !== null && $year > 0) {
                $where[] = "s.Year = :year";
                $params[':year'] = $year;
            }

            $whereSql = implode(' AND ', $where);

            $sql = "
                SELECT
                    u.External_ID AS Student_ID,
                    u.First_name,
                    u.Last_Name,
                    u.Uni_Email,
                    s.Year,
                    deg.DegreeName,
                    d.DepartmentName,
                    sa.Advisor_ID
                FROM users u
                INNER JOIN students s ON u.User_ID = s.User_ID
                INNER JOIN degree deg ON u.Department_ID = deg.DegreeID
                INNER JOIN departments d ON deg.DepartmentID = d.DepartmentID
                LEFT JOIN student_advisors sa ON sa.Student_ID = u.External_ID
                WHERE $whereSql
                ORDER BY s.Year ASC, u.Last_Name ASC, u.First_name ASC
            ";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getAdvisorStudentCounts(?int $departmentId = null, ?int $degreeId = null, ?int $year = null): array
    {
        try {
            $extraConditions = [];
            $params = [];

            if ($departmentId !== null && $departmentId > 0) {
                $extraConditions[] = "d.DepartmentID = :department_id";
                $params[':department_id'] = $departmentId;
            }

            if ($degreeId !== null && $degreeId > 0) {
                $extraConditions[] = "deg.DegreeID = :degree_id";
                $params[':degree_id'] = $degreeId;
            }

            if ($year !== null && $year > 0) {
                $extraConditions[] = "s.Year = :year";
                $params[':year'] = $year;
            }

            $filterSql = '';
            if (!empty($extraConditions)) {
                $filterSql = ' AND ' . implode(' AND ', $extraConditions);
            }

            $sql = "
                SELECT
                    a.External_ID AS Advisor_ID,
                    a.First_name,
                    a.Last_Name,
                    COUNT(DISTINCT st.External_ID) AS Total_Students
                FROM users a
                LEFT JOIN student_advisors sa ON sa.Advisor_ID = a.External_ID
                LEFT JOIN users st ON st.External_ID = sa.Student_ID AND st.Role = 'Student'
                LEFT JOIN students s ON st.User_ID = s.User_ID
                LEFT JOIN degree deg ON st.Department_ID = deg.DegreeID
                LEFT JOIN departments d ON deg.DepartmentID = d.DepartmentID
                WHERE a.Role = 'Advisor'
                $filterSql
                GROUP BY a.External_ID, a.First_name, a.Last_Name
                ORDER BY Total_Students DESC, a.Last_Name ASC, a.First_name ASC
            ";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}