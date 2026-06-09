<div class="topbar-left">
  <button class="hamburger-admin" onclick="toggleSidebar()">
    <span></span><span></span><span></span>
  </button>
  <div class="page-title" id="topbar-title">@yield('page_title', 'Dashboard')</div>
</div>
<div class="topbar-search">
  <span class="ts-icon">🔍</span>
  <input type="text" placeholder="Tìm đơn hàng, sản phẩm, khách hàng..."/>
</div>
<div class="topbar-right">
  <div class="topbar-date" id="topbar-date"></div>
  <button class="tb-icon-btn" onclick="toggleNotif()" title="Thông báo">
    🔔<span class="tb-badge">5</span>
  </button>
  <button class="tb-icon-btn" title="Cài đặt" onclick="location.href='{{ route('admin.settings') }}'">⚙️</button>
</div>