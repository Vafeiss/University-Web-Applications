<?php
declare(strict_types=1);

class DatabaseConnect
{
    private string $host = "localhost";
    private string $username = "root";
    private string $password = "";
    private string $database = "advicut";

    public function connect(): mysqli
    {
        $conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }

        return $conn;
    }
}
?>