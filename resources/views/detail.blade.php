@extends('layouts.app')

@section('title', $product->name . ' — FruitNest')
@section('meta_description', $product->desc ?: 'Mua ' . $product->name . ' tươi ngon mọng nước tại FruitNest. Cam kết sạch, giao nhanh trong 2 giờ.')
@section('meta_keywords', $product->name . ', ' . $product->origin . ', trái cây sạch, hoa quả sạch')

@section('schema')
<script type="application/ld+json">
{!! $product->toJsonLd() !!}
</script>
@endsection

@section('content')
<div class="page active" id="page-detail">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <a class="bc-link" href="{{ route('shop.index') }}">Sản phẩm</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">{{ $product->name }}</span>
  </div>
  
  <div style="display:grid;gap:10px;" class="detail-layout">
    <!-- Left Column: Images -->
    <div>
      <div class="detail-img-main {{ $product->bg }}" id="det-main">
        <div class="fruit-ico {{ $product->ic }}" id="det-icon">
          <svg viewBox="0 0 24 24">{!! $product->svg !!}</svg>
        </div>
      </div>
      <div class="detail-thumbs">
        <div class="detail-thumb on" onclick="selectThumb(this, '{{ $product->bg }}')">
          <div class="fruit-ico {{ $product->ic }}"><svg viewBox="0 0 24 24">{!! $product->svg !!}</svg></div>
        </div>
        <div class="detail-thumb" onclick="selectThumb(this, 'bg-g')">
          <div class="fruit-ico fi-g" style="background:var(--n50)"><svg viewBox="0 0 24 24"><path d="M12 22s8-6 8-12A8 8 0 004 10c0 6 8 12 8 12z"/></svg></div>
        </div>
        <div class="detail-thumb" onclick="selectThumb(this, 'bg-g')">
          <div class="fruit-ico fi-g" style="background:#f0f8f0"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/></svg></div>
        </div>
      </div>
    </div>
    
    <!-- Right Column: Product details info -->
    <div class="detail-info-box">
      <div class="detail-tags">
        <span class="dtag dtag-g">{{ $product->t1 }}</span>
        <span class="dtag dtag-r">{{ $product->t2 }}</span>
      </div>
      <h1 class="detail-name">{{ $product->name }}</h1>
      <div class="detail-rating">
        <span style="color:#e07b2a;">
            @for($i=1; $i<=$product->rating_stars; $i++)★@endfor
            @for($i=$product->rating_stars+1; $i<=5; $i++)☆@endfor
        </span>
        {{ $product->rating_value }} · {{ $product->reviews_count }} đánh giá · Đã bán {{ number_format($product->sold_count) }}
      </div>
      
      <div class="detail-price-row">
        <div class="detail-price">{{ number_format($product->price, 0, ',', '.') }}đ</div>
        <span class="detail-unit">/{{ $product->unit }}</span>
        @if($product->original_price)
          <span class="detail-orig">{{ number_format($product->original_price, 0, ',', '.') }}đ</span>
          <span class="detail-save">{{ $product->discount_percentage }}</span>
        @endif
      </div>
      
      <p style="font-size:var(--fs-base);color:var(--n700);line-height:1.6;margin-bottom:8px;">{{ $product->desc }}</p>
      
      <div class="detail-attrs">
        <div class="attr-row"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-4 0v2M8 21V7"/></svg><div><div class="attr-lbl">Quy cách</div><div class="attr-val">{{ $product->pack }}</div></div></div>
        <div class="attr-row"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg><div><div class="attr-lbl">Xuất xứ</div><div class="attr-val">{{ $product->origin }}</div></div></div>
        <div class="attr-row"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><div><div class="attr-lbl">Hạn dùng</div><div class="attr-val">3–5 ngày</div></div></div>
        <div class="attr-row"><svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg><div><div class="attr-lbl">Bảo quản</div><div class="attr-val">2–8°C</div></div></div>
      </div>
      
      <!-- Quantity Ctrl -->
      <div class="qty-row">
        <span class="qty-lbl">Số lượng:</span>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="chQty(-1)">−</button>
          <span class="qty-num" id="qtyNum">1</span>
          <button class="qty-btn" onclick="chQty(1)">+</button>
        </div>
      </div>
      
      <!-- Add & Buy -->
      <div class="detail-actions">
        <button class="btn btn-primary btn-lg" onclick="buyNow()">Mua ngay</button>
        <button class="btn btn-outline btn-lg" onclick="addFromDetail()">+ Thêm giỏ hàng</button>
      </div>
      
      <!-- Mini Trust -->
      <div class="trust-mini">
        <div class="trust-mini-item"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg> Giao trong 2 giờ</div>
        <div class="trust-mini-item"><svg viewBox="0 0 24 24"><path d="M12 22C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10-4.48 10-10 10z"/><path d="M9 12l2 2 4-4"/></svg> Hoàn tiền đảm bảo</div>
        <div class="trust-mini-item"><svg viewBox="0 0 24 24"><path d="M12 22s8-6 8-12A8 8 0 004 10c0 6 8 12 8 12z"/><path d="M9 12l2 2 4-4"/></svg> Không hóa chất</div>
        <div class="trust-mini-item"><svg viewBox="0 0 24 24"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/></svg> Gói quà miễn phí</div>
      </div>
      
      <!-- Tabs Section -->
      <div class="tabs">
        <div class="tab-nav">
          <button class="tab-btn on" onclick="switchTab(this,'t-desc')">Mô tả</button>
          <button class="tab-btn" onclick="switchTab(this,'t-nutri')">Dinh dưỡng</button>
          <button class="tab-btn" onclick="switchTab(this,'t-reviews')">Đánh giá ({{ $product->reviews_count }})</button>
        </div>
        <div class="tab-panel on" id="t-desc">
          <p>{{ $product->desc ?: 'Thông tin mô tả sản phẩm đang được cập nhật.' }}</p>
        </div>
        <div class="tab-panel" id="t-nutri">
          <p>{!! $product->nutrition ?: 'Mỗi 100g chứa nhiều vitamin, chất xơ và khoáng chất cần thiết. Rất tốt cho sức khỏe.' !!}</p>
        </div>
        <div class="tab-panel" id="t-reviews">
          <div class="review-item">
            <div class="rv-head"><span class="rv-name">Nguyễn Thị Hương</span><span class="rv-date">2 ngày trước</span></div>
            <div class="rv-stars">★★★★★</div>
            <p style="font-size:var(--fs-sm);color:var(--n700);margin-top:3px;">Trái cây rất tươi ngon, giao hàng cực nhanh, shipper lịch sự. Chắc chắn sẽ tiếp tục ủng hộ cửa hàng!</p>
          </div>
          <div class="review-item">
            <div class="rv-head"><span class="rv-name">Trần Văn Minh</span><span class="rv-date">5 ngày trước</span></div>
            <div class="rv-stars">★★★★★</div>
            <p style="font-size:var(--fs-sm);color:var(--n700);margin-top:3px;">Đóng gói đẹp và cẩn thận. Trái cây ngọt đậm đà, cả nhà ai cũng khen.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Related Products Grid -->
  <div class="section-block" style="margin-top:20px;">
    <div class="section-head"><div class="section-head-title">Sản phẩm liên quan</div></div>
    <div class="section-body">
      <div class="grid-5" id="related-grid">
        @foreach($relatedProducts as $rel)
          @include('partials.product-card', ['product' => $rel])
        @endforeach
      </div>
    </div>
  </div>
