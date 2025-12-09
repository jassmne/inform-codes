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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password!';
    } else {
        if ($user_type === 'staff') {
            $sql = "SELECT * FROM staff WHERE username = '$username'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($password === $user['password']) {
                    $_SESSION['user_id'] = $user['staff_id'];
                    $_SESSION['user_type'] = 'staff';
                    $_SESSION['name'] = $user['name'];
                    header('Location: staff_dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid password!';
                }
            } else {
                $error = 'Staff account not found!';
            }
        } else if ($user_type === 'student') {
            $sql = "SELECT * FROM students WHERE username = '$username'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($password === $user['password']) {
                    $_SESSION['user_id'] = $user['student_id'];
                    $_SESSION['user_type'] = 'student';
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['year_level'] = $user['year_level'];
                    header('Location: student_dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid password!';
                }
            } else {
                $error = 'Student account not found!';
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
    <title>Login - College Enrollment System</title>
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
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        
        .login-left {
            display: none;
        }
        
        .login-right {
            padding: 60px 40px;
        }
        
        .login-right h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .login-right p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
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
        
        .user-type-select {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .user-type-option {
            flex: 1;
            position: relative;
        }
        
        .user-type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .user-type-label {
            display: block;
            padding: 15px;
            background: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .user-type-option input[type="radio"]:checked + .user-type-label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
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
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .system-title h1 {
                font-size: 2em;
            }
            
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="system-title">
        <h1>🎓 College Enrollment System</h1>
        <p>BS Information Systems</p>
    </div>
    
    <div class="login-container">
        <div class="login-right">
            <h2>Login</h2>
            <p>Please select your account type and login</p>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <div class="user-type-select">
                    <div class="user-type-option">
                        <input type="radio" name="user_type" value="student" id="student" checked>
                        <label for="student" class="user-type-label">👨‍🎓 Student</label>
                    </div>
                    <div class="user-type-option">
                        <input type="radio" name="user_type" value="staff" id="staff">
                        <label for="staff" class="user-type-label">👨‍💼 Staff</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required autocomplete="new-password">
                </div>
                
                <button type="submit">Login</button>
                
                <div class="register-link" id="registerLink">
                    <p>Don't have an account? <a href="register.php">Register as Student</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const userTypeInputs = document.querySelectorAll('input[name="user_type"]');
        const registerLink = document.getElementById('registerLink');
        
        userTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'student') {
                    registerLink.style.display = 'block';
                } else {
                    registerLink.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>