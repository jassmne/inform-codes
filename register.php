<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isStaff()) {
        header('Location: staff_dashboard.php');
    } else {
        header('Location: student_dashboard.php');
    }
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $conn->real_escape_string(trim($_POST['student_id']));
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $year_level = (int)$_POST['year_level'];
    
    // Validation
    if (empty($student_id) || empty($username) || empty($password) || empty($name) || empty($email)) {
        $error = 'All fields are required!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } else {
        // Check if student ID already exists
        $check_id = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'");
        if ($check_id && $check_id->num_rows > 0) {
            $error = 'Student ID already exists!';
        } else {
            // Check if username already exists
            $check_username = $conn->query("SELECT * FROM students WHERE username = '$username'");
            if ($check_username && $check_username->num_rows > 0) {
                $error = 'Username already taken! Please choose a different username.';
            } else {
                // Check if email already exists
                $check_email = $conn->query("SELECT * FROM students WHERE email = '$email'");
                if ($check_email && $check_email->num_rows > 0) {
                    $error = 'Email already registered!';
                } else {
                    // Insert new student
                    $sql = "INSERT INTO students (student_id, username, password, name, email, program, year_level) 
                            VALUES ('$student_id', '$username', '$password', '$name', '$email', 'BS Information Systems', $year_level)";
                    
                    if ($conn->query($sql)) {
                        $message = 'Registration successful! You can now login.';
                        $_POST = array();
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Enrollment System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .system-title {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .system-title h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .system-title p {
            font-size: 1.2em;
            opacity: 0.95;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        label .required {
            color: #dc3545;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .info-box p {
            color: #004085;
            margin: 5px 0;
            font-size: 0.95em;
        }
        
        @media (max-width: 768px) {
            .system-title h1 {
                font-size: 2em;
            }
            
            .register-container {
                padding: 30px 25px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="system-title">
        <h1>🎓 Student Registration</h1>
        <p>BS Information Systems</p>
    </div>
    
    <div class="register-container">
        <h2>Create Account</h2>
        <p class="subtitle">Register to access the enrollment system</p>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <p><strong>📋 Registration Requirements:</strong></p>
            <p>• Use your official Student ID number</p>
            <p>• Choose any username (must be unique)</p>
            <p>• Password must be at least 6 characters</p>
            <p>• Use your institutional email address</p>
        </div>
        
        <form method="POST" autocomplete="off">
            <div class="form-grid">
                <div class="form-group">
                    <label>Student ID <span class="required">*</span></label>
                    <input type="text" name="student_id" placeholder="e.g., 2024001" 
                           value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" placeholder="Choose any username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           pattern="[A-Za-z0-9._-]+" title="Username can only contain letters, numbers, dots, underscores and hyphens" required>
                </div>
                
                <div class="form-group full-width">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="name" placeholder="Juan Dela Cruz" 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>
                
                <div class="form-group full-width">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="email" placeholder="student@college.edu" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" placeholder="Min. 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
                
                <div class="form-group">
                    <label>Year Level <span class="required">*</span></label>
                    <select name="year_level" required>
                        <option value="">Select Year Level</option>
                        <option value="1" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == 1) ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == 2) ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == 3) ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == 4) ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Program</label>
                    <input type="text" value="BS Information Systems" readonly style="background: #f5f5f5; color: #666;">
                </div>
            </div>
            
            <button type="submit">Register</button>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</body>
</html>