</div>
</div>
@endsection

@section('scripts')
<script>
    let curQty = 1;
    
    function chQty(d) {
        curQty = Math.max(1, curQty + d);
        document.getElementById('qtyNum').textContent = curQty;
    }
    
    function selectThumb(thumb, bgClass) {
        document.querySelectorAll('.detail-thumb').forEach(t => t.classList.remove('on'));
        thumb.classList.add('on');
        
        // Cập nhật background cho ảnh chính dựa vào class ảnh nhỏ
        const main = document.getElementById('det-main');
        main.className = 'detail-img-main ' + bgClass;
    }
    
    function switchTab(btn, tabId) {
        btn.closest('.tab-nav').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('on'));
        btn.classList.add('on');
        
        btn.closest('.tabs').querySelectorAll('.tab-panel').forEach(p => p.classList.remove('on'));
        document.getElementById(tabId).classList.add('on');
    }

    // Gửi yêu cầu AJAX thêm vào giỏ hàng
    function addFromDetail() {
        fetch("{{ route('cart.add') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Accept": "application/json"
            },
            body: JSON.stringify({
                product_id: "{{ $product->id }}",
                quantity: curQty
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

    function buyNow() {
        // Thêm vào giỏ hàng trước, sau đó chuyển hướng đến trang thanh toán
        fetch("{{ route('cart.add') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Accept": "application/json"
            },
            body: JSON.stringify({
                product_id: "{{ $product->id }}",
                quantity: curQty
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = "{{ route('checkout.index') }}";
            } else {
                showToast('Không thể tiến hành mua ngay.');
            }
        });
    }

    // Thêm sản phẩm liên quan vào giỏ hàng bằng AJAX
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
        });
    }
</script>
@endsection
