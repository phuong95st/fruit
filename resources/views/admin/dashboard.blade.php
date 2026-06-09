@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Tổng quan hôm nay</div>
    <h2>Dashboard</h2>
  </div>
  <div class="ph-right">
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
@endsection

@push('scripts')
<script>
  document.addEventListener("DOMContentLoaded", function() {
    renderRevenueChart();
    renderCategoryChart();
  });

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