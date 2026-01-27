<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

require_once '../database/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'student';
    $student_id = $_POST['student_id'] ?? '';
    $club_name = $_POST['club_name'] ?? '';
    
    // Validation
    $errors = [];
    
    // Required fields
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($confirm_password)) $errors[] = "Confirm password is required";
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Password validation
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Type-specific validation
    if ($user_type === 'student' && empty($student_id)) {
        $errors[] = "Student ID is required for students";
    }
    
    if ($user_type === 'club' && empty($club_name)) {
        $errors[] = "Club name is required for club members";
    }
    
    if (empty($errors)) {
        $conn = getDBConnection();
        
        // Check if username or email already exists
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Store password as plain text (no hashing for demo)
            
            // Map form user_type to database role
            $role = $user_type; // 'student', 'faculty', 'club', 'admin'
            
            // Insert user
            $insert_sql = "INSERT INTO users (
                username, email, password, role, 
                first_name, last_name, student_id, club_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param(
                "ssssssss", 
                $username, $email, $password, $role,
                $first_name, $last_name, $student_id, $club_name
            );
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
                
                // Auto-login after registration (optional)
                /*
                $user_id = $stmt->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['first_name'] = $first_name;
                header('Location: home.php');
                exit();
                */
            } else {
                $error = "Registration failed: " . $conn->error;
            }
            
            $stmt->close();
        }
        
        $check_stmt->close();
        closeDBConnection($conn);
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniRoom - Register</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/register.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-card">
            <div class="register-header">
                <i class='bx bxs-school'></i>
                <h1>Create Account</h1>
                <p>Join UniRoom to book campus facilities</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <br>
                <a href="loginstudent.php" style="color: #2E7D32; text-decoration: underline;">Click here to login</a>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="register-form" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                               required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <small id="passwordMatch" style="display: none; color: #2ed573;">✓ Passwords match</small>
                        <small id="passwordMismatch" style="display: none; color: #ff4757;">✗ Passwords don't match</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>I am a *</label>
                    <div class="user-type-selector">
                        <div class="user-type-option <?php echo ($_POST['user_type'] ?? 'student') === 'student' ? 'selected' : ''; ?>" 
                             data-type="student">
                            <i class='bx bx-user'></i>
                            Student
                        </div>
                        <div class="user-type-option <?php echo ($_POST['user_type'] ?? '') === 'faculty' ? 'selected' : ''; ?>" 
                             data-type="faculty">
                            <i class='bx bx-user-circle'></i>
                            Faculty
                        </div>
                        <div class="user-type-option <?php echo ($_POST['user_type'] ?? '') === 'club' ? 'selected' : ''; ?>" 
                             data-type="club">
                            <i class='bx bx-group'></i>
                            Club Member
                        </div>
                    </div>
                    <input type="hidden" id="user_type" name="user_type" value="<?php echo $_POST['user_type'] ?? 'student'; ?>">
                </div>
                
                <!-- Dynamic Fields -->
                <div id="studentFields" class="dynamic-field <?php echo ($_POST['user_type'] ?? 'student') === 'student' ? 'show' : ''; ?>">
                    <div class="form-group">
                        <label for="student_id">Student ID *</label>
                        <input type="text" id="student_id" name="student_id" 
                               value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                    </div>
                </div>
                
                <div id="facultyFields" class="dynamic-field <?php echo ($_POST['user_type'] ?? '') === 'faculty' ? 'show' : ''; ?>">
                    <!-- Faculty has no additional required fields -->
                </div>
                
                <div id="clubFields" class="dynamic-field <?php echo ($_POST['user_type'] ?? '') === 'club' ? 'show' : ''; ?>">
                    <div class="form-group">
                        <label for="club_name">Club Name *</label>
                        <input type="text" id="club_name" name="club_name" 
                               value="<?php echo htmlspecialchars($_POST['club_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn-register">Create Account</button>
                
                <div class="login-link">
                    Already have an account? <a href="loginstudent.php">Sign In</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const userTypeOptions = document.querySelectorAll('.user-type-option');
        const userTypeInput = document.getElementById('user_type');
        const studentFields = document.getElementById('studentFields');
        const facultyFields = document.getElementById('facultyFields');
        const clubFields = document.getElementById('clubFields');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        const passwordMismatch = document.getElementById('passwordMismatch');
        
        // User type selection
        userTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                const type = this.dataset.type;
                
                // Update UI
                userTypeOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                userTypeInput.value = type;
                
                // Show/hide dynamic fields
                studentFields.classList.remove('show');
                facultyFields.classList.remove('show');
                clubFields.classList.remove('show');
                
                if (type === 'student') {
                    studentFields.classList.add('show');
                } else if (type === 'faculty') {
                    facultyFields.classList.add('show');
                } else if (type === 'club') {
                    clubFields.classList.add('show');
                }
            });
        });
        
        // Password match checker
        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length === 0) {
                passwordMatch.style.display = 'none';
                passwordMismatch.style.display = 'none';
            } else if (password === confirmPassword) {
                passwordMatch.style.display = 'block';
                passwordMismatch.style.display = 'none';
            } else {
                passwordMatch.style.display = 'none';
                passwordMismatch.style.display = 'block';
            }
        }
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let valid = true;
            const userType = userTypeInput.value;
            
            // Check required fields based on user type
            if (userType === 'student') {
                const studentId = document.getElementById('student_id').value;
                if (!studentId.trim()) {
                    alert('Student ID is required');
                    valid = false;
                }
            } else if (userType === 'club') {
                const clubName = document.getElementById('club_name').value;
                if (!clubName.trim()) {
                    alert('Club name is required');
                    valid = false;
                }
            }
            
            // Check password match
            if (passwordInput.value !== confirmPasswordInput.value) {
                alert('Passwords do not match');
                valid = false;
            }
            
            // Check password strength
            if (passwordInput.value.length < 6) {
                alert('Password must be at least 6 characters');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>