@extends('layouts.app')

@section('title', 'Chính Sách & Hướng Dẫn Mua Hàng | Hoa quả Sơn Tây')
@section('meta_description', 'Các chính sách giao nhận hàng, chính sách hoàn tiền, đổi trả và điều khoản sử dụng dịch vụ tại cửa hàng trái cây Hoa quả Sơn Tây.')

@section('content')
<div class="page active">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Chính sách</span>
  </div>

  <div class="static-layout">
    <!-- Static Sidebar -->
    <aside class="static-sidebar">
      <div class="static-sidebar-title">Thông tin</div>
      <a class="static-sidebar-link {{ request()->routeIs('page.about') ? 'on' : '' }}" href="{{ route('page.about') }}">Giới thiệu</a>
      <a class="static-sidebar-link {{ request()->routeIs('page.policy') ? 'on' : '' }}" href="{{ route('page.policy') }}">Chính sách</a>
      <a class="static-sidebar-link {{ request()->routeIs('page.contact') ? 'on' : '' }}" href="{{ route('page.contact') }}">Liên hệ</a>
    </aside>

    <!-- Content -->
    <div class="static-content">
      <h2>Chính sách & Hướng dẫn</h2>
      
      <h3>1. Hướng dẫn đặt hàng</h3>
      <p>Quý khách có thể mua hàng tại Hoa quả Sơn Tây thông qua các cách sau:</p>
      <ul>
        <li>Đặt hàng trực tiếp trên website bằng cách chọn sản phẩm và bấm "Mua ngay" hoặc "Tiến hành thanh toán".</li>
        <li>Gọi điện thoại đến hotline: <b>0909 123 456</b> để đặt nhanh.</li>
        <li>Nhắn tin qua trang Fanpage/Zalo chính thức của Hoa quả Sơn Tây để nhân viên tư vấn.</li>
      </ul>

      <h3>2. Chính sách giao hàng</h3>
      <p>Chúng tôi giao hàng chuyên dụng khắp khu vực Sơn Tây và Hà Nội:</p>
      <ul>
        <li><b>Thời gian giao hàng:</b> Giao nhanh trong ngày kể từ khi xác nhận đơn hàng, hoặc giao theo khung giờ quý khách lựa chọn trước.</li>
        <li><b>Phí giao hàng:</b> Miễn phí giao hàng cho tất cả các đơn hàng khu vực Sơn Tây, hỗ trợ phí ship ưu đãi cho khu vực lân cận.</li>
      </ul>

      <h3>3. Chính sách đổi trả & hoàn tiền</h3>
      <p>Với tôn chỉ đặt khách hàng làm trung tâm, Hoa quả Sơn Tây áp dụng chính sách bảo hành chất lượng 100%:</p>
      <ul>
        <li>Quý khách vui lòng kiểm tra kỹ trái cây khi nhận hàng.</li>
        <li>Nếu phát hiện trái cây bị dập nát, hỏng, héo, không đúng mô tả, Hoa quả Sơn Tây cam kết <b>đổi trả miễn phí</b> hoặc <b>hoàn tiền 100%</b> giá trị sản phẩm đó trong vòng 24 giờ.</li>
      </ul>

      <h3>4. Bảo mật thông tin khách hàng</h3>
      <p>Mọi thông tin cá nhân của quý khách (Họ tên, số điện thoại, email, địa chỉ) chỉ được sử dụng cho mục đích xác nhận và xử lý đơn giao hàng, cam kết bảo mật tuyệt đối không chia sẻ cho bên thứ ba.</p>
    </div>
  </div>
</div>
</div>
@endsection
