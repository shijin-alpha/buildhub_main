<?php
// Database connection class for compatibility

class Database {
    private $host = 'localhost';
    private $db_name = 'buildhub';
    private $username = 'root'; // use your MySQL app user
    private $password = ''; // use your MySQL password
    private $charset = 'utf8mb4';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// Create global $db variable for compatibility
$database = new Database();
$db = $database->getConnection();
?>