<?php
/**
 * =====================================================
 * Database Configuration & Connection
 * ChatApp - MySQL 8+ | mysqli
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Database Configuration Constants
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'chatapp_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Encryption Key for Messages (AES-256-CBC)
 * Change this to a secure random string in production
 */
define('ENCRYPTION_KEY', 'ChatApp2024!SecureKey#AES256');

/**
 * Database Connection Instance
 */
$GLOBALS['db'] = null;

/**
 * Establish Database Connection using mysqli
 * 
 * @return mysqli|false Returns mysqli object on success, false on failure
 */
function db_connect() {
    // Check if connection already exists
    if ($GLOBALS['db'] !== null && $GLOBALS['db']->ping()) {
        return $GLOBALS['db'];
    }
    
    // Create new connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error (don't expose details in production)
        error_log('Database Connection Error: ' . $conn->connect_error);
        return false;
    }
    
    // Disable exception mode - handle errors gracefully
    mysqli_report(MYSQLI_REPORT_OFF);
    
    // Set charset
    if (!$conn->set_charset(DB_CHARSET)) {
        error_log('Database Charset Error: ' . $conn->error);
        $conn->close();
        return false;
    }
    
    // Enable strict mode
    $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
    // Store connection in global
    $GLOBALS['db'] = $conn;
    $GLOBALS['conn'] = $conn;
    
    return $conn;
}

/**
 * Get Database Connection
 * 
 * @return mysqli|null Returns mysqli object or null
 */
function db_get_connection() {
    return $GLOBALS['db'];
}

/**
 * Close Database Connection
 * 
 * @return void
 */
function db_close() {
    if ($GLOBALS['db'] !== null) {
        $GLOBALS['db']->close();
$GLOBALS['db'] = null;
$GLOBALS['conn'] = null;
    }
}

/**
 * Execute a Prepared Statement
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameter values
 * @param string $types Type string (i=integer, d=double, s=string, b=blob)
 * @return mysqli_stmt|false Returns statement object or false
 */
function db_prepare($sql, $params = [], $types = '') {
    $conn = db_connect();
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare Error: ' . $conn->error);
        return false;
    }
    
    // Bind parameters if provided
    if (!empty($params)) {
        // Auto-detect types if not provided
        if (empty($types)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
        }
        
        if (!$stmt->bind_param($types, ...$params)) {
            error_log('Bind Param Error: ' . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    return $stmt;
}

/**
 * Execute Query and Return Result
 * 
 * @param string $sql SQL query
 * @param array $params Parameter values
 * @param string $types Type string
 * @return mysqli_result|bool Returns result set or boolean
 */
function db_execute($sql, $params = [], $types = '') {
    $stmt = db_prepare($sql, $params, $types);
    if (!$stmt) {
        return false;
    }
    
    try {
        $result = $stmt->execute();
    } catch (Exception $e) {
        error_log('Execute Error: ' . $e->getMessage());
        $stmt->close();
        return false;
    }
    
    if (!$result) {
        error_log('Execute Error: ' . $stmt->error);
        $stmt->close();
        return false;
    }
    
    // Get result set for SELECT queries
    $resultSet = $stmt->get_result();
    $stmt->close();
    
    return $resultSet !== false ? $resultSet : $result;
}

/**
 * Fetch Single Row
 * 
 * @param string $sql SQL query
 * @param array $params Parameter values
 * @param string $types Type string
 * @return array|false Returns associative array or false
 */
function db_fetch_single($sql, $params = [], $types = '') {
    $result = db_execute($sql, $params, $types);
    if (!$result || !($result instanceof mysqli_result)) {
        return false;
    }
    
    $row = $result->fetch_assoc();
    $result->free();
    return $row;
}

/**
 * Fetch All Rows
 * 
 * @param string $sql SQL query
 * @param array $params Parameter values
 * @param string $types Type string
 * @return array Returns array of associative arrays
 */
function db_fetch_all($sql, $params = [], $types = '') {
    $result = db_execute($sql, $params, $types);
    if (!$result || !($result instanceof mysqli_result)) {
        return [];
    }
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
    return $rows;
}

/**
 * Get Insert ID
 * 
 * @return int Returns last insert ID
 */
function db_insert_id() {
    $conn = db_connect();
    return $conn ? $conn->insert_id : 0;
}

/**
 * Get Affected Rows
 * 
 * @return int Returns affected rows count
 */
function db_affected_rows() {
    $conn = db_connect();
    return $conn ? $conn->affected_rows : 0;
}

/**
 * Sanitize String for Database
 * 
 * @param string $string Input string
 * @return string Sanitized string
 */
function db_escape($string) {
    $conn = db_connect();
    if (!$conn) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    return $conn->real_escape_string($string);
}

// Register shutdown function to close connection
register_shutdown_function('db_close');

// Compatibility: set $conn global so legacy code works
function &db_get_conn() {
    $conn = db_connect();
    return $conn;
}

// Eagerly establish connection so $GLOBALS['conn'] is available for legacy code
db_connect();
