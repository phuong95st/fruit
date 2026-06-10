@extends('layouts.app')

@section('title', 'Thông Tin Cá Nhân | Hoa quả Sơn Tây')
@section('meta_robots', 'noindex, nofollow')

@section('content')
<div class="page active" id="page-profile">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Tài khoản</span>
  </div>
  
  <div class="account-layout" style="margin-top: 15px;">
    <!-- Sidebar Account Info -->
    <aside class="acc-sidebar">
      <div class="acc-profile">
        <div class="acc-avatar" style="position: relative; overflow: hidden; width: 48px; height: 48px; border-radius: 50%;">
          @if($user->avatar)
            <img id="sidebar-avatar-img" src="{{ $user->avatar_url }}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
          @else
            <svg viewBox="0 0 24 24" style="width: 100%; height: 100%;"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          @endif
        </div>
        <div class="acc-name">{{ $user->name }}</div>
        <div class="acc-email">{{ $user->email }}</div>
      </div>
      <ul class="acc-menu">
        <li>
          <a href="{{ route('page.auth') }}">
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
          <a class="on" href="{{ route('page.profile') }}">
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
          <a class="danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('profile-logout-form').submit();">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Đăng xuất
          </a>
          <form id="profile-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
          </form>
        </li>
      </ul>
    </aside>
    
    <!-- Account Content List -->
    <div class="orders-box">
      <div class="orders-title" style="border-bottom:1px solid var(--border); padding-bottom:10px; margin-bottom:20px;">
        Thông tin tài khoản
      </div>

      @if ($errors->any())
        <div style="background:#fce8e8; color:#9b2226; padding:12px; border-radius:var(--radius-md); margin-bottom:20px; font-size:var(--fs-sm);">
          <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" style="max-width: 500px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px;">
        @csrf
        
        <!-- Interactive Avatar Upload -->
        <div class="avatar-upload-container">
          <div class="avatar-preview-wrapper" onclick="document.getElementById('avatar-input').click()">
            @if($user->avatar)
              <img id="avatar-preview-img" src="{{ $user->avatar_url }}" alt="Avatar">
            @else
              <div class="avatar-placeholder-svg" id="avatar-placeholder">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
            @endif
            <div class="avatar-upload-overlay">
              <svg viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            </div>
          </div>
          <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display:none;"/>
          <div class="avatar-hint">Bấm vào vòng tròn để tải lên ảnh mới (định dạng JPG, PNG, tối đa 4MB)</div>
        </div>

        <div class="form-group">
          <label class="form-label" style="font-weight: 600;">Họ và tên *</label>
          <input class="form-input" name="name" value="{{ old('name', $user->name) }}" placeholder="Nguyễn Văn A" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--radius);"/>
        </div>

        <div class="form-group">
          <label class="form-label" style="font-weight: 600;">Số điện thoại *</label>
          <input class="form-input" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="0909 123 456" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--radius);"/>
        </div>

        <div class="form-group">
          <label class="form-label" style="font-weight: 600;">Email *</label>
          <input class="form-input" type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="email@example.com" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--radius);"/>
        </div>

        <div style="margin-top: 10px;">
          <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; font-weight: 600; font-size: var(--fs-md);">
            Lưu thay đổi
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
</div>

<style>
.avatar-upload-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 20px;
}
.avatar-preview-wrapper {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 3px solid var(--g300);
    overflow: hidden;
    cursor: pointer;
    box-shadow: var(--shadow);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: var(--n50);
}
.avatar-preview-wrapper:hover {
    border-color: var(--g700);
    transform: scale(1.05);
}
.avatar-preview-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.avatar-placeholder-svg {
    width: 100%;
    height: 100%;
    background: var(--g100);
    display: flex;
    align-items: center;
    justify-content: center;
}
.avatar-placeholder-svg svg {
    width: 48px;
    height: 48px;
    stroke: var(--g700);
    fill: none;
    stroke-width: 1.5;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.avatar-upload-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.avatar-preview-wrapper:hover .avatar-upload-overlay {
    opacity: 1;
}
.avatar-upload-overlay svg {
    width: 28px;
    height: 28px;
    stroke: #fff;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.avatar-hint {
    font-size: 11px;
    color: var(--n500);
    margin-top: 8px;
    text-align: center;
    max-width: 320px;
    line-height: 1.4;
}
</style>
@endsection

@section('scripts')
<script>
    document.getElementById('avatar-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file size (4MB)
            if (file.size > 4 * 1024 * 1024) {
                showToast('Kích thước ảnh đại diện không được vượt quá 4MB.');
                this.value = '';
                return;
            }
            // Validate file type
            if (!file.type.startsWith('image/')) {
                showToast('Vui lòng chọn file hình ảnh hợp lệ.');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(event) {
                const wrapper = document.querySelector('.avatar-preview-wrapper');
                let img = document.getElementById('avatar-preview-img');
                
                if (!img) {
                    // Remove placeholder div if it exists
                    const placeholder = document.getElementById('avatar-placeholder');
                    if (placeholder) {
                        placeholder.remove();
                    }
                    
                    img = document.createElement('img');
                    img.id = 'avatar-preview-img';
                    img.alt = 'Avatar';
                    wrapper.insertBefore(img, wrapper.firstChild);
                }
                
                img.src = event.target.result;
                img.style.opacity = 0;
                
                // Add soft transition
                setTimeout(() => {
                    img.style.transition = 'opacity 0.3s ease-in-out';
                    img.style.opacity = 1;
                }, 50);
                
                showToast('Đã chọn ảnh đại diện. Nhấn "Lưu thay đổi" để hoàn thành.');
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection
