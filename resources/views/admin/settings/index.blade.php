@extends('admin.layouts.app')

@section('title', 'Cài đặt hệ thống')
@section('page_title', 'Cài đặt')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Hệ thống</div>
    <h2>Cài đặt</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-primary btn-sm" onclick="showToast('✅ Đã lưu cài đặt','success')">💾 Lưu thay đổi</button>
  </div>
</div>

<div class="settings-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <div>
    <div class="card mb-16">
      <div class="card-body">
        <div class="form-section-title">🏪 Thông tin cửa hàng</div>
        <div class="form-grid">
          <div class="fg form-full"><label>Tên cửa hàng</label><input value="Hoa quả Sơn Tây — Hoa Quả Tươi Ngon"/></div>
          <div class="fg"><label>Số điện thoại</label><input value="0909 123 456"/></div>
          <div class="fg"><label>Email</label><input value="hello@hoaquasontay.vn"/></div>
          <div class="fg form-full"><label>Địa chỉ kho</label><input value="Thị xã Sơn Tây, Hà Nội"/></div>
          <div class="fg"><label>Giờ mở cửa</label><input value="07:00 – 21:00"/></div>
          <div class="fg"><label>Múi giờ</label><select><option>Asia/Ho_Chi_Minh (GMT+7)</option></select></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="form-section-title">🚚 Vận chuyển & Giao hàng</div>
        <div class="form-grid">
          <div class="fg"><label>Phí giao hàng mặc định</label>
            <div class="input-group"><input type="number" value="25000"/><div class="input-addon">đ</div></div>
          </div>
          <div class="fg"><label>Miễn phí giao từ</label>
            <div class="input-group"><input type="number" value="150000"/><div class="input-addon">đ</div></div>
          </div>
          <div class="fg"><label>Bán kính giao hàng</label>
            <div class="input-group"><input type="number" value="25"/><div class="input-addon">km</div></div>
          </div>
          <div class="fg"><label>Thời gian giao chuẩn</label><select><option>2 giờ</option><option>4 giờ</option><option>Trong ngày</option></select></div>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="card mb-16">
      <div class="card-body">
        <div class="form-section-title">🔔 Thông báo hệ thống</div>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div class="flex justify-between items-center"><div><div style="font-size:.85rem;font-weight:600;">Đơn hàng mới</div><div style="font-size:.72rem;color:var(--text-3);">Gửi thông báo khi có đơn mới</div></div><button class="toggle on" onclick="this.classList.toggle('on')"></button></div>
          <div class="flex justify-between items-center"><div><div style="font-size:.85rem;font-weight:600;">Sắp hết hàng</div><div style="font-size:.72rem;color:var(--text-3);">Cảnh báo khi tồn kho thấp</div></div><button class="toggle on" onclick="this.classList.toggle('on')"></button></div>
          <div class="flex justify-between items-center"><div><div style="font-size:.85rem;font-weight:600;">Yêu cầu hoàn hàng</div><div style="font-size:.72rem;color:var(--text-3);">Thông báo ngay khi có khiếu nại</div></div><button class="toggle on" onclick="this.classList.toggle('on')"></button></div>
          <div class="flex justify-between items-center"><div><div style="font-size:.85rem;font-weight:600;">Báo cáo hàng ngày</div><div style="font-size:.72rem;color:var(--text-3);">Email tổng kết lúc 22:00</div></div><button class="toggle" onclick="this.classList.toggle('on')"></button></div>
          <div class="fg" style="margin-top:4px;"><label>Email nhận thông báo</label><input value="admin@hoaquasontay.vn"/></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="form-section-title">👤 Tài khoản Admin</div>
        <div class="flex items-center gap-12 mb-16" style="gap:12px;">
          <div class="ap-avatar" style="width:56px;height:56px;font-size:1.4rem;display:flex;align-items:center;justify-content:center;">A</div>
          <div>
            <div style="font-weight:700;margin-bottom:2px;">Admin</div>
            <div style="font-size:.78rem;color:var(--text-3);">admin@hoaquasontay.vn</div>
            <div style="font-size:.72rem;margin-top:4px;"><span class="badge badge-warning">Quản trị viên</span></div>
          </div>
        </div>
        <div class="form-grid">
          <div class="fg"><label>Họ và tên</label><input value="Admin"/></div>
          <div class="fg"><label>Số điện thoại</label><input value="0909 999 888"/></div>
          <div class="fg"><label>Mật khẩu mới</label><input type="password" placeholder="••••••••"/></div>
          <div class="fg"><label>Xác nhận mật khẩu</label><input type="password" placeholder="••••••••"/></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection