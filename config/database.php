<?php
class Database {
    // Ambil dari environment variable Render
    // atau fallback ke local
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Render MySQL / PlanetScale / Railway pakai env vars
        $this->host     = getenv('DB_HOST')     ?: 'localhost';
        $this->db_name  = getenv('DB_NAME')     ?: 'attendance_db';
        $this->username = getenv('DB_USER')     ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->port     = getenv('DB_PORT')     ?: '3306';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // Jangan tampilkan error detail di production
            die(json_encode([
                'status' => 'error',
                'message' => 'Database connection failed'
            ]));
        }
        return $this->conn;
    }
}
?>
