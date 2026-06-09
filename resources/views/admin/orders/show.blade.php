@extends('admin.layouts.app')

@section('title', 'Đơn hàng #' . $order->order_code)
@section('page_title', 'Đơn hàng / ' . $order->order_code)

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow" id="od-breadcrumb">Đơn hàng / Chi tiết</div>
    <h2 id="od-title">#{{ $order->order_code }}</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.orders') }}'">← Quay lại</button>
    <button class="btn btn-ghost btn-sm" onclick="showToast('Đang in đơn hàng...','info')">🖨️ In đơn</button>
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-update-status')">✏️ Cập nhật</button>
  </div>
</div>

<div class="od-layout">
  <!-- LEFT COL -->
  <div class="od-main">
    <!-- Order items -->
    <div class="card mb-16">
      <div class="card-header">
        <div class="card-title">🛒 Sản phẩm đặt hàng</div>
        @php
          $statusBadge = 'badge-warning';
          if ($order->status === 'Hoàn thành') $statusBadge = 'badge-success';
          if ($order->status === 'Đang giao') $statusBadge = 'badge-info';
          if ($order->status === 'Hoàn hàng') $statusBadge = 'badge-danger';
          if ($order->status === 'Đã hủy') $statusBadge = 'badge-gray';
        @endphp
        <span class="badge {{ $statusBadge }}" id="od-status-badge">⏳ {{ $order->status }}</span>
      </div>
      <div class="card-body">
        @php
          $subtotalSum = 0;
        @endphp
        @foreach($order->items as $item)
          @php
            $subtotalSum += $item->subtotal;
            // Xác định icon/màu nền tượng trưng cho sản phẩm
            $bg = 'bg-g';
            $icon = '🍎';
            if ($item->product) {
                $bg = $item->product->bg;
                $icon = strip_tags($item->product->svg) ? '{!! $item->product->svg !!}' : '🍎';
            }
          @endphp
          <div class="od-item-row">
            <div class="od-item-img {{ $bg }}" style="width:48px;height:48px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
              @if($item->product && $item->product->svg)
                <div style="width:24px;height:24px;display:inline-block;">{!! $item->product->svg !!}</div>
              @else
                🍇
              @endif
            </div>
            <div class="od-item-info">
              <div class="od-item-name">{{ $item->product_name }}</div>
              <div class="od-item-sub">Đơn giá: {{ number_format($item->unit_price, 0, ',', '.') }}đ</div>
            </div>
            <div class="od-item-right">
              <div class="od-item-qty">× {{ $item->quantity }}</div>
              <div class="od-item-price">{{ number_format($item->subtotal, 0, ',', '.') }}đ</div>
            </div>
          </div>
        @endforeach

        <div class="od-total-block">
          <div class="od-total-row"><span>Tạm tính</span><span>{{ number_format($subtotalSum, 0, ',', '.') }}đ</span></div>
          <div class="od-total-row"><span>Phí giao hàng</span><span style="color:var(--g-mid)">Miễn phí</span></div>
          @if($subtotalSum > $order->total_price)
            <div class="od-total-row">
              <span>Giảm giá</span>
              <span style="color:var(--g-mid)">–{{ number_format($subtotalSum - $order->total_price, 0, ',', '.') }}đ</span>
            </div>
          @endif
          <div class="od-total-row grand"><span>Tổng cộng</span><span style="color:var(--orange); font-weight:700;">{{ number_format($order->total_price, 0, ',', '.') }}đ</span></div>
        </div>
      </div>
    </div>

    <!-- Timeline -->
    <div class="card mb-16">
      <div class="card-header"><div class="card-title">⏱️ Lịch sử trạng thái</div></div>
      <div class="card-body" style="padding-top:8px;">
        <div class="timeline">
          <div class="tl-item done">
            <div class="tl-dot-wrap"><div class="tl-dot">✓</div><div class="tl-line"></div></div>
            <div class="tl-content"><div class="tl-title">Đơn hàng đã đặt</div><div class="tl-time">{{ $order->created_at->format('H:i d/m/Y') }}</div><div class="tl-desc">Khách hàng xác nhận qua web</div></div>
          </div>
          @if($order->payment_method === 'ATM' || $order->status === 'Hoàn thành')
            <div class="tl-item done">
              <div class="tl-dot-wrap"><div class="tl-dot">✓</div><div class="tl-line"></div></div>
              <div class="tl-content"><div class="tl-title">Thanh toán thành công</div><div class="tl-time">{{ $order->created_at->format('H:i d/m/Y') }}</div><div class="tl-desc">{{ $order->payment_method }} · Giao dịch thành công</div></div>
            </div>
          @endif
          <div class="tl-item {{ $order->status === 'Chuẩn bị' ? 'active' : 'done' }}">
            <div class="tl-dot-wrap"><div class="tl-dot">✓</div><div class="tl-line"></div></div>
            <div class="tl-content"><div class="tl-title">Chuẩn bị hàng</div><div class="tl-desc">Kho hàng chuẩn bị sản phẩm</div></div>
          </div>
          <div class="tl-item {{ $order->status === 'Đang giao' ? 'active' : ($order->status === 'Hoàn thành' ? 'done' : 'pending') }}">
            <div class="tl-dot-wrap"><div class="tl-dot">→</div><div class="tl-line"></div></div>
            <div class="tl-content"><div class="tl-title">Giao cho shipper</div><div class="tl-desc">Đang vận chuyển</div></div>
          </div>
          <div class="tl-item {{ $order->status === 'Hoàn thành' ? 'done' : 'pending' }}">
            <div class="tl-dot-wrap"><div class="tl-dot">✓</div></div>
            <div class="tl-content"><div class="tl-title">Giao thành công</div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Note -->
    <div class="card">
      <div class="card-header"><div class="card-title">📝 Ghi chú khách hàng</div><button class="btn btn-ghost btn-sm" onclick="showToast('Đã lưu ghi chú','success')">💾 Lưu</button></div>
      <div class="card-body">
        <textarea class="w-full" style="border:1.5px solid var(--border);border-radius:var(--r-md);padding:10px 12px;font-size:.85rem;outline:none;resize:vertical;min-height:80px;font-family:'DM Sans',sans-serif;" placeholder="Ghi chú về đơn này...">{{ $order->notes ?? 'Khách không để lại ghi chú.' }}</textarea>
      </div>
    </div>
  </div>

  <!-- RIGHT COL -->
  <div class="od-side">
    <!-- Customer info -->
    <div class="card mb-12">
      <div class="card-header">
        <div class="card-title">👤 Khách hàng</div>
        @if($order->customer)
          <button class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.customers.detail', $order->customer->id) }}'">Xem hồ sơ</button>
        @endif
      </div>
      <div class="card-body">
        <div class="flex items-center gap-12 mb-12" style="gap:10px;margin-bottom:12px;">
          <div class="ap-avatar" style="width:42px;height:42px;font-size:1.1rem;">
            {{ mb_substr($order->customer_name, 0, 1) }}
          </div>
          <div>
            <div style="font-weight:700;font-size:.9rem;">{{ $order->customer_name }}</div>
            <div style="font-size:.75rem;color:var(--text-3);">
              @if($order->customer)
                {{ $order->customer->level }} · Điểm: {{ $order->customer->rating }}★
              @else
                Khách vãng lai
              @endif
            </div>
          </div>
        </div>
        <div class="od-info-row"><span>📞</span><span>{{ $order->customer_phone }}</span></div>
        <div class="od-info-row"><span>✉️</span><span>{{ $order->customer->email ?? 'N/A' }}</span></div>
      </div>
    </div>

    <!-- Delivery info -->
    <div class="card mb-12">
      <div class="card-header"><div class="card-title">🚚 Giao hàng</div></div>
      <div class="card-body">
        <div class="od-info-row"><span>📍</span><span>{{ $order->delivery_address }}</span></div>
        <div class="od-info-row"><span>🛵</span><span>Giao hàng tiêu chuẩn</span></div>
        <button class="btn btn-ghost btn-sm w-full mt-8" style="margin-top:10px;" onclick="showToast('Đã sao chép địa chỉ','info')">📋 Sao chép địa chỉ</button>
      </div>
    </div>

    <!-- Payment -->
    <div class="card mb-12">
      <div class="card-header"><div class="card-title">💳 Thanh toán</div></div>
      <div class="card-body">
        <div class="od-info-row"><span>Phương thức</span><span class="badge badge-success">{{ $order->payment_method }}</span></div>
        <div class="od-info-row"><span>Trạng thái</span>
          @if($order->status === 'Hoàn thành' || $order->payment_method === 'ATM')
            <span class="badge badge-success">✅ Đã thanh toán</span>
          @else
            <span class="badge badge-warning">⏳ Chờ thu hộ</span>
          @endif
        </div>
        <div class="od-info-row grand"><span>Tổng thu</span><span style="color:var(--orange);font-weight:700;">{{ number_format($order->total_price, 0, ',', '.') }}đ</span></div>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="card">
      <div class="card-header"><div class="card-title">⚡ Thao tác nhanh</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px;">
        <button class="btn btn-primary w-full" onclick="openModal('modal-update-status')">🔄 Cập nhật trạng thái</button>
        <button class="btn btn-ghost w-full" onclick="showToast('Đang in đơn hàng...','info')">🖨️ In đơn hàng</button>
        <button class="btn btn-ghost w-full" onclick="showToast('Đã gửi SMS cho khách','success')">📱 Gửi SMS khách hàng</button>
        <button class="btn btn-danger w-full" onclick="openModal('modal-cancel-order')">✕ Hủy đơn hàng</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('modals')
