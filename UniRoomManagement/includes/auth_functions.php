<?php
require_once '../database/db.php';

class AuthHelper {
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Check if user has specific role
    public static function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    // Get current user ID
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    // Get current user role
    public static function getUserRole() {
        return $_SESSION['role'] ?? 'student';
    }
    
    // Require login - redirect if not logged in
    public static function requireLogin($redirectUrl = 'loginstudent.php') {
        if (!self::isLoggedIn()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    // Require specific role
    public static function requireRole($role, $redirectUrl = 'home.php') {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    // Create a new user (for registration)
    public static function createUser($username, $email, $password, $role = 'student') {
        $conn = getDBConnection();
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();
            closeDBConnection($conn);
            return $userId;
        }
        
        $stmt->close();
        closeDBConnection($conn);
        return false;
    }
    
    // Verify user credentials
    public static function verifyUser($username, $password) {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("
            SELECT id, username, password, role 
            FROM users 
            WHERE username = ? OR email = ?
        ");
        
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $stmt->close();
                closeDBConnection($conn);
                return [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'role' => $row['role']
                ];
            }
        }
        
        $stmt->close();
        closeDBConnection($conn);
        return false;
    }
}
?>