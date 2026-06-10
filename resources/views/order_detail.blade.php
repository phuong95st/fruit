@extends('layouts.app')

@section('title', 'Chi Tiết Đơn Hàng ' . $order->order_code . ' | Hoa quả Sơn Tây')

@section('content')
<div class="page active" id="page-order-detail">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <a class="bc-link" href="{{ route('page.orders') }}">Tài khoản</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Đơn hàng {{ $order->order_code }}</span>
  </div>
  
  <div class="account-layout">
    <!-- Sidebar Account Info -->
    <aside class="acc-sidebar">
      @php
        $customerName = 'Khách vãng lai';
        $customerContact = 'Chưa đăng nhập';
        if (Auth::check()) {
            $customerName = Auth::user()->name;
            $customerContact = Auth::user()->email;
        } else {
            $customerName = $order->customer_name;
            $customerContact = $order->customer_phone;
        }
      @endphp
      <div class="acc-profile">
        <div class="acc-avatar" style="position: relative; overflow: hidden; width: 48px; height: 48px; border-radius: 50%;">
          @if(Auth::check() && Auth::user()->avatar)
            <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
          @else
            <svg viewBox="0 0 24 24" style="width: 100%; height: 100%;"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          @endif
        </div>
        <div class="acc-name">{{ $customerName }}</div>
        <div class="acc-email">{{ $customerContact }}</div>
      </div>
      <ul class="acc-menu">
        <li>
          <a href="{{ route('page.auth') }}">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
            Bảng điều khiển
          </a>
        </li>
        <li>
          <a class="on" href="{{ route('page.orders') }}">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Đơn hàng của tôi
          </a>
        </li>
        <li>
          <a @if(Auth::check()) href="{{ route('page.profile') }}" @else onclick="alert('Tính năng dành cho thành viên đã đăng nhập')" style="cursor:pointer;" @endif>
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Thông tin cá nhân
          </a>
        </li>
        <li>
          <a onclick="alert('{{ Auth::check() ? 'Tính năng đang được phát triển' : 'Tính năng dành cho thành viên đã đăng nhập' }}')" style="cursor:pointer;">
            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Địa chỉ giao hàng
          </a>
        </li>
        <li>
          <a onclick="alert('{{ Auth::check() ? 'Tính năng đang được phát triển' : 'Tính năng dành cho thành viên đã đăng nhập' }}')" style="cursor:pointer;">
            <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
            Sản phẩm yêu thích
          </a>
        </li>
        @if(Auth::check() && Auth::user()->is_admin)
        <li>
          <a href="{{ route('admin.dashboard') }}" style="color: var(--g700); font-weight: 600;">
            <svg viewBox="0 0 24 24" style="stroke: var(--g700); fill: none;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="21" x2="9" y2="9"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="15" x2="21" y2="15"/></svg>
            Trang quản trị (Admin)
          </a>
        </li>
        @endif
        @if(Auth::check())
          <li>
            <a class="danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('order-detail-logout-form').submit();">
              <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Đăng xuất
            </a>
            <form id="order-detail-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
              @csrf
            </form>
          </li>
        @endif
      </ul>
    </aside>
    
    <!-- Account Content List -->
    <div class="orders-box">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border); padding-bottom:10px;">
        <div class="orders-title" style="margin:0;">Chi tiết đơn hàng</div>
        <a href="{{ route('page.orders') }}" class="btn btn-outline btn-sm">← Lịch sử đơn</a>
      </div>

      <div class="order-info-box" style="margin-bottom:16px;">
        <div class="oib"><div class="oib-label">Mã đơn</div><div class="oib-val">{{ $order->order_code }}</div></div>
        <div class="oib"><div class="oib-label">Ngày đặt</div><div class="oib-val">{{ $order->created_at->format('d/m/Y H:i') }}</div></div>
        <div class="oib"><div class="oib-label">Tổng tiền</div><div class="oib-val red">{{ number_format($order->total_price, 0, ',', '.') }}đ</div></div>
        <div class="oib"><div class="oib-label">Thanh toán</div><div class="oib-val" style="font-size:12px;">{{ $order->payment_method }}</div></div>
      </div>

      <!-- Timeline mapping real status -->
      @php
        $status = $order->status;
        $step = 1;
        if ($status === 'Chuẩn bị' || $status === 'Chuẩn bị hàng') $step = 2;
        elseif ($status === 'Đang giao' || $status === 'Đang giao hàng') $step = 3;
        elseif ($status === 'Hoàn thành' || $status === 'Đã giao') $step = 4;
        elseif ($status === 'Đã hủy' || $status === 'Hoàn hàng') $step = -1;
      @endphp

      @if($step == -1)
        <div style="background:rgba(239,68,68,.06); border:1px solid rgba(239,68,68,.2); border-radius:var(--radius-md); padding:12px; margin-bottom:16px; font-size:var(--fs-sm); color:var(--red); text-align:center;">
          ⚠️ Đơn hàng này ở trạng thái: <b>{{ $status }}</b>. Vui lòng liên hệ hotline 0909 123 456 để được giải quyết khiếu nại.
        </div>
      @else
        <div class="track-list" style="margin-bottom:16px;">
          <div class="track-item">
            <div class="track-dot {{ $step >= 1 ? 'td-done' : 'td-wait' }}">
              @if($step >= 1)<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>@else 1 @endif
            </div>
            <div>
              <div class="track-title">Đơn hàng đã xác nhận</div>
              <div class="track-time">Hệ thống đã nhận đơn hàng</div>
            </div>
          </div>
          <div class="track-item">
            <div class="track-dot {{ $step >= 2 ? 'td-done' : ($step == 1 ? 'td-active' : 'td-wait') }}">
              @if($step >= 2)<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>@else 2 @endif
            </div>
            <div>
              <div class="track-title">Đang chuẩn bị hàng</div>
              <div class="track-time">Đang đóng gói và dán tem xuất kho</div>
            </div>
          </div>
          <div class="track-item">
            <div class="track-dot {{ $step >= 3 ? 'td-done' : ($step == 2 ? 'td-active' : 'td-wait') }}">
              @if($step >= 3)<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>@else 3 @endif
            </div>
            <div>
              <div class="track-title">Đang giao hàng</div>
              <div class="track-time">Shipper đang trên đường vận chuyển</div>
            </div>
          </div>
          <div class="track-item">
            <div class="track-dot {{ $step >= 4 ? 'td-done' : ($step == 3 ? 'td-active' : 'td-wait') }}">
              @if($step >= 4)<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>@else 4 @endif
            </div>
            <div>
              <div class="track-title">Giao thành công</div>
              <div class="track-time">Khách hàng nhận hàng và hoàn thành thanh toán</div>
            </div>
          </div>
        </div>
      @endif

      <!-- Delivery and notes info -->
      <div style="background:var(--n50); border:1px solid var(--n100); border-radius:var(--radius-md); padding:12px; margin-bottom:16px; font-size:var(--fs-sm);">
        <div style="font-weight:700; margin-bottom:5px; color:var(--g900); font-size:13px;">Thông tin nhận hàng:</div>
        <div style="margin-bottom:3px;"><b>Người nhận:</b> {{ $order->customer_name }}</div>
        <div style="margin-bottom:3px;"><b>Số điện thoại:</b> {{ $order->customer_phone }}</div>
        <div style="margin-bottom:3px;"><b>Địa chỉ giao:</b> {{ $order->delivery_address }}</div>
        @if($order->notes)
          <div style="margin-top:6px; padding-top:6px; border-top:1px dashed var(--border);"><b>Ghi chú đơn hàng:</b> {{ $order->notes }}</div>
        @endif
      </div>

      <!-- Items List -->
      <div class="cart-table" style="margin-bottom:0;">
        <div class="cart-table-head" style="grid-template-columns: 1fr 100px 80px 100px;">
          <span>Sản phẩm</span><span>Đơn giá</span><span>Số lượng</span><span>Thành tiền</span>
        </div>
        
        <div style="display:flex; flex-direction:column;">
          @foreach($order->items as $item)
            <div class="cart-item" style="grid-template-columns: 1fr 100px 80px 100px; align-items:center;">
              <div style="display:flex; align-items:center; gap:8px;">
                <div class="ci-img-box {{ $item->product->bg ?? 'bg-g' }}" style="width:48px; height:48px;">
                  @if($item->product && $item->product->image_url)
                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">
                  @elseif($item->product && $item->product->svg)
                    <div class="fruit-ico {{ $item->product->ic }}"><svg viewBox="0 0 24 24" style="width:20px; height:20px;">{!! $item->product->svg !!}</svg></div>
                  @else
                    🍊
                  @endif
                </div>
                <div class="ci-info">
                  <div class="ci-name" style="font-size:12px;">{{ $item->product_name }}</div>
                  @if($item->product)
                    <div class="ci-sub" style="font-size:10px;">{{ $item->product->pack }}</div>
                  @endif
                </div>
              </div>
              
              <div class="ci-mob-row" style="display:contents;">
                <span style="font-size:var(--fs-sm); color:var(--n700);">{{ number_format($item->unit_price, 0, ',', '.') }}đ</span>
                <span style="font-size:var(--fs-sm); font-weight:600; text-align:center;">{{ $item->quantity }}</span>
                <span class="ci-price" style="font-size:13px;">{{ number_format($item->subtotal, 0, ',', '.') }}đ</span>
              </div>
            </div>
          @endforeach
        </div>
      </div>
      
    </div>
  </div>
</div>
</div>
@endsection
