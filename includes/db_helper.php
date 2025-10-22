<?php
/**
 * Database Helper Functions
 * Provides consistent database operations across the application
 */

require_once __DIR__ . '/../config/database.php';

$_DB_INSTANCE = null;

/**
 * Get or create database connection
 */
function getDB() {
    global $_DB_INSTANCE;
    
    if ($_DB_INSTANCE === null) {
        try {
            $_DB_INSTANCE = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if ($_DB_INSTANCE->connect_error) {
                throw new Exception("Connection failed: " . $_DB_INSTANCE->connect_error);
            }
            $_DB_INSTANCE->set_charset("utf8mb4");
        } catch (Exception $e) {
            logError("Database connection failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    return $_DB_INSTANCE;
}

/**
 * Execute SELECT query and fetch all rows
 */
function dbFetchAll($query, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    } catch (Exception $e) {
        logError("Database fetch all error", ['query' => $query, 'error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Execute SELECT query and fetch single row
 */
function dbFetchOne($query, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    } catch (Exception $e) {
        logError("Database fetch one error", ['query' => $query, 'error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Execute INSERT query
 */
function dbInsert($query, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        
        return $insertId;
    } catch (Exception $e) {
        logError("Database insert error", ['query' => $query, 'error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Execute UPDATE/DELETE query
 */
function dbUpdate($query, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    } catch (Exception $e) {
        logError("Database update error", ['query' => $query, 'error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Execute arbitrary query
 */
function dbExecute($query, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        logError("Database execute error", ['query' => $query, 'error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Return JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log information
 */
function logInfo($message, $context = []) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] INFO: $message";
    
    if (!empty($context)) {
        $logMessage .= " | " . json_encode($context);
    }
    
    $logMessage .= "\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Log error
 */
function logError($message, $context = []) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message";
    
    if (!empty($context)) {
        $logMessage .= " | " . json_encode($context);
    }
    
    $logMessage .= "\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

?>
