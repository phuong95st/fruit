@extends('admin.layouts.app')

@section('title', 'Sản phẩm ' . $product->name)
@section('page_title', 'Sản phẩm / Chi tiết')

@section('content')
@php
  // Tính toán tồn kho mô phỏng giống với danh sách
  $stock = 250 - ($product->sold_count % 250);
  if ($product->code === 'orange') $stock = 245;
  if ($product->code === 'strawberry') $stock = 42;
  if ($product->code === 'kiwi') $stock = 5;
  if ($product->code === 'grape') $stock = 0;
  
  $percent = min(100, max(0, ($stock / 300) * 100));
  
  $fillClass = 'sf-high';
  if ($stock < 15) $fillClass = 'sf-low';
  elseif ($stock < 50) $fillClass = 'sf-mid';
@endphp

<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Sản phẩm / Chi tiết</div>
    <h2 id="pd-title">{{ $product->name }}</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.products') }}'">← Quay lại</button>
    <button class="btn btn-ghost btn-sm" onclick="showToast('Đã sao chép link sản phẩm','info')">🔗 Copy link</button>
    <button class="btn btn-primary btn-sm" onclick="location.href='{{ route('admin.products.edit', $product->id) }}'">✏️ Chỉnh sửa</button>
  </div>
</div>

