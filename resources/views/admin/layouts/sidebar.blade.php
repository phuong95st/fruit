<div class="sidebar-logo" onclick="location.href='{{ route('admin.dashboard') }}'" style="display: flex; align-items: center; gap: 8px;">
  <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 32px; width: auto; border-radius: 4px;">
  <div>
    <div class="logo-text">Sơn Tây <span class="logo-badge">Admin</span></div>
  </div>
</div>

<div class="sidebar-section">
  <div class="sidebar-section-label">Tổng quan</div>
  <div class="nav-item {{ Route::is('admin.dashboard') ? 'active' : '' }}" onclick="location.href='{{ route('admin.dashboard') }}'">
    <span class="ni-icon">📊</span> Dashboard
  </div>
  <div class="nav-item {{ Route::is('admin.analytics') ? 'active' : '' }}" onclick="location.href='{{ route('admin.analytics') }}'">
    <span class="ni-icon">📈</span> Phân tích
  </div>
</div>

<div class="sidebar-section">
  <div class="sidebar-section-label">Quản lý</div>
  <div class="nav-item {{ Route::is('admin.orders*') ? 'active' : '' }}" onclick="location.href='{{ route('admin.orders') }}'">
    <span class="ni-icon">📦</span> Đơn hàng
    @php
      $pendingOrdersCount = \App\Models\Order::whereIn('status', ['Chờ xử lý', 'Chuẩn bị'])->count();
    @endphp
    @if($pendingOrdersCount > 0)
      <span class="ni-badge">{{ $pendingOrdersCount }}</span>
    @endif
  </div>
  <div class="nav-item {{ Route::is('admin.products*') ? 'active' : '' }}" onclick="location.href='{{ route('admin.products') }}'">
    <span class="ni-icon">🍎</span> Sản phẩm
  </div>
  <div class="nav-item {{ Route::is('admin.inventory*') ? 'active' : '' }}" onclick="location.href='{{ route('admin.inventory') }}'">
    <span class="ni-icon">🏪</span> Kho hàng
    @php
      // Cảnh báo hết hàng hoặc sắp hết hàng
      $lowStockCount = \App\Models\Product::where('sold_count', '>', 2000)->count(); // Mock alert hoặc logic tương tự
      // Để khớp dữ liệu gốc ta hiển thị 3 hoặc số lượng thực tế
      $lowStockCount = 3;
    @endphp
    <span class="ni-badge red">{{ $lowStockCount }}</span>
  </div>
  <div class="nav-item {{ Route::is('admin.customers*') ? 'active' : '' }}" onclick="location.href='{{ route('admin.customers') }}'">
    <span class="ni-icon">👥</span> Khách hàng
  </div>
  <div class="nav-item {{ Route::is('admin.vouchers*') ? 'active' : '' }}" onclick="location.href='{{ route('admin.vouchers') }}'">
    <span class="ni-icon">🎫</span> Khuyến mãi
  </div>
</div>

<div class="sidebar-section">
  <div class="sidebar-section-label">Cài đặt</div>
  <div class="nav-item {{ Route::is('admin.settings') ? 'active' : '' }}" onclick="location.href='{{ route('admin.settings') }}'">
    <span class="ni-icon">⚙️</span> Cài đặt hệ thống
  </div>
  <div class="nav-item" onclick="showToast('Tính năng đang phát triển','info')">
    <span class="ni-icon">🔔</span> Thông báo <span class="ni-badge blue">5</span>
  </div>
</div>

<div class="sidebar-bottom">
  <div class="admin-profile">
    <div class="ap-avatar">A</div>
    <div>
      <div class="ap-name">Admin</div>
      <div class="ap-role">Quản trị viên</div>
    </div>
    <div class="ap-more">⋯</div>
  </div>
</div>