<?php
require_once 'config.php';
requireStudent();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$year_level = $_SESSION['year_level'];

// Get student information
$student_info = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'")->fetch_assoc();

// Get enrolled courses
$enrolled_courses = $conn->query("
    SELECT e.id, e.course_code, c.course_name, c.instructor, c.credits, 
           c.year_level, c.semester, e.enrolled_at
    FROM enrollments e
    JOIN courses c ON e.course_code = c.course_code
    WHERE e.student_id = '$student_id'
    ORDER BY c.year_level, c.semester, c.course_code
");

// Get available courses for student's year level
$available_courses = $conn->query("
    SELECT c.*, 
           (c.max_students - c.enrolled_count) as available_slots,
           CASE WHEN e.student_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled
    FROM courses c
    LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.student_id = '$student_id'
    WHERE c.year_level = $year_level
    ORDER BY c.semester, c.course_code
");

// Calculate statistics
$total_enrolled = $enrolled_courses->num_rows;
$total_credits = 0;
$enrolled_courses->data_seek(0);
while ($course = $enrolled_courses->fetch_assoc()) {
    $total_credits += $course['credits'];
}
$enrolled_courses->data_seek(0);

// Get courses by semester for current year
$semester1_courses = $conn->query("
    SELECT c.*, 
           CASE WHEN e.student_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled
    FROM courses c
    LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.student_id = '$student_id'
    WHERE c.year_level = $year_level AND c.semester = 1
    ORDER BY c.course_code
");

$semester2_courses = $conn->query("
    SELECT c.*, 
           CASE WHEN e.student_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled
    FROM courses c
    LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.student_id = '$student_id'
    WHERE c.year_level = $year_level AND c.semester = 2
    ORDER BY c.course_code
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Enrollment System</title>
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
            max-width: 1200px;
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
        
        .header-left h1 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .header-left p {
            color: #666;
            font-size: 1.1em;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
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
        
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 25px;
            align-items: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: white;
        }
        
        .profile-info h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .profile-detail {
            display: flex;
            flex-direction: column;
        }
        
        .profile-detail label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .profile-detail span {
            color: #333;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-semester-1 { background: #17a2b8; color: white; }
        .badge-semester-2 { background: #6f42c1; color: white; }
        .badge-enrolled { background: #28a745; color: white; }
        .badge-available { background: #ffc107; color: #333; }
        .badge-full { background: #dc3545; color: white; }
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .course-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .course-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .course-card.enrolled {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        .course-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        
        .course-card p {
            color: #666;
            margin: 5px 0;
            font-size: 0.95em;
        }
        
        .course-card .course-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>🎓 Student Portal</h1>
                <p>Course Enrollment System</p>
            </div>
            <div class="user-info">
                <a href="?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>
        
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar">
                👤
            </div>
            <div class="profile-info">
                <h2><?php echo $student_info['name']; ?></h2>
                <div class="profile-details">
                    <div class="profile-detail">
                        <label>Student ID</label>
                        <span><?php echo $student_info['student_id']; ?></span>
                    </div>
                    <div class="profile-detail">
                        <label>Email</label>
                        <span><?php echo $student_info['email']; ?></span>
                    </div>
                    <div class="profile-detail">
                        <label>Program</label>
                        <span><?php echo $student_info['program']; ?></span>
                    </div>
                    <div class="profile-detail">
                        <label>Year Level</label>
                        <span>Year <?php echo $student_info['year_level']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_enrolled; ?></h3>
                <p>Enrolled Courses</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_credits; ?></h3>
                <p>Total Credits</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $year_level; ?></h3>
                <p>Current Year Level</p>
            </div>
        </div>
        
        <!-- My Enrolled Courses -->
        <div class="card">
            <h2>📚 My Enrolled Courses</h2>
            <?php if ($total_enrolled > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Instructor</th>
                            <th>Credits</th>
                            <th>Year/Semester</th>
                            <th>Enrolled Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $course['course_code']; ?></strong></td>
                                <td><?php echo $course['course_name']; ?></td>
                                <td><?php echo $course['instructor']; ?></td>
                                <td><?php echo $course['credits']; ?></td>
                                <td>
                                    Year <?php echo $course['year_level']; ?> - 
                                    <span class="badge badge-semester-<?php echo $course['semester']; ?>">
                                        Semester <?php echo $course['semester']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($course['enrolled_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📖</div>
                    <p>You are not enrolled in any courses yet.</p>
                    <p>Please contact the staff office to enroll in courses.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Available Courses - First Semester -->
        <div class="card">
            <h2>📋 Available Courses - Year <?php echo $year_level; ?> (First Semester)</h2>
            <?php if ($semester1_courses->num_rows > 0): ?>
                <div class="course-grid">
                    <?php while ($course = $semester1_courses->fetch_assoc()): 
                        $available = $course['max_students'] - $course['enrolled_count'];
                        $is_enrolled = $course['is_enrolled'];
                    ?>
                        <div class="course-card <?php echo $is_enrolled ? 'enrolled' : ''; ?>">
                            <h3><?php echo $course['course_code']; ?></h3>
                            <p><strong><?php echo $course['course_name']; ?></strong></p>
                            <p>👨‍🏫 <?php echo $course['instructor']; ?></p>
                            <p>📊 Credits: <?php echo $course['credits']; ?></p>
                            <div class="course-footer">
                                <span>
                                    <?php if ($is_enrolled): ?>
                                        <span class="badge badge-enrolled">✓ Enrolled</span>
                                    <?php elseif ($available > 0): ?>
                                        <span class="badge badge-available"><?php echo $available; ?> slots left</span>
                                    <?php else: ?>
                                        <span class="badge badge-full">Full</span>
                                    <?php endif; ?>
                                </span>
                                <span><?php echo $course['enrolled_count']; ?>/<?php echo $course['max_students']; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📚</div>
                    <p>No courses available for First Semester</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Available Courses - Second Semester -->
        <div class="card">
            <h2>📋 Available Courses - Year <?php echo $year_level; ?> (Second Semester)</h2>
            <?php if ($semester2_courses->num_rows > 0): ?>
                <div class="course-grid">
                    <?php while ($course = $semester2_courses->fetch_assoc()): 
                        $available = $course['max_students'] - $course['enrolled_count'];
                        $is_enrolled = $course['is_enrolled'];
                    ?>
                        <div class="course-card <?php echo $is_enrolled ? 'enrolled' : ''; ?>">
                            <h3><?php echo $course['course_code']; ?></h3>
                            <p><strong><?php echo $course['course_name']; ?></strong></p>
                            <p>👨‍🏫 <?php echo $course['instructor']; ?></p>
                            <p>📊 Credits: <?php echo $course['credits']; ?></p>
                            <div class="course-footer">
                                <span>
                                    <?php if ($is_enrolled): ?>
                                        <span class="badge badge-enrolled">✓ Enrolled</span>
                                    <?php elseif ($available > 0): ?>
                                        <span class="badge badge-available"><?php echo $available; ?> slots left</span>
                                    <?php else: ?>
                                        <span class="badge badge-full">Full</span>
                                    <?php endif; ?>
                                </span>
                                <span><?php echo $course['enrolled_count']; ?>/<?php echo $course['max_students']; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📚</div>
                    <p>No courses available for Second Semester</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Info Card -->
        <div class="card" style="background: #fff3cd; border: 2px solid #ffc107;">
            <h2 style="color: #856404; border-color: #ffc107;">ℹ️ Information</h2>
            <p style="color: #856404; margin: 0;">
                To enroll in courses, please visit the Registrar's Office or contact the staff. 
                This portal is for viewing your enrolled courses and available courses for your year level.
            </p>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>