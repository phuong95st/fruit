@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Tổng quan hôm nay</div>
    <h2>Dashboard</h2>
  </div>
  <div class="ph-right flex gap-12" style="align-items:center;">
    <button class="btn btn-sm" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;font-weight:600;box-shadow:0 4px 12px rgba(99,102,241,.3);" onclick="openAiModal()">
      🤖 AI Phân Tích Giá Thị Trường (Gemini)
    </button>
    <select style="border:1.5px solid var(--border);border-radius:50px;padding:8px 14px;font-size:.82rem;outline:none;background:white;color:var(--text-2);">
      <option>Hôm nay</option>
      <option>7 ngày qua</option>
      <option>30 ngày qua</option>
      <option>Tháng này</option>
    </select>
    <button class="btn btn-primary btn-sm" onclick="showToast('Đã xuất báo cáo PDF','success')">⬇️ Xuất báo cáo</button>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card sc-green">
    <div class="stat-top">
      <div class="stat-icon si-green">💰</div>
      <div class="stat-trend trend-up">↑ 12.5%</div>
    </div>
    <div class="stat-value">
      @if($todayRevenue >= 1000000)
        {{ number_format($todayRevenue / 1000000, 1) }}tr
      @else
        {{ number_format($todayRevenue, 0, ',', '.') }}đ
      @endif
    </div>
    <div class="stat-label">Doanh thu hôm nay</div>
    <div class="stat-sub">Đã trừ các đơn hàng bị hủy</div>
  </div>
  <div class="stat-card sc-orange">
    <div class="stat-top">
      <div class="stat-icon si-orange">📦</div>
      <div class="stat-trend trend-up">↑ 8.3%</div>
    </div>
    <div class="stat-value">{{ $newOrdersCount }}</div>
    <div class="stat-label">Đơn hàng mới</div>
    <div class="stat-sub">{{ \App\Models\Order::where('status', 'Chuẩn bị')->count() }} đơn đang chuẩn bị</div>
  </div>
  <div class="stat-card sc-blue">
    <div class="stat-top">
      <div class="stat-icon si-blue">👥</div>
      <div class="stat-trend trend-up">↑ 5.1%</div>
    </div>
    <div class="stat-value">{{ number_format($totalCustomers) }}</div>
    <div class="stat-label">Khách hàng</div>
    <div class="stat-sub">Tổng số khách hàng đăng ký</div>
  </div>
  <div class="stat-card sc-purple">
    <div class="stat-top">
      <div class="stat-icon si-purple">🔄</div>
      <div class="stat-trend trend-down">↓ 2.1%</div>
    </div>
    <div class="stat-value">{{ $returnRate }}%</div>
    <div class="stat-label">Tỉ lệ hoàn hàng</div>
    <div class="stat-sub">{{ \App\Models\Order::where('status', 'Hoàn hàng')->count() }} đơn hoàn trong tuần này</div>
  </div>
</div>

<!-- Charts Row -->
<div class="charts-row">
  <div class="card">
    <div class="card-header">
      <div class="card-title">📈 Doanh thu 7 ngày qua</div>
      <div class="flex gap-8">
        <span class="badge badge-success">● Doanh thu</span>
        <span class="badge badge-info">● Đơn hàng</span>
      </div>
    </div>
    <div class="card-body">
      <div class="chart-wrap">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <div class="card-title">🍰 Danh mục bán chạy</div>
    </div>
    <div class="card-body">
      <div class="chart-wrap" style="height:180px;">
        <canvas id="categoryChart"></canvas>
      </div>
      <div class="donut-legend">
        <div class="dl-item"><div class="dl-dot" style="background:#4caf80"></div><div class="dl-name">Tươi lẻ</div><div class="dl-val">38%</div></div>
        <div class="dl-item"><div class="dl-dot" style="background:#f06a3b"></div><div class="dl-name">Nhập khẩu</div><div class="dl-val">31%</div></div>
        <div class="dl-item"><div class="dl-dot" style="background:#f5c518"></div><div class="dl-name">Giỏ quà</div><div class="dl-val">21%</div></div>
        <div class="dl-item"><div class="dl-dot" style="background:#7c3aed"></div><div class="dl-name">Combo</div><div class="dl-val">10%</div></div>
      </div>
    </div>
  </div>
</div>

