@extends('admin.layouts.app')

@section('title', 'Danh sách khách hàng')
@section('page_title', 'Khách hàng')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Quản lý</div>
    <h2>Khách hàng</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-ghost btn-sm" onclick="showToast('Đã xuất danh sách','success')">⬇️ Xuất Excel</button>
  </div>
</div>

<div class="stats-grid stats-grid-3">
  <div class="stat-card sc-green">
    <div class="stat-top"><div class="stat-icon si-green">👥</div><div class="stat-trend trend-up">↑ 5.1%</div></div>
    <div class="stat-value">{{ number_format(\App\Models\Customer::count()) }}</div>
    <div class="stat-label">Tổng khách hàng</div>
  </div>
  <div class="stat-card sc-orange">
    <div class="stat-top"><div class="stat-icon si-orange">🔁</div><div class="stat-trend trend-up">↑ 12%</div></div>
    <div class="stat-value">{{ number_format(\App\Models\Customer::where('total_orders', '>', 3)->count()) }}</div>
    <div class="stat-label">Khách mua lại (>3 đơn)</div>
  </div>
  <div class="stat-card sc-blue">
    <div class="stat-top"><div class="stat-icon si-blue">⭐</div></div>
    <div class="stat-value">4.9</div>
    <div class="stat-label">Điểm hài lòng trung bình</div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.customers') }}" class="filter-bar">
      <input type="text" name="search" placeholder="🔍 Tìm tên, SĐT, email..." value="{{ request('search') }}" style="min-width:220px;"/>
      <button type="submit" class="btn btn-ghost btn-sm" style="border-radius:50px;">Tìm kiếm</button>
      <div class="filter-bar-spacer"></div>
    </form>
    
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Khách hàng</th>
            <th>Điện thoại</th>
            <th>Tổng đơn</th>
            <th>Tổng chi tiêu</th>
            <th>Đánh giá</th>
            <th>Phân loại</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @forelse($customers as $customer)
            <tr>
              <td>
                <div class="td-name">
                  <div class="ap-avatar" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
                    {{ mb_substr($customer->name, 0, 1) }}
                  </div>
                  <div>
                    <div style="font-size:.85rem;font-weight:600;">{{ $customer->name }}</div>
                    <div class="text-xs text-muted">{{ $customer->email }}</div>
                  </div>
                </div>
              </td>
              <td><span style="font-size:.82rem;">{{ $customer->phone }}</span></td>
              <td><span style="font-weight:700;">{{ $customer->total_orders }} đơn</span></td>
              <td><span style="font-weight:700;color:var(--orange);">{{ number_format($customer->total_spending, 0, ',', '.') }}đ</span></td>
              <td><span style="color:var(--yellow);">★</span> {{ number_format($customer->rating, 1) }}</td>
              <td>
                @if($customer->level === 'VIP')
                  <span class="badge badge-warning">⭐ VIP</span>
                @else
                  <span class="badge badge-success">Thường</span>
                @endif
              </td>
              <td>
                <div class="td-actions">
                  <button class="action-btn" title="Hồ sơ" onclick="location.href='{{ route('admin.customers.detail', $customer->id) }}'">👁️</button>
                  <button class="action-btn" title="Nhắn tin" onclick="showToast('Tính năng đang phát triển','info')">💬</button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="text-align:center;color:var(--text-3);">Không tìm thấy khách hàng nào.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Custom Pagination -->
    @if($customers->lastPage() > 1)
      <div class="pagination">
        <span class="pg-info">Hiển thị {{ $customers->firstItem() }}–{{ $customers->lastItem() }} / {{ $customers->total() }} khách hàng</span>
        
        @if($customers->onFirstPage())
          <button class="pg-btn" disabled>×</button>
        @else
          <a href="{{ $customers->previousPageUrl() }}" class="pg-btn">←</a>
        @endif
        
        @for($i = 1; $i <= $customers->lastPage(); $i++)
          <a href="{{ $customers->url($i) }}" class="pg-btn {{ $customers->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
        @endfor

        @if($customers->hasMorePages())
          <a href="{{ $customers->nextPageUrl() }}" class="pg-btn">→</a>
        @else
          <button class="pg-btn" disabled>×</button>
        @endif
      </div>
    @endif
  </div>
</div>
@endsection