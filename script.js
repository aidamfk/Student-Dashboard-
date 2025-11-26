/* script.js — TP2 & TP3 Complete
   - Storage & retrieval (localStorage)
   - Render table with 6 sessions (P & Pa)
   - Toggle checkboxes save -> updates counts, messages, row colors
   - PROPER FORM VALIDATION (Tutorial 2, Exercise 2)
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

  bindRowInteractions();
}

/* ---------- Helpers ---------- */
function escapeHtml(str='') {
  return String(str).replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
}

/* ---------- TUTORIAL 2 EXERCISE 2: PROPER VALIDATION ---------- */
function validateStudentID(id) {
  // Must not be empty and contain only numbers
  if (!id || id.trim() === '') return { valid: false, message: 'Student ID is required' };
  if (!/^\d+$/.test(id)) return { valid: false, message: 'Student ID must contain only numbers' };
  return { valid: true, message: '' };
}

function validateName(name, fieldName) {
  // Must not be empty and contain only letters (and spaces for compound names)
  if (!name || name.trim() === '') return { valid: false, message: `${fieldName} is required` };
  if (!/^[a-zA-Z\s]+$/.test(name)) return { valid: false, message: `${fieldName} must contain only letters` };
  return { valid: true, message: '' };
}

function validateEmail(email) {
  // Must follow valid email format
  if (!email || email.trim() === '') return { valid: false, message: 'Email is required' };
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) return { valid: false, message: 'Email format is invalid (e.g., name@example.com)' };
  return { valid: true, message: '' };
}

function showFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  if (!field) return;
  
  // Remove any existing error message
  let errorEl = document.getElementById(`${fieldId}-error`);
  if (errorEl) errorEl.remove();
  
  if (message) {
    // Create and display error message under the field
    errorEl = document.createElement('div');
    errorEl.id = `${fieldId}-error`;
    errorEl.className = 'field-error';
    errorEl.textContent = message;
    errorEl.style.color = '#ef4444';
    errorEl.style.fontSize = '12px';
    errorEl.style.marginTop = '4px';
    errorEl.style.textAlign = 'left';
    field.parentNode.insertBefore(errorEl, field.nextSibling);
    field.style.borderColor = '#ef4444';
  } else {
    field.style.borderColor = '#e6e6e6';
  }
}

function clearAllErrors() {
  ['id', 'lastname', 'firstname', 'email'].forEach(fieldId => {
    showFieldError(fieldId, '');
  });
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

/* ---------- Add Student behavior with PROPER VALIDATION (Tutorial 2, Exercise 2) ---------- */
function initAddStudentForm() {
  const form = document.getElementById('studentForm');
  if (!form) return;

  // Real-time validation on input
  ['id', 'lastname', 'firstname', 'email'].forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
      field.addEventListener('blur', function() {
        validateField(fieldId);
      });
    }
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    clearAllErrors();
    
    const id = document.getElementById('id').value.trim();
    const lastname = document.getElementById('lastname').value.trim();
    const firstname = document.getElementById('firstname').value.trim();
    const email = document.getElementById('email').value.trim();
    const course = document.getElementById('course').value.trim() || 'AWP';
    const messageEl = document.getElementById('formMsg');

    // Validate all fields
    const idValidation = validateStudentID(id);
    const lastnameValidation = validateName(lastname, 'Last Name');
    const firstnameValidation = validateName(firstname, 'First Name');
    const emailValidation = validateEmail(email);

    // Show errors under each field
    showFieldError('id', idValidation.message);
    showFieldError('lastname', lastnameValidation.message);
    showFieldError('firstname', firstnameValidation.message);
    showFieldError('email', emailValidation.message);

    // Prevent submission if any validation fails
    if (!idValidation.valid || !lastnameValidation.valid || 
        !firstnameValidation.valid || !emailValidation.valid) {
      if (messageEl) messageEl.textContent = '❌ Please fix the errors above before submitting.';
      return;
    }

    // Check for duplicate ID
    const students = getStudents();
    if (students.some(s => String(s.id) === String(id))) {
      showFieldError('id', 'A student with this ID already exists');
      if (messageEl) messageEl.textContent = '❌ Student ID already exists.';
      return;
    }

    // All validations passed - add student
    const sessions = [];
    for (let i=0;i<6;i++) sessions.push({ present:false, participated:false });

    const newStudent = { id, lastname, firstname, email, course, date: new Date().toLocaleDateString(), sessions };
    students.push(newStudent);
    saveStudents(students);
    form.reset();
    clearAllErrors();
    
    if (messageEl) {
      messageEl.style.color = '#10B981';
      messageEl.textContent = '✅ Student added successfully!';
      setTimeout(()=>{ 
        if (messageEl) {
          messageEl.textContent='';
          messageEl.style.color = '';
        }
      }, 2500);
    }
    
    renderAttendanceTable();
  });
}

