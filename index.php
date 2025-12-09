<?php
require_once 'config.php';
requireStaff();

$message = '';
$error = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Add new student
if (isset($_POST['add_student'])) {
    $student_id = $conn->real_escape_string(trim($_POST['student_id']));
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $program = $conn->real_escape_string(trim($_POST['program']));
    $year_level = (int)$_POST['year_level'];
    
    $sql = "INSERT INTO students (student_id, username, password, name, email, program, year_level) 
            VALUES ('$student_id', '$username', '$password', '$name', '$email', '$program', $year_level)";
    if ($conn->query($sql)) {
        $message = "Student added successfully!";
    } else {
        $error = "Error adding student. Please check if Student ID, Username or Email already exists.";
    }
}

// Enroll student in course
if (isset($_POST['enroll'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $course_code = $conn->real_escape_string($_POST['course_code']);
    
    // Check if course is full
    $check = $conn->query("SELECT enrolled_count, max_students FROM courses WHERE course_code = '$course_code'");
    if ($check && $check->num_rows > 0) {
        $course = $check->fetch_assoc();
        
        if ($course['enrolled_count'] < $course['max_students']) {
            $sql = "INSERT INTO enrollments (student_id, course_code) VALUES ('$student_id', '$course_code')";
            if ($conn->query($sql)) {
                $conn->query("UPDATE courses SET enrolled_count = enrolled_count + 1 WHERE course_code = '$course_code'");
                $message = "Student enrolled successfully!";
            } else {
                $error = "Error: Student already enrolled in this course!";
            }
        } else {
            $error = "Course is full!";
        }
    }
}

// Drop enrollment
if (isset($_GET['drop'])) {
    $enrollment_id = (int)$_GET['drop'];
    
    $result = $conn->query("SELECT course_code FROM enrollments WHERE id = $enrollment_id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $course_code = $row['course_code'];
        $conn->query("DELETE FROM enrollments WHERE id = $enrollment_id");
        $conn->query("UPDATE courses SET enrolled_count = enrolled_count - 1 WHERE course_code = '$course_code'");
        $message = "Enrollment dropped successfully!";
    }
}

// Get filter parameters
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;

// Get all students
$students = $conn->query("SELECT * FROM students ORDER BY year_level, name");

// Get courses based on filters
$course_query = "SELECT * FROM courses WHERE 1=1";
if ($year_filter > 0) {
    $course_query .= " AND year_level = $year_filter";
}
if ($semester_filter > 0) {
    $course_query .= " AND semester = $semester_filter";
}
$course_query .= " ORDER BY year_level, semester, course_code";
$courses = $conn->query($course_query);

// Get all enrollments with details
$enrollments = $conn->query("
    SELECT e.id, e.student_id, s.name as student_name, s.year_level,
           e.course_code, c.course_name, c.year_level as course_year, c.semester, e.enrolled_at 
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN courses c ON e.course_code = c.course_code
    ORDER BY e.enrolled_at DESC
");

// Get enrollment statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_courses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$total_enrollments = $enrollments ? $enrollments->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Enrollment System</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2em;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info span {
            color: #666;
            font-weight: 600;
        }
        
        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 1.1em;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
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
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        h2 {
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-bar select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .filter-bar button, .filter-bar a {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .filter-bar a {
            background: #6c757d;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        
        input, select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .btn-drop {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            display: inline-block;
        }
        
        .btn-drop:hover {
            background: #c82333;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-year-1 { background: #007bff; color: white; }
        .badge-year-2 { background: #28a745; color: white; }
        .badge-year-3 { background: #ffc107; color: #333; }
        .badge-year-4 { background: #dc3545; color: white; }
        
        .badge-semester-1 { background: #17a2b8; color: white; }
        .badge-semester-2 { background: #6f42c1; color: white; }
        
        .badge-full { background: #dc3545; color: white; }
        .badge-available { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Staff Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_courses; ?></h3>
                <p>Total Courses</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_enrollments; ?></h3>
                <p>Total Enrollments</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Add Student Form -->
        <div class="card">
            <h2>📝 Register New Student</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Student ID:</label>
                        <input type="text" name="student_id" required>
                    </div>
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name:</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Program:</label>
                        <input type="text" name="program" value="BS Information Systems" required>
                    </div>
                    <div class="form-group">
                        <label>Year Level:</label>
                        <select name="year_level" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_student">Add Student</button>
            </form>
        </div>
        
        <!-- Enrollment Form -->
        <div class="card">
            <h2>✏️ Enroll Student in Course</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Select Student:</label>
                        <select name="student_id" required>
                            <option value="">Choose a student...</option>
                            <?php 
                            if ($students && $students->num_rows > 0) {
                                $students->data_seek(0);
                                while ($student = $students->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                    <?php echo htmlspecialchars($student['name']) . ' - Year ' . $student['year_level'] . ' (' . htmlspecialchars($student['student_id']) . ')'; ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Course:</label>
                        <select name="course_code" required>
                            <option value="">Choose a course...</option>
                            <?php 
                            if ($courses && $courses->num_rows > 0) {
                                $courses->data_seek(0);
                                while ($course = $courses->fetch_assoc()): 
                                    $available = $course['max_students'] - $course['enrolled_count'];
                            ?>
                                <option value="<?php echo htmlspecialchars($course['course_code']); ?>">
                                    <?php echo htmlspecialchars($course['course_code']) . ' - ' . htmlspecialchars($course['course_name']) . ' (Y' . $course['year_level'] . '-S' . $course['semester'] . ') - Available: ' . $available; ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="enroll">Enroll Student</button>
            </form>
        </div>
        
        <!-- Available Courses -->
        <div class="card">
            <h2>📚 Available Courses</h2>
            
            <div class="filter-bar">
                <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <label style="margin: 0;">Filter by Year:</label>
                    <select name="year">
                        <option value="0">All Years</option>
                        <option value="1" <?php echo $year_filter == 1 ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo $year_filter == 2 ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo $year_filter == 3 ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo $year_filter == 4 ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                    
                    <label style="margin: 0;">Semester:</label>
                    <select name="semester">
                        <option value="0">All Semesters</option>
                        <option value="1" <?php echo $semester_filter == 1 ? 'selected' : ''; ?>>1st Semester</option>
                        <option value="2" <?php echo $semester_filter == 2 ? 'selected' : ''; ?>>2nd Semester</option>
                    </select>
                    
                    <button type="submit">Apply Filter</button>
                    <a href="staff_dashboard.php">Clear</a>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Instructor</th>
                        <th>Credits</th>
                        <th>Year/Sem</th>
                        <th>Enrollment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($courses && $courses->num_rows > 0) {
                        $courses->data_seek(0);
                        while ($course = $courses->fetch_assoc()): 
                            $available = $course['max_students'] - $course['enrolled_count'];
                            $isFull = $available <= 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['instructor']); ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td>
                                <span class="badge badge-year-<?php echo $course['year_level']; ?>">Year <?php echo $course['year_level']; ?></span>
                                <span class="badge badge-semester-<?php echo $course['semester']; ?>">Sem <?php echo $course['semester']; ?></span>
                            </td>
                            <td><?php echo $course['enrolled_count'] . '/' . $course['max_students']; ?></td>
                            <td>
                                <?php if ($isFull): ?>
                                    <span class="badge badge-full">FULL</span>
                                <?php else: ?>
                                    <span class="badge badge-available"><?php echo $available; ?> slots</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Current Enrollments -->
        <div class="card">
            <h2>👥 Current Enrollments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Year</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Course Year/Sem</th>
                        <th>Enrolled Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($enrollments && $enrollments->num_rows > 0) {
                        while ($enrollment = $enrollments->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enrollment['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                            <td><span class="badge badge-year-<?php echo $enrollment['year_level']; ?>">Year <?php echo $enrollment['year_level']; ?></span></td>
                            <td><?php echo htmlspecialchars($enrollment['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                            <td>
                                <span class="badge badge-year-<?php echo $enrollment['course_year']; ?>">Y<?php echo $enrollment['course_year']; ?></span>
                                <span class="badge badge-semester-<?php echo $enrollment['semester']; ?>">S<?php echo $enrollment['semester']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($enrollment['enrolled_at'])); ?></td>
                            <td>
                                <a href="?drop=<?php echo $enrollment['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to drop this enrollment?')"
                                   class="btn-drop">Drop</a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="8" style="text-align:center; color:#666;">No enrollments yet</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>