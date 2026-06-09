@extends('admin.layouts.app')

@section('title', 'Phân tích doanh thu')
@section('page_title', 'Phân tích')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Báo cáo</div>
    <h2>Phân tích doanh thu</h2>
  </div>
  <div class="ph-right">
    <select style="border:1.5px solid var(--border);border-radius:50px;padding:8px 14px;font-size:.82rem;outline:none;background:white;">
      <option>Tháng này</option>
      <option>Tháng trước</option>
    </select>
    <button class="btn btn-primary btn-sm" onclick="showToast('Đang xuất báo cáo tháng','success')">⬇️ Xuất PDF</button>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card sc-green">
    <div class="stat-top"><div class="stat-icon si-green">💰</div><div class="stat-trend trend-up">↑ 18.4%</div></div>
    <div class="stat-value">
      @php
        $totalMonthRevenue = \App\Models\Order::where('status', '!=', 'Đã hủy')->sum('total_price');
      @endphp
      {{ number_format($totalMonthRevenue / 1000000, 1) }}tr
    </div>
    <div class="stat-label">Tổng doanh thu hệ thống</div>
    <div class="stat-sub">Đã trừ các đơn hàng bị hủy</div>
  </div>
  <div class="stat-card sc-orange">
    <div class="stat-top"><div class="stat-icon si-orange">🛒</div><div class="stat-trend trend-up">↑ 9.2%</div></div>
    <div class="stat-value">{{ number_format(\App\Models\Order::count()) }}</div>
    <div class="stat-label">Tổng đơn hàng</div>
    <div class="stat-sub">
      Trung bình: 
      @php
        $totalOrders = \App\Models\Order::count();
        $avgOrder = $totalOrders > 0 ? $totalMonthRevenue / $totalOrders : 0;
      @endphp
      {{ number_format($avgOrder / 1000, 0) }}k đ/đơn
    </div>
  </div>
  <div class="stat-card sc-blue">
    <div class="stat-top"><div class="stat-icon si-blue">👤</div><div class="stat-trend trend-up">↑ 14.1%</div></div>
    <div class="stat-value">{{ number_format(\App\Models\Customer::count()) }}</div>
    <div class="stat-label">Tổng số khách hàng</div>
    <div class="stat-sub">Tỉ lệ VIP: {{ round((\App\Models\Customer::where('level', 'VIP')->count() / max(1, \App\Models\Customer::count())) * 100) }}%</div>
  </div>
  <div class="stat-card sc-purple">
    <div class="stat-top"><div class="stat-icon si-purple">💳</div><div class="stat-trend trend-up">↑ 4.8%</div></div>
    <div class="stat-value">{{ number_format($avgOrder / 1000, 0) }}k</div>
    <div class="stat-label">Giá trị đơn TB</div>
    <div class="stat-sub">Tính trên tất cả đơn hàng</div>
  </div>
</div>

<div class="charts-row analytics-charts">
  <div class="card">
    <div class="card-header">
      <div class="card-title">📊 Doanh thu theo ngày — 31 ngày qua</div>
    </div>
    <div class="card-body">
      <div class="chart-wrap" style="height:240px;">
        <canvas id="dailyChart"></canvas>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <div class="card-title">📱 Kênh đặt hàng</div>
    </div>
    <div class="card-body">
      <div class="chart-wrap" style="height:180px;">
        <canvas id="channelChart"></canvas>
      </div>
      <div class="donut-legend mt-16">
        <div class="dl-item"><div class="dl-dot" style="background:#4caf80"></div><div class="dl-name">Mobile App</div><div class="dl-val">52%</div></div>
        <div class="dl-item"><div class="dl-dot" style="background:#f06a3b"></div><div class="dl-name">Web</div><div class="dl-val">33%</div></div>
        <div class="dl-item"><div class="dl-dot" style="background:#f5c518"></div><div class="dl-name">Điện thoại</div><div class="dl-val">15%</div></div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener("DOMContentLoaded", function() {
    renderDailyChart();
    renderChannelChart();
  });

  function renderDailyChart() {
    destroyChart('daily');
    var ctx = document.getElementById('dailyChart');
    if (!ctx) return;
    
    var days = {!! json_encode($days) !!};
    var data = {!! json_encode($revenueData) !!};

    charts['daily'] = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: days,
        datasets: [{ 
          label: 'Doanh thu (tr đ)', 
          data: data,
          backgroundColor: data.map(function(v){ return v >= 0.5 ? 'rgba(76,175,128,.85)' : 'rgba(76,175,128,.35)'; }),
          borderRadius: 4, 
          borderSkipped: false 
        }]
      },
      options: {
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0 } },
          y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } } }
        }
      }
    });
  }

  function renderChannelChart() {
    destroyChart('channel');
    var ctx = document.getElementById('channelChart');
    if (!ctx) return;
    charts['channel'] = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Mobile App','Web','Điện thoại'],
        datasets: [{ 
          data: [52,33,15],
          backgroundColor: ['#4caf80','#f06a3b','#f5c518'],
          borderWidth: 0, 
          hoverOffset: 6 
        }]
      },
      options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }, 
        cutout: '60%' 
      }
    });
  }
</script>
@endpush