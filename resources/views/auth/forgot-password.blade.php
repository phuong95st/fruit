@extends('layouts.app')

@section('title', 'Quên Mật Khẩu | Hoa quả Sơn Tây')
@section('meta_robots', 'noindex, nofollow')

@section('content')
<div class="page active" id="page-forgot-password">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">Hoa quả Sơn Tây</div>
    <p class="auth-sub" style="margin-bottom: 20px;">Khôi phục mật khẩu tài khoản của bạn</p>
    
    @if ($errors->any())
      <div style="background:#fce8e8; color:#9b2226; padding:10px; border-radius:var(--radius); margin-bottom:15px; font-size:var(--fs-sm);">
        <ul style="margin: 0; padding-left: 20px;">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (session('success'))
      <div style="background:#e6f4ec; color:#1a5c35; padding:12px; border-radius:var(--radius); margin-bottom:15px; font-size:var(--fs-sm); font-weight:600; line-height:1.5;">
        {{ session('success') }}
      </div>
    @endif

    <form class="auth-form on" action="{{ route('password.email') }}" method="POST">
      @csrf
      <div class="form-group">
        <label class="form-label">Email tài khoản</label>
        <input class="form-input" type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com" required autofocus/>
      </div>
      
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:10px;">Gửi yêu cầu khôi phục</button>
      
      <div class="auth-back" style="margin-top:20px;">
        <a href="{{ route('page.auth') }}" class="btn btn-outline btn-sm" style="display:inline-block; width:100%;">← Quay lại đăng nhập</a>
      </div>
    </form>
  </div>
</div>
</div>
@endsection
