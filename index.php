<?php
/**
 * Tutorial 1 - Exercise 3: Dashboard with Navigation
 * Main homepage showing statistics and system features
 */
require_once 'db_connect.php';

// Fetch database stats
$totalStudents = 0;
$openSessions = 0;
$todaysSessions = 0;

$conn = getConnection();
$today = date('Y-m-d');

if ($conn) {
    try {
        $totalStudents = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch()['c'];
        $openSessions = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE status='open'")->fetch()['c'];
        $todaysSessions = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE date='$today'")->fetch()['c'];
    } catch (PDOException $e) {
        // ignore, show zeroes
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Dashboard</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>

<!-- SIDEBAR (Tutorial 1 - Exercise 3: Navigation Bar) -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">👥</div>
        <div class="sidebar-title">Student Dashboard</div>
        <div class="sidebar-subtitle">Management System</div>
    </div>
    
    <nav class="sidebar-menu">
        <ul>
            <li><a href="index.php" class="active"><span class="icon">🏠</span> <span>Dashboard</span></a></li>
            <li><a href="students.php"><span class="icon">📋</span> <span>Students</span></a></li>
            <li><a href="sessions.php"><span class="icon">📅</span> <span>Attendance</span></a></li>
            <li><a href="reports.php"><span class="icon">📊</span> <span>Reports</span></a></li>
        </ul>
    </nav>
    
    <div class="sidebar-logout">
        <a href="logout.php"><span class="icon">🚪</span> <span>Logout</span></a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    
    <!-- TOP BAR -->
    <div class="topbar">
        <h1>DASHBOARD</h1>
        <div class="topbar-actions">
            <div class="topbar-icon">💾</div>
            <div class="topbar-icon">⚙️</div>
            <div class="topbar-icon">🔔</div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content-section">

        <!-- WELCOME HERO CARD -->
        <div class="card" style="background: linear-gradient(135deg, #FFB84D 0%, #F2994A 100%); color: white; padding: 50px 40px; text-align: center; margin-bottom: 30px;">
            <h1 style="font-size: 42px; margin-bottom: 15px; color: white;">Welcome to Student Management</h1>
            <p style="font-size: 18px; margin-bottom: 30px; opacity: 0.95;">Manage students, track attendance, and analyze performance all in one place.</p>
            
            <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <a href="sessions.php" class="btn" style="background: white; color: #FFB84D; padding: 15px 30px; font-size: 16px;">
                    📝 Take Attendance
                </a>
                <a href="students.php" class="btn btn-outline" style="border-color: white; color: white; padding: 15px 30px; font-size: 16px;">
                    👥 Manage Students
                </a>
            </div>
        </div>

        <!-- STATISTICS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-change">📚 Enrolled</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Open Sessions</div>
                <div class="stat-value"><?php echo $openSessions; ?></div>
                <div class="stat-change">🟢 Active Now</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Today's Sessions</div>
                <div class="stat-value"><?php echo $todaysSessions; ?></div>
                <div class="stat-change">📅 Scheduled</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">System Status</div>
                <div class="stat-value" style="font-size: 28px; color: #48BB78;">✓</div>
                <div class="stat-change" style="color: #48BB78;">All Systems Operational</div>
            </div>
        </div>

        <!-- FEATURES SECTION -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">System Features</h2>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                
                <div style="padding: 25px; background: #F7FAFC; border-radius: 10px; border-left: 4px solid #FFB84D;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📋</div>
                    <h3 style="color: #2D3748; margin-bottom: 10px; font-size: 18px;">Attendance Tracking</h3>
                    <p style="color: #718096; font-size: 14px; line-height: 1.6;">
                        Record presence and participation across 6 sessions with automatic calculations.
                    </p>
                </div>

                <div style="padding: 25px; background: #F7FAFC; border-radius: 10px; border-left: 4px solid #48BB78;">
                    <div style="font-size: 32px; margin-bottom: 10px;">👥</div>
                    <h3 style="color: #2D3748; margin-bottom: 10px; font-size: 18px;">Student Management</h3>
                    <p style="color: #718096; font-size: 14px; line-height: 1.6;">
                        Add, edit, update, and delete students with validation and database integration.
                    </p>
                </div>

                <div style="padding: 25px; background: #F7FAFC; border-radius: 10px; border-left: 4px solid #667eea;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📊</div>
                    <h3 style="color: #2D3748; margin-bottom: 10px; font-size: 18px;">Reports & Analytics</h3>
                    <p style="color: #718096; font-size: 14px; line-height: 1.6;">
                        Generate charts and view detailed performance statistics and insights.
                    </p>
                </div>

                <div style="padding: 25px; background: #F7FAFC; border-radius: 10px; border-left: 4px solid #F56565;">
                    <div style="font-size: 32px; margin-bottom: 10px;">🎓</div>
                    <h3 style="color: #2D3748; margin-bottom: 10px; font-size: 18px;">Session Management</h3>
                    <p style="color: #718096; font-size: 14px; line-height: 1.6;">
                        Create and close attendance sessions for each course and group.
                    </p>
                </div>

            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
            </div>

            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="students.php" class="btn btn-primary">
                    ➕ Add New Student
                </a>
                <a href="sessions.php" class="btn btn-success">
                    📅 Create Session
                </a>
                <a href="reports.php" class="btn btn-outline">
                    📈 View Reports
                </a>
                <a href="sessions.php" class="btn btn-secondary">
                    📋 View Sessions
                </a>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div style="text-align: center; padding: 30px; color: #718096; font-size: 14px;">
        <p>Student Management System © <?php echo date('Y'); ?></p>
    </div>

</div>

</body>
</html>