<?php
require_once 'db_connect.php';

$conn = getConnection();
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

$students = [];
$attendanceMap = [];
$sessionRow = null;
$sessionError = '';

if (!$sessionId) {
    $sessionError = 'No session selected.';
} elseif ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $sessionRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sessionRow) {
            $sessionError = 'Selected session does not exist.';
        }
    } catch (PDOException $e) {
        $sessionError = 'Error loading session: ' . $e->getMessage();
    }
}

if (!$sessionError && $conn) {
    try {
        $stmt = $conn->query("SELECT id, fullname, matricule, group_id FROM students ORDER BY fullname ASC");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT student_id, status, participated FROM attendance_records WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $attendanceMap[$r['student_id']] = $r;
        }
    } catch (PDOException $e) {
        $sessionError = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Attendance Session</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
        <h1>ATTENDANCE SESSION</h1>
        <div class="topbar-actions">
            <div class="topbar-icon">💾</div>
            <div class="topbar-icon">⚙️</div>
            <div class="topbar-icon">🔔</div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content-section">

        <?php if ($sessionError): ?>
            <div class="message error">
                ⚠️ <?= htmlspecialchars($sessionError) ?><br>
                <a href="sessions.php" style="color:#742A2A; text-decoration:underline;">Go back to Sessions</a>
            </div>
        <?php else: ?>

        <!-- SESSION INFO CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Course</div>
                <div class="stat-value" style="font-size:24px;"><?= htmlspecialchars($sessionRow['course_id']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Group</div>
                <div class="stat-value" style="font-size:24px;"><?= htmlspecialchars($sessionRow['group_id']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Date</div>
                <div class="stat-value" style="font-size:24px;"><?= htmlspecialchars($sessionRow['date']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Session ID</div>
                <div class="stat-value" style="font-size:24px;">#<?= (int)$sessionRow['id'] ?></div>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Attendance Table</h2>
                <div style="display:flex; gap:10px;">
                    <a href="sessions.php" class="btn btn-secondary btn-sm">← Back</a>
                    <button id="saveAll" class="btn btn-primary btn-sm">💾 Save All</button>
                    <span id="saveStatus" style="color:#48BB78; font-weight:600; font-size:14px;"></span>
                </div>
            </div>

            <!-- EXERCISE 7: SEARCH & SORT BAR -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <input type="text" id="searchByName" placeholder="Search by Name (First or Last)...">
                </div>
                
                <div class="filter-buttons">
                    <button id="sortAscAbs" class="btn btn-outline btn-sm">⬆️ Sort by Absences</button>
                    <button id="sortDescPart" class="btn btn-outline btn-sm">⬇️ Sort by Participation</button>
                    <button id="resetSort" class="btn btn-secondary btn-sm">🔄 Reset</button>
                </div>
            </div>

            <!-- SORT MODE INDICATOR (Exercise 7) -->
            <div id="sortModeIndicator" class="sort-indicator" style="display:none;">
                Currently sorted by: <strong id="sortModeText">None</strong>
            </div>

            <!-- TABLE (Tutorial 1 - Exercise 1) -->
            <div class="table-container">
                <table class="table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <?php for ($i=1;$i<=6;$i++): ?>
                                <th>S<?= $i ?> P</th>
                                <th>S<?= $i ?> Pa</th>
                            <?php endfor; ?>
                            <th>Absences</th>
                            <th>Participation</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceBody">
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="18" class="empty-state">
                                    <div class="empty-state-icon">📭</div>
                                    <p>No students in database. <a href="students.php">Add students first</a></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): 
                                $nameParts = explode(' ', $student['fullname'], 2);
                                $last = $nameParts[0] ?? '';
                                $first = $nameParts[1] ?? '';
                                $rec = $attendanceMap[$student['id']] ?? null;
                                $presentFromDb = $rec && $rec['status'] === 'present';
                                $partFromDb = $rec && (int)$rec['participated'] === 1;
                            ?>
                            <tr data-student-id="<?= (int)$student['id'] ?>" 
                                data-matricule="<?= htmlspecialchars($student['matricule']) ?>"
                                data-lastname="<?= htmlspecialchars(strtolower($last)) ?>"
                                data-firstname="<?= htmlspecialchars(strtolower($first)) ?>">
                                <td><?= htmlspecialchars($last) ?></td>
                                <td><?= htmlspecialchars($first) ?></td>

                                <?php for ($j=0;$j<6;$j++): ?>
                                    <td>
                                        <input type="checkbox" class="present-check" data-session="<?= $j ?>"
                                            <?= $presentFromDb ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="participated-check" data-session="<?= $j ?>"
                                            <?= $partFromDb ? 'checked' : '' ?>>
                                    </td>
                                <?php endfor; ?>

                                <td class="absences-count">0 Abs</td>
                                <td class="participation-count">0 Par</td>
                                <td class="message-cell"></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- EXERCISE 4 & 6: CONTROLS -->
            <div style="margin-top:25px; display:flex; gap:15px; flex-wrap:wrap;">
                <button id="showReport" class="btn btn-primary">📊 Show Report</button>
                <button id="highlightExcellent" class="btn btn-success">⭐ Highlight Excellent Students</button>
                <button id="resetColors" class="btn btn-secondary">🎨 Reset Colors</button>
            </div>

            <!-- EXERCISE 4: REPORT SECTION -->
            <div id="reportSection" style="display:none; margin-top:30px; padding:25px; background:#F7FAFC; border-radius:10px;">
                <h3 style="color:#2D3748; margin-bottom:15px;">📈 Attendance Report</h3>
                <p style="color:#4A5568; font-size:15px; margin-bottom:20px;">
                    <strong>Total students:</strong> <span id="reportTotal">0</span> · 
                    <strong>Present (≥1):</strong> <span id="reportPresent">0</span> · 
                    <strong>Participated (≥1):</strong> <span id="reportParticipated">0</span>
                </p>
                <div class="chart-container">
                    <canvas id="reportChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div>
</div>

<script>
(function(){
    const SESSION_ID = <?= $sessionId && !$sessionError ? (int)$sessionId : 'null' ?>;

    // EXERCISE 1: Update row with absences, participation, and highlighting
    function updateRow(row) {
        const presentChecks = row.querySelectorAll('.present-check');
        const partChecks = row.querySelectorAll('.participated-check');
        let abs = 0, par = 0;
        presentChecks.forEach(ch => { if (!ch.checked) abs++; });
        partChecks.forEach(ch => { if (ch.checked) par++; });

        row.querySelector('.absences-count').textContent = abs + ' Abs';
        row.querySelector('.participation-count').textContent = par + ' Par';
        
        // Store absences and participation as data attributes for sorting (Exercise 7)
        row.dataset.absences = abs;
        row.dataset.participation = par;

        // EXERCISE 1: Generate message based on attendance and participation
        let message = '';
        if (abs >= 5) message = 'Excluded — too many absences — You need to participate more';
        else if (abs >= 3) message = 'Warning — attendance low — You need to participate more';
        else if (par >= 4) message = 'Good attendance — Excellent participation';
        else message = 'Good attendance — Work on participation';

        row.querySelector('.message-cell').textContent = message;

        // EXERCISE 1: Color coding based on absences
        row.classList.remove('row-green','row-yellow','row-red');
        if (abs >= 5) row.classList.add('row-red');
        else if (abs >= 3) row.classList.add('row-yellow');
        else row.classList.add('row-green');
    }

    function rowToPayload(row) {
        const studentId = row.dataset.studentId;
        const matricule = row.dataset.matricule;
        const presentChecks = row.querySelectorAll('.present-check');
        const partChecks = row.querySelectorAll('.participated-check');

        const sessions = [];
        for (let i=0;i<6;i++){
            sessions.push({
                present: !!presentChecks[i].checked,
                participated: !!partChecks[i].checked
            });
        }

        const anyPresent = sessions.some(s => s.present);
        const anyParticipated = sessions.some(s => s.participated);

        return {
            session_id: SESSION_ID,
            student_id: parseInt(studentId,10) || null,
            matricule: matricule,
            status: anyPresent ? 'present' : 'absent',
            participated: anyParticipated ? 1 : 0,
            sessions: sessions
        };
    }

    function saveRowToServer(row, indicatorEl=null) {
        if (!SESSION_ID) return Promise.resolve({local:true});
        
        const payload = rowToPayload(row);
        if (indicatorEl) indicatorEl.textContent = '💾 Saving...';
        
        return fetch('save_attendance.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json())
          .then(json => {
              if (indicatorEl) indicatorEl.textContent = (json && json.success) ? '✅ Saved' : '❌ Error';
              setTimeout(() => { if(indicatorEl) indicatorEl.textContent = ''; }, 2000);
              return json;
          }).catch(err => {
              if (indicatorEl) indicatorEl.textContent = '❌ Error';
              return {error:true};
          });
    }

    function bindRowEvents(row) {
        row.querySelectorAll('.present-check, .participated-check').forEach(ch => {
            ch.addEventListener('change', function(){
                updateRow(row);
                if (row._saveTimer) clearTimeout(row._saveTimer);
                row._saveTimer = setTimeout(()=> {
                    saveRowToServer(row, document.getElementById('saveStatus'));
                }, 350);
            });
        });
    }

    // ====== EXERCISE 7: SEARCH FUNCTIONALITY ======
    function setupSearch() {
        const searchInput = document.getElementById('searchByName');
        if (!searchInput) return;

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#attendanceBody tr[data-student-id]');

            rows.forEach(row => {
                const lastname = row.dataset.lastname || '';
                const firstname = row.dataset.firstname || '';
                
                if (lastname.includes(query) || firstname.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // ====== EXERCISE 7: SORT FUNCTIONALITY ======
    function sortTable(mode) {
        const tbody = document.getElementById('attendanceBody');
        const rows = Array.from(tbody.querySelectorAll('tr[data-student-id]'));

        rows.sort((a, b) => {
            if (mode === 'absences-asc') {
                return parseInt(a.dataset.absences || 0) - parseInt(b.dataset.absences || 0);
            } else if (mode === 'participation-desc') {
                return parseInt(b.dataset.participation || 0) - parseInt(a.dataset.participation || 0);
            }
            return 0;
        });

        // Re-append rows in sorted order
        rows.forEach(row => tbody.appendChild(row));

        // Update sort indicator
        const indicator = document.getElementById('sortModeIndicator');
        const modeText = document.getElementById('sortModeText');
        
        if (mode === 'absences-asc') {
            modeText.textContent = 'Absences (Ascending)';
        } else if (mode === 'participation-desc') {
            modeText.textContent = 'Participation (Descending)';
        }
        
        indicator.style.display = 'inline-block';
    }

    function setupSorting() {
        document.getElementById('sortAscAbs')?.addEventListener('click', function() {
            sortTable('absences-asc');
        });

        document.getElementById('sortDescPart')?.addEventListener('click', function() {
            sortTable('participation-desc');
        });

        document.getElementById('resetSort')?.addEventListener('click', function() {
            document.getElementById('sortModeIndicator').style.display = 'none';
            location.reload();
        });
    }

    function init() {
        document.querySelectorAll('#attendanceBody tr[data-student-id]').forEach(row => {
            updateRow(row);
            bindRowEvents(row);
        });

        // Exercise 7: Search and Sort
        setupSearch();
        setupSorting();

        // Save All Button
        document.getElementById('saveAll')?.addEventListener('click', function(){
            const rows = document.querySelectorAll('#attendanceBody tr[data-student-id]');
            const indicator = document.getElementById('saveStatus');
            indicator.textContent = '💾 Saving all...';
            let promises = [];
            rows.forEach(r => promises.push(saveRowToServer(r)));
            Promise.all(promises).then(() => {
                indicator.textContent = '✅ All saved';
                setTimeout(()=> indicator.textContent = '', 2000);
            });
        });

        // EXERCISE 4: Show Report Button
        document.getElementById('showReport')?.addEventListener('click', function(){
            const rows = document.querySelectorAll('#attendanceBody tr[data-student-id]');
            const total = rows.length;
            let present = 0;
            let participated = 0;
            rows.forEach(r => {
                const abs = parseInt(r.dataset.absences) || 0;
                const part = parseInt(r.dataset.participation) || 0;
                if (abs < 6) present++;
                if (part > 0) participated++;
            });
            document.getElementById('reportTotal').textContent = total;
            document.getElementById('reportPresent').textContent = present;
            document.getElementById('reportParticipated').textContent = participated;
            document.getElementById('reportSection').style.display = 'block';

            const ctx = document.getElementById('reportChart').getContext('2d');
            if (window._attendanceChart) window._attendanceChart.destroy();
            window._attendanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Total', 'Present (≥1)', 'Participated (≥1)'],
                    datasets: [{ 
                        label: 'Counts', 
                        data: [total, present, participated], 
                        backgroundColor: ['#FFB84D', '#48BB78', '#667eea'] 
                    }]
                },
                options: { 
                    responsive:true, 
                    plugins:{legend:{display:false}}, 
                    scales:{y:{beginAtZero:true}} 
                }
            });
        });

        // EXERCISE 6: Highlight Excellent Students
        document.getElementById('highlightExcellent')?.addEventListener('click', function(){
            document.querySelectorAll('#attendanceBody tr[data-student-id]').forEach(row=>{
                const abs = parseInt(row.dataset.absences) || 0;
                if (abs < 3) row.classList.add('animate-glow');
            });
        });

        // EXERCISE 6: Reset Colors
        document.getElementById('resetColors')?.addEventListener('click', function(){
            document.querySelectorAll('#attendanceBody tr').forEach(row => {
                row.classList.remove('animate-glow','hover-highlight');
                updateRow(row);
            });
        });

        // EXERCISE 5: jQuery hover & click
        $('#attendanceBody').on('mouseenter', 'tr[data-student-id]', function(){ 
            $(this).addClass('hover-highlight'); 
        }).on('mouseleave','tr[data-student-id]', function(){ 
            $(this).removeClass('hover-highlight'); 
        }).on('click','tr[data-student-id]', function(e){
            if ($(e.target).is('input')) return;
            const last = $(this).find('td').eq(0).text();
            const first = $(this).find('td').eq(1).text();
            const abs = $(this).find('.absences-count').text();
            alert('Student: ' + last + ' ' + first + '\n' + abs);
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
</script>

</body>
</html>