// js/dashboard.js

// ── Sidebar toggle ──────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

// ── Dropdown toggle in sidebar ──────────────────────
function toggleDrop(id, btn) {
  const ul = document.getElementById(id);
  const isOpen = ul.classList.contains('open');
  // Close all
  document.querySelectorAll('.sidebar-dropdown').forEach(d => d.classList.remove('open'));
  document.querySelectorAll('.dropdown-toggle-btn').forEach(b => b.classList.remove('open-parent'));
  if (!isOpen) {
    ul.classList.add('open');
    btn.classList.add('open-parent');
  }
}

// ── User dropdown menu ──────────────────────────────
function toggleUserMenu() {
  document.getElementById('userMenu').classList.toggle('show');
}
document.addEventListener('click', function(e) {
  const menu = document.getElementById('userMenu');
  if (menu && !e.target.closest('.user-pill') && !e.target.closest('#userMenu')) {
    menu.classList.remove('show');
  }
});

// ── Modal helpers ───────────────────────────────────
function openModal(id) {
  document.getElementById(id).classList.add('show');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('show');
  document.body.style.overflow = '';
}
// Close modal on overlay click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
    document.body.style.overflow = '';
  }
});

// ── Toast notifications ─────────────────────────────
function showToast(message, type = 'success') {
  const existing = document.getElementById('toastContainer');
  if (!existing) {
    const c = document.createElement('div');
    c.id = 'toastContainer';
    c.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:8px';
    document.body.appendChild(c);
  }
  const toast = document.createElement('div');
  const colors = { success:'#2ecc71', danger:'#e74c3c', warning:'#f39c12', info:'#3498db' };
  toast.style.cssText = `background:${colors[type]||colors.info};color:#fff;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:700;box-shadow:0 6px 20px rgba(0,0,0,.2);animation:slideInRight .3s ease;max-width:300px;`;
  toast.innerHTML = `<i class="fa fa-${type==='success'?'check-circle':type==='danger'?'times-circle':'info-circle'}"></i> ${message}`;
  document.getElementById('toastContainer').appendChild(toast);
  setTimeout(() => { toast.style.animation = 'fadeOut .3s ease forwards'; setTimeout(() => toast.remove(), 300); }, 3500);
}

// ── Search filter for tables ────────────────────────
function filterTable(inputId, tableId) {
  const val = document.getElementById(inputId).value.toLowerCase();
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}

// ── Confirm delete ──────────────────────────────────
function confirmDelete(url, message) {
  if (confirm(message || 'Are you sure you want to delete this record? This action cannot be undone.')) {
    window.location.href = url;
  }
}

// ── Auto-dismiss alerts ─────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.alert-auto').forEach(el => {
    setTimeout(() => { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }, 4000);
  });

  // Re-open active dropdown parents on load
  document.querySelectorAll('.sidebar-dropdown.open').forEach(d => {
    const btn = d.previousElementSibling;
    if (btn) btn.classList.add('open-parent');
  });
});

// CSS animations for toast
const style = document.createElement('style');
style.textContent = `
@keyframes slideInRight { from{opacity:0;transform:translateX(60px)} to{opacity:1;transform:translateX(0)} }
@keyframes fadeOut { to{opacity:0;transform:translateX(60px)} }
`;
document.head.appendChild(style);
