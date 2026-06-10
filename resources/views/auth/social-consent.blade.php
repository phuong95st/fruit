@extends('layouts.app')

@section('title', 'Đăng Nhập Bằng ' . $provider . ' | Hoa quả Sơn Tây')

@section('content')
<div class="page active" id="page-social-consent">
<div class="auth-wrap" style="background: #e9ecef; min-height: 80vh; padding: 40px 12px; display: flex; justify-content: center; align-items: center;">
  
  @if($provider === 'Google')
    <!-- Google Simulated Card -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 420px; padding: 32px 40px; font-family: 'Roboto', 'Arial', sans-serif;">
      <div style="text-align: center; margin-bottom: 24px;">
        <svg viewBox="0 0 24 24" style="width:40px; height:40px; margin: 0 auto 12px; display:block;">
          <path fill="#EA4335" d="M12 5.04c1.73 0 3.29.6 4.51 1.76l3.37-3.37C17.84 1.54 15.12 1 12 1 7.24 1 3.2 3.73 1.25 7.72l3.92 3.04C6.1 7.76 8.82 5.04 12 5.04z"/>
          <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.36H12v4.51h6.46c-.29 1.48-1.14 2.73-2.4 3.58l3.73 2.89c2.18-2.01 3.7-4.99 3.7-8.62z"/>
          <path fill="#FBBC05" d="M5.17 10.76C4.93 11.53 4.8 12.35 4.8 13.2s.13 1.67.37 2.44l-3.92 3.04C.45 16.71 0 15.01 0 13.2s.45-3.51 1.25-5.48l3.92 3.04z"/>
          <path fill="#34A853" d="M12 23c3.24 0 5.97-1.07 7.96-2.91l-3.73-2.89c-1.1.74-2.5 1.18-4.23 1.18-3.18 0-5.9-2.72-6.83-5.72l-3.92 3.04C3.2 20.27 7.24 23 12 23z"/>
        </svg>
        <h2 style="font-size: 20px; font-weight: 500; color: #202124; margin: 0;">Đăng nhập bằng Google</h2>
        <p style="font-size: 14px; color: #5f6368; margin-top: 8px; margin-bottom: 0;">để tiếp tục đến trang <b>Hoa quả Sơn Tây</b></p>
      </div>

      <div style="border: 1px solid #dadce0; border-radius: 8px; overflow: hidden; margin-bottom: 24px;">
        <div style="padding: 16px; border-bottom: 1px solid #dadce0; font-size: 13px; color: #3c4043; font-weight: 500; background: #fafafa;">
          Chọn một tài khoản giả lập để tiếp tục:
        </div>

        <!-- Google Profile Item 1 -->
        <a href="{{ route('auth.google.callback', ['email' => 'nguyen.gg@gmail.com', 'name' => 'Nguyễn Văn Google']) }}" 
           style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #dadce0; transition: background 0.15s; background: #fff;"
           onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='#fff'">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: #4285F4; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">G</div>
          <div style="flex: 1; text-align: left;">
            <div style="font-size: 14px; font-weight: 500; color: #3c4043;">Nguyễn Văn Google</div>
            <div style="font-size: 12px; color: #5f6368;">nguyen.gg@gmail.com</div>
          </div>
        </a>

        <!-- Google Profile Item 2 -->
        <a href="{{ route('auth.google.callback', ['email' => 'lethi.gg@gmail.com', 'name' => 'Lê Thị Google']) }}" 
           style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #dadce0; transition: background 0.15s; background: #fff;"
           onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='#fff'">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: #e57373; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">L</div>
          <div style="flex: 1; text-align: left;">
            <div style="font-size: 14px; font-weight: 500; color: #3c4043;">Lê Thị Google</div>
            <div style="font-size: 12px; color: #5f6368;">lethi.gg@gmail.com</div>
          </div>
        </a>

        <!-- Custom Mock Input -->
        <div style="padding: 16px; background: #fff;">
          <form action="{{ route('auth.google.callback') }}" method="GET" style="display:flex; flex-direction:column; gap:8px;">
            <div style="font-size:12px; color:#5f6368; font-weight: 600; margin-bottom: 4px;">Sử dụng tài khoản khác:</div>
            <input type="text" name="name" placeholder="Nhập họ và tên" required style="border: 1px solid #dadce0; border-radius: 4px; padding: 8px; font-size: 13px; outline: none;"/>
            <input type="email" name="email" placeholder="email_cua_ban@gmail.com" required style="border: 1px solid #dadce0; border-radius: 4px; padding: 8px; font-size: 13px; outline: none;"/>
            <button type="submit" style="background: #1a73e8; color: #fff; border: none; border-radius: 4px; padding: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: background 0.15s;">
              Đăng nhập tài khoản tùy biến
            </button>
          </form>
        </div>
      </div>

      <div style="font-size: 12px; color: #5f6368; line-height: 1.4; text-align: left;">
        Để tiếp tục, Google sẽ chia sẻ tên, địa chỉ email, tùy chọn ngôn ngữ và ảnh hồ sơ của bạn với Hoa quả Sơn Tây.
      </div>
    </div>

  @else
    <!-- Facebook Simulated Card -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 100%; max-width: 420px; font-family: Helvetica, Arial, sans-serif; overflow: hidden;">
      <div style="background: #1877f2; color: #fff; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: 20px; font-weight: bold; letter-spacing: -0.5px;">facebook</span>
        <span style="font-size: 13px; opacity: 0.85;">Đăng nhập tiện lợi</span>
      </div>

      <div style="padding: 24px;">
        <div style="text-align: center; margin-bottom: 20px;">
          <h3 style="font-size: 18px; color: #1c1e21; margin: 0; font-weight: 600;">Kết nối với Hoa quả Sơn Tây</h3>
          <p style="font-size: 13px; color: #606770; margin-top: 6px; margin-bottom: 0;">Nhấn chọn tài khoản Facebook để cấp quyền truy cập nhanh.</p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
          <!-- Facebook Profile Item 1 -->
          <a href="{{ route('auth.facebook.callback', ['email' => 'hoang.fb@gmail.com', 'name' => 'Hoàng Facebook']) }}" 
             style="display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid #dddfe2; border-radius: 6px; text-decoration: none; transition: background-color 0.15s; background: #fff;"
             onmouseover="this.style.backgroundColor='#f5f6f7'" onmouseout="this.style.backgroundColor='#fff'">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #1877f2; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px;">H</div>
            <div style="flex: 1; text-align: left;">
              <div style="font-size: 14px; font-weight: bold; color: #1c1e21;">Hoàng Facebook</div>
              <div style="font-size: 11px; color: #606770;">Tiếp tục với vai trò Hoàng</div>
            </div>
          </a>

          <!-- Facebook Profile Item 2 -->
          <a href="{{ route('auth.facebook.callback', ['email' => 'mai.fb@gmail.com', 'name' => 'Mai Facebook']) }}" 
             style="display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid #dddfe2; border-radius: 6px; text-decoration: none; transition: background-color 0.15s; background: #fff;"
             onmouseover="this.style.backgroundColor='#f5f6f7'" onmouseout="this.style.backgroundColor='#fff'">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #f06292; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px;">M</div>
            <div style="flex: 1; text-align: left;">
              <div style="font-size: 14px; font-weight: bold; color: #1c1e21;">Mai Facebook</div>
              <div style="font-size: 11px; color: #606770;">Tiếp tục với vai trò Mai</div>
            </div>
          </a>
        </div>

        <div style="border-top: 1px solid #dadde1; padding-top: 16px;">
          <form action="{{ route('auth.facebook.callback') }}" method="GET" style="display:flex; flex-direction:column; gap:8px;">
            <div style="font-size:12px; color:#606770; font-weight: bold; text-align: left; margin-bottom: 2px;">Hoặc nhập tài khoản Facebook tùy chỉnh:</div>
            <input type="text" name="name" placeholder="Tên Facebook" required style="border: 1px solid #dddfe2; border-radius: 4px; padding: 8px; font-size: 13px; outline: none;"/>
            <input type="email" name="email" placeholder="email_facebook@example.com" required style="border: 1px solid #dddfe2; border-radius: 4px; padding: 8px; font-size: 13px; outline: none;"/>
            <button type="submit" style="background: #42b72a; color: #fff; border: none; border-radius: 4px; padding: 9px; font-size: 13px; font-weight: bold; cursor: pointer; transition: background 0.15s;">
              Đăng nhập Facebook mới
            </button>
          </form>
        </div>
      </div>
      
      <div style="background: #f5f6f7; padding: 12px 24px; text-align: center; font-size: 12px; color: #606770; border-top: 1px solid #dddfe2;">
        <a href="{{ route('page.auth') }}" style="color: #1877f2; text-decoration: none;">Hủy và quay lại đăng nhập</a>
      </div>
    </div>
  @endif

</div>
</div>
@endsection
