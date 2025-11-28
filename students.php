<?php
require_once 'db_connect.php';

$message = '';
$error = '';
$editStudent = null;

$conn = getConnection();

// Handle Edit Request
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $editStudent = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($conn) {
        try {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $message = "✅ Student deleted successfully!";
        } catch (PDOException $e) {
            $error = "Delete error: " . $e->getMessage();
        }
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $matricule = trim($_POST['matricule'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');
    $id = $_POST['id'] ?? null;

    if (empty($fullname) || empty($matricule) || empty($email) || empty($group_id)) {
        $error = 'All fields are required';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
        $error = 'Full name must contain only letters';
    } elseif (!preg_match('/^\d+$/', $matricule)) {
        $error = 'Matricule must contain only numbers';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        if ($conn) {
            try {
                if ($id) {
                    // Update - check for duplicate matricule or email
                    $stmt = $conn->prepare("SELECT id FROM students WHERE (matricule = ? OR email = ?) AND id != ?");
                    $stmt->execute([$matricule, $email, $id]);
                    if ($stmt->fetch()) {
                        $error = 'This matricule or email is already used by another student';
                    } else {
                        $stmt = $conn->prepare("UPDATE students SET fullname=?, matricule=?, email=?, group_id=? WHERE id=?");
                        $stmt->execute([$fullname, $matricule, $email, $group_id, $id]);
                        $message = "✅ Student updated successfully!";
                        $editStudent = null;
                    }
                } else {
                    // Insert - check for duplicate matricule or email
                    $stmt = $conn->prepare("SELECT id FROM students WHERE matricule = ? OR email = ?");
                    $stmt->execute([$matricule, $email]);
                    if ($stmt->fetch()) {
                        $error = 'A student with this matricule or email already exists';
                    } else {
                        $stmt = $conn->prepare("INSERT INTO students(fullname, matricule, email, group_id) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$fullname, $matricule, $email, $group_id]);
                        $message = "✅ Student added successfully!";
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Database connection failed";
        }
    }
}

// Fetch all students
$students = [];
if ($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM students ORDER BY id DESC");
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Error fetching students: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Students</title>
    <link rel="stylesheet" href="style.css" />
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
            <li><a href="students.php" class="active"><span class="icon">📋</span> <span>Students</span></a></li>
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
        <h1>STUDENT MANAGEMENT</h1>
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
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- STATISTICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?php echo count($students); ?></div>
                <div class="stat-change">📚 In Database</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Groups</div>
                <div class="stat-value"><?php 
                    $groups = array_unique(array_column($students, 'group_id'));
                    echo count($groups);
                ?></div>
                <div class="stat-change">🎓 Active Groups</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Last Added</div>
                <div class="stat-value" style="font-size: 20px;">
                    <?php 
                    if (!empty($students)) {
                        echo htmlspecialchars($students[0]['fullname']);
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div class="stat-change">👤 Most Recent</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Status</div>
                <div class="stat-value" style="font-size: 28px; color: #48BB78;">✓</div>
                <div class="stat-change" style="color: #48BB78;">System Active</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 2fr; gap:25px; margin-top: 20px;">

            <!-- Add / Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <?php echo $editStudent ? '✏️ Edit Student' : '➕ Add New Student'; ?>
                    </h2>
                </div>

                <form id="studentForm" method="POST" action="" class="student-form" novalidate>
                    <?php if ($editStudent): ?>
                        <input type="hidden" name="id" value="<?php echo $editStudent['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="fullname">Full Name *</label>
                        <input type="text" id="fullname" name="fullname"
                               value="<?php echo htmlspecialchars($editStudent['fullname'] ?? ''); ?>" 
                               placeholder="e.g., Ahmed Sara"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="matricule">Matricule *</label>
                        <input type="text" id="matricule" name="matricule"
                               value="<?php echo htmlspecialchars($editStudent['matricule'] ?? ''); ?>" 
                               placeholder="e.g., 1001"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($editStudent['email'] ?? ''); ?>" 
                               placeholder="e.g., student@example.com"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="group_id">Group *</label>
                        <input type="text" id="group_id" name="group_id"
                               value="<?php echo htmlspecialchars($editStudent['group_id'] ?? ''); ?>" 
                               placeholder="e.g., G1"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                        <?php echo $editStudent ? '💾 Update Student' : '➕ Add Student'; ?>
                    </button>

                    <?php if ($editStudent): ?>
                        <a href="students.php" class="btn btn-secondary" style="width: 100%; margin-top:10px; display:block; text-align:center; text-decoration: none;">
                            ❌ Cancel Edit
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Student List -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📋 Students List (<?php echo count($students); ?>)</h2>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="window.print()">
                            🖨️ Print List
                        </button>
                    </div>
                </div>

                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <p>No students found. Add your first student using the form!</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Matricule</th>
                                    <th>Email</th>
                                    <th>Group</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><strong>#<?php echo $student['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                                        <td><span style="background: #E2E8F0; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 12px;"><?php echo htmlspecialchars($student['matricule']); ?></span></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['group_id']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($student['created_at'])); ?></td>

                                        <td>
                                            <a href="students.php?edit=<?php echo $student['id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                ✏️ Edit
                                            </a>
                                            <a href="students.php?delete=<?php echo $student['id']; ?>"
                                               onclick="return confirm('Are you sure you want to delete this student?');"
                                               class="btn btn-danger btn-sm">
                                                🗑️ Delete
                                            </a>
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
        <p>Student Management System © <?php echo date('Y'); ?></p>
    </div>

</div>

<script>
// Tutorial 2 - Exercise 2: Client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('studentForm');
    const fullname = document.getElementById('fullname');
    const matricule = document.getElementById('matricule');
    const email = document.getElementById('email');
    const group_id = document.getElementById('group_id');

    function showError(field, message) {
        let errorDiv = field.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('field-error')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
        }
        errorDiv.textContent = message;
        field.style.borderColor = message ? '#F56565' : '#E2E8F0';
    }

    function validateName(value) {
        if (!value.trim()) return 'Full name is required';
        if (!/^[A-Za-z\s]+$/.test(value)) return 'Full name must contain only letters';
        return '';
    }

    function validateMatricule(value) {
        if (!value.trim()) return 'Matricule is required';
        if (!/^\d+$/.test(value)) return 'Matricule must contain only numbers';
        return '';
    }

    function validateEmail(value) {
        if (!value.trim()) return 'Email is required';
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Invalid email format (e.g., name@example.com)';
        return '';
    }

    function validateGroup(value) {
        if (!value.trim()) return 'Group is required';
        return '';
    }

    // Real-time validation on blur
    fullname.addEventListener('blur', () =>
        showError(fullname, validateName(fullname.value))
    );

    matricule.addEventListener('blur', () =>
        showError(matricule, validateMatricule(matricule.value))
    );

    email.addEventListener('blur', () =>
        showError(email, validateEmail(email.value))
    );

    group_id.addEventListener('blur', () =>
        showError(group_id, validateGroup(group_id.value))
    );

    // Form submission validation
    form.addEventListener('submit', function(e) {
        const nameErr = validateName(fullname.value);
        const matErr  = validateMatricule(matricule.value);
        const emailErr = validateEmail(email.value);
        const grpErr  = validateGroup(group_id.value);

        showError(fullname, nameErr);
        showError(matricule, matErr);
        showError(email, emailErr);
        showError(group_id, grpErr);

        if (nameErr || matErr || emailErr || grpErr) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>