<div class="pd-layout">
  <!-- LEFT -->
  <div class="pd-main">
    <!-- Overview -->
    <div class="card mb-16">
      <div class="card-body">
        <div class="pd-overview">
          <div class="pd-img-area {{ $product->image_url ? '' : ($product->bg ?? 'bg-y') }}" style="width:120px;height:120px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:3.5rem;overflow:hidden;">
            @if($product->image_url)
              <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width:100%; height:100%; object-fit:cover;">
            @elseif($product->svg)
              <div style="width:70px;height:70px;">{!! $product->svg !!}</div>
            @else
              🍊
            @endif
          </div>
          <div class="pd-overview-info">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
              <span class="badge badge-info">{{ $product->t1 }}</span>
              <span class="badge badge-success">{{ $stock > 0 ? '● Đang bán' : '✕ Hết hàng' }}</span>
              @if($product->t2)
                <span class="badge badge-warning">⭐ {{ $product->t2 }}</span>
              @endif
            </div>
            <div style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--g-dark);margin-bottom:4px;">{{ $product->name }}</div>
            <div style="font-size:.8rem;color:var(--text-3);margin-bottom:12px;">
              SKU: FN-{{ Str::upper(Str::substr($product->code, 0, 3)) }}-00{{ $product->id }} · Xuất xứ: {{ $product->origin }} · Quy cách: {{ $product->pack }}
            </div>
            <div class="pd-price-row">
              <div class="pd-price-main">{{ number_format($product->price, 0, ',', '.') }}đ<span style="font-size:.8rem;font-weight:400;color:var(--text-3)">/{{ $product->unit }}</span></div>
              @if($product->original_price)
                <div style="text-decoration:line-through;color:var(--text-3);font-size:.9rem;">{{ number_format($product->original_price, 0, ',', '.') }}đ</div>
                <span class="badge badge-danger">–{{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}%</span>
              @endif
            </div>
            <div class="pd-quick-stats">
              <div class="pqs-item"><div class="pqs-val">{{ number_format($product->sold_count) }}</div><div class="pqs-label">Đã bán ({{ $product->unit }})</div></div>
              <div class="pqs-item"><div class="pqs-val">{{ $stock }}</div><div class="pqs-label">Tồn kho</div></div>
              <div class="pqs-item"><div class="pqs-val">{{ number_format(($product->sold_count * $product->price) / 1000000, 1) }}tr</div><div class="pqs-label">Doanh thu</div></div>
              <div class="pqs-item"><div class="pqs-val">{{ number_format($product->rating_value, 1) }} ★</div><div class="pqs-label">Đánh giá</div></div>
            </div>
          </div>
        </div>
      </div>

    <!-- Media Gallery (Sub-images and Video) -->
    <div class="card mb-16">
      <div class="card-header">
        <div class="card-title">🖼️ Thư viện ảnh & video chi tiết</div>
      </div>
      <div class="card-body">
        <div style="display:flex; flex-direction:column; gap:16px;">
          <!-- Sub images list -->
          @php $subImages = $product->images ?? []; @endphp
          @if(count($subImages) > 0)
            <div>
              <div style="font-size:.85rem; font-weight:600; color:var(--text-2); margin-bottom:8px;">Hình ảnh phụ (tối đa 5 ảnh)</div>
              <div style="display:flex; flex-wrap:wrap; gap:12px;">
                @foreach($product->images_urls as $subUrl)
                  <div style="width:100px; height:100px; border-radius:var(--r-md); border:1px solid var(--border); overflow:hidden; cursor:pointer;" onclick="window.open('{{ $subUrl }}', '_blank')">
                    <img src="{{ $subUrl }}" alt="Ảnh phụ" style="width:100%; height:100%; object-fit:cover; transition: transform .2s;" onmouseenter="this.style.transform='scale(1.05)'" onmouseleave="this.style.transform='scale(1)'">
                  </div>
                @endforeach
              </div>
            </div>
          @else
            <div style="font-size:.82rem; color:var(--text-3);">Không có ảnh phụ cho sản phẩm này.</div>
          @endif

          <!-- Video section -->
          @if($product->video)
            <div style="border-top:1px solid var(--border); padding-top:16px;">
              <div style="font-size:.85rem; font-weight:600; color:var(--text-2); margin-bottom:8px;">Video sản phẩm</div>
              <div style="max-width:400px; border-radius:var(--r-md); overflow:hidden; border:1px solid var(--border); background:#000;">
                @if($product->is_youtube)
                  {{-- YouTube embed --}}
                  <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden;">
                    <iframe src="{{ $product->video_embed_url }}?rel=0"
                      style="position:absolute; top:0; left:0; width:100%; height:100%;"
                      frameborder="0" allowfullscreen loading="lazy"></iframe>
                  </div>
                  <div style="padding:8px 12px; background:var(--bg); font-size:.78rem; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:.7rem; background:rgba(255,0,0,.1); color:#c00; border-radius:50px; padding:2px 8px; font-weight:700;">▶ YouTube</span>
                    <a href="{{ $product->video }}" target="_blank" style="color:var(--g-dark); text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;">{{ $product->video }}</a>
                  </div>
                @else
                  {{-- Direct video upload --}}
                  <video src="{{ $product->video_url }}" controls style="width:100%; display:block; max-height:250px;"></video>
                  <div style="padding:8px 12px; background:var(--bg); font-size:.78rem; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:.7rem; background:rgba(76,175,128,.1); color:var(--g-mid); border-radius:50px; padding:2px 8px; font-weight:700;">📁 File</span>
                    <span style="color:var(--text-2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;">{{ basename($product->video) }}</span>
                  </div>
                @endif
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Sales chart -->
    <div class="card mb-16">
      <div class="card-header">
        <div class="card-title">📈 Doanh số 30 ngày gần đây</div>
        <div class="flex gap-8">
          <span class="badge badge-success">● Doanh số</span>
        </div>
      </div>
      <div class="card-body">
        <div class="chart-wrap" style="height:180px;"><canvas id="productSalesChart"></canvas></div>
      </div>
    </div>

    <!-- Reviews -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">⭐ Đánh giá từ khách hàng</div>
        <div style="display:flex;align-items:center;gap:6px;">
          <span style="font-size:1.1rem;font-weight:700;color:var(--g-dark);">{{ number_format($product->rating_value, 1) }}</span>
          <span style="color:var(--yellow);">
            @for($i=1; $i<=floor($product->rating_value); $i++)★@endfor
            @for($i=floor($product->rating_value)+1; $i<=5; $i++)☆@endfor
          </span>
          <span style="font-size:.78rem;color:var(--text-3);">({{ $product->reviews_count }} đánh giá)</span>
        </div>
      </div>
      <div class="card-body" style="padding-top:8px;">
        <div class="review-row">
          <div class="rr-avatar" style="background:linear-gradient(135deg,var(--g-light),var(--g-mid));">H</div>
          <div class="rr-content">
            <div class="rr-top"><span class="rr-name">Nguyễn Hương</span><span style="color:var(--yellow);font-size:.85rem;">★★★★★</span><span class="rr-date">2 ngày trước</span></div>
            <div class="rr-text">Sản phẩm rất tươi ngon, đúng chuẩn thương hiệu, giao hàng nhanh, chất lượng xứng đáng giá tiền. Sẽ tiếp tục ủng hộ.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="pd-side">
    <!-- Stock -->
    <div class="card mb-12">
      <div class="card-header"><div class="card-title">🏪 Tồn kho</div><button class="btn btn-orange btn-sm" onclick="location.href='{{ route('admin.inventory.stock-in') }}'">+ Nhập kho</button></div>
      <div class="card-body">
        <div class="pd-stock-big">{{ $stock }} {{ $product->unit }}</div>
        <div class="stock-bar" style="margin-bottom:6px;height:8px;border-radius:4px;"><div class="stock-fill {{ $fillClass }}" style="width:{{ $percent }}%;height:8px;"></div></div>
        <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-3);margin-bottom:14px;">
          <span>Mức tối thiểu: 20 {{ $product->unit }}</span>
          @if($stock >= 20)
            <span class="text-success">Đủ hàng ✓</span>
          @else
            <span class="text-danger" style="color:var(--red);">Cần nhập hàng! ⚠️</span>
          @endif
        </div>
        <div class="od-info-row"><span>Nhập lần cuối</span><span>25/01/2026</span></div>
        <div class="od-info-row"><span>Giá nhập kho</span><span>{{ number_format($product->price * 0.7, 0, ',', '.') }}đ/{{ $product->unit }}</span></div>
      </div>
    </div>

    <!-- Attributes -->
    <div class="card mb-12">
      <div class="card-header"><div class="card-title">📋 Thông tin chi tiết</div></div>
      <div class="card-body">
        <div class="od-info-row"><span>Quy cách</span><span>{{ $product->pack }}</span></div>
        <div class="od-info-row"><span>Bảo quản</span><span>Ngăn mát tủ lạnh</span></div>
        <div class="od-info-row"><span>Mô tả ngắn</span><span>{{ Str::limit($product->desc, 50) }}</span></div>
      </div>
    </div>

    <!-- Toggle status -->
    <div class="card">
      <div class="card-header"><div class="card-title">⚙️ Trạng thái</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <div class="flex justify-between items-center"><div><div style="font-size:.85rem;font-weight:600;">Hiển thị trên web</div></div><button class="toggle on" onclick="this.classList.toggle('on');showToast('Đã cập nhật trạng thái','success')"></button></div>
        <div class="flex justify-between items-center"><div><div style="font-size:.85rem;font-weight:600;">Nổi bật trang chủ</div></div><button class="toggle on" onclick="this.classList.toggle('on');showToast('Đã cập nhật','success')"></button></div>
        <div class="flex justify-between items-center"><div><div style="font-size:.85rem;font-weight:600;">Bán trong ngày</div></div><button id="toggle-daily-btn" class="toggle {{ $product->is_daily ? 'on' : '' }}" onclick="toggleDaily({{ $product->id }})"></button></div>
        <button class="btn btn-danger w-full" style="margin-top:4px;" onclick="openModal('modal-delete-product')">🗑️ Xóa sản phẩm</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('modals')
