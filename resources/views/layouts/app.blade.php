<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', 'FruitNest — Hoa Quả Tươi Ngon Mỗi Ngày')</title>
    <meta name="description" content="@yield('meta_description', 'FruitNest chuyên cung cấp trái cây tươi sạch nhập khẩu và nội địa tại TP.HCM. Giao hàng siêu tốc trong 2 giờ, cam kết chất lượng tươi ngon.')">
    <meta name="keywords" content="@yield('meta_keywords', 'trái cây sạch, hoa quả tươi, trái cây nhập khẩu, giỏ quà tết, đĩa quả thắp hương, mâm quả cưới hỏi, giao hàng 2h')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="@yield('canonical_url', request()->url())">
    
    <!-- Open Graph for Facebook/Zalo/AI search snippets -->
    <meta property="og:title" content="@yield('title', 'FruitNest — Hoa Quả Tươi Ngon Mỗi Ngày')">
    <meta property="og:description" content="@yield('meta_description', 'FruitNest chuyên cung cấp trái cây tươi sạch nhập khẩu và nội địa tại TP.HCM. Giao hàng siêu tốc trong 2 giờ, cam kết chất lượng tươi ngon.')">
    <meta property="og:url" content="@yield('canonical_url', request()->url())">
    <meta property="og:type" content="website">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet"/>
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    <!-- JSON-LD Local Business Schema for Search Bots & AI Crawlers -->
    @if(request()->is('/'))
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Store",
      "name": "FruitNest",
      "image": "{{ asset('images/logo.png') }}",
      "@@id": "{{ route('home') }}",
      "url": "{{ route('home') }}",
      "telephone": "0909 123 456",
      "priceRange": "$$",
      "address": {
        "@@type": "PostalAddress",
        "streetAddress": "123 Nguyễn Văn Linh, Quận 7",
        "addressLocality": "Thành phố Hồ Chí Minh",
        "addressRegion": "TP.HCM",
        "postalCode": "700000",
        "addressCountry": "VN"
      },
      "geo": {
        "@@type": "GeoCoordinates",
        "latitude": 10.7769,
        "longitude": 106.7009
      },
      "openingHoursSpecification": {
        "@@type": "OpeningHoursSpecification",
        "dayOfWeek": [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday"
        ],
        "opens": "07:00",
        "closes": "21:00"
      }
    }
    </script>
    @endif
    @yield('schema')
</head>
<body>

<!-- ═══ TOPBAR ═══ -->
<div class="topbar">
  <div class="wrap topbar-inner">
    <span>Hotline: <b>0909 123 456</b> | 7:00 – 21:00 hàng ngày</span>
    <div class="topbar-links">
      <a href="{{ route('page.about') }}">Giới thiệu</a>
      <a href="{{ route('page.policy') }}">Chính sách</a>
      <a href="{{ route('page.contact') }}">Liên hệ</a>
      <a href="{{ route('page.auth') }}">Đăng nhập</a>
    </div>
  </div>
</div>

<!-- ═══ HEADER ═══ -->
<header>
  <div class="wrap header-inner">
    <a href="{{ route('home') }}" class="logo"><span class="logo-dot"></span> FruitNest</a>
    
    <!-- Desktop Search -->
    <form action="{{ route('shop.index') }}" method="GET" class="header-search hide-mobile">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm kiếm trái cây, giỏ quà..."/>
      <button type="submit">Tìm</button>
    </form>
    
    <div class="header-actions">
      <a class="hbtn hide-mobile" href="{{ route('page.auth') }}">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span class="hbtn-label">Tài khoản</span>
      </a>
      <a class="hbtn hide-mobile" href="{{ route('page.orders') }}">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span class="hbtn-label">Đơn hàng</span>
      </a>
      <a class="hbtn hbtn-cart" href="{{ route('cart.index') }}">
        <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
        <span class="hbtn-label">Giỏ hàng</span>
        <span class="cart-badge" id="cartBadge">{{ array_sum(array_column(session('cart', []), 'quantity')) }}</span>
      </a>
      <button class="hamburger-btn hide-desktop" onclick="openDrawer()">
        <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </div>
  
  <!-- Mobile search bar -->
  <div class="mob-search hide-desktop">
    <form action="{{ route('shop.index') }}" method="GET">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm kiếm sản phẩm..."/>
      <button type="submit"><svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2;stroke-linecap:round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
    </form>
  </div>
</header>

