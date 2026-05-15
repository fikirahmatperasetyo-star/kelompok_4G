<?php
class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $db   = "db_kopma"; 

    public $conn;

    public function __construct() {
        try {
            // Aktifkan pelaporan eror agar catch (Exception $e) berfungsi 
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
            
            // Set charset ke utf8 agar data teks aman
            $this->conn->set_charset("utf8");
            
        } catch (Exception $e) {
            // Jika nama database salah atau server mati, kode ini akan berjalan
            die("Koneksi Error: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>