<?php
/* Name: SelectionClass.php 
   Description: This is a class that is used to select data from the database.
   Paraskevas Vafeiadis
   20-Mar-2026 v0.1
   Inputs: Depends on the functions but mostly actions from the dashbaords
   Outputs: Depends on the functions but mostly arrays
   Files in use: routes.php,admin_dashboard.php
   */


require_once __DIR__ . '/databaseconnect.php';

class SelectionClass{
private $conn;

function __construct() {
    $this->conn = ConnectToDatabase();
}

function getDegrees(?int $department_id = null) {
    $sql = "SELECT degree.DegreeID, degree.DegreeName, degree.DepartmentID, departments.DepartmentName, departments.DepartmentName AS Department_Name FROM degree JOIN departments ON degree.DepartmentID = departments.DepartmentID";
    $params = [];

    if ($department_id !== null && $department_id > 0) {
        $sql .= " WHERE degree.DepartmentID = :department_id";
        $params['department_id'] = $department_id;
    }

    $sql .= " ORDER BY departments.DepartmentName ASC, degree.DegreeName ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute($params);
    $degrees = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if (empty($degrees)) {
        return [];
    }
    return $degrees;
    }

function getDepartment() {
    $sql = "SELECT DepartmentID, DepartmentName FROM departments ORDER BY DepartmentName ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    $departments = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if(empty($departments)){
        return [];
    }
    return $departments;
    }

}