<!-- ═══ NAVBAR ═══ -->
<nav class="navbar">
  <div class="wrap navbar-inner">
    <div class="nav-item">
      <a class="nav-link {{ request()->routeIs('home') ? 'active-nav' : '' }}" href="{{ route('home') }}">
        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Trang chủ
      </a>
    </div>
    <div class="nav-item">
      <a class="nav-link {{ request('tag') === 'trai-cay-tuoi' ? 'active-nav' : '' }}" href="{{ route('shop.index', ['tag' => 'trai-cay-tuoi']) }}">Trái cây tươi lẻ <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg></a>
      <div class="dropdown">
        <div class="dd-section">Theo loại</div>
        <a class="dd-item" href="{{ route('shop.index') }}">Tất cả trái cây tươi</a>
        <a class="dd-item" href="{{ route('shop.index', ['origins[]' => 'Việt Nam']) }}">Trái cây nội địa</a>
        <a class="dd-item" href="{{ route('shop.index', ['origins[]' => 'Đà Lạt']) }}">Trái cây Đà Lạt</a>
        <a class="dd-item" href="{{ route('shop.index') }}">Trái cây theo mùa</a>
        <div class="dd-section">Theo giá</div>
        <a class="dd-item" href="{{ route('shop.index', ['max_price' => 50000]) }}">Dưới 50.000đ/kg</a>
        <a class="dd-item" href="{{ route('shop.index', ['min_price' => 50000, 'max_price' => 150000]) }}">50.000đ – 150.000đ/kg</a>
        <a class="dd-item" href="{{ route('shop.index', ['min_price' => 150000]) }}">Trên 150.000đ/kg</a>
      </div>
    </div>
    <div class="nav-item">
      <a class="nav-link {{ request('categories[]') === 'Nhập khẩu' ? 'active-nav' : '' }}" href="{{ route('shop.index', ['categories[]' => 'Nhập khẩu']) }}">Trái cây nhập khẩu <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg></a>
      <div class="dropdown">
        <div class="dd-section">Theo xuất xứ</div>
        <a class="dd-item" href="{{ route('shop.index', ['origins[]' => 'Úc / NZ']) }}">Nhập khẩu Úc / New Zealand</a>
        <a class="dd-item" href="{{ route('shop.index', ['origins[]' => 'Hoa Kỳ']) }}">Nhập khẩu Hoa Kỳ</a>
        <a class="dd-item" href="{{ route('shop.index', ['origins[]' => 'Thái Lan']) }}">Nhập khẩu Thái Lan</a>
      </div>
    </div>
    <div class="nav-item">
      <a class="nav-link {{ request('categories[]') === 'Giỏ quà' ? 'active-nav' : '' }}" href="{{ route('shop.index', ['categories[]' => 'Giỏ quà']) }}">Giỏ quà <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg></a>
      <div class="dropdown">
        <a class="dd-item" href="{{ route('shop.index', ['categories[]' => 'Giỏ quà']) }}">Giỏ quà tiêu chuẩn</a>
        <a class="dd-item" href="{{ route('shop.index', ['categories[]' => 'Giỏ quà']) }}">Giỏ quà cao cấp</a>
        <a class="dd-item" href="{{ route('shop.index', ['tag' => 'ban-chay']) }}">Combo bán chạy</a>
      </div>
    </div>
    <div class="nav-item">
      <a class="nav-link featured {{ request()->routeIs('page.services') ? 'active-nav' : '' }}" href="{{ route('page.services') }}">
        ✦ Dịch vụ đặc biệt <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </a>
      <div class="dropdown">
        <div class="dd-section">Dịp lễ tết</div>
        <a class="dd-item" href="{{ route('page.services') }}#sv-tet">Giỏ quà Tết Nguyên Đán</a>
        <a class="dd-item" href="{{ route('page.services') }}#sv-tet">Giỏ quà Tết Trung Thu</a>
        <div class="dd-section">Nghi lễ & tâm linh</div>
        <a class="dd-item" href="{{ route('page.services') }}#sv-tamlinh">Đĩa quả thắp hương gia đình</a>
        <a class="dd-item" href="{{ route('page.services') }}#sv-tamlinh">Đĩa quả đi lễ đền, chùa</a>
        <div class="dd-section">Sự kiện</div>
        <a class="dd-item" href="{{ route('page.services') }}#sv-cuoihoi">Mâm quả cưới hỏi</a>
        <a class="dd-item" href="{{ route('page.services') }}#sv-machay">Mâm quả ma chay</a>
      </div>
    </div>
    <div class="nav-item">
      <a class="nav-link {{ request()->routeIs('page.about') ? 'active-nav' : '' }}" href="{{ route('page.about') }}">Về chúng tôi</a>
    </div>
    <div class="nav-item">
      <a class="nav-link {{ request()->routeIs('page.contact') ? 'active-nav' : '' }}" href="{{ route('page.contact') }}">Liên hệ</a>
    </div>
  </div>
</nav>

