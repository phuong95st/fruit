@extends('layouts.app')

@section('title', Auth::check() ? 'Tài Khoản Của Tôi | Hoa quả Sơn Tây' : 'Đăng Nhập / Đăng Ký | Hoa quả Sơn Tây')
@section('meta_robots', 'noindex, nofollow')

@section('content')
<div class="page active" id="page-auth">
<div class="wrap page-wrap">
  
  @if(Auth::check())
    <!-- LOGGED IN USER DASHBOARD -->
    <div class="account-layout" style="margin-top: 15px;">
      <!-- Sidebar profile info -->
      <div class="acc-sidebar">
        <div class="acc-profile">
          <div class="acc-avatar" style="position: relative; overflow: hidden; width: 48px; height: 48px; border-radius: 50%;">
            @if($user->avatar)
              <img src="{{ $user->avatar_url }}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
            @else
              <svg viewBox="0 0 24 24" style="width: 100%; height: 100%;"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            @endif
          </div>
          <div class="acc-name">{{ $user->name }}</div>
          <div class="acc-email">{{ $user->email }}</div>
        </div>
        <ul class="acc-menu">
          <li>
            <a class="on" href="{{ route('page.auth') }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
              Bảng điều khiển
            </a>
          </li>
          <li>
            <a href="{{ route('page.orders') }}">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Đơn hàng của tôi
            </a>
          </li>
          <li>
            <a href="{{ route('page.profile') }}">
              <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Thông tin cá nhân
            </a>
          </li>
          <li>
            <a onclick="alert('Tính năng đang được phát triển')" style="cursor:pointer;">
              <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Địa chỉ giao hàng
            </a>
          </li>
          <li>
            <a onclick="alert('Tính năng đang được phát triển')" style="cursor:pointer;">
              <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
              Sản phẩm yêu thích
            </a>
          </li>
          @if(Auth::user()->is_admin)
          <li>
            <a href="{{ route('admin.dashboard') }}" style="color: var(--g700); font-weight: 600;">
              <svg viewBox="0 0 24 24" style="stroke: var(--g700); fill: none;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="21" x2="9" y2="9"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="15" x2="21" y2="15"/></svg>
              Trang quản trị (Admin)
            </a>
          </li>
          @endif
          <li>
            <a class="danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Đăng xuất
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
              @csrf
            </form>
          </li>
        </ul>
      </div>

      <!-- Main Dashboard Content -->
      <div style="display: flex; flex-direction: column; gap: 15px;">
        
        <!-- Welcome banner -->
        <div class="welcome-banner">
          <h2 style="font-family: 'Merriweather', serif; font-size: var(--fs-xl); margin-bottom: 5px;">Xin chào, {{ $user->name }}!</h2>
          <p style="font-size: var(--fs-base); opacity: 0.9; line-height: 1.5;">Tại trang cá nhân, bạn có thể theo dõi các đơn hàng gần đây, quản lý thông tin tài khoản và xem các mã khuyến mại dành riêng cho bạn.</p>
        </div>

        <div class="dashboard-grid">
          
          <!-- Recent Orders Section -->
          <div class="orders-box" style="margin-bottom: 0;">
            <div class="orders-title" style="display:flex; justify-content:space-between; align-items:center;">
              <span>Đơn đặt hàng gần đây</span>
              <a href="{{ route('page.orders') }}" style="font-size: var(--fs-xs); color: var(--g700); font-weight: 600;">Xem tất cả</a>
            </div>
            
            @if($orders->isEmpty())
              <p style="font-size: var(--fs-sm); color: var(--n500); padding: 10px 0;">Bạn chưa đặt đơn hàng nào.</p>
            @else
              <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($orders->take(3) as $order)
                  <div class="recent-order-item">
                    <div>
                      <div style="font-weight:700; color:var(--n900);">Mã: {{ $order->order_code }}</div>
                      <div style="font-size:var(--fs-xs); color:var(--n500); margin-top:2px;">Ngày đặt: {{ $order->created_at->format('d/m/Y') }}</div>
                      <div style="font-size:var(--fs-xs); color:var(--r700); font-weight:700; margin-top:2px;">{{ number_format($order->total_price, 0, ',', '.') }}đ</div>
                    </div>
                    <div style="text-align:right;">
                      <span class="status-pill {{ $order->status === 'Hoàn thành' ? 'sp-done' : ($order->status === 'Chờ xử lý' || $order->status === 'Chuẩn bị' ? 'sp-pend' : 'sp-proc') }}" style="display:inline-block; margin-bottom: 4px;">
                        {{ $order->status }}
                      </span>
                      <a href="{{ route('page.orders.detail', $order->id) }}" class="btn btn-outline btn-sm" style="padding: 3px 8px; font-size:10px; display:block; text-align:center;">Chi tiết</a>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>

          <!-- Special Coupons Section -->
          <div class="orders-box" style="margin-bottom: 0;">
            <div class="orders-title">Mã khuyến mại dành cho bạn</div>
            @if($vouchers->isEmpty())
              <p style="font-size: var(--fs-sm); color: var(--n500); padding: 10px 0;">Hiện tại không có mã khuyến mại nào khả dụng.</p>
            @else
              <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($vouchers->take(3) as $voucher)
                  <div class="dashboard-voucher-card">
                    <div>
                      <div style="font-weight:700; color:var(--g700); font-size: var(--fs-md);">{{ $voucher->code }}</div>
                      <div style="font-size: 11px; color: var(--n700); margin-top:3px;">
                        @if($voucher->discount_type === 'percent')
                          Giảm {{ (int)$voucher->discount_value }}% cho đơn hàng
                        @else
                          Giảm {{ number_format($voucher->discount_value, 0, ',', '.') }}đ cho đơn hàng
                        @endif
                      </div>
                      <div style="font-size: 10px; color: var(--n500); margin-top:2px;">HSD: {{ Carbon\Carbon::parse($voucher->expires_at)->format('d/m/Y') }}</div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText('{{ $voucher->code }}'); showToast('Đã copy mã: {{ $voucher->code }}');" style="padding: 4px 8px; font-size: 11px; z-index: 5;">Copy</button>
                  </div>
                @endforeach
              </div>
            @endif
          </div>

        </div>

      </div>
    </div>

  @else
    <!-- GUEST: LOGIN / REGISTER FORMS -->
    <div class="auth-wrap">
      <div class="auth-card">
        <div class="auth-logo">Hoa quả Sơn Tây</div>
        <p class="auth-sub">Chào mừng đến với Hoa quả Sơn Tây</p>
        
        <div class="auth-tabs">
          <button class="auth-tab {{ $errors->has('register') ? '' : 'on' }}" id="tab-login-btn" onclick="switchAuthTab(this,'a-login')">Đăng nhập</button>
          <button class="auth-tab {{ $errors->has('register') ? 'on' : '' }}" id="tab-register-btn" onclick="switchAuthTab(this,'a-register')">Tạo tài khoản</button>
        </div>
        
        @if ($errors->any() && !$errors->has('register'))
          <div style="background:#fce8e8; color:#9b2226; padding:10px; border-radius:var(--radius); margin-bottom:10px; font-size:var(--fs-sm);">
            <ul style="margin: 0; padding-left: 20px;">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @if ($errors->has('register'))
          <div style="background:#fce8e8; color:#9b2226; padding:10px; border-radius:var(--radius); margin-bottom:10px; font-size:var(--fs-sm);">
            <ul style="margin: 0; padding-left: 20px;">
              @foreach ($errors->get('register') as $error)
                <li>{{ $error }}</li>
              @endforeach
              @foreach ($errors->all() as $error)
                @if($error != $errors->first('register'))
                  <li>{{ $error }}</li>
                @endif
              @endforeach
            </ul>
          </div>
        @endif
        
        <!-- Login Form -->
        <form class="auth-form {{ $errors->has('register') ? '' : 'on' }}" id="a-login" action="{{ route('login') }}" method="POST">
          @csrf
          <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-input" type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Mật khẩu</label>
            <div class="password-input-container">
              <input class="form-input" type="password" name="password" id="login-password" placeholder="Nhập mật khẩu" required/>
              <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this, 'login-password')" aria-label="Toggle password visibility">
                <svg class="eye-open-icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed-icon" viewBox="0 0 24 24" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
          <div class="auth-forgot">
            <a href="{{ route('password.request') }}" style="color: var(--g700); font-weight: 600;">Quên mật khẩu?</a>
          </div>
          <button type="submit" class="btn btn-primary btn-full" style="margin-top: 5px;">Đăng nhập</button>
          
          <div class="auth-divider">hoặc đăng nhập bằng</div>
          <div class="social-row">
            <a href="{{ route('auth.google') }}" class="social-btn" style="text-decoration:none; color:inherit;">
              <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:none;fill:currentColor;"><path d="M12.24 10.285V13.4h6.887c-.275 1.565-1.88 4.604-6.887 4.604-4.33 0-7.866-3.577-7.866-8s3.536-8 7.866-8c2.46 0 4.105 1.025 5.047 1.926l2.427-2.334C17.955 2.192 15.34 1 12.24 1 6.033 1 1 6.033 1 12.24s5.033 11.24 11.24 11.24c6.478 0 10.793-4.537 10.793-10.984 0-.743-.08-1.3-.178-1.857H12.24z"/></svg> 
              Google
            </a>
            <a href="{{ route('auth.facebook') }}" class="social-btn" style="text-decoration:none; color:inherit;">
              <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:none;fill:currentColor;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> 
              Facebook
            </a>
          </div>
        </form>
        
        <!-- Register Form -->
        <form class="auth-form {{ $errors->has('register') ? 'on' : '' }}" id="a-register" action="{{ route('register') }}" method="POST">
          @csrf
          <div class="form-group">
            <label class="form-label">Họ và tên *</label>
            <input class="form-input" name="name" value="{{ old('name') }}" placeholder="Nguyễn Văn A" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Số điện thoại *</label>
            <input class="form-input" name="phone" value="{{ old('phone') }}" placeholder="0909 123 456" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input class="form-input" type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Mật khẩu *</label>
            <div class="password-input-container">
              <input class="form-input" type="password" name="password" id="register-password" placeholder="Tối thiểu 6 ký tự" required/>
              <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this, 'register-password')" aria-label="Toggle password visibility">
                <svg class="eye-open-icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed-icon" viewBox="0 0 24 24" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Xác nhận mật khẩu *</label>
            <div class="password-input-container">
              <input class="form-input" type="password" name="password_confirmation" id="register-password-conf" placeholder="Nhập lại mật khẩu" required/>
              <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this, 'register-password-conf')" aria-label="Toggle password visibility">
                <svg class="eye-open-icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed-icon" viewBox="0 0 24 24" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-full" style="margin-top: 5px;">Tạo tài khoản</button>
          <p class="auth-terms">Bằng cách đăng ký, bạn đồng ý với <a href="{{ route('page.policy') }}">Điều khoản dịch vụ</a> và <a href="{{ route('page.policy') }}">Chính sách bảo mật</a>.</p>
        </form>
        
        <div class="auth-back"><a href="{{ route('home') }}" class="btn btn-outline btn-sm" style="display:inline-block;">← Về trang chủ</a></div>
      </div>
    </div>
  @endif

</div>
</div>
@endsection

@section('scripts')
<script>
    function switchAuthTab(btn, id) {
        document.querySelectorAll('.auth-tab').forEach(b => b.classList.remove('on'));
        btn.classList.add('on');
        
        document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('on'));
        document.getElementById(id).classList.add('on');
    }

    function togglePasswordVisibility(button, inputId) {
        const input = document.getElementById(inputId);
        const eyeOpen = button.querySelector('.eye-open-icon');
        const eyeClosed = button.querySelector('.eye-closed-icon');
        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
        } else {
            input.type = 'password';
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
        }
    }
</script>
@endsection
