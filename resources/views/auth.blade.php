@extends('layouts.app')

@section('title', 'Đăng Nhập / Đăng Ký | Hoa quả Sơn Tây')

@section('content')
<div class="page active" id="page-auth">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">Hoa quả Sơn Tây</div>
    <p class="auth-sub">Chào mừng đến với Hoa quả Sơn Tây</p>
    <div class="auth-tabs">
      <button class="auth-tab on" onclick="switchAuthTab(this,'a-login')">Đăng nhập</button>
      <button class="auth-tab" onclick="switchAuthTab(this,'a-register')">Tạo tài khoản</button>
    </div>
    
    <!-- Login Form -->
    <form class="auth-form on" id="a-login" action="{{ route('home') }}" method="GET" onsubmit="alert('Đăng nhập thành công!');">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" placeholder="email@example.com" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Mật khẩu</label>
        <input class="form-input" type="password" placeholder="Nhập mật khẩu" required/>
      </div>
      <div class="auth-forgot">Quên mật khẩu?</div>
      <button type="submit" class="btn btn-primary btn-full">Đăng nhập</button>
      <div class="auth-divider">hoặc</div>
      <div class="social-row">
        <button type="button" class="social-btn" onclick="alert('Đăng nhập bằng Google');"><svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;"><circle cx="12" cy="12" r="10"/></svg> Google</button>
        <button type="button" class="social-btn" onclick="alert('Đăng nhập bằng Facebook');"><svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg> Facebook</button>
      </div>
    </form>
    
    <!-- Register Form -->
    <form class="auth-form" id="a-register" action="{{ route('home') }}" method="GET" onsubmit="alert('Đăng ký tài khoản thành công!');">
      <div class="form-group">
        <label class="form-label">Họ và tên</label>
        <input class="form-input" placeholder="Nguyễn Văn A" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Số điện thoại</label>
        <input class="form-input" placeholder="0909 123 456" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" placeholder="email@example.com" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Mật khẩu</label>
        <input class="form-input" type="password" placeholder="Tối thiểu 8 ký tự" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Xác nhận mật khẩu</label>
        <input class="form-input" type="password" placeholder="Nhập lại mật khẩu" required/>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Tạo tài khoản</button>
      <p class="auth-terms">Bằng cách đăng ký, bạn đồng ý với <a href="{{ route('page.policy') }}">Điều khoản dịch vụ</a> và <a href="{{ route('page.policy') }}">Chính sách bảo mật</a>.</p>
    </form>
    
    <div class="auth-back"><a href="{{ route('home') }}" class="btn btn-outline btn-sm" style="display:inline-block;">← Về trang chủ</a></div>
  </div>
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
</script>
@endsection