<!-- ═══ MOBILE DRAWER ═══ -->
<div class="mob-overlay" id="mobOverlay" onclick="closeDrawer()"></div>
<div class="mob-drawer" id="mobDrawer">
  <div class="mob-head">
    <span class="mob-head-logo">FruitNest</span>
    <button class="mob-close" onclick="closeDrawer()"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
  </div>
  <div class="mob-section-title">Danh mục</div>
  <a class="mob-link" href="{{ route('home') }}"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Trang chủ</a>
  <a class="mob-link" href="{{ route('shop.index', ['tag' => 'trai-cay-tuoi']) }}"><svg viewBox="0 0 24 24"><path d="M12 22s8-6 8-12A8 8 0 004 10c0 6 8 12 8 12z"/></svg> Trái cây tươi lẻ</a>
  <a class="mob-link" href="{{ route('shop.index', ['categories[]' => 'Nhập khẩu']) }}"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07"/><path d="M3.07 11.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3"/></svg> Trái cây nhập khẩu</a>
  <a class="mob-link" href="{{ route('shop.index', ['categories[]' => 'Giỏ quà']) }}"><svg viewBox="0 0 24 24"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/></svg> Giỏ quà</a>
  <a class="mob-link featured" href="{{ route('page.services') }}"><svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Dịch vụ đặc biệt</a>
  <div class="mob-section-title">Tài khoản</div>
  <a class="mob-link" href="{{ route('page.auth') }}"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Đăng nhập / Đăng ký</a>
  <a class="mob-link" href="{{ route('page.orders') }}"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16"/></svg> Đơn hàng của tôi</a>
  <div class="mob-section-title">Thông tin</div>
  <a class="mob-link" href="{{ route('page.about') }}"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg> Giới thiệu</a>
  <a class="mob-link" href="{{ route('page.policy') }}"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Chính sách</a>
  <a class="mob-link" href="{{ route('page.contact') }}"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2"/></svg> Liên hệ</a>
</div>

<!-- Dynamic View Content -->
@yield('content')

<!-- Footer -->
<footer>
  <div class="wrap">
    <div class="footer-top">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="f-logo">FruitNest</div>
          <p>Chuyên cung cấp trái cây tươi nhập khẩu và nội địa tại TP.HCM. Giao hàng nhanh, chất lượng đảm bảo.</p>
          <div class="footer-socials">
            <div class="fsocial"><svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></div>
            <div class="fsocial"><svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/></svg></div>
            <div class="fsocial"><svg viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg></div>
          </div>
        </div>
        <div class="footer-col">
          <h4>Sản phẩm</h4>
          <ul>
            <li><a href="{{ route('shop.index', ['tag' => 'trai-cay-tuoi']) }}">Trái cây tươi lẻ</a></li>
            <li><a href="{{ route('shop.index', ['categories[]' => 'Nhập khẩu']) }}">Trái cây nhập khẩu</a></li>
            <li><a href="{{ route('shop.index', ['categories[]' => 'Giỏ quà']) }}">Giỏ quà / Combo</a></li>
            <li><a href="{{ route('page.services') }}#sv-tet">Lễ tết & Tâm linh</a></li>
            <li><a href="{{ route('page.services') }}#sv-cuoihoi">Mâm quả cưới hỏi</a></li>
            <li><a href="{{ route('shop.index', ['tag' => 'giam-gia']) }}">Khuyến mãi</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Hỗ trợ</h4>
          <ul>
            <li><a href="{{ route('page.policy') }}">Hướng dẫn đặt hàng</a></li>
            <li><a href="{{ route('page.policy') }}">Chính sách giao hàng</a></li>
            <li><a href="{{ route('page.policy') }}">Chính sách đổi trả</a></li>
            <li><a href="{{ route('page.policy') }}">Chính sách bảo mật</a></li>
            <li><a href="{{ route('page.policy') }}">Điều khoản sử dụng</a></li>
            <li><a href="{{ route('page.contact') }}">Liên hệ hỗ trợ</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Liên hệ</h4>
          <ul>
            <li><a>Hotline: 0909 123 456</a></li>
            <li><a>Email: hello@fruitnest.vn</a></li>
            <li><a>123 Nguyễn Văn Linh, Q7</a></li>
            <li><a>7:00 – 21:00 hàng ngày</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 FruitNest. Bảo lưu mọi quyền.</span>
      <span>Thanh toán: ATM · Thẻ ngân hàng · Tiền mặt (COD)</span>
    </div>
  </div>
</footer>

<!-- Toast notification box -->
<div class="toast" id="toast"></div>

<!-- JavaScript Logic -->
<script>
    function openDrawer() {
        document.getElementById('mobOverlay').classList.add('show');
        document.getElementById('mobDrawer').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    
    function closeDrawer() {
        document.getElementById('mobOverlay').classList.remove('show');
        document.getElementById('mobDrawer').classList.remove('open');
        document.body.style.overflow = '';
    }
    
    let toastTimeout;
    function showToast(msg) {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => toast.classList.remove('show'), 2500);
    }

    // Flash session message toast handler
    @if(session('success'))
        window.addEventListener('DOMContentLoaded', () => {
            showToast("{{ session('success') }}");
        });
    @endif
    @if(session('error'))
        window.addEventListener('DOMContentLoaded', () => {
            showToast("{{ session('error') }}");
        });
    @endif
</script>

@yield('scripts')
</body>
</html>
