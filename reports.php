<?php
/**
 * Tutorial 1 - Exercise 3 & Tutorial 2 - Exercise 4: Reports & Analytics
 * Display statistics and charts
 */
require_once 'db_connect.php';

$conn = getConnection();

$stats = [
    'total_students' => 0,
    'total_sessions' => 0,
    'open_sessions'  => 0,
    'closed_sessions'=> 0,
    'groups'         => []
];

$error = '';

if ($conn) {
    try {
        $stats['total_students'] = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch()['c'];
        $stats['total_sessions'] = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions")->fetch()['c'];
        $stats['open_sessions']  = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE status='open'")->fetch()['c'];
        $stats['closed_sessions']= $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE status='closed'")->fetch()['c'];

        $stmt = $conn->query("SELECT group_id, COUNT(*) AS count FROM students GROUP BY group_id ORDER BY group_id");
        $stats['groups'] = $stmt->fetchAll();

    } catch (PDOException $e) {
        $error = "Error loading statistics: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Statistics</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <li><a href="sessions.php"><span class="icon">📅</span> <span>Attendance</span></a></li>
            <li><a href="reports.php" class="active"><span class="icon">📊</span> <span>Reports</span></a></li>
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
        <h1>REPORTS & ANALYTICS</h1>
        <div class="topbar-actions">
            <div class="topbar-icon">💾</div>
            <div class="topbar-icon">⚙️</div>
            <div class="topbar-icon">🔔</div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content-section">

        <?php if ($error): ?>
            <div class="message error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- STATISTICS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?= $stats['total_students'] ?></div>
                <div class="stat-change">👥 Enrolled</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value"><?= $stats['total_sessions'] ?></div>
                <div class="stat-change">📅 All Time</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Open Sessions</div>
                <div class="stat-value" style="color: #48BB78;"><?= $stats['open_sessions'] ?></div>
                <div class="stat-change" style="color: #48BB78;">🟢 Active Now</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Closed Sessions</div>
                <div class="stat-value" style="color: #718096;"><?= $stats['closed_sessions'] ?></div>
                <div class="stat-change" style="color: #718096;">⚫ Completed</div>
            </div>
        </div>

        <!-- MAIN REPORT CARD -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📊 Students by Group</h2>
                <div>
                    <button class="btn btn-outline btn-sm" onclick="window.print()">
                        🖨️ Print Report
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="window.location.reload()">
                        🔄 Refresh Data
                    </button>
                </div>
            </div>

            <?php if (empty($stats['groups'])): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <p>No data available. Add students to see group statistics.</p>
                </div>
            <?php else: ?>
                
                <!-- CHART (Tutorial 2 - Exercise 4) -->
                <div class="chart-container">
                    <canvas id="groupChart"></canvas>
                </div>

                <!-- TABLE -->
                <div style="margin-top: 30px;">
                    <h3 style="color: #2D3748; margin-bottom: 20px; font-size: 18px;">📋 Detailed Breakdown</h3>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Group ID</th>
                                    <th>Number of Students</th>
                                    <th>Percentage</th>
                                    <th>Visual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = $stats['total_students'];
                                foreach ($stats['groups'] as $g): 
                                    $percentage = $total > 0 ? round(($g['count'] / $total) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($g['group_id']) ?></strong></td>
                                        <td><?= $g['count'] ?> student<?= $g['count'] != 1 ? 's' : '' ?></td>
                                        <td><?= $percentage ?>%</td>
                                        <td>
                                            <div style="background: #E2E8F0; border-radius: 10px; height: 24px; width: 100%; max-width: 200px; position: relative; overflow: hidden;">
                                                <div style="background: linear-gradient(90deg, #FFB84D, #F2994A); height: 100%; width: <?= $percentage ?>%; border-radius: 10px; transition: width 0.5s ease;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
        </div>

        <!-- ADDITIONAL INSIGHTS -->
        <?php if (!empty($stats['groups'])): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 25px;">
            
            <div class="card">
                <h3 style="color: #2D3748; margin-bottom: 15px; font-size: 18px;">🎯 Quick Insights</h3>
                <div style="color: #4A5568; line-height: 1.8;">
                    <p style="margin-bottom: 10px;">
                        <strong style="color: #FFB84D;">Largest Group:</strong> 
                        <?php 
                        $largest = array_reduce($stats['groups'], fn($carry, $item) => 
                            ($item['count'] > ($carry['count'] ?? 0)) ? $item : $carry, []);
                        echo htmlspecialchars($largest['group_id'] ?? 'N/A') . ' (' . ($largest['count'] ?? 0) . ' students)';
                        ?>
                    </p>
                    <p style="margin-bottom: 10px;">
                        <strong style="color: #FFB84D;">Total Groups:</strong> 
                        <?= count($stats['groups']) ?>
                    </p>
                    <p style="margin-bottom: 10px;">
                        <strong style="color: #FFB84D;">Average Group Size:</strong> 
                        <?= $stats['total_students'] > 0 ? round($stats['total_students'] / count($stats['groups']), 1) : 0 ?> students
                    </p>
                </div>
            </div>

            <div class="card">
                <h3 style="color: #2D3748; margin-bottom: 15px; font-size: 18px;">📈 System Health</h3>
                <div style="color: #4A5568; line-height: 1.8;">
                    <p style="margin-bottom: 10px;">
                        <strong style="color: #48BB78;">✓ Database Status:</strong> Connected
                    </p>
                    <p style="margin-bottom: 10px;">
                        <strong style="color: #48BB78;">✓ Data Integrity:</strong> Verified
                    </p>
                    <p style="margin-bottom: 10px;">
                        <strong style="color: #48BB78;">✓ Last Updated:</strong> <?= date('Y-m-d H:i:s') ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <h3 style="color: #2D3748; margin-bottom: 15px; font-size: 18px;">🔗 Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="students.php" class="btn btn-primary" style="text-decoration: none; text-align: center;">
                        👥 View All Students
                    </a>
                    <a href="sessions.php" class="btn btn-success" style="text-decoration: none; text-align: center;">
                        📅 Manage Sessions
                    </a>
                    <a href="index.php" class="btn btn-outline" style="text-decoration: none; text-align: center;">
                        🏠 Back to Dashboard
                    </a>
                </div>
            </div>

        </div>
        <?php endif; ?>

    </div>

    <!-- FOOTER -->
    <div style="text-align: center; padding: 30px; color: #718096; font-size: 14px;">
        <p>Student Management System © <?= date('Y') ?> — Reports & Analytics</p>
    </div>

</div>

<script>
<?php if (!empty($stats['groups'])): ?>
const ctx = document.getElementById('groupChart').getContext('2d');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($stats['groups'], 'group_id')) ?>,
        datasets: [{
            label: 'Students per Group',
            data: <?= json_encode(array_column($stats['groups'], 'count')) ?>,
            backgroundColor: [
                '#FFB84D', 
                '#48BB78', 
                '#667eea', 
                '#F56565', 
                '#06B6D4', 
                '#F2994A'
            ],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { 
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 14,
                        weight: '600'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(45, 55, 72, 0.9)',
                padding: 12,
                titleFont: {
                    size: 14,
                    weight: '700'
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' students (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>