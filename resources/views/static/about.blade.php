@extends('layouts.app')

@section('title', 'Giới Thiệu Về Chúng Tôi | FruitNest')
@section('meta_description', 'Tìm hiểu về FruitNest, cửa hàng hoa quả tươi sạch hàng đầu tại TP.HCM. Cam kết mang đến nguồn trái cây giàu dinh dưỡng nhất.')

@section('content')
<div class="page active">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Giới thiệu</span>
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
      <h2>Về FruitNest</h2>
      <p>Chào mừng bạn đến với <b>FruitNest</b> — Tổ ấm của những loại trái cây tươi ngon, sạch sẽ và giàu dinh dưỡng nhất!</p>
      
      <h3>Sứ mệnh của chúng tôi</h3>
      <p>FruitNest ra đời với sứ mệnh mang nguồn trái cây tươi mát, sạch từ các nông trại nội địa Việt Nam và các nông trại uy tín thế giới (Úc, Mỹ, New Zealand, Nhật Bản) trực tiếp tới bàn ăn của gia đình bạn trong vòng 2 giờ. Chúng tôi tin rằng dinh dưỡng từ trái cây tươi sạch chính là nền tảng của sức khỏe bền vững.</p>

      <h3>Giá trị cốt lõi</h3>
      <ul>
        <li><b>Chất lượng hàng đầu:</b> 100% trái cây tại FruitNest đều đạt tiêu chuẩn VietGAP, GlobalGAP, không sử dụng chất bảo quản độc hại.</li>
        <li><b>Tươi sạch tự nhiên:</b> Trái cây được thu hoạch đúng mùa vụ, bảo quản trong hệ thống kho lạnh chuyên dụng đảm bảo độ tươi ngon trọn vẹn nhất.</li>
        <li><b>Dịch vụ tận tâm:</b> Hỗ trợ đặt hàng dễ dàng, giao hàng lạnh chuyên nghiệp và chính sách đổi trả linh hoạt 100% nếu không hài lòng.</li>
      </ul>

      <h3>Dịch vụ cung cấp</h3>
      <p>Bên cạnh bán lẻ trái cây nội địa và nhập khẩu, FruitNest tự hào cung cấp các gói dịch vụ quà tặng lễ tết, đĩa quả cúng lễ thắp hương mùng 1 ngày rằm, mâm tráp cưới hỏi nghệ thuật kết rồng phượng tinh xảo theo yêu cầu riêng của cá nhân và doanh nghiệp.</p>
    </div>
  </div>
</div>
</div>
@endsection
