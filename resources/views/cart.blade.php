@extends('layouts.app')

@section('title', 'Giỏ Hàng | FruitNest')

@section('content')
<div class="page active" id="page-cart">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Giỏ hàng</span>
  </div>
  
  <div class="cart-layout">
    <!-- Left Column: Items List -->
    <div>
      <div class="cart-table">
        <div class="cart-table-head" style="font-size:10px;color:var(--n500);padding:6px 12px;background:var(--n50);border-bottom:1px solid var(--n100);">
          <span>Sản phẩm</span><span>Đơn giá</span><span>Số lượng</span><span>Thành tiền</span><span></span>
        </div>
        
        <div id="cart-list">
          @if(empty($cart))
            <div class="cart-empty-box">
              <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
              <h3>Giỏ hàng trống</h3>
              <p>Chưa có sản phẩm nào trong giỏ hàng.</p>
              <a class="btn btn-primary" href="{{ route('shop.index') }}">Mua sắm ngay</a>
            </div>
          @else
            @foreach($cart as $id => $item)
              <div class="cart-item" id="cart-item-{{ $item['id'] }}">
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="ci-img-box {{ $item['bg'] }}">
                    <div class="fruit-ico {{ $item['ic'] }}"><svg viewBox="0 0 24 24">{!! $item['svg'] !!}</svg></div>
                  </div>
                  <div class="ci-info">
                    <div class="ci-name">{{ $item['name'] }}</div>
                    <div class="ci-sub">/{{ $item['unit'] }} · FruitNest</div>
                  </div>
                </div>
                <div class="ci-mob-row">
                  <span style="font-size:var(--fs-sm);color:var(--n700);">{{ number_format($item['price'], 0, ',', '.') }}đ</span>
                  <div class="ci-qty">
                    <button class="ci-qty-btn" onclick="updateQty({{ $item['id'] }}, -1)">−</button>
                    <span class="ci-qty-num" id="qty-num-{{ $item['id'] }}">{{ $item['quantity'] }}</span>
                    <button class="ci-qty-btn" onclick="updateQty({{ $item['id'] }}, 1)">+</button>
                  </div>
                  <span class="ci-price" id="item-total-{{ $item['id'] }}">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</span>
                  <button class="ci-del" onclick="removeItem({{ $item['id'] }})">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </div>
              </div>
            @endforeach
          @endif
        </div>
      </div>
      <a class="btn btn-outline btn-sm" href="{{ route('shop.index') }}">← Tiếp tục mua sắm</a>
    </div>
    
    <!-- Right Column: Summary Box -->
    <div class="summary-box">
      <div class="sum-title">Tóm tắt đơn hàng</div>
      <div class="sum-row"><span>Tạm tính</span><span id="sub-val">{{ number_format($subtotal, 0, ',', '.') }}đ</span></div>
      <div class="sum-row"><span>Phí giao hàng</span><span style="color:var(--g500);font-weight:600;">Miễn phí</span></div>
      <div class="sum-row"><span>Giảm giá</span><span id="disc-val">-{{ number_format($discount, 0, ',', '.') }}đ</span></div>
      <div class="coupon-row">
        <input id="couponInp" placeholder="Nhập mã giảm giá (FRUIT10)..." value="{{ session('coupon.code') }}"/>
        <button onclick="applyCoupon()">Áp dụng</button>
      </div>
      <div class="sum-row total"><span>Tổng cộng</span><span class="val" id="total-val">{{ number_format($total, 0, ',', '.') }}đ</span></div>
      <a class="btn btn-primary btn-full btn-lg" href="{{ route('checkout.index') }}" style="margin-top:10px; text-align:center;">Tiến hành thanh toán →</a>
      <div class="pay-logos"><span class="pay-logo">ATM</span><span class="pay-logo">VISA</span><span class="pay-logo">MasterCard</span><span class="pay-logo">COD</span></div>
      <div class="secure-note"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> Thanh toán bảo mật SSL</div>
    </div>
  </div>
</div>
</div>
@endsection

@section('scripts')
<script>
    // AJAX Cập nhật số lượng
    function updateQty(productId, delta) {
        const qtyNumEl = document.getElementById('qty-num-' + productId);
        let currentQty = parseInt(qtyNumEl.textContent);
        let newQty = currentQty + delta;
        
        if (newQty < 1) return;
        
        fetch("{{ route('cart.update') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Accept": "application/json"
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: newQty
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Cập nhật giao diện
                qtyNumEl.textContent = newQty;
                document.getElementById('item-total-' + productId).textContent = data.item_total;
                document.getElementById('cartBadge').textContent = data.cart_count;
                
                // Cập nhật tổng số đơn hàng
                document.getElementById('sub-val').textContent = data.totals.subtotal;
                document.getElementById('disc-val').textContent = data.totals.discount;
                document.getElementById('total-val').textContent = data.totals.total;
            }
        });
    }

    // AJAX Xóa sản phẩm
    function removeItem(productId) {
        if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) return;
        
        fetch("{{ route('cart.remove') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Accept": "application/json"
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Xóa thẻ DOM của sản phẩm
                const itemEl = document.getElementById('cart-item-' + productId);
                itemEl.remove();
                
                document.getElementById('cartBadge').textContent = data.cart_count;
                
                // Cập nhật tổng số đơn hàng
                document.getElementById('sub-val').textContent = data.totals.subtotal;
                document.getElementById('disc-val').textContent = data.totals.discount;
                document.getElementById('total-val').textContent = data.totals.total;
                
                showToast(data.message);
                
                // Reload nếu giỏ hàng trống
                if (data.cart_count === 0) {
                    location.reload();
                }
            }
        });
    }

    // AJAX Áp dụng coupon
    function applyCoupon() {
        const code = document.getElementById('couponInp').value.trim();
        if (!code) {
            showToast('Vui lòng nhập mã giảm giá.');
            return;
        }
        
        fetch("{{ route('cart.coupon') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Accept": "application/json"
            },
            body: JSON.stringify({
                coupon_code: code
            })
        })
        .then(res => res.json())
        .then(data => {
            showToast(data.message);
            if (data.success) {
                document.getElementById('disc-val').textContent = data.totals.discount;
                document.getElementById('total-val').textContent = data.totals.total;
            }
        });
    }
</script>
@endsection
