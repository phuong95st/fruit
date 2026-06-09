@extends('admin.layouts.app')

@section('title', 'Đơn hàng')
@section('page_title', 'Đơn hàng')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Quản lý</div>
    <h2>Đơn hàng</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-ghost btn-sm" onclick="showToast('Đã xuất danh sách Excel','success')">⬇️ Xuất Excel</button>
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-order')">+ Tạo đơn thủ công</button>
  </div>
</div>

<div class="mini-grid mb-16">
  <div class="mini-card"><div class="mc-icon">⏳</div><div><div class="mc-val">{{ \App\Models\Order::where('status', 'Chờ xử lý')->count() }}</div><div class="mc-label">Chờ xử lý</div></div></div>
  <div class="mini-card"><div class="mc-icon">📦</div><div><div class="mc-val">{{ \App\Models\Order::where('status', 'Chuẩn bị')->count() }}</div><div class="mc-label">Chuẩn bị</div></div></div>
  <div class="mini-card"><div class="mc-icon">🚚</div><div><div class="mc-val">{{ \App\Models\Order::where('status', 'Đang giao')->count() }}</div><div class="mc-label">Đang giao</div></div></div>
  <div class="mini-card"><div class="mc-icon">✅</div><div><div class="mc-val">{{ \App\Models\Order::where('status', 'Hoàn thành')->count() }}</div><div class="mc-label">Hoàn thành</div></div></div>
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.orders') }}" class="filter-bar">
      <input type="text" name="search" placeholder="🔍 Tìm mã đơn, tên KH..." value="{{ request('search') }}" style="min-width:220px;"/>
      <select name="status" onchange="this.form.submit()">
        <option value="Tất cả trạng thái" {{ request('status') === 'Tất cả trạng thái' ? 'selected' : '' }}>Tất cả trạng thái</option>
        <option value="Chờ xử lý" {{ request('status') === 'Chờ xử lý' ? 'selected' : '' }}>Chờ xử lý</option>
        <option value="Chuẩn bị" {{ request('status') === 'Chuẩn bị' ? 'selected' : '' }}>Đang chuẩn bị</option>
        <option value="Đang giao" {{ request('status') === 'Đang giao' ? 'selected' : '' }}>Đang giao</option>
        <option value="Hoàn thành" {{ request('status') === 'Hoàn thành' ? 'selected' : '' }}>Hoàn thành</option>
        <option value="Hoàn hàng" {{ request('status') === 'Hoàn hàng' ? 'selected' : '' }}>Hoàn hàng</option>
        <option value="Đã hủy" {{ request('status') === 'Đã hủy' ? 'selected' : '' }}>Đã hủy</option>
      </select>
      <button type="submit" class="btn btn-ghost btn-sm" style="border-radius:50px;">Lọc</button>
      <div class="filter-bar-spacer"></div>
      <button type="button" class="btn-icon-square" onclick="location.href='{{ route('admin.orders') }}'">🔄</button>
    </form>
    
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox" onclick="toggleAllCheck(this)"/></th>
            <th>Mã đơn</th>
            <th>Khách hàng</th>
            <th>Sản phẩm</th>
            <th>Tổng tiền</th>
            <th>Thanh toán</th>
            <th>Trạng thái</th>
            <th>Thời gian</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $order)
            <tr>
              <td><input type="checkbox"/></td>
              <td><span class="font-bold" style="color:var(--g-dark);">#{{ $order->order_code }}</span></td>
              <td>
                <div class="td-name">
                  <div class="ap-avatar" style="width:32px;height:32px;font-size:.8rem;">
                    {{ mb_substr($order->customer_name, 0, 1) }}
                  </div>
                  <div>
                    <div style="font-size:.82rem;font-weight:600;">{{ $order->customer_name }}</div>
                    <div class="text-xs text-muted">{{ $order->customer_phone }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-size:.8rem;">
                  @if($order->items->count() > 0)
                    {{ implode(', ', $order->items->take(2)->map(fn($item) => mb_substr($item->product_name, 0, 8) . ' × ' . $item->quantity)->toArray()) }}
                    @if($order->items->count() > 2) ... @endif
                  @else
                    Không có sản phẩm
                  @endif
                </div>
              </td>
              <td><span style="font-weight:700;color:var(--orange);">{{ number_format($order->total_price, 0, ',', '.') }}đ</span></td>
              <td>
                <span class="badge {{ $order->payment_method === 'ATM' ? 'badge-success' : 'badge-gray' }}">
                  {{ $order->payment_method }}
                </span>
              </td>
              <td>
                @php
                  $statusBadge = 'badge-warning';
                  if ($order->status === 'Hoàn thành') $statusBadge = 'badge-success';
                  if ($order->status === 'Đang giao') $statusBadge = 'badge-info';
                  if ($order->status === 'Hoàn hàng') $statusBadge = 'badge-danger';
                  if ($order->status === 'Đã hủy') $statusBadge = 'badge-gray';
                @endphp
                <span class="badge {{ $statusBadge }}">● {{ $order->status }}</span>
              </td>
              <td><div style="font-size:.78rem;color:var(--text-3);">{{ $order->created_at->format('H:i d/m/Y') }}</div></td>
              <td>
                <div class="td-actions">
                  <button class="action-btn" title="Xem chi tiết" onclick="location.href='{{ route('admin.orders.detail', $order->id) }}'">👁️</button>
                  <button class="action-btn" title="Cập nhật" onclick="openModal('modal-update-status')">✏️</button>
                  <button class="action-btn" title="In đơn" onclick="showToast('Đang in đơn hàng','info')">🖨️</button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" style="text-align: center; color: var(--text-3);">Không tìm thấy đơn hàng nào.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Custom Pagination -->
    @if($orders->lastPage() > 1)
      <div class="pagination">
        <span class="pg-info">Hiển thị {{ $orders->firstItem() }}–{{ $orders->lastItem() }} / {{ $orders->total() }} đơn</span>
        
        @if($orders->onFirstPage())
          <button class="pg-btn" disabled>×</button>
        @else
          <a href="{{ $orders->previousPageUrl() }}" class="pg-btn">←</a>
        @endif
        
        @for($i = 1; $i <= $orders->lastPage(); $i++)
          <a href="{{ $orders->url($i) }}" class="pg-btn {{ $orders->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
        @endfor

        @if($orders->hasMorePages())
          <a href="{{ $orders->nextPageUrl() }}" class="pg-btn">→</a>
        @else
          <button class="pg-btn" disabled>×</button>
        @endif
      </div>
    @endif
  </div>
</div>
@endsection

@section('modals')
<!-- Modals từ theme gốc -->
<div class="modal-overlay" id="modal-order">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">➕ Tạo đơn hàng thủ công</div>
      <button class="modal-close" onclick="closeModal('modal-order')">✕</button>
    </div>
    <div class="form-grid">
      <div class="fg"><label>Khách hàng *</label><input placeholder="Tên hoặc SĐT khách..."/></div>
      <div class="fg"><label>Số điện thoại *</label><input placeholder="0901 234 567"/></div>
      <div class="fg form-full"><label>Địa chỉ giao hàng *</label><input placeholder="Số nhà, đường, quận..."/></div>
      <div class="fg form-full"><label>Sản phẩm</label><select><option>🍊 Cam Navel Úc — 65.000đ/kg</option><option>🍓 Dâu tây Đà Lạt — 85.000đ/hộp</option><option>🥭 Xoài cát Thái — 45.000đ/kg</option></select></div>
      <div class="fg"><label>Số lượng</label><input type="number" value="1"/></div>
      <div class="fg"><label>Thanh toán</label><select><option>COD</option><option>ATM</option></select></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-order')">Hủy</button>
      <button class="btn btn-primary" onclick="closeModal('modal-order');showToast('✅ Đã tạo đơn hàng','success')">Tạo đơn</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-update-status">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🔄 Cập nhật trạng thái đơn hàng</div>
      <button class="modal-close" onclick="closeModal('modal-update-status')">✕</button>
    </div>
    <div class="fg mb-12"><label>Trạng thái mới *</label>
      <select>
        <option>⏳ Chờ xử lý</option>
        <option selected>📦 Đang chuẩn bị</option>
        <option>🚚 Đang giao hàng</option>
        <option>✅ Giao thành công</option>
        <option>↩️ Hoàn hàng</option>
        <option>✕ Đã hủy</option>
      </select>
    </div>
    <div class="fg mb-12"><label>Người thực hiện</label><input value="Admin" readonly style="background:var(--bg);"/></div>
    <div class="fg"><label>Ghi chú (tùy chọn)</label><textarea placeholder="Lý do thay đổi trạng thái..."></textarea></div>
    <div style="display:flex;align-items:center;gap:8px;margin-top:12px;padding:10px 12px;background:var(--bg);border-radius:var(--r-md);">
      <input type="checkbox" id="notify-customer" checked/>
      <label for="notify-customer" style="font-size:.82rem;cursor:pointer;">Gửi SMS thông báo cho khách hàng</label>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-update-status')">Hủy</button>
      <button class="btn btn-primary" onclick="closeModal('modal-update-status');showToast('✅ Đã cập nhật trạng thái đơn','success')">Xác nhận</button>
    </div>
  </div>
</div>
@endsection