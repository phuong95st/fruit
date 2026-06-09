@extends('admin.layouts.app')

@section('title', 'Quản lý kho hàng')
@section('page_title', 'Kho hàng')

@section('content')
@php
  // Tính toán tồn kho mô phỏng cho tất cả sản phẩm
  $products = \App\Models\Product::all();
  $veryLowCount = 0;
  $lowCount = 0;
  $stableCount = 0;
  
  $processedProducts = [];
  foreach($products as $product) {
      $stock = 250 - ($product->sold_count % 250);
      if ($product->code === 'orange') $stock = 245;
      if ($product->code === 'strawberry') $stock = 42;
      if ($product->code === 'kiwi') $stock = 5;
      if ($product->code === 'grape') $stock = 0;
      
      $minStock = 30;
      if ($product->code === 'kiwi') $minStock = 20;
      if ($product->code === 'grape') $minStock = 15;
      if ($product->code === 'strawberry') $minStock = 50;
      
      $status = 'Đủ hàng';
      if ($stock === 0) {
          $veryLowCount++;
          $status = 'Hết hàng';
      } elseif ($stock < $minStock) {
          $veryLowCount++;
          $status = 'Rất thấp';
      } elseif ($stock < $minStock * 1.5) {
          $lowCount++;
          $status = 'Thấp';
      } else {
          $stableCount++;
          $status = 'Đủ hàng';
      }
      
      $processedProducts[] = [
          'model' => $product,
          'stock' => $stock,
          'min_stock' => $minStock,
          'status' => $status
      ];
  }
@endphp

<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Quản lý</div>
    <h2>Kho hàng</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-orange btn-sm" onclick="location.href='{{ route('admin.inventory.stock-in') }}'">+ Nhập kho</button>
  </div>
</div>

<div class="inventory-cards-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">
  <div class="card" style="border-left:3px solid var(--red);">
    <div class="card-body" style="padding:14px 16px;">
      <div style="font-size:.72rem;font-weight:700;color:var(--red);margin-bottom:6px;">⚠️ HÀNG SẮP HẾT / HẾT HÀNG</div>
      <div style="font-size:1.4rem;font-weight:700;color:var(--g-dark);">{{ $veryLowCount }} sản phẩm</div>
      <div style="font-size:.78rem;color:var(--text-3);">Cần nhập thêm ngay</div>
    </div>
  </div>
  <div class="card" style="border-left:3px solid var(--yellow);">
    <div class="card-body" style="padding:14px 16px;">
      <div style="font-size:.72rem;font-weight:700;color:#b45309;margin-bottom:6px;">📦 TỒN KHO THẤP</div>
      <div style="font-size:1.4rem;font-weight:700;color:var(--g-dark);">{{ $lowCount }} sản phẩm</div>
      <div style="font-size:.78rem;color:var(--text-3);">Dưới mức tối ưu</div>
    </div>
  </div>
  <div class="card" style="border-left:3px solid var(--g-light);">
    <div class="card-body" style="padding:14px 16px;">
      <div style="font-size:.72rem;font-weight:700;color:var(--g-mid);margin-bottom:6px;">✅ TỒN KHO ĐỦ</div>
      <div style="font-size:1.4rem;font-weight:700;color:var(--g-dark);">{{ $stableCount }} sản phẩm</div>
      <div style="font-size:.78rem;color:var(--text-3);">Ổn định, không cần nhập</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">📊 Tình trạng tồn kho chi tiết</div>
    <button class="btn btn-ghost btn-sm" onclick="showToast('Đã xuất báo cáo kho','success')">⬇️ Xuất báo cáo</button>
  </div>
  <div class="card-body">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Sản phẩm</th>
            <th>Tồn hiện tại</th>
            <th>Mức tối thiểu</th>
            <th>Tình trạng</th>
            <th>Nhập lần cuối</th>
            <th>Giá nhập</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @foreach($processedProducts as $item)
            @php
              $p = $item['model'];
              $badgeClass = 'badge-success';
              if ($item['status'] === 'Hết hàng') $badgeClass = 'badge-danger';
              elseif ($item['status'] === 'Rất thấp') $badgeClass = 'badge-danger';
              elseif ($item['status'] === 'Thấp') $badgeClass = 'badge-warning';
            @endphp
            <tr>
              <td>
                <div class="td-name">
                  <div class="td-icon {{ $p->bg ?? 'bg-g' }}" style="display:flex;align-items:center;justify-content:center;">
                    @if($p->svg)
                      <div style="width:22px;height:22px;">{!! $p->svg !!}</div>
                    @else
                      🍎
                    @endif
                  </div>
                  <div class="td-text">
                    <div class="tn-name">{{ $p->name }}</div>
                    <div class="tn-sub">SKU: FN-{{ Str::upper(Str::substr($p->code, 0, 3)) }}-00{{ $p->id }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span style="font-weight:700;color:{{ $item['stock'] < $item['min_stock'] ? 'var(--red)' : 'var(--text)' }};">
                  {{ $item['stock'] }} {{ $p->unit }}
                </span>
              </td>
              <td><span style="font-size:.82rem;color:var(--text-3);">{{ $item['min_stock'] }} {{ $p->unit }}</span></td>
              <td><span class="badge {{ $badgeClass }}">{{ $item['status'] }}</span></td>
              <td><span style="font-size:.78rem;color:var(--text-3);">25/01/2026</span></td>
              <td><span style="font-size:.82rem;">{{ number_format($p->price * 0.7, 0, ',', '.') }}đ/{{ $p->unit }}</span></td>
              <td>
                @if($item['stock'] < $item['min_stock'])
                  <button class="btn btn-orange btn-sm" onclick="location.href='{{ route('admin.inventory.stock-in') }}'">📥 Nhập ngay</button>
                @else
                  <button class="btn btn-ghost btn-sm">— Ổn định</button>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection