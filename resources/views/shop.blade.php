@extends('layouts.app')

@section('title', 'Cửa Hàng Trái Cây Sạch | FruitNest')

@section('content')
<div class="page active" id="page-shop">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Sản phẩm</span>
  </div>

  <!-- Main Filter Form -->
  <form id="filterForm" action="{{ route('shop.index') }}" method="GET">
    <!-- Search Bar -->
    <div class="search-bar">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm kiếm sản phẩm..."/>
      <button type="submit">Tìm kiếm</button>
    </div>

    <!-- Hidden Input for Tag Chip selection -->
    <input type="hidden" name="tag" id="tagInput" value="{{ request('tag') }}">

    <!-- Tag Chips Row -->
    <div class="tag-row">
      <div class="tag-chip {{ !request('tag') ? 'on' : '' }}" onclick="selectTag('')">Tất cả</div>
      <div class="tag-chip {{ request('tag') === 'trai-cay-tuoi' ? 'on' : '' }}" onclick="selectTag('trai-cay-tuoi')">Trái cây tươi</div>
      <div class="tag-chip {{ request('tag') === 'nhap-khau-uc-nz' ? 'on' : '' }}" onclick="selectTag('nhap-khau-uc-nz')">Nhập khẩu Úc/NZ</div>
      <div class="tag-chip {{ request('tag') === 'nhap-khau-my' ? 'on' : '' }}" onclick="selectTag('nhap-khau-my')">Nhập khẩu Mỹ</div>
      <div class="tag-chip {{ request('tag') === 'nhap-khau-thai' ? 'on' : '' }}" onclick="selectTag('nhap-khau-thai')">Nhập khẩu Thái Lan</div>
      <div class="tag-chip {{ request('tag') === 'gio-qua' ? 'on' : '' }}" onclick="selectTag('gio-qua')">Giỏ quà</div>
      <div class="tag-chip {{ request('tag') === 'giam-gia' ? 'on' : '' }}" onclick="selectTag('giam-gia')">Đang giảm giá</div>
      <div class="tag-chip {{ request('tag') === 'ban-chay' ? 'on' : '' }}" onclick="selectTag('ban-chay')">Bán chạy</div>
    </div>

    <div class="shop-layout">
      <!-- Sidebar Filters -->
      <aside class="filter-box">
        <div class="filter-box-title"><svg viewBox="0 0 24 24"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg> Bộ lọc</div>
        
        <!-- Category Filter -->
        <div class="filter-section">
          <div class="filter-section-title">Danh mục</div>
          <div class="filter-opts">
            @php $selectedCats = (array)request('categories', []); @endphp
            <label class="filter-opt {{ in_array('Tươi lẻ', $selectedCats) ? 'on' : '' }}">
              <input type="checkbox" name="categories[]" value="Tươi lẻ" style="display:none;" {{ in_array('Tươi lẻ', $selectedCats) ? 'checked' : '' }} onchange="toggleCheckboxStyle(this)"> Tươi lẻ
            </label>
            <label class="filter-opt {{ in_array('Nhập khẩu', $selectedCats) ? 'on' : '' }}">
              <input type="checkbox" name="categories[]" value="Nhập khẩu" style="display:none;" {{ in_array('Nhập khẩu', $selectedCats) ? 'checked' : '' }} onchange="toggleCheckboxStyle(this)"> Nhập khẩu
            </label>
            <label class="filter-opt {{ in_array('Giỏ quà', $selectedCats) ? 'on' : '' }}">
              <input type="checkbox" name="categories[]" value="Giỏ quà" style="display:none;" {{ in_array('Giỏ quà', $selectedCats) ? 'checked' : '' }} onchange="toggleCheckboxStyle(this)"> Giỏ quà
            </label>
          </div>
        </div>

        <!-- Origin Filter -->
        <div class="filter-section">
          <div class="filter-section-title">Xuất xứ</div>
          <div class="filter-opts">
            @php $selectedOrigins = (array)request('origins', []); @endphp
            @foreach(['Việt Nam', 'Thái Lan', 'Úc / NZ', 'Hoa Kỳ', 'Hàn Quốc'] as $originOpt)
              <label class="filter-opt {{ in_array($originOpt, $selectedOrigins) ? 'on' : '' }}">
                <input type="checkbox" name="origins[]" value="{{ $originOpt }}" style="display:none;" {{ in_array($originOpt, $selectedOrigins) ? 'checked' : '' }} onchange="toggleCheckboxStyle(this)"> {{ $originOpt }}
              </label>
            @endforeach
          </div>
        </div>

        <!-- Price Range Filter -->
        <div class="filter-section">
          <div class="filter-section-title">Khoảng giá (đ)</div>
          <div class="price-inputs">
            <input class="price-input" type="number" name="min_price" placeholder="Từ" value="{{ request('min_price', $minPrice) }}"/>
            <span style="font-size:11px;color:var(--n500);">—</span>
            <input class="price-input" type="number" name="max_price" placeholder="Đến" value="{{ request('max_price', $maxPrice) }}"/>
          </div>
        </div>

        <!-- Rating Filter -->
        <div class="filter-section">
          <div class="filter-section-title">Đánh giá</div>
          <div class="filter-opts">
            <label class="filter-opt {{ request('rating') == '5' ? 'on' : '' }}">
              <input type="radio" name="rating" value="5" style="display:none;" {{ request('rating') == '5' ? 'checked' : '' }} onchange="toggleRadioStyle(this)"> 5 sao
            </label>
            <label class="filter-opt {{ request('rating') == '4' ? 'on' : '' }}">
              <input type="radio" name="rating" value="4" style="display:none;" {{ request('rating') == '4' ? 'checked' : '' }} onchange="toggleRadioStyle(this)"> 4+ sao
            </label>
            <label class="filter-opt {{ request('rating') == '3' ? 'on' : '' }}">
              <input type="radio" name="rating" value="3" style="display:none;" {{ request('rating') == '3' ? 'checked' : '' }} onchange="toggleRadioStyle(this)"> 3+ sao
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="filter-section" style="display:flex;gap:5px;">
          <button type="submit" class="btn btn-primary btn-sm btn-full">Áp dụng</button>
          <a href="{{ route('shop.index') }}" class="btn btn-outline btn-sm text-center" style="flex:1;">Xóa</a>
        </div>
      </aside>

      <!-- Products Content -->
      <div>
        <div class="shop-toolbar">
          <div class="shop-count">
            Hiển thị <b>{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</b> trong <b>{{ $totalCount }}</b> sản phẩm
          </div>
          <select class="sort-select" name="sort" onchange="this.form.submit()">
            <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>Phổ biến nhất</option>
            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Giá tăng dần</option>
            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Giá giảm dần</option>
            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Mới nhất</option>
          </select>
        </div>

        @if($products->isEmpty())
          <div style="text-align:center; padding: 40px 20px; background:#fff; border:1px solid var(--border); border-radius:var(--radius-md);">
             <p style="font-size:var(--fs-lg); color:var(--n500);">Không tìm thấy sản phẩm nào khớp với bộ lọc.</p>
             <a href="{{ route('shop.index') }}" class="btn btn-primary" style="margin-top:10px;">Xóa bộ lọc</a>
          </div>
        @else
          <div class="grid-4" id="shop-grid">
            @foreach($products as $product)
              @include('partials.product-card', ['product' => $product])
            @endforeach
          </div>

          <!-- Pagination -->
          <div class="pagination">
            {{ $products->links('partials.pagination') }}
          </div>
        @endif
      </div>
    </div>
  </form>
</div>
</div>
@endsection

@section('scripts')
<script>
    // Chọn tag chip ngang
    function selectTag(tagValue) {
        document.getElementById('tagInput').value = tagValue;
        document.getElementById('filterForm').submit();
    }

    // Toggle class "on" khi checkbox thay đổi
    function toggleCheckboxStyle(checkbox) {
        if (checkbox.checked) {
            checkbox.parentElement.classList.add('on');
        } else {
            checkbox.parentElement.classList.remove('on');
        }
    }

    // Radio style toggle
    function toggleRadioStyle(radio) {
        const name = radio.name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
            r.parentElement.classList.remove('on');
        });
        if (radio.checked) {
            radio.parentElement.classList.add('on');
        }
    }

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
                document.getElementById('cartBadge').textContent = data.cart_count;
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
