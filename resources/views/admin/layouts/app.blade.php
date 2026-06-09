<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>@yield('title', 'Hoa quả Sơn Tây Admin') — Quản trị viên</title>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
@stack('styles')
</head>
<body>

<!-- Sidebar overlay for mobile tap-to-close -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="admin-layout">

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar" id="sidebar">
  @include('admin.layouts.sidebar')
</aside>

<!-- ========== MAIN AREA ========== -->
<div class="main-area">

  <!-- TOPBAR -->
  <header class="topbar">
    @include('admin.layouts.topbar')
  </header>

  <!-- CONTENT -->
  <div class="content-area">
    @yield('content')
  </div><!-- end content-area -->
</div><!-- end main-area -->
</div><!-- end admin-layout -->

<!-- ========== NOTIFICATION PANEL ========== -->
<div class="notif-panel" id="notifPanel">
  @include('admin.layouts.notifications')
</div>

<!-- ========== MODALS ========== -->
@yield('modals')

<!-- ========== BOTTOM NAV (mobile) ========== -->
<nav class="bottom-nav-admin" id="bottomNavAdmin">
  @include('admin.layouts.bottom_nav')
</nav>

<!-- ========== TOAST STACK ========== -->
<div class="toast-stack" id="toastStack"></div>

<!-- ========== SCRIPTS ========== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
  var charts = {};
  function destroyChart(id) {
    if (charts[id]) { charts[id].destroy(); delete charts[id]; }
  }

  function toggleSidebar() {
    var sb = document.getElementById('sidebar');
    var ov = document.getElementById('sidebarOverlay');
    if (sb) sb.classList.toggle('open');
    if (ov) ov.classList.toggle('open');
  }

  function toggleNotif() {
    var p = document.getElementById('notifPanel');
    if (p) p.classList.toggle('open');
  }

  function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('open');
  }
  
  function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('open');
  }
  
  document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('modal-overlay')) {
      e.target.classList.remove('open');
    }
  });

  function showToast(msg, type) {
    type = type || 'success';
    var stack = document.getElementById('toastStack');
    if (!stack) return;
    var el = document.createElement('div');
    el.className = 'toast-item ' + type;
    el.textContent = msg;
    stack.appendChild(el);
    setTimeout(function() { el.style.opacity = '0'; }, 2200);
    setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 2600);
  }

  function updateDate() {
    var el = document.getElementById('topbar-date');
    if (!el) return;
    var d    = new Date();
    var opts = { weekday:'long', day:'2-digit', month:'2-digit', year:'numeric' };
    el.textContent = d.toLocaleDateString('vi-VN', opts);
  }
  updateDate();
</script>
@stack('scripts')
</body>
</html>
