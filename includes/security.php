<?php
/**
 * =====================================================
 * Security Layer
 * ChatApp - Comprehensive Security System
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Security Class
 * Handles all security operations for ChatApp
 */
class Security {
    
    private static $instance = null;
    private $db = null;
    
    /**
     * Singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Use db_connect() to get the connection
        if (function_exists('db_connect')) {
            $this->db = db_connect();
        } else {
            global $conn;
            $this->db = $conn;
        }
    }
    
    /**
     * ==================== SECURITY HEADERS ====================
     */
    public function sendSecurityHeaders() {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: ' . HEADERS_CONTENT_TYPE_OPTIONS);
        
        // Prevent clickjacking
        header('X-Frame-Options: ' . HEADERS_X_FRAME_OPTIONS);
        
        // XSS Protection
        header('X-XSS-Protection: ' . HEADERS_X_XSS_PROTECTION);
        
        // HSTS (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: ' . HEADERS_STRICT_TRANSPORT_SECURITY);
        }
        
        // Referrer Policy
        header('Referrer-Policy: ' . HEADERS_REFERRER_POLICY);
        
        // Permissions Policy
        header('Permissions-Policy: ' . HEADERS_PERMISSIONS_POLICY);
        
        // Content Security Policy
        if (XSS_CSP_ENABLED) {
            $this->sendCSPHeaders();
        }
        
