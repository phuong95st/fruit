@extends('layouts.app')

@section('title', 'FruitNest — Hoa Quả Tươi Ngon Mỗi Ngày | Trang Chủ')

@section('content')
<div class="page active" id="page-home">
<div class="wrap page-wrap">

  <!-- Hero -->
  <div class="hero-banner">
    <div class="hero-text">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.6);margin-bottom:5px;">Giao hàng tận nơi tại TP.HCM</div>
      <h1>Hoa quả <span>tươi ngon</span> mỗi ngày</h1>
      <p>Trái cây tươi nhập khẩu và nội địa, tuyển chọn kỹ lưỡng. Giao trong 2 giờ — đảm bảo tươi ngon hoặc hoàn tiền 100%.</p>
      <div class="hero-btns">
        <a class="btn btn-primary" href="{{ route('shop.index') }}">Mua ngay</a>
        <a class="btn" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.3);" href="{{ route('page.services') }}">Dịch vụ đặc biệt</a>
      </div>
    </div>
    <div class="hero-stats">
      <div class="hstat"><div class="hstat-num">500+</div><div class="hstat-lbl">Sản phẩm</div></div>
      <div class="hstat"><div class="hstat-num">10K+</div><div class="hstat-lbl">Khách hàng</div></div>
      <div class="hstat"><div class="hstat-num">4.9★</div><div class="hstat-lbl">Đánh giá</div></div>
    </div>
  </div>

  <!-- Trust bar -->
  <div class="trust-bar">
    <div class="trust-item"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg><div class="trust-item-text"><b>Giao hàng 2 giờ</b><span>Xe lạnh chuyên dụng</span></div></div>
    <div class="trust-item"><svg viewBox="0 0 24 24"><path d="M12 22s8-6 8-12A8 8 0 004 10c0 6 8 12 8 12z"/><path d="M9 12l2 2 4-4"/></svg><div class="trust-item-text"><b>100% tươi sạch</b><span>Không hóa chất</span></div></div>
    <div class="trust-item"><svg viewBox="0 0 24 24"><path d="M23 12a11.05 11.05 0 01-22 0 11.05 11.05 0 0122 0z"/><path d="M12 6v6l4 2"/></svg><div class="trust-item-text"><b>Hoàn tiền 100%</b><span>Không hài lòng</span></div></div>
    <div class="trust-item"><svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg><div class="trust-item-text"><b>ATM / COD</b><span>Thanh toán dễ dàng</span></div></div>
  </div>

  <!-- Promo strip -->
  <div class="promo-strip">
    <a class="promo-card pc-green" href="{{ route('shop.index', ['categories[]' => 'Giỏ quà']) }}">
      <div class="promo-card-label">Nhập khẩu cao cấp</div>
      <div class="promo-card-title">Giỏ quà<br/>nhập khẩu</div>
      <div class="promo-card-sub">Từ 250.000đ</div>
    </a>
    <a class="promo-card pc-red" href="{{ route('page.services') }}#sv-tamlinh">
      <div class="promo-card-label">Tâm linh & Lễ tết</div>
      <div class="promo-card-title">Đĩa quả<br/>thắp hương</div>
      <div class="promo-card-sub">Đặt theo yêu cầu</div>
    </a>
    <a class="promo-card pc-gold" href="{{ route('page.services') }}#sv-cuoihoi">
      <div class="promo-card-label">Sự kiện</div>
      <div class="promo-card-title">Mâm quả<br/>cưới hỏi</div>
      <div class="promo-card-sub">Theo phong tục</div>
    </a>
  </div>

  <!-- Main layout: sidebar + content -->
  <div class="home-layout">
    <!-- Category sidebar -->
    <aside class="cat-sidebar">
      <div class="cat-sidebar-title"><svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> Danh mục</div>
      <div class="cat-sidebar-menu">
        <a class="cat-sidebar-item" href="{{ route('shop.index', ['tag' => 'trai-cay-tuoi']) }}"><svg viewBox="0 0 24 24"><path d="M12 22s8-6 8-12A8 8 0 004 10c0 6 8 12 8 12z"/></svg> Trái cây tươi lẻ</a>
        <a class="cat-sidebar-item" href="{{ route('shop.index', ['origins[]' => 'Úc / NZ']) }}"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2"/><path d="M3.07 11.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3"/></svg> Nhập khẩu Úc/NZ</a>
        <a class="cat-sidebar-item" href="{{ route('shop.index', ['origins[]' => 'Hoa Kỳ']) }}"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg> Nhập khẩu Hoa Kỳ</a>
        <a class="cat-sidebar-item" href="{{ route('shop.index', ['origins[]' => 'Thái Lan']) }}"><svg viewBox="0 0 24 24"><path d="M12 22C6.48 22 3 17 3 13c0-4 3-8 9-11 6 3 9 7 9 11 0 4-3.48 9-9 9z"/></svg> Nhập khẩu Thái Lan</a>
        <a class="cat-sidebar-item" href="{{ route('shop.index', ['categories[]' => 'Giỏ quà']) }}"><svg viewBox="0 0 24 24"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/></svg> Giỏ quà</a>
        <a class="cat-sidebar-item special" href="{{ route('page.services') }}#sv-tamlinh"><svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Lễ tết & Tâm linh</a>
        <a class="cat-sidebar-item red-cat" href="{{ route('page.services') }}#sv-cuoihoi"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg> Cưới hỏi & Sự kiện</a>
        <a class="cat-sidebar-item" href="{{ route('shop.index', ['tag' => 'ban-chay']) }}"><svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Bán chạy</a>
      </div>
    </aside>

    <!-- Main content -->
    <div>
      <!-- Nổi bật -->
      <div class="section-block">
        <div class="section-head">
          <div class="section-head-title">Sản phẩm nổi bật</div>
          <a class="section-head-link" href="{{ route('shop.index') }}">Xem tất cả »</a>
        </div>
        <div class="section-body">
          <div class="grid-5" id="home-featured">
            @foreach($featuredProducts as $product)
              @include('partials.product-card', ['product' => $product])
            @endforeach
          </div>
        </div>
      </div>

      <!-- Special Services preview -->
      <div class="section-block">
        <div class="section-head">
          <div class="section-head-title" style="color:var(--gold);">Dịch vụ đặc biệt</div>
          <a class="section-head-link" href="{{ route('page.services') }}">Xem tất cả »</a>
        </div>
        <div class="section-body">
          <div class="service-grid">
            <a class="service-card" href="{{ route('page.services') }}#sv-tet">
              <div class="service-icon si-gold"><svg viewBox="0 0 24 24"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/></svg></div>
              <div class="service-name">Giỏ quà Tết</div>
              <div class="service-desc">Tết Nguyên Đán, Trung Thu, ngày lễ</div>
            </a>
            <a class="service-card" href="{{ route('page.services') }}#sv-tamlinh">
              <div class="service-icon si-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div>
              <div class="service-name">Đĩa quả thắp hương</div>
              <div class="service-desc">Gia đình, đền chùa, mâm cúng</div>
            </a>
            <a class="service-card" href="{{ route('page.services') }}#sv-cuoihoi">
              <div class="service-icon si-purple"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></div>
              <div class="service-name">Mâm quả cưới hỏi</div>
              <div class="service-desc">Lễ ăn hỏi, đám cưới truyền thống</div>
            </a>
            <a class="service-card" href="{{ route('page.services') }}#sv-custom">
              <div class="service-icon si-green"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
              <div class="service-name">Đặt theo yêu cầu</div>
              <div class="service-desc">Theo khoảng giá và yêu cầu riêng</div>
            </a>
          </div>
        </div>
      </div>

      <!-- Giỏ quà -->
      <div class="section-block">
        <div class="section-head">
          <div class="section-head-title">Giỏ quà & Combo</div>
          <a class="section-head-link" href="{{ route('shop.index', ['categories[]' => 'Giỏ quà']) }}">Xem tất cả »</a>
        </div>
        <div class="section-body">
          <div class="grid-4" id="home-baskets">
            @foreach($baskets as $product)
              @include('partials.product-card', ['product' => $product])
            @endforeach
          </div>
        </div>
      </div>

      <!-- Nhập khẩu -->
      <div class="section-block">
        <div class="section-head">
          <div class="section-head-title">Trái cây nhập khẩu</div>
          <a class="section-head-link" href="{{ route('shop.index', ['categories[]' => 'Nhập khẩu']) }}">Xem tất cả »</a>
        </div>
        <div class="section-body">
          <div class="grid-5" id="home-imports">
            @foreach($imports as $product)
              @include('partials.product-card', ['product' => $product])
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
@endsection

@section('scripts')
<script>
    // AJAX thêm vào giỏ hàng
    function addToCart(productId, productName) {
        fetch("{{ route('cart.add') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Accept": "application/json"
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Cập nhật badge giỏ hàng
                document.getElementById('cartBadge').textContent = data.cart_count;
                // Hiển thị thông báo
                showToast(data.message);
            } else {
                showToast('Không thể thêm sản phẩm.');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Lỗi hệ thống, vui lòng thử lại.');
        });
    }
</script>
@endsection
