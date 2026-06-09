@extends('layouts.app')

@section('title', 'Đơn Hàng Của Tôi | FruitNest')

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
      <div class="acc-profile">
        <div class="acc-avatar"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="acc-name">Nguyễn Thị Hương</div>
        <div class="acc-email">huong@email.com</div>
      </div>
      <ul class="acc-menu">
        <li><a class="on"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Đơn hàng của tôi</a></li>
        <li><a onclick="alert('Tính năng đang được phát triển')"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Thông tin cá nhân</a></li>
        <li><a onclick="alert('Tính năng đang được phát triển')"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg> Địa chỉ giao hàng</a></li>
        <li><a onclick="alert('Tính năng đang được phát triển')"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg> Sản phẩm yêu thích</a></li>
        <li><a onclick="alert('Tính năng đang được phát triển')"><svg viewBox="0 0 24 24"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/></svg> Voucher của tôi</a></li>
        <li><a onclick="alert('Tính năng đang được phát triển')"><svg viewBox="0 0 24 24"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg> Lịch sử giao dịch</a></li>
        <li><a class="danger" href="{{ route('page.auth') }}"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Đăng xuất</a></li>
      </ul>
    </aside>
    
    <!-- Account Content List -->
    <div class="orders-box">
      <div class="orders-title">Đơn hàng của tôi</div>
      
      <!-- Order Tabs -->
      <div class="ord-tabs">
        <div class="ord-tab on" onclick="filterOrders(this, 'all')">Tất cả</div>
        <div class="ord-tab" onclick="filterOrders(this, 'pending')">Chờ xử lý</div>
        <div class="ord-tab" onclick="filterOrders(this, 'shipping')">Đang giao</div>
        <div class="ord-tab" onclick="filterOrders(this, 'done')">Hoàn thành</div>
      </div>
      
      <div id="orders-list-container">
        <!-- 1. Hiển thị đơn hàng vừa đặt trong session nếu có -->
        @if(session('last_order'))
          @php $lastOrder = session('last_order'); @endphp
          <div class="order-card active-order" data-status="pending" onclick="window.location.href='{{ route('checkout.success') }}'">
            <div class="order-card-head">
              <div>
                <div class="order-id">#{{ $lastOrder['id'] }}</div>
                <div class="order-date">{{ $lastOrder['date'] }}</div>
              </div>
              <span class="status-pill sp-proc">Đang chuẩn bị</span>
            </div>
            <div class="order-card-body">
              <div class="order-thumbs">
                @foreach($lastOrder['items'] as $item)
                  <div class="order-thumb" title="{{ $item['name'] }}">
                    <div class="fruit-ico {{ $item['ic'] }}"><svg viewBox="0 0 24 24">{!! $item['svg'] !!}</svg></div>
                  </div>
                @endforeach
              </div>
              <span style="font-size:var(--fs-xs);color:var(--n500);margin-left:8px;">{{ count($lastOrder['items']) }} sản phẩm</span>
            </div>
            <div class="order-card-foot">
              <div class="order-total-txt">Tổng: <span class="order-total-val">{{ number_format($lastOrder['total'], 0, ',', '.') }}đ</span></div>
              <div class="order-foot-btns">
                <button class="btn btn-outline btn-sm">Hỗ trợ</button>
                <button class="btn btn-primary btn-sm">Theo dõi</button>
              </div>
            </div>
          </div>
        @endif

        <!-- 2. Danh sách các đơn hàng mock tĩnh giống HTML theme cũ -->
        <div class="order-card" data-status="done">
          <div class="order-card-head">
            <div>
              <div class="order-id">#FN-2026-08098</div>
              <div class="order-date">Hôm qua, 09:15</div>
            </div>
            <span class="status-pill sp-done">Đã giao</span>
          </div>
          <div class="order-card-body">
            <div class="order-thumbs">
              <div class="order-thumb" title="Nho đen"><div class="fruit-ico fi-p"><svg viewBox="0 0 24 24"><circle cx="9" cy="14" r="3"/><circle cx="15" cy="14" r="3"/><circle cx="12" cy="9" r="3"/></svg></div></div>
              <div class="order-thumb" title="Kiwi xanh"><div class="fruit-ico fi-g"><svg viewBox="0 0 24 24"><ellipse cx="12" cy="12" rx="10" ry="7"/></svg></div></div>
            </div>
            <span style="font-size:var(--fs-xs);color:var(--n500);margin-left:8px;">2 sản phẩm</span>
          </div>
          <div class="order-card-foot">
            <div class="order-total-txt">Tổng: <span class="order-total-val">195.000đ</span></div>
            <div class="order-foot-btns">
              <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); alert('Cảm ơn bạn đã đánh giá!');">Đánh giá</button>
              <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); alert('Đã thêm sản phẩm đơn hàng này vào lại giỏ hàng.');">Mua lại</button>
            </div>
          </div>
        </div>

        <div class="order-card" data-status="done">
          <div class="order-card-head">
            <div>
              <div class="order-id">#FN-2026-07941</div>
              <div class="order-date">05/06/2026</div>
            </div>
            <span class="status-pill sp-done">Đã giao</span>
          </div>
          <div class="order-card-body">
            <div class="order-thumbs">
              <div class="order-thumb" title="Giỏ quà"><div class="fruit-ico fi-y"><svg viewBox="0 0 24 24"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/></svg></div></div>
            </div>
            <span style="font-size:var(--fs-xs);color:var(--n500);margin-left:8px;">1 sản phẩm · Giỏ quà</span>
          </div>
          <div class="order-card-foot">
            <div class="order-total-txt">Tổng: <span class="order-total-val">450.000đ</span></div>
            <div class="order-foot-btns">
              <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); alert('Cảm ơn bạn đã đánh giá!');">Đánh giá</button>
              <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); alert('Đã thêm sản phẩm đơn hàng này vào lại giỏ hàng.');">Mua lại</button>
            </div>
          </div>
        </div>
      </div>
      
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