function validateField(fieldId) {
  const value = document.getElementById(fieldId).value.trim();
  let validation;
  
  switch(fieldId) {
    case 'id':
      validation = validateStudentID(value);
      break;
    case 'lastname':
      validation = validateName(value, 'Last Name');
      break;
    case 'firstname':
      validation = validateName(value, 'First Name');
      break;
    case 'email':
      validation = validateEmail(value);
      break;
  }
  
  if (validation) {
    showFieldError(fieldId, validation.message);
  }
}

/* ---------- Report (Exercise 4) ---------- */
let chartInstance = null;
function computeReportCounts() {
  const students = getStudents();
  const total = students.length;
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

/* ---------- Exercise 5 & 6: jQuery interactions ---------- */
function bindRowInteractions() {
  if (typeof jQuery === 'undefined') return;

  $('#attendanceBody').off('mouseenter', 'tr').off('mouseleave', 'tr').off('click', 'tr');

  $('#attendanceBody').on('mouseenter', 'tr', function() {
    $(this).addClass('hover-highlight');
  });
  
  $('#attendanceBody').on('mouseleave', 'tr', function() {
    $(this).removeClass('hover-highlight');
  });

  $('#attendanceBody').on('click', 'tr', function(e) {
    if ($(e.target).is('input, button')) return;
    const rowIndex = $(this).index();
    const students = getStudents();
    const s = students[rowIndex];
    if (!s) return;
    const absences = s.sessions.reduce((acc, sess) => acc + (sess.present ? 0 : 1), 0);
    alert(`Student: ${s.firstname} ${s.lastname}\nAbsences: ${absences}`);
  });
}

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

function resetRowColors() {
  if (typeof jQuery === 'undefined') return;
  $('#attendanceBody tr').removeClass('animate-glow hover-highlight');
  renderAttendanceTable();
}

/* ---------- Update home page stats ---------- */
function updateHomeStats() {
  const students = getStudents();
  const totalEl = document.getElementById('totalStudents');
  const presentEl = document.getElementById('todayPresent');
  const participatedEl = document.getElementById('todayParticipated');
  
  if (totalEl) totalEl.textContent = students.length;
  if (presentEl) {
    const presentCount = students.filter(s => s.sessions.some(sess => sess.present)).length;
    presentEl.textContent = presentCount;
  }
  if (participatedEl) {
    const participatedCount = students.filter(s => s.sessions.some(sess => sess.participated)).length;
    participatedEl.textContent = participatedCount;
  }
}

/* ---------- Init on DOM ready ---------- */
document.addEventListener('DOMContentLoaded', function() {
  seedDemoStudents();
  renderAttendanceTable();
  initAttendanceInteractions();
  initAddStudentForm();
  updateHomeStats();

  const showBtn = document.getElementById('showReport');
  if (showBtn) showBtn.addEventListener('click', showReport);

  const highBtn = document.getElementById('highlightExcellent');
  if (highBtn) highBtn.addEventListener('click', highlightExcellentStudents);

  const resetBtn = document.getElementById('resetColors');
  if (resetBtn) resetBtn.addEventListener('click', resetRowColors);

  bindRowInteractions();
});