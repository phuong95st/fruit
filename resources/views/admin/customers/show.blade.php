@extends('admin.layouts.app')

@section('title', 'Hồ sơ khách hàng ' . $customer->name)
@section('page_title', 'Khách hàng / Hồ sơ')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Khách hàng / Hồ sơ</div>
    <h2 id="cd-title">{{ $customer->name }}</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.customers') }}'">← Quay lại</button>
    <button class="btn btn-ghost btn-sm" onclick="showToast('Đã gửi SMS cho khách hàng','success')">📱 Gửi SMS</button>
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-send-voucher')">🎫 Tặng voucher</button>
  </div>
</div>

<div class="cd-layout">
  <!-- LEFT -->
  <div class="cd-main">
    <!-- Order history -->
    <div class="card mb-16">
      <div class="card-header">
        <div class="card-title">📦 Lịch sử đơn hàng</div>
        <span style="font-size:.78rem;color:var(--text-3);">{{ $customer->orders->count() }} đơn · {{ number_format($customer->total_spending, 0, ',', '.') }}đ</span>
      </div>
      <div class="card-body" style="padding-top:8px;">
        <div class="table-wrap">
          <table style="min-width:480px;">
            <thead><tr><th>Mã đơn</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày đặt</th><th></th></tr></thead>
            <tbody>
              @forelse($customer->orders as $o)
                <tr>
                  <td><span style="font-weight:700;color:var(--g-dark);">#{{ $o->order_code }}</span></td>
                  <td>
                    <span style="font-size:.8rem;">
                      @if($o->items->count() > 0)
                        {{ implode(', ', $o->items->take(2)->map(fn($item) => mb_substr($item->product_name, 0, 8) . ' × ' . $item->quantity)->toArray()) }}
                        @if($o->items->count() > 2) ... @endif
                      @else
                        N/A
                      @endif
                    </span>
                  </td>
                  <td><span style="font-weight:700;color:var(--orange);">{{ number_format($o->total_price, 0, ',', '.') }}đ</span></td>
                  <td>
                    @php
                      $statusBadge = 'badge-warning';
                      if ($o->status === 'Hoàn thành') $statusBadge = 'badge-success';
                      if ($o->status === 'Đang giao') $statusBadge = 'badge-info';
                      if ($o->status === 'Hoàn hàng') $statusBadge = 'badge-danger';
                      if ($o->status === 'Đã hủy') $statusBadge = 'badge-gray';
                    @endphp
                    <span class="badge {{ $statusBadge }}">{{ $o->status }}</span>
                  </td>
                  <td><span style="font-size:.78rem;color:var(--text-3);">{{ $o->created_at->format('d/m/Y') }}</span></td>
                  <td><button class="action-btn" onclick="location.href='{{ route('admin.orders.detail', $o->id) }}'">👁️</button></td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" style="text-align: center; color: var(--text-3);">Chưa có đơn hàng nào.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Spending chart -->
    <div class="card">
      <div class="card-header"><div class="card-title">📊 Chi tiêu theo tháng</div></div>
      <div class="card-body">
        <div class="chart-wrap" style="height:160px;"><canvas id="customerSpendChart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="cd-side">
    <!-- Profile -->
    <div class="card mb-12">
      <div class="card-body" style="text-align:center;padding-bottom:20px;">
        <div class="ap-avatar" style="width:64px;height:64px;font-size:1.6rem;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;">
          {{ mb_substr($customer->name, 0, 1) }}
        </div>
        <div style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--g-dark);margin-bottom:4px;">{{ $customer->name }}</div>
        <div>
          @if($customer->level === 'VIP')
            <span class="badge badge-warning">⭐ Khách VIP</span>
          @else
            <span class="badge badge-success">Thường</span>
          @endif
        </div>
        <div style="font-size:.75rem;color:var(--text-3);margin-top:8px;">Thành viên từ 01/2026</div>
      </div>
      <div style="border-top:1px solid var(--border);padding:14px 20px;">
        <div class="od-info-row"><span>📞</span><span>{{ $customer->phone }}</span></div>
        <div class="od-info-row"><span>✉️</span><span>{{ $customer->email }}</span></div>
        <div class="od-info-row"><span>📍</span><span>{{ $customer->address ?? 'N/A' }}</span></div>
        <div class="od-info-row"><span>🎂</span><span>{{ $customer->dob ? Carbon\Carbon::parse($customer->dob)->format('d/m/Y') : 'N/A' }}</span></div>
      </div>
    </div>

    <!-- Stats -->
    <div class="card mb-12">
      <div class="card-header"><div class="card-title">📈 Tổng quan</div></div>
      <div class="card-body">
        <div class="cd-stat-row"><div class="cd-stat-val" style="color:var(--orange)">{{ number_format($customer->total_spending, 0, ',', '.') }}đ</div><div class="cd-stat-label">Tổng chi tiêu</div></div>
        <div class="cd-stat-row"><div class="cd-stat-val">{{ $customer->total_orders }}</div><div class="cd-stat-label">Tổng đơn hàng</div></div>
        <div class="cd-stat-row">
          <div class="cd-stat-val" style="color:var(--g-mid)">
            {{ number_format($customer->total_orders > 0 ? $customer->total_spending / $customer->total_orders : 0, 0, ',', '.') }}đ
          </div>
          <div class="cd-stat-label">Giá trị đơn TB</div>
        </div>
        <div class="cd-stat-row"><div class="cd-stat-val">{{ number_format($customer->rating, 1) }} ★</div><div class="cd-stat-label">Điểm đánh giá TB</div></div>
      </div>
    </div>

    <!-- Vouchers -->
    <div class="card">
      <div class="card-header"><div class="card-title">🎫 Voucher đã dùng</div></div>
      <div class="card-body">
        <div class="od-info-row"><span style="font-family:monospace;font-weight:700;">FRUIT10</span><span class="badge badge-success">Đã dùng</span></div>
        <div class="od-info-row"><span style="font-family:monospace;font-weight:700;">WELCOME20</span><span class="badge badge-gray">Hết hạn</span></div>
        <button class="btn btn-primary w-full" style="margin-top:10px;" onclick="openModal('modal-send-voucher')">🎁 Tặng voucher mới</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('modals')
