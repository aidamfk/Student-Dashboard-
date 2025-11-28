<?php
/**
 * Tutorial 3 - Exercise 5: Session Management
 * Create and manage attendance sessions
 */
require_once 'db_connect.php';

$message = '';
$error = '';
$conn = getConnection();

// Handle Create Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $course_id = trim($_POST['course_id'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');
    $professor = trim($_POST['professor'] ?? '');
    $date = trim($_POST['date'] ?? date('Y-m-d'));

    if (empty($course_id) || empty($group_id) || empty($professor)) {
        $error = 'All fields are required';
    } elseif ($conn) {
        try {
            $stmt = $conn->prepare("SELECT id FROM attendance_sessions WHERE course_id = ? AND group_id = ? AND date = ?");
            $stmt->execute([$course_id, $group_id, $date]);
            if ($stmt->fetch()) {
                $error = 'A session already exists for this course/group/date';
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance_sessions (course_id, group_id, date, opened_by, status) 
                                        VALUES (?, ?, ?, ?, 'open')");
                $stmt->execute([$course_id, $group_id, $date, $professor]);
                $message = "✅ Session created successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Close Session
if (isset($_GET['close'])) {
    $id = $_GET['close'];
    if ($conn) {
        try {
            $stmt = $conn->prepare("UPDATE attendance_sessions SET status='closed', closed_at=NOW() WHERE id=?");
            $stmt->execute([$id]);
            $message = "✅ Session closed successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all sessions
$sessions = [];
if ($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM attendance_sessions ORDER BY date DESC, id DESC");
        $sessions = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$openCount = count(array_filter($sessions, fn($s) => $s['status'] === 'open'));
$closedCount = count(array_filter($sessions, fn($s) => $s['status'] === 'closed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">👥</div>
        <div class="sidebar-title">Student Dashboard</div>
        <div class="sidebar-subtitle">Management System</div>
    </div>
    
    <nav class="sidebar-menu">
        <ul>
            <li><a href="index.php"><span class="icon">🏠</span> <span>Dashboard</span></a></li>
            <li><a href="students.php"><span class="icon">📋</span> <span>Students</span></a></li>
            <li><a href="sessions.php" class="active"><span class="icon">📅</span> <span>Attendance</span></a></li>
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
        <h1>SESSION MANAGEMENT</h1>
        <div class="topbar-actions">
            <div class="topbar-icon">💾</div>
            <div class="topbar-icon">⚙️</div>
            <div class="topbar-icon">🔔</div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content-section">

        <!-- MESSAGES -->
        <?php if ($message): ?>
            <div class="message success">
                ✅ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- STATISTICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value"><?= count($sessions) ?></div>
                <div class="stat-change">📅 All Time</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Open Sessions</div>
                <div class="stat-value" style="color: #48BB78;"><?= $openCount ?></div>
                <div class="stat-change" style="color: #48BB78;">🟢 Active</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Closed Sessions</div>
                <div class="stat-value" style="color: #718096;"><?= $closedCount ?></div>
                <div class="stat-change" style="color: #718096;">⚫ Completed</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Today's Date</div>
                <div class="stat-value" style="font-size: 20px;"><?= date('M d') ?></div>
                <div class="stat-change">📆 <?= date('Y') ?></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 2fr; gap:25px;">

            <!-- Create Session Form (Tutorial 3 - Exercise 5) -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">➕ Create New Session</h2>
                </div>

                <form method="POST" action="" class="student-form">
                    <input type="hidden" name="create" value="1">

                    <div class="form-group">
                        <label for="course_id">Course ID *</label>
                        <input type="text" id="course_id" name="course_id" value="AWP" placeholder="e.g., AWP" required>
                    </div>

                    <div class="form-group">
                        <label for="group_id">Group ID *</label>
                        <input type="text" id="group_id" name="group_id" placeholder="e.g., G1, G2" required>
                    </div>

                    <div class="form-group">
                        <label for="professor">Professor Name *</label>
                        <input type="text" id="professor" name="professor" placeholder="e.g., Prof. Benali" required>
                    </div>

                    <div class="form-group">
                        <label for="date">Date *</label>
                        <input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        ✨ Create Session
                    </button>
                </form>
            </div>

            <!-- Sessions List -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📋 All Sessions (<?= count($sessions) ?>)</h2>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="window.location.reload()">
                            🔄 Refresh
                        </button>
                    </div>
                </div>

                <?php if (empty($sessions)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📅</div>
                        <p>No sessions yet. Create a session to begin tracking attendance.</p>
                    </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Course</th>
                                <th>Group</th>
                                <th>Date</th>
                                <th>Opened By</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($sessions as $s): ?>
                            <tr>
                                <td><strong>#<?= $s['id'] ?></strong></td>
                                <td><?= htmlspecialchars($s['course_id']) ?></td>
                                <td><?= htmlspecialchars($s['group_id']) ?></td>
                                <td><?= htmlspecialchars($s['date']) ?></td>
                                <td><?= htmlspecialchars($s['opened_by']) ?></td>

                                <td>
                                    <span class="status-badge status-<?= $s['status'] ?>">
                                        <?= $s['status'] === 'open' ? '🟢' : '⚫' ?> <?= ucfirst($s['status']) ?>
                                    </span>
                                </td>

                                <td><?= date('Y-m-d H:i', strtotime($s['created_at'])) ?></td>

                                <td style="display:flex; gap:8px; flex-wrap:wrap;">

                                    <!-- Take Attendance -->
                                    <a href="attendance.php?session_id=<?= $s['id'] ?>"
                                       class="btn btn-primary btn-sm">
                                       📝 Attendance
                                    </a>

                                    <!-- Close Session -->
                                    <?php if ($s['status'] === 'open'): ?>
                                        <a href="sessions.php?close=<?= $s['id'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Close this session?');">
                                           🔒 Close
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#999; font-size:12px;">Closed</span>
                                    <?php endif; ?>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- FOOTER -->
    <div style="text-align: center; padding: 30px; color: #718096; font-size: 14px;">
        <p>Student Management System © <?= date('Y') ?> </p>
    </div>

</div>

</body>
</html>