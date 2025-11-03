<?php
// app/models/Database.php
class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "jobfinder";
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        if ($this->conn->connect_error) {
            die("Lỗi kết nối CSDL: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
    }
}
?>