        // Remove server signature
        header('Server: ');
        header('X-Powered-By: ');
    }
    
    /**
     * Content Security Policy Headers
     */
    private function sendCSPHeaders() {
        $nonce = $this->generateNonce();
        $_SERVER['csp_nonce'] = $nonce;
        
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "img-src 'self' data: blob: https:",
            "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com",
            "connect-src 'self'",
            "media-src 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        header('Content-Security-Policy: ' . implode('; ', $csp));
    }
    
    /**
     * Generate CSP nonce
     */
    public function generateNonce() {
        return base64_encode(random_bytes(16));
    }
    
    /**
     * ==================== CSRF PROTECTION ====================
     */
    public function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate new token
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        
        // Store in session with timestamp
        $_SESSION[CSRF_TOKEN_NAME] = [
            'token' => $token,
            'created' => time()
        ];
        
        return $token;
    }
    
    /**
     * Validate CSRF Token
     */
    public function validateCSRFToken($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Get token from input or POST
        $inputToken = $token ?? $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        // Get stored token
        $storedToken = $_SESSION[CSRF_TOKEN_NAME] ?? null;
        
        if (!$storedToken || !$inputToken) {
            return false;
        }
        
        // Check expiry
        if (time() - $storedToken['created'] > CSRF_TOKEN_EXPIRY) {
            unset($_SESSION[CSRF_TOKEN_NAME]);
            return false;
        }
        
        // Compare tokens (timing-safe)
        return hash_equals($storedToken['token'], $inputToken);
    }
    
    /**
     * Get CSRF token field HTML
     */
    public function csrfField() {
        $token = $this->generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get CSRF token for AJAX
     */
    public function getCSRFToken() {
        return $this->generateCSRFToken();
    }
    
    /**
     * ==================== RATE LIMITING ====================
     */
    public function checkRateLimit($identifier, $action, $customLimit = null) {
        $limit = $customLimit ?? (RATE_LIMIT_MAX_ATTEMPTS[$action] ?? RATE_LIMIT_MAX_ATTEMPTS['api_general']);
        $window = RATE_LIMIT_WINDOW;
        
        $key = "rate_limit:{$action}:{$identifier}";
        
        $query = "SELECT attempts, first_attempt_at, last_attempt_at 
                  FROM rate_limits 
                  WHERE ip_address = ? AND action_type = ? 
                  AND last_attempt_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 'ssi', $identifier, $action, $window);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $record = mysqli_fetch_assoc($result);
        
        if ($record) {
            if ($record['attempts'] >= $limit) {
                return false; // Rate limit exceeded
            }
            
            // Increment attempts
            $update = "UPDATE rate_limits SET attempts = attempts + 1, last_attempt_at = NOW() 
                       WHERE ip_address = ? AND action_type = ?";
            $stmt = mysqli_prepare($this->db, $update);
            mysqli_stmt_bind_param($stmt, 'ss', $identifier, $action);
            mysqli_stmt_execute($stmt);
        } else {
            // Create new record
            $insert = "INSERT INTO rate_limits (ip_address, action_type, attempts, first_attempt_at, last_attempt_at) 
                       VALUES (?, ?, 1, NOW(), NOW())";
            $stmt = mysqli_prepare($this->db, $insert);
            mysqli_stmt_bind_param($stmt, 'ss', $identifier, $action);
            mysqli_stmt_execute($stmt);
        }
        
        return true;
    }
    
    /**
     * Get rate limit info
     */
    public function getRateLimitInfo($identifier, $action) {
        $window = RATE_LIMIT_WINDOW;
        
        $query = "SELECT attempts, first_attempt_at 
                  FROM rate_limits 
                  WHERE ip_address = ? AND action_type = ? 
                  AND last_attempt_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 'ssi', $identifier, $action, $window);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_fetch_assoc($result);
    }
    
    /**
     ==================== LOGIN ATTEMPTS ====================
     */
    public function recordLoginAttempt($identifier, $success = false) {
        if ($success) {
            // Clear attempts on successful login
            $query = "DELETE FROM login_attempts WHERE identifier = ?";
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, 's', $identifier);
            mysqli_stmt_execute($stmt);
            return true;
        }
        
        // Check if currently locked out
        if ($this->isLoginLocked($identifier)) {
            return false;
        }
        
        // Record attempt
        $query = "INSERT INTO login_attempts (identifier, ip_address, user_agent, attempted_at) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($this->db, $query);
        $ip = $this->getClientIP();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        mysqli_stmt_bind_param($stmt, 'sss', $identifier, $ip, $ua);
        mysqli_stmt_execute($stmt);
        
        // Check if should lock
        $attempts = $this->getLoginAttempts($identifier);
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $this->lockLogin($identifier);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get login attempts count
     */
    public function getLoginAttempts($identifier) {
        $query = "SELECT COUNT(*) as count FROM login_attempts 
                  WHERE identifier = ? 
                  AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 'si', $identifier, LOGIN_ATTEMPT_WINDOW);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        return (int)($row['count'] ?? 0);
    }
    
    /**
     * Check if login is locked
     */
    public function isLoginLocked($identifier) {
        $query = "SELECT id FROM login_lockouts 
                  WHERE identifier = ? AND locked_until > NOW()";
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 's', $identifier);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_num_rows($result) > 0;
    }
    
    /**
     * Lock login attempts
     */
    private function lockLogin($identifier) {
        $query = "INSERT INTO login_lockouts (identifier, locked_at, locked_until) 
                  VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))
                  ON DUPLICATE KEY UPDATE locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND)";
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 'sii', $identifier, LOGIN_LOCKOUT_DURATION, LOGIN_LOCKOUT_DURATION);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Get lockout time remaining
     */
    public function getLockoutRemaining($identifier) {
        $query = "SELECT TIMESTAMPDIFF(SECOND, NOW(), locked_until) as remaining 
                  FROM login_lockouts 
                  WHERE identifier = ? AND locked_until > NOW()";
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 's', $identifier);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        return max(0, (int)($row['remaining'] ?? 0));
    }
    
    /**
     * ==================== PASSWORD SECURITY ====================
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        $strength = 0;
        
        // Length check
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
        } elseif (strlen($password) > PASSWORD_MAX_LENGTH) {
            $errors[] = "Password must be less than " . PASSWORD_MAX_LENGTH . " characters";
        } else {
            $strength++;
        }
        
        // Lowercase check
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        } elseif (preg_match('/[a-z]/', $password)) {
            $strength++;
        }
        
        // Uppercase check
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        } elseif (preg_match('/[A-Z]/', $password)) {
            $strength++;
        }
        
        // Number check
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        } elseif (preg_match('/[0-9]/', $password)) {
            $strength++;
        }
        
        // Special character check
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        } elseif (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $strength++;
        }
        
        // Common password check
        if ($this->isCommonPassword($password)) {
            $errors[] = "This password is too common";
            $strength = max(0, $strength - 2);
        }
        
        // Calculate strength level
        $level = 'weak';
        if ($strength >= 5) $level = 'strong';
        elseif ($strength >= 4) $level = 'good';
        elseif ($strength >= 3) $level = 'fair';
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $level,
            'score' => $strength
        ];
    }
    
    /**
     * Check if password is common
     */
    private function isCommonPassword($password) {
        $common = [
            'password', '123456', '12345678', 'qwerty', 'abc123',
            'monkey', 'master', 'dragon', 'login', 'princess',
            'football', 'shadow', 'sunshine', 'trustno1', 'iloveyou',
            'batman', 'access', 'hello', 'charlie', 'letmein',
            'password1', 'password123', 'admin', 'welcome', 'passw0rd'
        ];
        
        return in_array(strtolower($password), $common);
    }
    
    /**
     * Hash password
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check password history
     */
    public function checkPasswordHistory($userId, $newPassword) {
        $query = "SELECT password_hash FROM password_history 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $userId, PASSWORD_HISTORY_COUNT);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($newPassword, $row['password_hash'])) {
                return false; // Password was used before
            }
        }
        
        return true;
    }
    
    /**
     * Save password to history
     */
    public function savePasswordHistory($userId, $passwordHash) {
        $query = "INSERT INTO password_history (user_id, password_hash, created_at) 
                  VALUES (?, ?, NOW())";
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 'is', $userId, $passwordHash);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * ==================== SESSION SECURITY ====================
     */
    public function initSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.sid_length', 48);
            ini_set('session.sid_bits_per_character', 6);
            
            session_name('CHATAPP_SESSION');
            session_start();
        }
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            return;
        }
        
        $lastRegen = $_SESSION['last_regeneration'] ?? 0;
        
        if (time() - $lastRegen > SESSION_REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Set session fingerprint
     */
    public function setSessionFingerprint() {
        if (!SESSION_FINGERPRINT_ENABLED) return;
        
        $fingerprint = $this->generateFingerprint();
        $_SESSION['fingerprint'] = $fingerprint;
    }
    
    /**
     * Validate session fingerprint
     */
    public function validateSessionFingerprint() {
        if (!SESSION_FINGERPRINT_ENABLED) return true;
        
        $currentFingerprint = $this->generateFingerprint();
        $storedFingerprint = $_SESSION['fingerprint'] ?? '';
        
        return hash_equals($storedFingerprint, $currentFingerprint);
    }
    
    /**
     * Generate browser fingerprint
     */
    private function generateFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Check session validity
     */
    public function validateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session exists and is logged in
        if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
            return false;
        }
        
        // Check session expiry
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if (time() - $lastActivity > SESSION_LIFETIME) {
            $this->destroySession();
            return false;
        }
        
        // Check fingerprint
        if (!$this->validateSessionFingerprint()) {
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Regenerate if needed
        $this->regenerateSession();
        
        return true;
    }
    
    /**
     * Destroy session
     */
    public function destroySession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * ==================== INPUT VALIDATION ====================
     */
    public function validateInput($input, $type, $options = []) {
        if ($input === null || $input === '') {
            return $options['required'] ?? false ? ['valid' => false, 'error' => 'This field is required'] : ['valid' => true];
        }
        
        switch ($type) {
            case 'username':
                return $this->validateUsername($input);
            case 'email':
                return $this->validateEmail($input);
            case 'password':
                return $this->validatePassword($input);
            case 'friend_code':
                return $this->validateFriendCode($input);
            case 'message':
                return $this->validateMessage($input);
            case 'integer':
                return $this->validateInteger($input, $options);
            case 'url':
                return $this->validateURL($input);
            case 'date':
                return $this->validateDate($input);
            case 'alphanumeric':
                return $this->validateAlphanumeric($input, $options);
            default:
                return $this->validateString($input, $options);
        }
    }
    
    /**
     * Validate username
     */
    private function validateUsername($username) {
        $username = trim($username);
        
        if (strlen($username) < 3 || strlen($username) > 30) {
            return ['valid' => false, 'error' => 'Username must be 3-30 characters'];
        }
        
        if (!preg_match(VALID_USERNAME_PATTERN, $username)) {
            return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores'];
        }
        
        if ($this->containsBlockedContent($username)) {
            return ['valid' => false, 'error' => 'Username contains blocked content'];
        }
        
        return ['valid' => true, 'sanitized' => $username];
    }
    
    /**
     * Validate email
     */
    private function validateEmail($email) {
        $email = trim(strtolower($email));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Invalid email format'];
        }
        
        if (!preg_match(VALID_EMAIL_PATTERN, $email)) {
            return ['valid' => false, 'error' => 'Invalid email format'];
        }
        
        return ['valid' => true, 'sanitized' => $email];
    }
    
    /**
     * Validate password
     */
    private function validatePassword($password) {
        $result = $this->validatePasswordStrength($password);
        
        if (!$result['valid']) {
            return ['valid' => false, 'error' => implode(', ', $result['errors'])];
        }
        
        return ['valid' => true, 'strength' => $result['strength']];
    }
    
    /**
     * Validate friend code
     */
    private function validateFriendCode($code) {
        $code = strtoupper(trim($code));
        
        if (!preg_match(VALID_FRIEND_CODE_PATTERN, $code)) {
            return ['valid' => false, 'error' => 'Invalid friend code format (e.g., ABC-123456)'];
        }
        
        return ['valid' => true, 'sanitized' => $code];
    }
    
    /**
     * Validate message
     */
    private function validateMessage($message) {
        $message = trim($message);
        
        if (empty($message)) {
            return ['valid' => false, 'error' => 'Message cannot be empty'];
        }
        
        if (strlen($message) > MAX_INPUT_LENGTH['message']) {
            return ['valid' => false, 'error' => 'Message too long (max ' . MAX_INPUT_LENGTH['message'] . ' characters)'];
        }
        
        if ($this->containsSuspiciousContent($message)) {
            return ['valid' => false, 'error' => 'Message contains suspicious content'];
        }
        
        return ['valid' => true, 'sanitized' => $message];
    }
    
    /**
     * Validate integer
     */
    private function validateInteger($value, $options = []) {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($value === false) {
            return ['valid' => false, 'error' => 'Invalid number'];
        }
        
        if (isset($options['min']) && $value < $options['min']) {
            return ['valid' => false, 'error' => 'Value must be at least ' . $options['min']];
        }
        
        if (isset($options['max']) && $value > $options['max']) {
            return ['valid' => false, 'error' => 'Value must be at most ' . $options['max']];
        }
        
        return ['valid' => true, 'sanitized' => $value];
    }
    
    /**
     * Validate URL
     */
    private function validateURL($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }
        
        return ['valid' => true, 'sanitized' => $url];
    }
    
    /**
     * Validate date
     */
    private function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        
        if (!$d || $d->format('Y-m-d') !== $date) {
            return ['valid' => false, 'error' => 'Invalid date format (YYYY-MM-DD)'];
        }
        
        return ['valid' => true, 'sanitized' => $date];
    }
    
    /**
     * Validate alphanumeric
     */
    private function validateAlphanumeric($value, $options = []) {
        $pattern = '/^[a-zA-Z0-9';
        if (isset($options['allow_spaces'])) $pattern .= ' ';
        $pattern .= ']+$/';
        
        if (!preg_match($pattern, $value)) {
            return ['valid' => false, 'error' => 'Only letters, numbers' . ($options['allow_spaces'] ? ' and spaces' : '') . ' allowed'];
        }
        
        return ['valid' => true, 'sanitized' => $value];
    }
    
    /**
     * Validate string
     */
    private function validateString($value, $options = []) {
        $value = trim($value);
        
        $min = $options['min'] ?? 1;
        $max = $options['max'] ?? 255;
        
        if (strlen($value) < $min) {
            return ['valid' => false, 'error' => 'Minimum ' . $min . ' characters required'];
        }
        
        if (strlen($value) > $max) {
            return ['valid' => false, 'error' => 'Maximum ' . $max . ' characters allowed'];
        }
        
        return ['valid' => true, 'sanitized' => $value];
    }
    
    /**
     * ==================== SANITIZATION ====================
     */
    public function sanitizeInput($input, $type = 'string') {
        if ($input === null) return null;
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'string':
            default:
                return $this->sanitizeString($input);
        }
    }
    
    /**
     * Sanitize string
     */
    private function sanitizeString($string) {
        $string = trim($string);
        $string = stripslashes($string);
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        return $string;
    }
    
    /**
     * ==================== OUTPUT ESCAPING ====================
     */
    public function escape($input) {
        if ($input === null) return '';
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    public function escapeJS($input) {
        return json_encode($input);
    }
    
    public function escapeURL($input) {
        return urlencode($input);
    }
    
    /**
     * ==================== XSS DETECTION ====================
     */
    public function containsSuspiciousContent($input) {
        foreach (SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }
    
    public function containsBlockedContent($input) {
        $input = strtolower($input);
        foreach (BLOCKED_WORDS as $word) {
            if (strpos($input, strtolower($word)) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * ==================== FILE UPLOAD SECURITY ====================
     */
    public function validateFileUpload($file, $allowedCategory = null) {
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => $this->getUploadError($file['error'])];
        }
        
        // Check file size
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            return ['valid' => false, 'error' => 'File too large (max ' . $this->formatSize(UPLOAD_MAX_SIZE) . ')'];
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Get MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Find matching category
        $category = null;
        foreach (UPLOAD_ALLOWED_TYPES as $cat => $config) {
            if (in_array($extension, $config['extensions']) && in_array($mimeType, $config['mimes'])) {
                $category = $cat;
                
                // Check category-specific size limit
                if ($file['size'] > $config['max_size']) {
                    return ['valid' => false, 'error' => 'File too large for this type (max ' . $this->formatSize($config['max_size']) . ')'];
                }
                break;
            }
        }
        
        if (!$category) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        if ($allowedCategory && $category !== $allowedCategory) {
            return ['valid' => false, 'error' => 'Invalid file category'];
        }
        
        // Additional validation for images
        if ($category === 'images') {
            $imageInfo = @getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                return ['valid' => false, 'error' => 'Invalid image file'];
            }
        }
        
        // Check for PHP code in file
        if ($this->fileContainsPHP($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Invalid file content'];
        }
        
        return [
            'valid' => true,
            'category' => $category,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'sanitized_name' => $this->sanitizeFileName($file['name'])
        ];
    }
    
    /**
     * Check if file contains PHP code
     */
    private function fileContainsPHP($filePath) {
        $content = file_get_contents($filePath, false, null, 0, 1024);
        return preg_match('/<\?php|<\?=|<\?[^x]/i', $content);
    }
    
    /**
     * Sanitize filename
     */
    public function sanitizeFileName($filename) {
        // Remove path information
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Remove multiple dots
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        
        // Ensure reasonable length
        if (strlen($filename) > 100) {
            $parts = explode('.', $filename);
            $extension = array_pop($parts);
            $name = implode('.', $parts);
            $filename = substr($name, 0, 100 - strlen($extension) - 1) . '.' . $extension;
        }
        
        return $filename;
    }
    
    /**
     * Generate unique filename
     */
    public function generateUniqueFilename($extension) {
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadError($error) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
        ];
        
        return $errors[$error] ?? 'Unknown upload error';
    }
    
    /**
     * Format file size
     */
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * ==================== UTILITY FUNCTIONS ====================
     */
    public function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($event, $details = []) {
        global $conn;
        
        $query = "INSERT INTO security_log (event, ip_address, user_agent, user_id, details, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conn, $query);
        $ip = $this->getClientIP();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;
        $jsonDetails = json_encode($details);
        
        mysqli_stmt_bind_param($stmt, 'sssis', $event, $ip, $ua, $userId, $jsonDetails);
        mysqli_stmt_execute($stmt);
    }
}

/**
 * Global security helper functions
 */
function security() {
    return Security::getInstance();
}

function csrf_token() {
    return security()->getCSRFToken();
}

function csrf_field() {
    return security()->csrfField();
}

function csrf_verify($token = null) {
    return security()->validateCSRFToken($token);
}

function sanitize($input, $type = 'string') {
    return security()->sanitizeInput($input, $type);
}

function escape($input) {
    return security()->escape($input);
}

function is_rate_limited($identifier, $action) {
    return !security()->checkRateLimit($identifier, $action);
}
