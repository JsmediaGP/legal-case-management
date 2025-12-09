<?php
// engine/config/db.php

class Database {
    private static $instance = null;
    private $pdo;

    private $host     = 'localhost';
    private $dbname   = 'legal_case_management';   // ← change if your DB name is different
    private $username = 'root';                    // ← your MySQL username
    private $password = '';                        // ← your MySQL password (usually empty in XAMPP)

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// Helper function used everywhere
function db() {
    return Database::getInstance()->getConnection();
}