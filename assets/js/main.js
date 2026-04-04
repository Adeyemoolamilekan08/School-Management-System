// ============================================================
// SCHOOL MANAGEMENT SYSTEM — MAIN JAVASCRIPT
// ============================================================

// ----- Clock in topbar -----
function updateClock() {
    var el = document.getElementById('topbarTime');
    if (el) {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        el.textContent = h + ':' + m;
    }
}
updateClock();
setInterval(updateClock, 60000);

// ----- Sidebar mobile toggle -----
var menuToggle = document.getElementById('menuToggle');
var sidebar    = document.getElementById('sidebar');

if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function () {
        sidebar.classList.toggle('open');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && e.target !== menuToggle) {
                sidebar.classList.remove('open');
            }
        }
    });
}

// ----- Highlight active nav link -----
var currentPath = window.location.pathname;
document.querySelectorAll('.nav-item').forEach(function (link) {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('/').pop().replace('.php',''))) {
        link.classList.add('active');
    }
});

// ----- Modal helpers -----
function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// Close modal on overlay click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// ----- Confirm delete -----
function confirmDelete(url, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?\nThis action cannot be undone.')) {
        window.location.href = url;
    }
}

// ----- Auto-dismiss alerts after 4 seconds -----
setTimeout(function () {
    document.querySelectorAll('.alert').forEach(function (el) {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 500);
    });
}, 4000);

// ----- Score auto-calculate total -----
function calcTotal() {
    var ca   = parseFloat(document.getElementById('ca_score')   ? document.getElementById('ca_score').value   : 0) || 0;
    var exam = parseFloat(document.getElementById('exam_score') ? document.getElementById('exam_score').value : 0) || 0;
    var total = ca + exam;
    var grade = '';
    var remark = '';

    if (total >= 70)      { grade = 'A'; remark = 'Excellent'; }
    else if (total >= 60) { grade = 'B'; remark = 'Very Good'; }
    else if (total >= 50) { grade = 'C'; remark = 'Good'; }
    else if (total >= 40) { grade = 'D'; remark = 'Pass'; }
    else                  { grade = 'F'; remark = 'Fail'; }

    var totalEl  = document.getElementById('total_score');
    var gradeEl  = document.getElementById('grade_display');
    if (totalEl)  totalEl.value = total;
    if (gradeEl)  gradeEl.textContent = grade + ' — ' + remark;
}

var caInput   = document.getElementById('ca_score');
var examInput = document.getElementById('exam_score');
if (caInput)   caInput.addEventListener('input', calcTotal);
if (examInput) examInput.addEventListener('input', calcTotal);

// ----- Print result -----
function printResult() {
    window.print();
}

// ----- Search filter for tables -----
var searchInput = document.getElementById('tableSearch');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        var filter = this.value.toLowerCase();
        var rows = document.querySelectorAll('table tbody tr');
        rows.forEach(function (row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// ----- Photo preview -----
var photoInput = document.getElementById('photo');
if (photoInput) {
    photoInput.addEventListener('change', function () {
        var file = this.files[0];
        var preview = document.getElementById('photoPreview');
        if (file && preview) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
}
