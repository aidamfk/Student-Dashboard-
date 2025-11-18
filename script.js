/* script.js — TP3 combined
   - Storage & retrieval (localStorage)
   - Render table with 6 sessions (P & Pa)
   - Toggle checkboxes save -> updates counts, messages, row colors
   - Add student form support (in add_student.html)
   - Show Report (counts + Chart.js)
   - jQuery for row hover, click alert, highlight excellent animation & reset
*/

/* ---------- Storage ---------- */
const STORAGE_KEY = 'awp_students_tp3_v1';

function getStudents() {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; }
  catch(e) { console.error('parse error', e); return []; }
}
function saveStudents(arr) { localStorage.setItem(STORAGE_KEY, JSON.stringify(arr)); }

/* ---------- Demo seed ---------- */
function seedDemoStudents() {
  const existing = getStudents();
  if (existing.length > 0) return;
  const makeSessions = (pArr, paArr) => {
    const sessions = [];
    for (let i=0;i<6;i++){
      sessions.push({ present: !!pArr[i], participated: !!paArr[i] });
    }
    return sessions;
  };

  const students = [
    { id:'1001', lastname:'Ahmed', firstname:'Sara', email:'sara@example.com', course:'AWP', date: new Date().toLocaleDateString(),
      sessions: makeSessions([0,0,1,1,0,0],[0,0,1,1,0,0]) },
    { id:'1002', lastname:'Yacine', firstname:'Ali', email:'ali@example.com', course:'AWP', date: new Date().toLocaleDateString(),
      sessions: makeSessions([1,1,1,1,1,1],[1,1,1,1,1,0]) },
    { id:'1003', lastname:'Houcine', firstname:'Rania', email:'rania@example.com', course:'AWP', date: new Date().toLocaleDateString(),
      sessions: makeSessions([1,1,0,1,0,0],[1,1,0,1,0,0]) }
  ];
  saveStudents(students);
}

