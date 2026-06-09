<div class="bna-inner">
  <div class="bna-item {{ Route::is('admin.dashboard') ? 'active' : '' }}" onclick="location.href='{{ route('admin.dashboard') }}'">
    <div class="bna-icon">📊</div>
    <div class="bna-label">Dashboard</div>
  </div>
  <div class="bna-item {{ Route::is('admin.orders*') ? 'active' : '' }}" onclick="location.href='{{ route('admin.orders') }}'">
    <div class="bna-icon">
      📦@php
        $pendingOrders = \App\Models\Order::whereIn('status', ['Chờ xử lý', 'Chuẩn bị'])->count();
      @endphp
      @if($pendingOrders > 0)
        <span class="bna-dot">{{ $pendingOrders }}</span>
      @endif
    </div>
    <div class="bna-label">Đơn hàng</div>
  </div>
  <div class="bna-item {{ Route::is('admin.products*') ? 'active' : '' }}" onclick="location.href='{{ route('admin.products') }}'">
    <div class="bna-icon">🍎</div>
    <div class="bna-label">Sản phẩm</div>
  </div>
  <div class="bna-item {{ Route::is('admin.customers*') ? 'active' : '' }}" onclick="location.href='{{ route('admin.customers') }}'">
    <div class="bna-icon">👥</div>
    <div class="bna-label">Khách hàng</div>
  </div>
  <div class="bna-item" onclick="toggleSidebar()">
    <div class="bna-icon">☰</div>
    <div class="bna-label">Menu</div>
  </div>
</div>