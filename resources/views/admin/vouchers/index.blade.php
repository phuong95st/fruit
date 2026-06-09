@extends('admin.layouts.app')

@section('title', 'Voucher khuyến mãi')
@section('page_title', 'Khuyến mãi')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Quản lý</div>
    <h2>Khuyến mãi & Voucher</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-voucher')">+ Tạo voucher</button>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="filter-bar">
      <input type="text" placeholder="🔍 Tìm mã voucher..." style="min-width:200px;"/>
      <div class="filter-bar-spacer"></div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Mã voucher</th>
            <th>Loại giảm giá</th>
            <th>Giá trị</th>
            <th>Điều kiện</th>
            <th>Tổng số</th>
            <th>Thời hạn</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @forelse($vouchers as $v)
            @php
              $today = \Carbon\Carbon::today();
              $isExpired = $v->expires_at && $v->expires_at->isPast();
              
              $statusBadge = 'badge-success';
              $statusText = '● Đang hoạt động';
              if ($isExpired) {
                  $statusBadge = 'badge-danger';
                  $statusText = '✕ Hết hạn';
              }
            @endphp
            <tr>
              <td>
                <span style="font-family:monospace;font-size:.9rem;font-weight:700;background:{{ $isExpired ? 'var(--bg)' : 'var(--g-pale)' }};padding:4px 10px;border-radius:6px;color:{{ $isExpired ? 'var(--text-3)' : 'var(--g-dark)' }};">
                  {{ $v->code }}
                </span>
              </td>
              <td>
                <span class="badge {{ $v->discount_type === 'percent' ? 'badge-info' : 'badge-success' }}">
                  {{ $v->discount_type === 'percent' ? '% Phần trăm' : '💰 Tiền mặt' }}
                </span>
              </td>
              <td>
                <span style="font-weight:700;color:{{ $isExpired ? 'var(--text-3)' : 'var(--orange)' }};">
                  Giảm {{ $v->discount_type === 'percent' ? $v->discount_value.'%' : number_format($v->discount_value, 0, ',', '.').'đ' }}
                </span>
              </td>
              <td><span style="font-size:.82rem;">Đơn tối thiểu {{ number_format($v->min_order_value, 0, ',', '.') }}đ</span></td>
              <td><div style="font-size:.82rem;"><span style="font-weight:700;">{{ $v->quantity }}</span></div></td>
              <td>
                <span style="font-size:.78rem;color:{{ $isExpired ? 'var(--red)' : 'var(--text-3)' }};">
                  {{ $v->expires_at ? $v->expires_at->format('d/m/Y') : 'Không giới hạn' }}
                </span>
              </td>
              <td><span class="badge {{ $statusBadge }}">{{ $statusText }}</span></td>
              <td>
                <div class="td-actions">
                  <button class="action-btn" onclick="showToast('Tính năng đang phát triển','info')">✏️</button>
                  <button class="action-btn" onclick="showToast('Đã dừng voucher','error')">⏹️</button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align:center;color:var(--text-3);">Chưa có voucher nào được tạo.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('modals')
<div class="modal-overlay" id="modal-voucher">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🎫 Tạo mã khuyến mãi mới</div>
      <button class="modal-close" onclick="closeModal('modal-voucher')">✕</button>
    </div>
    <form method="POST" action="{{ route('admin.vouchers.store') }}">
      @csrf
      <div class="form-grid">
        <div class="fg"><label>Mã voucher *</label><input name="code" placeholder="VD: SUMMER25" required/></div>
        <div class="fg"><label>Loại giảm</label>
          <select name="discount_type">
            <option value="percent">Phần trăm (%)</option>
            <option value="fixed">Số tiền cố định (đ)</option>
          </select>
        </div>
        <div class="fg"><label>Giá trị giảm *</label>
          <div class="input-group"><input type="number" name="discount_value" placeholder="10" required/><div class="input-addon">% / đ</div></div>
        </div>
        <div class="fg"><label>Đơn tối thiểu</label>
          <div class="input-group"><input type="number" name="min_order_value" placeholder="200000"/><div class="input-addon">đ</div></div>
        </div>
        <div class="fg"><label>Số lượng phát hành</label><input type="number" name="quantity" placeholder="500"/></div>
        <div class="fg"><label>Ngày hết hạn</label><input type="date" name="expires_at"/></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-voucher')">Hủy</button>
        <button type="submit" class="btn btn-primary">Tạo voucher</button>
      </div>
    </form>
  </div>
</div>
@endsection