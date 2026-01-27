<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

require_once '../database/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $conn = getDBConnection();
    
    // Get user from database
    $sql = "SELECT id, username, password, role, first_name, is_active FROM users 
            WHERE (username = ? OR email = ?) AND is_active = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check password (plain text comparison)
        if ($password === $row['password']) {
            // Success - update last login
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Set session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['first_name'] = $row['first_name'];
            
            // Redirect
            if ($row['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: home.php');
            }
            exit();
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found or account inactive";
    }
    
    $stmt->close();
    closeDBConnection($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniRoom - Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/loginstudent.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <i class='bx bxs-school'></i>
                <h1>UniRoom</h1>
                <p>Sign in to manage your bookings</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message" style="color: red; margin-bottom: 1rem; text-align: center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <div class="user-type-info">
                <i class='bx bx-info-circle'></i>
                Login as: Student, Faculty, or Club Member
            </div>

            <form method="POST" class="login-form">
                <div class="input-group">
                    <i class='bx bxs-user'></i>
                    <input type="text" name="username" placeholder="Username or Email" required>
                </div>
                
                <div class="input-group">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" class="btn-login">Sign In</button>
                
                <div class="login-footer">
                    <a href="#">Forgot Password?</a>
                </div>
            </form>
            
            <div class="register-prompt">
                <p>Don't have an account?</p>
                <a href="register.php" class="btn-register-link">
                    <i class='bx bx-user-plus'></i> Create New Account
                </a>
            </div>
            
        </div>
    </div>
</body>
</html>