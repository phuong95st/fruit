@extends('layouts.app')

@section('title', 'Đặt Hàng Thành Công | Hoa quả Sơn Tây')

@section('content')
<div class="page active" id="page-success">
<div class="success-wrap">
  <div class="success-card">
    <div class="success-icon"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
    <div class="success-title">Đặt hàng thành công!</div>
    <p class="success-sub">Cảm ơn bạn đã mua sắm tại Hoa quả Sơn Tây. Chúng tôi sẽ liên hệ xác nhận trong 15 phút.</p>
    
    <div class="order-info-box">
      <div class="oib"><div class="oib-label">Mã đơn</div><div class="oib-val">{{ $order['id'] }}</div></div>
      <div class="oib"><div class="oib-label">Ngày đặt</div><div class="oib-val">{{ explode(' ', $order['date'])[0] }}</div></div>
      <div class="oib"><div class="oib-label">Tổng tiền</div><div class="oib-val red">{{ number_format($order['total'], 0, ',', '.') }}đ</div></div>
    </div>
    
    <div style="background:var(--n50); border:1px solid var(--n100); border-radius:var(--radius-md); padding:10px; margin-bottom:14px; text-align:left; font-size:var(--fs-sm);">
        <div style="font-weight:700; margin-bottom:5px; color:var(--g900);">Thông tin người nhận:</div>
        <div><b>Họ tên:</b> {{ $order['fullname'] }}</div>
        <div><b>Điện thoại:</b> {{ $order['phone'] }}</div>
        <div><b>Địa chỉ:</b> {{ $order['address'] }}</div>
    </div>
    
    <!-- Delivery tracking timeline -->
    <div class="track-list">
      <div class="track-item">
        <div class="track-dot td-done"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div>
          <div class="track-title">Đơn hàng đã xác nhận</div>
          <div class="track-time">{{ $order['date'] }}</div>
        </div>
      </div>
      <div class="track-item">
        <div class="track-dot td-active">2</div>
        <div>
          <div class="track-title">Đang chuẩn bị hàng</div>
          <div class="track-time">Dự kiến hoàn tất sau 30 phút</div>
        </div>
      </div>
      <div class="track-item">
        <div class="track-dot td-wait">3</div>
        <div>
          <div class="track-title">Đang giao hàng</div>
          <div class="track-time">Shipper liên hệ trước khi tới</div>
        </div>
      </div>
      <div class="track-item">
        <div class="track-dot td-wait">4</div>
        <div>
          <div class="track-title">Giao hàng thành công</div>
          <div class="track-time">Dự kiến giao trong 2 giờ</div>
        </div>
      </div>
    </div>
    
    <div class="success-btns">
      <a class="btn btn-primary" href="{{ isset($order['db_id']) ? route('page.orders.detail', $order['db_id']) : route('page.orders') }}">Theo dõi đơn</a>
      <a class="btn btn-outline" href="{{ route('home') }}">Mua tiếp</a>
    </div>
  </div>
</div>
</div>
@endsection