<div class="modal-overlay" id="modal-delete-product">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🗑️ Xóa sản phẩm</div>
      <button class="modal-close" onclick="closeModal('modal-delete-product')">✕</button>
    </div>
    <div style="text-align:center;padding:20px 0;">
      <div style="font-size:3rem;margin-bottom:12px;">⚠️</div>
      <div style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--g-dark);margin-bottom:6px;">{{ $product->name }}</div>
      <div style="font-size:.82rem;color:var(--text-3);">SKU: FN-{{ Str::upper(Str::substr($product->code, 0, 3)) }}-00{{ $product->id }}</div>
    </div>
    <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:var(--r-md);padding:12px;margin-bottom:16px;font-size:.82rem;color:var(--red);">
      ⚠️ Sản phẩm sẽ bị ẩn khỏi hệ thống (xóa mềm). Có thể khôi phục lại trong tương lai.
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modal-delete-product')">Hủy bỏ</button>
      <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">🗑️ Xóa sản phẩm</button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener("DOMContentLoaded", function() {
    renderProductCharts();
  });

  function toggleDaily(productId) {
    const btn = document.getElementById('toggle-daily-btn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    btn.disabled = true;
    fetch(`/admin/products/${productId}/toggle-daily`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(res => res.json())
    .then(data => {
      btn.disabled = false;
      if (data.success) {
        if (data.is_daily) {
          btn.classList.add('on');
          showToast('Đã thiết định sản phẩm bán trong ngày!', 'success');
        } else {
          btn.classList.remove('on');
          showToast('Đã hủy thiết định sản phẩm bán trong ngày!', 'info');
        }
      } else {
        showToast('Có lỗi xảy ra, vui lòng thử lại', 'error');
      }
    })
    .catch(err => {
      btn.disabled = false;
      showToast('Có lỗi kết nối, vui lòng thử lại', 'error');
      console.error(err);
    });
  }

  function renderProductCharts() {
    destroyChart('productSales');
    var ctx = document.getElementById('productSalesChart');
    if (!ctx) return;
    var days = [];
    for (var i = 1; i <= 30; i++) days.push(i);
    // Tạo doanh số ngẫu nhiên mô phỏng cho sản phẩm dựa trên sold_count
    var soldAvg = Math.round({{ $product->sold_count }} / 30);
    var data = [];
    for (var i = 1; i <= 30; i++) {
        data.push(Math.round(soldAvg * (0.6 + Math.random() * 0.8)));
    }
    
    charts['productSales'] = new Chart(ctx, {
      type: 'line',
      data: { 
        labels: days,
        datasets: [{ 
          data: data, 
          borderColor: '#4caf80', 
          backgroundColor: 'rgba(76,175,128,.08)',
          borderWidth: 2.5, 
          tension: 0.4, 
          fill: true, 
          pointRadius: 0,
          pointHoverRadius: 5, 
          pointBackgroundColor: '#4caf80' 
        }] 
      },
      options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
          x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 10 } },
          y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 10 } } } 
        } 
      }
    });
  }
</script>
@endpush