<div class="modal-overlay" id="modal-update-status">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🔄 Cập nhật trạng thái đơn hàng</div>
      <button class="modal-close" onclick="closeModal('modal-update-status')">✕</button>
    </div>
    <form method="GET" action="{{ route('admin.orders') }}">
      <div class="fg mb-12"><label>Trạng thái mới *</label>
        <select name="new_status" style="width:100%; padding:10px; border-radius:6px; border:1.5px solid var(--border);">
          <option value="Chờ xử lý" {{ $order->status === 'Chờ xử lý' ? 'selected' : '' }}>⏳ Chờ xử lý</option>
          <option value="Chuẩn bị" {{ $order->status === 'Chuẩn bị' ? 'selected' : '' }}>📦 Đang chuẩn bị</option>
          <option value="Đang giao" {{ $order->status === 'Đang giao' ? 'selected' : '' }}>🚚 Đang giao hàng</option>
          <option value="Hoàn thành" {{ $order->status === 'Hoàn thành' ? 'selected' : '' }}>✅ Giao thành công</option>
          <option value="Hoàn hàng" {{ $order->status === 'Hoàn hàng' ? 'selected' : '' }}>↩️ Hoàn hàng</option>
          <option value="Đã hủy" {{ $order->status === 'Đã hủy' ? 'selected' : '' }}>✕ Đã hủy</option>
        </select>
      </div>
      <div class="fg mb-12"><label>Người thực hiện</label><input value="Admin" readonly style="background:var(--bg); width:95%;"/></div>
      <div class="fg"><label>Ghi chú (tùy chọn)</label><textarea placeholder="Lý do thay đổi trạng thái..." style="width:95%; height:60px;"></textarea></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-update-status')">Hủy</button>
        <button type="button" class="btn btn-primary" onclick="closeModal('modal-update-status');showToast('✅ Đã cập nhật trạng thái đơn','success')">Xác nhận</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modal-cancel-order">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">⚠️ Hủy đơn hàng</div>
      <button class="modal-close" onclick="closeModal('modal-cancel-order')">✕</button>
    </div>
    <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:var(--r-md);padding:12px;margin-bottom:16px;font-size:.82rem;color:var(--red);">
      ⚠️ Đơn hàng <strong>#{{ $order->order_code }}</strong> sẽ bị hủy và không thể khôi phục.
    </div>
    <div class="fg mb-12"><label>Lý do hủy *</label>
      <select style="width:100%; padding:10px; border-radius:6px; border:1.5px solid var(--border);">
        <option>Khách hàng yêu cầu hủy</option>
        <option>Hết hàng trong kho</option>
        <option>Không liên lạc được khách</option>
        <option>Lý do khác</option>
      </select>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modal-cancel-order')">Không hủy</button>
      <button type="button" class="btn btn-danger" onclick="closeModal('modal-cancel-order');location.href='{{ route('admin.orders') }}';showToast('✕ Đã hủy đơn hàng','error')">Xác nhận hủy đơn</button>
    </div>
  </div>
</div>
@endsection