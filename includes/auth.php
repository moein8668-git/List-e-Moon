<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/**
 * Login user
 */
function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Check Active Status
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            return 'banned';
        }

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true); 
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['password_needs_reset'] = $user['password_needs_reset'];
        
        return true;
    }
    
    return false;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login (redirects to login page if not)
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('index.php');
    }
    
    // Enforce password reset policy
    if (isset($_SESSION['password_needs_reset']) && $_SESSION['password_needs_reset']) {
        // Allow access only to change_password.php or logout logic
        $current_script = basename($_SERVER['PHP_SELF']);
        if ($current_script !== 'change_password.php' && $current_script !== 'logout.php' && $current_script !== 'actions.php') {
             redirect('change_password.php');
        }
    }
}

/**
 * Logout user
 */
function logout() {
    $_SESSION = [];
    session_destroy();
    header("Location: index.php");
    exit;
}

/**
 * Create a new user (Admin only)
 */
function create_user($username, $password, $is_admin = 0) {
    global $pdo;
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin, password_needs_reset) VALUES (?, ?, ?, 1)");
        $stmt->execute([$username, $password_hash, $is_admin]);
        return true;
    } catch (PDOException $e) {
        // Likely duplicate username
        return false;
    }
}

/**
 * Change password
 */
function change_password($user_id, $new_password) {
    global $pdo;
    
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_needs_reset = 0 WHERE id = ?");
    return $stmt->execute([$password_hash, $user_id]);
}
