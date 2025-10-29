<?php
/**
 * Database Configuration and Connection Class
 * Shoe Retail ERP System
 * Author: Generated for PHP/MySQL Implementation
 * Date: 2024
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration
    private $host = 'localhost';
    private $database = 'shoeretailerp';
    private $username = 'root';  // Change to your MySQL username
    private $password = 'DaniloNagaaralSaUCC';      // Change to your MySQL password
    private $charset = 'utf8mb4';
    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    /**
     * Private constructor to prevent multiple instances
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $this->connection = new PDO($dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a prepared statement
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Parameters: " . json_encode($params));
            throw new Exception("Database query failed.");
        }
    }
    
    /**
     * Fetch a single row
     */
    public function fetchOne($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert($query, $params = []) {
        $this->execute($query, $params);
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update record and return affected rows
     */
    public function update($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Delete record and return affected rows
     */
    public function delete($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Call stored procedure
     */
    public function callProcedure($procedureName, $params = []) {
        try {
            $placeholders = str_repeat('?,', count($params));
            $placeholders = rtrim($placeholders, ',');
            
            $query = "CALL {$procedureName}({$placeholders})";
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            
            // For procedures that return result sets
            $results = [];
            do {
                try {
                    $result = $stmt->fetchAll();
                    if (!empty($result)) {
                        $results[] = $result;
                    }
                } catch (PDOException $e) {
                    // No more result sets
                    break;
                }
            } while ($stmt->nextRowset());
            
            return $results;
        } catch (PDOException $e) {
            error_log("Stored procedure call failed: " . $e->getMessage());
            error_log("Procedure: " . $procedureName);
            error_log("Parameters: " . json_encode($params));
            throw new Exception("Stored procedure execution failed.");
        }
    }
    
    /**
     * Prevent cloning
     */
    public function __clone() {
        throw new Exception("Cannot clone database instance.");
    }
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize database instance.");
    }
}

/**
 * Global database helper functions
 */

/**
 * Get database instance
 */
function getDB() {
    return Database::getInstance();
}

/**
 * Execute query with parameters
 */
function dbExecute($query, $params = []) {
    return getDB()->execute($query, $params);
}

/**
 * Fetch single row
 */
function dbFetchOne($query, $params = []) {
    return getDB()->fetchOne($query, $params);
}

/**
 * Fetch all rows
 */
function dbFetchAll($query, $params = []) {
    return getDB()->fetchAll($query, $params);
}

/**
 * Insert and get last ID
 */
function dbInsert($query, $params = []) {
    return getDB()->insert($query, $params);
}

/**
 * Update and get affected rows
 */
function dbUpdate($query, $params = []) {
    return getDB()->update($query, $params);
}

/**
 * Delete and get affected rows
 */
function dbDelete($query, $params = []) {
    return getDB()->delete($query, $params);
}

/**
 * Call stored procedure
 */
function dbCallProcedure($procedureName, $params = []) {
    return getDB()->callProcedure($procedureName, $params);
}

/**
 * Sanitize input for security
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 */
function validatePhone($phone) {
    return preg_match('/^[\d\-\+\(\)\s]+$/', $phone);
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * JSON response helper
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Error logging helper
 */
function logError($message, $context = []) {
    $logMessage = $message;
    if (!empty($context)) {
        $logMessage .= " Context: " . json_encode($context);
    }
    $logPath = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    error_log(date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, 3, $logPath);
}

/**
 * Success logging helper
 */
function logInfo($message, $context = []) {
    $logMessage = $message;
    if (!empty($context)) {
        $logMessage .= " Context: " . json_encode($context);
    }
    $logPath = __DIR__ . '/../logs/info.log';
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    error_log(date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, 3, $logPath);
}
?>