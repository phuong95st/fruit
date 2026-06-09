<div class="notif-header">
  <div class="notif-title">🔔 Thông báo</div>
  <button class="modal-close" onclick="toggleNotif()">✕</button>
</div>
<div class="notif-list">
  @php
    $recentOrders = \App\Models\Order::latest()->take(2)->get();
  @endphp
  @foreach($recentOrders as $order)
    <div class="notif-item unread" onclick="location.href='{{ route('admin.orders.detail', $order->id) }}'">
      <div class="ni-title">📦 Đơn hàng {{ $order->status }} #{{ $order->order_code }}</div>
      <div class="ni-desc">{{ $order->customer_name }} vừa đặt đơn {{ number_format($order->total_price, 0, ',', '.') }}đ</div>
      <div class="ni-time">{{ $order->created_at->diffForHumans() }}</div>
    </div>
  @endforeach

  <!-- Cảnh báo hết hàng giả lập nhưng dựa trên sản phẩm thật -->
  @php
    $kiwi = \App\Models\Product::where('code', 'kiwi')->first();
  @endphp
  @if($kiwi)
    <div class="notif-item unread" onclick="location.href='{{ route('admin.products.detail', $kiwi->id) }}'">
      <div class="ni-title">⚠️ {{ $kiwi->name }} sắp hết hàng</div>
      <div class="ni-desc">Chỉ còn 5 túi, dưới mức cảnh báo (20 túi)</div>
      <div class="ni-time">15 phút trước</div>
    </div>
  @endif

  @php
    $recentCustomer = \App\Models\Customer::latest()->first();
  @endphp
  @if($recentCustomer)
    <div class="notif-item" onclick="location.href='{{ route('admin.customers.detail', $recentCustomer->id) }}'">
      <div class="ni-title">👤 Khách hàng mới đăng ký</div>
      <div class="ni-desc">{{ $recentCustomer->name }} — {{ $recentCustomer->email }}</div>
      <div class="ni-time">{{ $recentCustomer->created_at->diffForHumans() }}</div>
    </div>
  @endif
</div>