@extends('layouts.app')

@section('title', 'Đơn Hàng Của Tôi | Hoa quả Sơn Tây')

@section('content')
<div class="page active" id="page-orders">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Tài khoản</span>
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
        } elseif (session('last_order')) {
            $lastOrder = session('last_order');
            $customerName = $lastOrder['fullname'];
            $customerContact = $lastOrder['phone'];
        } elseif ($orders->isNotEmpty()) {
            $firstOrder = $orders->first();
            $customerName = $firstOrder->customer_name;
            $customerContact = $firstOrder->customer_phone;
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
            <a class="danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('orders-logout-form').submit();">
              <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Đăng xuất
            </a>
            <form id="orders-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
              @csrf
            </form>
          </li>
        @endif
      </ul>
    </aside>
    
    <!-- Account Content List -->
    <div class="orders-box">
      @if(!Auth::check())
        <div style="background:var(--n50); border:1px solid var(--border); border-radius:var(--radius-md); padding:15px; margin-bottom:15px;">
          <h4 style="font-family:'Merriweather',serif; font-size:var(--fs-md); color:var(--g900); margin-bottom:6px;">Tra cứu đơn hàng khách vãng lai</h4>
          <p style="font-size:var(--fs-xs); color:var(--n700); margin-bottom:10px;">Vui lòng nhập số điện thoại để tra cứu lịch sử mua hàng của bạn:</p>
          <form action="{{ route('page.orders') }}" method="GET" style="display:flex; gap:8px;">
            <input class="form-input" name="lookup_phone" value="{{ request('lookup_phone') }}" placeholder="Ví dụ: 0909 123 456" required style="flex:1;"/>
            <button type="submit" class="btn btn-primary btn-sm">Tra cứu</button>
          </form>
        </div>
      @endif

      <div class="orders-title">Đơn hàng của tôi</div>
      
      <!-- Order Tabs -->
      <div class="ord-tabs">
        <div class="ord-tab on" onclick="filterOrders(this, 'all')">Tất cả</div>
        <div class="ord-tab" onclick="filterOrders(this, 'pending')">Chờ xử lý</div>
        <div class="ord-tab" onclick="filterOrders(this, 'shipping')">Đang giao</div>
        <div class="ord-tab" onclick="filterOrders(this, 'done')">Hoàn thành</div>
      </div>
      
      <div id="orders-list-container">
        @if($orders->isEmpty())
          <div style="text-align: center; padding: 40px 20px; color: var(--n500);">
            <svg viewBox="0 0 24 24" style="width: 48px; height: 48px; stroke: currentColor; fill: none; stroke-width: 1.5; margin-bottom: 12px; color: var(--n300); display: inline-block;">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
            <p style="font-size: var(--fs-md); font-weight: 600; margin-bottom: 8px;">Bạn chưa có đơn hàng nào</p>
            <p style="font-size: var(--fs-sm); color: var(--n400); margin-bottom: 16px;">Hãy khám phá những mặt hàng tươi ngon của cửa hàng nhé.</p>
            <a href="{{ route('shop.index') }}" class="btn btn-primary btn-sm">Mua sắm ngay</a>
          </div>
        @else
          @foreach($orders as $order)
            @php
              $status = $order->status;
              $statusClass = 'sp-pend';
              if ($status === 'Chờ xử lý') {
                  $statusClass = 'sp-pend';
              } elseif ($status === 'Chuẩn bị' || $status === 'Chuẩn bị hàng') {
                  $statusClass = 'sp-proc';
              } elseif ($status === 'Đang giao' || $status === 'Đang giao hàng') {
                  $statusClass = 'sp-ship';
              } elseif ($status === 'Hoàn thành' || $status === 'Đã giao') {
                  $statusClass = 'sp-done';
              } elseif ($status === 'Đã hủy' || $status === 'Hoàn hàng') {
                  $statusClass = 'sp-cancel';
              }

              $dataStatus = 'pending';
              if ($status === 'Đang giao' || $status === 'Đang giao hàng') {
                  $dataStatus = 'shipping';
              } elseif ($status === 'Hoàn thành' || $status === 'Đã giao') {
                  $dataStatus = 'done';
              }
            @endphp
            <div class="order-card" data-status="{{ $dataStatus }}" onclick="window.location.href='{{ route('page.orders.detail', $order->id) }}'" style="cursor: pointer;">
              <div class="order-card-head">
                <div>
                  <div class="order-id">#{{ $order->order_code }}</div>
                  <div class="order-date">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <span class="status-pill {{ $statusClass }}">
                  @if($status === 'Chuẩn bị') Đang chuẩn bị @else {{ $status }} @endif
                </span>
              </div>
              <div class="order-card-body">
                <div class="order-thumbs">
                  @foreach($order->items as $item)
                    <div class="order-thumb" title="{{ $item->product_name }}">
                      @if($item->product && $item->product->image_url)
                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">
                      @elseif($item->product && $item->product->svg)
                        <div class="fruit-ico {{ $item->product->ic }}"><svg viewBox="0 0 24 24">{!! $item->product->svg !!}</svg></div>
                      @else
                        🍊
                      @endif
                    </div>
                  @endforeach
                </div>
                <span style="font-size:var(--fs-xs);color:var(--n500);margin-left:8px;">{{ count($order->items) }} sản phẩm</span>
              </div>
              <div class="order-card-foot">
                <div class="order-total-txt">Tổng: <span class="order-total-val">{{ number_format($order->total_price, 0, ',', '.') }}đ</span></div>
                <div class="order-foot-btns">
                  <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); window.location.href='{{ route('page.orders.detail', $order->id) }}'">Chi tiết</button>
                  @if($status === 'Hoàn thành' || $status === 'Đã giao')
                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); alert('Đã thêm các mặt hàng vào lại giỏ hàng.');">Mua lại</button>
                  @else
                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); window.location.href='{{ route('page.orders.detail', $order->id) }}'">Theo dõi</button>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        @endif
      
    </div>
  </div>
</div>
</div>
@endsection

@section('scripts')
<script>
    function filterOrders(tab, status) {
        document.querySelectorAll('.ord-tab').forEach(t => t.classList.remove('on'));
        tab.classList.add('on');
        
        document.querySelectorAll('.order-card').forEach(card => {
            const cardStatus = card.getAttribute('data-status');
            if (status === 'all') {
                card.style.display = 'block';
            } else if (status === 'pending' && cardStatus === 'pending') {
                card.style.display = 'block';
            } else if (status === 'shipping' && cardStatus === 'shipping') {
                card.style.display = 'block';
            } else if (status === 'done' && cardStatus === 'done') {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>
@endsection