/* ---------- Render Table ---------- */
function renderAttendanceTable() {
  const tbody = document.getElementById('attendanceBody');
  if (!tbody) return;
  tbody.innerHTML = '';
  const students = getStudents();

  if (students.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td colspan="18" style="padding:18px;text-align:center;color:#666;">No students. Use "Add Student" or "Seed demo students".</td>`;
    tbody.appendChild(tr);
    return;
  }

  students.forEach((s, idx) => {
    const absences = s.sessions.reduce((acc, sess) => acc + (sess.present ? 0 : 1), 0);
    const participations = s.sessions.reduce((acc, sess) => acc + (sess.participated ? 1 : 0), 0);
    let messageText = '';
    if (absences >= 5) messageText = 'Excluded — too many absences — You need to participate more';
    else if (absences >= 3) messageText = 'Warning — attendance low — You need to participate more';
    else {
      if (participations >= 4) messageText = 'Good attendance — Excellent participation';
      else messageText = 'Good attendance — Work on participation';
    }

    const tr = document.createElement('tr');
    let rowClass = '';
    if (absences >= 5) rowClass = 'row-red';
    else if (absences >= 3) rowClass = 'row-yellow';
    else rowClass = 'row-green';
    tr.className = rowClass;

    let inner = `<td>${escapeHtml(s.lastname)}</td><td>${escapeHtml(s.firstname)}</td>`;
    s.sessions.forEach((sess, si) => {
      const pChecked = sess.present ? 'checked' : '';
      const paChecked = sess.participated ? 'checked' : '';
      inner += `<td><input type="checkbox" data-action="toggle-present" data-index="${idx}" data-session="${si}" ${pChecked}></td>`;
      inner += `<td><input type="checkbox" data-action="toggle-part" data-index="${idx}" data-session="${si}" ${paChecked}></td>`;
    });
    inner += `<td>${absences} Abs</td>`;
    inner += `<td>${participations} Par</td>`;
    inner += `<td class="message-cell">${escapeHtml(messageText)}</td>`;
    tr.innerHTML = inner;
    tbody.appendChild(tr);
  });

  // rebind jQuery hover/ click behavior (Exercise 5) - using delegation is also possible
  bindRowInteractions();
}

/* ---------- Helpers ---------- */
function escapeHtml(str='') {
  return String(str).replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
}

/* ---------- Checkbox interactions (toggle present / participated) ---------- */
function initAttendanceInteractions() {
  const tbody = document.getElementById('attendanceBody');
  if (!tbody) return;

  tbody.addEventListener('change', function(e) {
    const input = e.target;
    const action = input.dataset.action;
    if (!action) return;
    const idx = Number(input.dataset.index);
    const sessionIndex = Number(input.dataset.session);
    const students = getStudents();
    if (!students[idx]) return;

    if (action === 'toggle-present') students[idx].sessions[sessionIndex].present = input.checked;
    else if (action === 'toggle-part') students[idx].sessions[sessionIndex].participated = input.checked;

    saveStudents(students);
    renderAttendanceTable();
  });

  // seed and clear
  const seedBtn = document.getElementById('seedBtn');
  if (seedBtn) seedBtn.addEventListener('click', function() {
    if (confirm('Seed demo students? This will only add demo if storage is empty.')) {
      seedDemoStudents();
      renderAttendanceTable();
    }
  });
  const clearBtn = document.getElementById('clearAll');
  if (clearBtn) clearBtn.addEventListener('click', function() {
    if (!confirm('Clear all stored students?')) return;
    localStorage.removeItem(STORAGE_KEY);
    renderAttendanceTable();
  });
}

/* ---------- Add Student behavior (add_student.html) ---------- */
function initAddStudentForm() {
  const form = document.getElementById('studentForm');
  if (!form) return;

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('id').value.trim();
    const lastname = document.getElementById('lastname').value.trim();
    const firstname = document.getElementById('firstname').value.trim();
    const email = document.getElementById('email').value.trim();
    const course = document.getElementById('course').value.trim() || 'AWP';
    const messageEl = document.getElementById('formMsg');

    // basic validation (simple)
    if (!id || !lastname || !firstname || !email) {
      if (messageEl) messageEl.textContent = 'Please fill all required fields.';
      return;
    }

    const students = getStudents();
    if (students.some(s => String(s.id) === String(id))) {
      if (messageEl) messageEl.textContent = 'A student with this ID already exists.';
      return;
    }

    const sessions = [];
    for (let i=0;i<6;i++) sessions.push({ present:false, participated:false });

    const newStudent = { id, lastname, firstname, email, course, date: new Date().toLocaleDateString(), sessions };
    students.push(newStudent);
    saveStudents(students);
    form.reset();
    if (messageEl) {
      messageEl.textContent = '✅ Student added (sessions default blank).';
      setTimeout(()=>{ if (messageEl) messageEl.textContent=''; }, 2200);
    }
    renderAttendanceTable();
  });
}

/* ---------- Report (Exercise 4) ---------- */
let chartInstance = null;
function computeReportCounts() {
  const students = getStudents();
  const total = students.length;
  // students marked present means students who have at least one present true
  const presentStudents = students.filter(s => s.sessions.some(sess => sess.present)).length;
  const participatedStudents = students.filter(s => s.sessions.some(sess => sess.participated)).length;
  return { total, presentStudents, participatedStudents };
}
function showReport() {
  const reportSection = document.getElementById('reportSection');
  if (!reportSection) return;
  reportSection.style.display = 'block';

  const { total, presentStudents, participatedStudents } = computeReportCounts();
  document.getElementById('reportTotal').textContent = total;
  document.getElementById('reportPresent').textContent = presentStudents;
  document.getElementById('reportParticipated').textContent = participatedStudents;

  // Chart.js: bar chart with three bars
  const ctx = document.getElementById('reportChart').getContext('2d');
  if (chartInstance) chartInstance.destroy();
  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Total Students','Students Present (≥1)','Students Participated (≥1)'],
      datasets: [{
        label: 'Counts',
        data: [total, presentStudents, participatedStudents],
        backgroundColor: ['#A6615A', '#10B981', '#06B6D4']
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display:false } },
      scales: {
        y: { beginAtZero:true, ticks:{ precision:0 } }
      }
    }
  });
}

/* ---------- Exercise 5 & 6: jQuery interactions (hover, click, highlight, reset) ---------- */
function bindRowInteractions() {
  // make sure jQuery exists
  if (typeof jQuery === 'undefined') return;

  // remove previous handlers to avoid duplicates
  $('#attendanceBody').off('mouseenter', 'tr').off('mouseleave', 'tr').off('click', 'tr');

  // hover highlight: add class hover-highlight
  $('#attendanceBody').on('mouseenter', 'tr', function() {
    $(this).addClass('hover-highlight');
  });
  $('#attendanceBody').on('mouseleave', 'tr', function() {
    $(this).removeClass('hover-highlight');
  });

  // click row: find student full name and absences, then show alert
  $('#attendanceBody').on('click', 'tr', function(e) {
    // ignore clicks on inputs (checkboxes)
    if ($(e.target).is('input, button')) return;

    const rowIndex = $(this).index();
    const students = getStudents();
    const s = students[rowIndex];
    if (!s) return;
    const absences = s.sessions.reduce((acc, sess) => acc + (sess.present ? 0 : 1), 0);
    alert(`Student: ${s.firstname} ${s.lastname}\nAbsences: ${absences}`);
  });
}

/* Highlight Excellent Students (fewer than 3 absences) + animate */
function highlightExcellentStudents() {
  if (typeof jQuery === 'undefined') return;
  const students = getStudents();
  $('#attendanceBody tr').removeClass('animate-glow');
  students.forEach((s, idx) => {
    const absences = s.sessions.reduce((acc, sess) => acc + (sess.present ? 0 : 1), 0);
    if (absences < 3) {
      $('#attendanceBody tr').eq(idx).addClass('animate-glow');
    }
  });
}

/* Reset colors -> restore row-green/yellow/red and remove animations */
function resetRowColors() {
  if (typeof jQuery === 'undefined') return;
  $('#attendanceBody tr').removeClass('animate-glow hover-highlight');
  // Re-rendering already sets classes; but to be safe we recompute classes by re-rendering:
  renderAttendanceTable(); // this also rebinds interactions
}

/* ---------- Init on DOM ready ---------- */
document.addEventListener('DOMContentLoaded', function() {
  // ensure demo data if empty (so teacher sees content)
  seedDemoStudents();
  renderAttendanceTable();
  initAttendanceInteractions();
  initAddStudentForm();

  // Show Report button
  const showBtn = document.getElementById('showReport');
  if (showBtn) showBtn.addEventListener('click', function() {
    showReport();
  });

  // Highlight excellent & reset buttons (use jQuery functions)
  const highBtn = document.getElementById('highlightExcellent');
  if (highBtn) highBtn.addEventListener('click', function() {
    highlightExcellentStudents();
  });
  const resetBtn = document.getElementById('resetColors');
  if (resetBtn) resetBtn.addEventListener('click', function() {
    resetRowColors();
  });

  // Ensure jQuery bindings (hover/click) are initialized
  bindRowInteractions();
});