<!-- Bottom Row -->
<div class="dashboard-bottom-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
  <!-- Recent Orders -->
  <div class="card" style="grid-column:span 1;">
    <div class="card-header">
      <div class="card-title">🕒 Đơn hàng gần đây</div>
      <button class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.orders') }}'">Xem tất cả</button>
    </div>
    <div class="card-body" style="padding-top:8px;">
      @foreach($recentOrders as $order)
        <div class="order-row-mini" style="cursor:pointer;" onclick="location.href='{{ route('admin.orders.detail', $order->id) }}'">
          <div class="orm-icon" style="background:linear-gradient(135deg,#d0f0dc,#a8dcb8)">📦</div>
          <div class="orm-info">
            <div class="orm-name">#{{ $order->order_code }} · {{ $order->customer_name }}</div>
            <div class="orm-time">{{ $order->created_at->format('H:i') }} · 
              <span class="text-warning">{{ $order->status }}</span>
            </div>
          </div>
          <div class="orm-price">{{ number_format($order->total_price / 1000, 0) }}k</div>
        </div>
      @endforeach
    </div>
  </div>

  <!-- Top Products -->
  <div class="card" style="grid-column:span 1;">
    <div class="card-header">
      <div class="card-title">🏆 Sản phẩm bán chạy</div>
    </div>
    <div class="card-body" style="padding-top:8px;">
      @php
        $ranks = ['gold', 'silver', 'bronze', '', ''];
      @endphp
      @foreach($topProducts as $index => $product)
        <div class="top-product-item" style="cursor:pointer;" onclick="location.href='{{ route('admin.products.detail', $product->id) }}'">
          <div class="tpi-rank {{ $ranks[$index] ?? '' }}">{{ $index + 1 }}</div>
          <div class="tpi-icon {{ $product->bg ?? 'bg-g' }}">{!! $product->svg !!}</div>
          <div class="tpi-info">
            <div class="tpi-name">{{ $product->name }}</div>
            <div class="tpi-sold">Đã bán: {{ number_format($product->sold_count) }} {{ $product->unit }}</div>
            <div class="tpi-bar-wrap">
              <div class="tpi-bar" style="width: {{ min(100, max(20, ($product->sold_count / 3500) * 100)) }}%"></div>
            </div>
          </div>
          <div class="tpi-revenue">{{ number_format(($product->sold_count * $product->price) / 1000000, 1) }}tr</div>
        </div>
      @endforeach
    </div>
  </div>

  <!-- Activity Feed -->
  <div class="card" style="grid-column:span 1;">
    <div class="card-header">
      <div class="card-title">⚡ Hoạt động mới nhất</div>
    </div>
    <div class="card-body" style="padding-top:8px;">
      @foreach($activities as $index => $act)
        <div class="activity-item">
          <div class="ai-dot-wrap">
            <div class="ai-dot" style="background:{{ $act['dot_color'] }}"></div>
            @if($index < count($activities) - 1)
              <div class="ai-line"></div>
            @endif
          </div>
          <div class="ai-content">
            <div class="ai-text">{!! $act['text'] !!}</div>
            <div class="ai-time">{{ $act['time'] }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>

<!-- Modal Phân Tích Giá Gemini AI -->
<div id="aiModal" class="modal-overlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(15,23,42,.7);z-index:99999;backdrop-filter:blur(5px);padding:20px;overflow-y:auto;box-sizing:border-box;">
  <div class="modal-container" style="max-width:980px;margin:20px auto;background:white;border-radius:16px;box-shadow:0 25px 50px -12px rgba(0,0,0,.3);overflow:hidden;animation:modalSlide .3s ease;">
    <!-- Modal Header -->
    <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:20px 24px;color:white;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h3 style="margin:0;font-size:1.25rem;font-weight:700;display:flex;align-items:center;gap:8px;">
          🤖 Báo Cáo Phân Tích Giá Gemini AI (Sản Phẩm Đơn Lẻ)
        </h3>
        <p style="margin:4px 0 0 0;font-size:0.85rem;opacity:.95;" id="aiAnalyzedTime">Phân tích giá thị trường từ Fuji Fruit, Tâm Fruit, Deli Fruit</p>
      </div>
      <div class="flex gap-8" style="align-items:center;">
        <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:white;border:1px solid rgba(255,255,255,.3);" onclick="runAiAnalysisNow()" id="btnRunAiNow">
          ⚡ Chạy phân tích AI ngay
        </button>
        <button style="background:none;border:none;color:white;font-size:1.8rem;cursor:pointer;line-height:1;padding:0 4px;" onclick="closeAiModal()">✕</button>
      </div>
    </div>

    <!-- Modal Body -->
    <div style="padding:24px;max-height:70vh;overflow-y:auto;">
      <div id="aiLoading" style="display:none;text-align:center;padding:40px;">
        <div class="spinner" style="width:40px;height:40px;border:4px solid #f3f3f3;border-top:4px solid #6366f1;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 16px;"></div>
        <p style="color:var(--text-2);font-weight:600;font-size:1rem;">Đang kết nối Gemini AI & phân tích dữ liệu đối thủ thị trường...</p>
      </div>

      <div id="aiNoData" style="display:none;text-align:center;padding:40px;color:var(--text-2);">
        <p style="font-size:1.1rem;margin-bottom:16px;">Chưa có dữ liệu phân tích giá AI cho hôm nay.</p>
        <button class="btn btn-primary" onclick="runAiAnalysisNow()">Chạy phân tích AI ngay bây giờ</button>
      </div>

      <div id="aiTableWrap" style="display:none;">
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#334155;display:flex;align-items:center;justify-content:space-between;">
          <span>💡 <b>Hướng dẫn:</b> Bạn có thể trực tiếp sửa số tiền ở cột <b>Giá AI đề xuất</b> bên dưới trước khi bấm <b>Áp dụng giá mới vào Database</b>.</span>
          <span class="badge badge-info" id="aiTotalProducts" style="font-size:0.8rem;padding:4px 10px;">0 sản phẩm</span>
        </div>

        <div style="overflow-x:auto;">
          <table class="table" style="width:100%;border-collapse:collapse;font-size:.88rem;">
            <thead>
              <tr style="background:#f1f5f9;color:#475569;text-align:left;">
                <th style="padding:10px 12px;border-bottom:2px solid #cbd5e1;">Mã SP</th>
                <th style="padding:10px 12px;border-bottom:2px solid #cbd5e1;">Tên sản phẩm đơn lẻ</th>
                <th style="padding:10px 12px;border-bottom:2px solid #cbd5e1;text-align:right;">Giá hiện tại</th>
                <th style="padding:10px 12px;border-bottom:2px solid #cbd5e1;text-align:center;">Giá đối thủ</th>
                <th style="padding:10px 12px;border-bottom:2px solid #cbd5e1;width:170px;">Giá AI đề xuất (Cho phép sửa)</th>
                <th style="padding:10px 12px;border-bottom:2px solid #cbd5e1;">Lý do phân tích Gemini AI</th>
              </tr>
            </thead>
            <tbody id="aiTableBody">
              <!-- Dữ liệu JS tự render -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <div style="background:#f8fafc;padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
      <button class="btn btn-ghost" onclick="closeAiModal()">Hủy bỏ</button>
      <button class="btn btn-primary" style="background:linear-gradient(135deg,#10b981,#059669);border:none;box-shadow:0 4px 12px rgba(16,185,129,.3);" onclick="applyAiPricesToDb()" id="btnApplyAiPrices">
        ✅ Áp dụng giá mới vào Database
      </button>
    </div>
  </div>
</div>

<style>
  @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
  @keyframes modalSlide { from { transform: translateY(-20px); opacity:0; } to { transform: translateY(0); opacity:1; } }
</style>
@endsection

@push('scripts')
<script>
  var currentAiData = [];

  document.addEventListener("DOMContentLoaded", function() {
    renderRevenueChart();
    renderCategoryChart();
  });

  function openAiModal() {
    var modal = document.getElementById('aiModal');
    if (modal) {
      modal.style.display = 'flex';
      modal.classList.add('open');
    }
    loadAiPriceAnalysis();
  }

  function closeAiModal() {
    var modal = document.getElementById('aiModal');
    if (modal) {
      modal.style.display = 'none';
      modal.classList.remove('open');
    }
  }

  function loadAiPriceAnalysis() {
    document.getElementById('aiLoading').style.display = 'block';
    document.getElementById('aiNoData').style.display = 'none';
    document.getElementById('aiTableWrap').style.display = 'none';

    fetch('{{ route("admin.ai-price-analysis") }}')
      .then(res => res.json())
      .then(res => {
        document.getElementById('aiLoading').style.display = 'none';
        if (res.success && res.data && res.data.items && res.data.items.length > 0) {
          renderAiTable(res.data);
        } else {
          document.getElementById('aiNoData').style.display = 'block';
        }
      })
      .catch(err => {
        document.getElementById('aiLoading').style.display = 'none';
        document.getElementById('aiNoData').style.display = 'block';
      });
  }

  function runAiAnalysisNow() {
    document.getElementById('aiLoading').style.display = 'block';
    document.getElementById('aiNoData').style.display = 'none';
    document.getElementById('aiTableWrap').style.display = 'none';

    fetch('{{ route("admin.run-ai-price-analysis") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(res => {
      document.getElementById('aiLoading').style.display = 'none';
      if (res.success && res.data) {
        showToast(res.message, 'success');
        renderAiTable(res.data);
      } else {
        showToast(res.message || 'Chạy phân tích AI thất bại', 'error');
        document.getElementById('aiNoData').style.display = 'block';
      }
    })
    .catch(err => {
      document.getElementById('aiLoading').style.display = 'none';
      showToast('Lỗi kết nối tới server phân tích AI', 'error');
    });
  }

  function renderAiTable(data) {
    currentAiData = data.items || [];
    document.getElementById('aiAnalyzedTime').innerText = 'Phân tích lúc: ' + (data.analyzed_at || 'Mới nhất');
    document.getElementById('aiTotalProducts').innerText = currentAiData.length + ' sản phẩm đơn lẻ';

    var tbody = document.getElementById('aiTableBody');
    tbody.innerHTML = '';

    currentAiData.forEach(function(item, idx) {
      var competitorRange = '-';
      if (item.competitor_min_price > 0) {
        competitorRange = formatMoney(item.competitor_min_price) + ' - ' + formatMoney(item.competitor_max_price);
      }

      var tr = document.createElement('tr');
      tr.style.borderBottom = '1px solid #e2e8f0';

      tr.innerHTML = `
        <td style="padding:10px 12px;"><code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;color:#475569;">${item.code}</code></td>
        <td style="padding:10px 12px;font-weight:600;color:#1e293b;">${item.name}</td>
        <td style="padding:10px 12px;text-align:right;color:#64748b;">${formatMoney(item.current_price)}đ</td>
        <td style="padding:10px 12px;text-align:center;font-size:0.82rem;color:#4f46e5;">${competitorRange}</td>
        <td style="padding:10px 12px;">
          <input type="number" id="aiPriceInput_${item.id}" class="form-control form-control-sm" value="${item.suggested_price}" style="font-weight:700;color:#059669;border:1.5px solid #10b981;border-radius:6px;padding:4px 8px;width:100%;box-sizing:border-box;" />
        </td>
        <td style="padding:10px 12px;font-size:0.82rem;color:#475569;">${item.reasoning}</td>
      `;
      tbody.appendChild(tr);
    });

    document.getElementById('aiTableWrap').style.display = 'block';
  }

  function applyAiPricesToDb() {
    if (!currentAiData || currentAiData.length === 0) {
      showToast('Không có sản phẩm nào để áp dụng giá', 'warning');
      return;
    }

    var payload = [];
    currentAiData.forEach(function(item) {
      var inputEl = document.getElementById('aiPriceInput_' + item.id);
      var newPrice = inputEl ? parseFloat(inputEl.value) : item.suggested_price;
      if (newPrice > 0) {
        payload.push({
          id: item.id,
          new_price: newPrice
        });
      }
    });

    if (payload.length === 0) {
      showToast('Vui lòng nhập giá hợp lệ', 'warning');
      return;
    }

    var btn = document.getElementById('btnApplyAiPrices');
    btn.disabled = true;
    btn.innerText = '⏳ Đang cập nhật Database...';

    fetch('{{ route("admin.apply-ai-prices") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ prices: payload })
    })
    .then(res => res.json())
    .then(res => {
      btn.disabled = false;
      btn.innerText = '✅ Áp dụng giá mới vào Database';

      if (res.success) {
        showToast(res.message, 'success');
        closeAiModal();
        setTimeout(function() { location.reload(); }, 1500);
      } else {
        showToast(res.message || 'Cập nhật thất bại', 'error');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerText = '✅ Áp dụng giá mới vào Database';
      showToast('Lỗi khi gửi dữ liệu lên server', 'error');
    });
  }

  function formatMoney(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
  }

  function renderRevenueChart() {
    destroyChart('revenue');
    var ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    
    var labels = {!! json_encode($labels) !!};
    var revenueData = {!! json_encode($revenueData) !!};
    var orderData = {!! json_encode($orderData) !!};

    charts['revenue'] = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          { 
            label: 'Doanh thu (tr đ)', 
            data: revenueData,
            borderColor: '#4caf80', 
            backgroundColor: 'rgba(76,175,128,.08)',
            borderWidth: 2.5, 
            tension: 0.4, 
            fill: true,
            pointBackgroundColor: '#4caf80', 
            pointRadius: 4, 
            pointHoverRadius: 6 
          },
          { 
            label: 'Đơn hàng', 
            data: orderData,
            borderColor: '#f06a3b', 
            backgroundColor: 'transparent',
            borderWidth: 2, 
            tension: 0.4, 
            borderDash: [5,3],
            pointBackgroundColor: '#f06a3b', 
            pointRadius: 3, 
            yAxisID: 'y2' 
          }
        ]
      },
      options: {
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 11 } } },
          y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } } },
          y2: { display: false, position: 'right' }
        }
      }
    });
  }

  function renderCategoryChart() {
    destroyChart('category');
    var ctx = document.getElementById('categoryChart');
    if (!ctx) return;
    charts['category'] = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Tươi lẻ','Nhập khẩu','Giỏ quà','Combo'],
        datasets: [{ 
          data: [38, 31, 21, 10],
          backgroundColor: ['#4caf80','#f06a3b','#f5c518','#7c3aed'],
          borderWidth: 0, 
          hoverOffset: 6 
        }]
      },
      options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }, 
        cutout: '65%' 
      }
    });
  }
</script>
@endpush