<div class="modal-overlay" id="modal-send-voucher">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🎁 Tặng voucher cho khách hàng</div>
      <button class="modal-close" onclick="closeModal('modal-send-voucher')">✕</button>
    </div>
    <div style="background:var(--g-pale);border-radius:var(--r-md);padding:12px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
      <div class="ap-avatar" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
        {{ mb_substr($customer->name, 0, 1) }}
      </div>
      <div>
        <div style="font-size:.85rem;font-weight:700;">{{ $customer->name }}</div>
        <div style="font-size:.72rem;color:var(--text-3);">{{ $customer->phone }} · {{ $customer->email }}</div>
      </div>
    </div>
    <div class="fg mb-12"><label>Loại voucher *</label>
      <select style="width:100%; padding:10px; border-radius:6px; border:1.5px solid var(--border);">
        <option>Chọn voucher có sẵn</option>
        @foreach(\App\Models\Voucher::all() as $v)
          <option>{{ $v->code }} — Giảm {{ $v->discount_type === 'percent' ? $v->discount_value.'%' : number_format($v->discount_value).'đ' }}</option>
        @endforeach
      </select>
    </div>
    <div style="text-align:center;padding:6px 0;color:var(--text-3);font-size:.8rem;">— hoặc tạo voucher riêng —</div>
    <div class="form-grid mb-12">
      <div class="fg"><label>Loại giảm</label><select><option>Phần trăm (%)</option><option>Số tiền cố định</option></select></div>
      <div class="fg"><label>Giá trị</label><div class="input-group"><input type="number" placeholder="15"/><div class="input-addon">%</div></div></div>
      <div class="fg form-full"><label>Hạn sử dụng</label><input type="date"/></div>
    </div>
    <div class="fg"><label>Lời nhắn gửi kèm (SMS)</label><textarea placeholder="VD: Hoa quả Sơn Tây gửi tặng bạn voucher VIP để cảm ơn sự ủng hộ!" style="width:95%; height:50px;"></textarea></div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modal-send-voucher')">Hủy</button>
      <button type="button" class="btn btn-primary" onclick="closeModal('modal-send-voucher');showToast('🎁 Đã gửi voucher cho khách','success')">🎁 Gửi voucher</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener("DOMContentLoaded", function() {
    renderCustomerChart();
  });

  function renderCustomerChart() {
    destroyChart('customerSpend');
    var ctx = document.getElementById('customerSpendChart');
    if (!ctx) return;
    
    var labels = {!! json_encode($spendLabels) !!};
    var data = {!! json_encode($spendData) !!};

    charts['customerSpend'] = new Chart(ctx, {
      type: 'bar',
      data: { 
        labels: labels,
        datasets: [{ 
          data: data, 
          backgroundColor: 'rgba(76,175,128,.6)',
          borderRadius: 6, 
          borderSkipped: false 
        }] 
      },
      options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
          x: { grid: { display: false }, ticks: { font: { size: 11 } } },
          y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 10 }, callback: function(v){ return v + 'k'; } } } 
        } 
      }
    });
  }
</script>
@endpush