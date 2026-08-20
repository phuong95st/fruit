@extends('layouts.app')

@section('title', 'Thanh Toán Đơn Hàng | Hoa quả Sơn Tây')
@section('meta_robots', 'noindex, nofollow')

@section('content')
<div class="page active" id="page-checkout">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <a class="bc-link" href="{{ route('cart.index') }}">Giỏ hàng</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Đặt hàng</span>
  </div>
  
  <form action="{{ route('checkout.place') }}" method="POST" onsubmit="const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.innerText = '⏳ Đang xử lý đặt hàng...'; }">
    @csrf
    <div class="checkout-layout">
      <!-- Left Column: Order Forms -->
      <div>
        <div class="checkout-card">
          <div class="checkout-step-title"><div class="step-circle">1</div> Thông tin giao hàng</div>
          
          @if ($errors->any())
            <div style="background:#fce8e8; color:#9b2226; padding:10px; border-radius:var(--radius); margin-bottom:10px; font-size:var(--fs-sm);">
              <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif
          
          <div class="form-2col">
            <div class="form-group">
              <label class="form-label">Họ và tên *</label>
              <input class="form-input" name="fullname" value="{{ old('fullname') }}" placeholder="Nguyễn Văn A" required/>
            </div>
            <div class="form-group">
              <label class="form-label">Số điện thoại *</label>
              <input class="form-input" name="phone" value="{{ old('phone') }}" placeholder="0909 123 456" required/>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-input" type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com"/>
          </div>
          
          <div class="form-2col">
            <div class="form-group">
              <label class="form-label">Tỉnh / Thành *</label>
              <select class="form-input" name="city" required>
                <option value="Hà Nội" selected>Hà Nội</option>
                <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Quận / Huyện *</label>
              <select class="form-input" name="district" required>
                <option value="Thị xã Sơn Tây" selected>Thị xã Sơn Tây</option>
                <option value="Ba Đình">Ba Đình</option>
                <option value="Cầu Giấy">Cầu Giấy</option>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Địa chỉ cụ thể *</label>
            <input class="form-input" name="address" value="{{ old('address') }}" placeholder="Số nhà, tên đường..." required/>
          </div>
          
          <div class="form-group">
            <label class="form-label">Ghi chú</label>
            <textarea class="form-input" name="notes" placeholder="Gọi trước khi giao, để hàng ở cổng...">{{ old('notes') }}</textarea>
          </div>
        </div>
        
        <div class="checkout-card">
          <div class="checkout-step-title"><div class="step-circle">2</div> Thời gian giao hàng</div>
          <div class="form-2col">
            <div class="form-group">
              <label class="form-label">Ngày giao</label>
              <input class="form-input" type="date" name="delivery_date" value="{{ old('delivery_date', date('Y-m-d')) }}"/>
            </div>
            <div class="form-group">
              <label class="form-label">Khung giờ</label>
              <select class="form-input" name="delivery_time">
                <option value="8:00 – 10:00" selected>8:00 – 10:00</option>
                <option value="10:00 – 12:00">10:00 – 12:00</option>
                <option value="13:00 – 15:00">13:00 – 15:00</option>
                <option value="15:00 – 18:00">15:00 – 18:00</option>
                <option value="18:00 – 21:00">18:00 – 21:00</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="checkout-card">
          <div class="checkout-step-title"><div class="step-circle">3</div> Phương thức thanh toán</div>
          <div class="pay-opts">
            @if(Auth::check())
              <label class="pay-opt sel" onclick="selPay(this)">
                <input type="radio" name="payment_method" value="banking" checked style="display:none;"/>
                <div class="pay-opt-icon">
                  <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <div>
                  <div class="pay-opt-name">ATM / Thẻ ngân hàng</div>
                  <div class="pay-opt-desc">Hỗ trợ tất cả ngân hàng nội địa</div>
                </div>
              </label>
            @else
              <div class="pay-opt disabled" style="opacity:0.6; cursor:not-allowed; display:flex; align-items:center; gap:10px; border:1px solid var(--border); border-radius:var(--radius-md); padding:9px 12px; position:relative; background:#fafafa;">
                <div class="pay-opt-icon">
                  <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <div>
                  <div class="pay-opt-name" style="color:var(--n500); font-weight:600;">ATM / Thẻ ngân hàng (Khóa)</div>
                  <div class="pay-opt-desc" style="color:var(--r700); font-weight:700;">Đăng nhập để sử dụng thanh toán Online</div>
                </div>
              </div>
            @endif

            <label class="pay-opt {{ Auth::check() ? '' : 'sel' }}" onclick="selPay(this)">
              <input type="radio" name="payment_method" value="cod" {{ Auth::check() ? '' : 'checked' }} style="display:none;"/>
              <div class="pay-opt-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
              </div>
              <div>
                <div class="pay-opt-name">COD — Tiền mặt khi nhận</div>
                <div class="pay-opt-desc">Trả tiền trực tiếp cho shipper</div>
              </div>
            </label>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary btn-full btn-lg">✓ Xác nhận đặt hàng</button>
      </div>
      
      <!-- Right Column: Order Items Summary -->
      <div class="summary-box">
        <div class="sum-title">Đơn hàng của bạn</div>
        <div id="co-items">
          @foreach($cart as $id => $item)
            <div class="co-item">
              <div class="co-img {{ (isset($item['image_url']) && $item['image_url']) ? '' : $item['bg'] }}">
                @if(isset($item['image_url']) && $item['image_url'])
                  <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                @else
                  <div class="fruit-ico {{ $item['ic'] }}"><svg viewBox="0 0 24 24">{!! $item['svg'] !!}</svg></div>
                @endif
              </div>
              <div>
                <div class="co-name">{{ $item['name'] }}</div>
                <div class="co-qty">×{{ $item['quantity'] }}</div>
              </div>
              <div class="co-price">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</div>
            </div>
          @endforeach
        </div>
        <div class="co-divider"></div>
        <div class="sum-row"><span>Tạm tính</span><span id="co-sub">{{ number_format($subtotal, 0, ',', '.') }}đ</span></div>
        <div class="sum-row"><span>Giao hàng</span><span style="color:var(--g500);font-weight:600;">Miễn phí</span></div>
        @if($discount > 0)
          <div class="sum-row"><span>Giảm giá</span><span style="color:var(--r700);">-{{ number_format($discount, 0, ',', '.') }}đ</span></div>
        @endif
        <div class="sum-row total"><span>Tổng cộng</span><span class="val" id="co-total">{{ number_format($total, 0, ',', '.') }}đ</span></div>
        <div class="secure-note" style="margin-top:8px;"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> Bảo vệ bởi SSL 256-bit</div>
      </div>
    </div>
  </form>
</div>
</div>
@endsection

@section('scripts')
<script>
    function selPay(el) {
        document.querySelectorAll('.pay-opt').forEach(p => p.classList.remove('sel'));
        el.classList.add('sel');
        
        // Cập nhật radio tương ứng
        const radio = el.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }
</script>
@endsection
