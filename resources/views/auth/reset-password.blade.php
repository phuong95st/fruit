@extends('layouts.app')

@section('title', 'Đặt Lại Mật Khẩu | Hoa quả Sơn Tây')
@section('meta_robots', 'noindex, nofollow')

@section('content')
<div class="page active" id="page-reset-password">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">Hoa quả Sơn Tây</div>
    <p class="auth-sub" style="margin-bottom: 20px;">Thiết lập mật khẩu mới của bạn</p>
    
    @if ($errors->any())
      <div style="background:#fce8e8; color:#9b2226; padding:10px; border-radius:var(--radius); margin-bottom:15px; font-size:var(--fs-sm);">
        <ul style="margin: 0; padding-left: 20px;">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form class="auth-form on" action="{{ route('password.update') }}" method="POST">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}"/>
      
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" value="{{ $email ?? old('email') }}" required readonly/>
      </div>

      <div class="form-group">
        <label class="form-label">Mật khẩu mới</label>
        <div class="password-input-container">
          <input class="form-input" type="password" name="password" id="reset-password" placeholder="Tối thiểu 6 ký tự" required autofocus/>
          <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this, 'reset-password')" aria-label="Toggle password visibility">
            <svg class="eye-open-icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-closed-icon" viewBox="0 0 24 24" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Xác nhận mật khẩu mới</label>
        <div class="password-input-container">
          <input class="form-input" type="password" name="password_confirmation" id="reset-password-conf" placeholder="Nhập lại mật khẩu mới" required/>
          <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this, 'reset-password-conf')" aria-label="Toggle password visibility">
            <svg class="eye-open-icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-closed-icon" viewBox="0 0 24 24" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>
      
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:10px;">Đặt lại mật khẩu</button>
    </form>
  </div>
</div>
</div>
@endsection

@section('scripts')
<script>
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
