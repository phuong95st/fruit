@extends('admin.layouts.app')

@section('title', 'Tạo phiếu nhập kho')
@section('page_title', 'Kho hàng / Nhập kho')

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Kho hàng / Nhập kho</div>
    <h2>Phiếu nhập kho</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.inventory') }}'">← Quay lại</button>
    <button class="btn btn-primary btn-sm" onclick="submitStockInForm()">✅ Xác nhận nhập kho</button>
  </div>
</div>

<div class="si-layout">
  <!-- FORM -->
  <div class="si-main">
    <div class="card mb-16">
      <div class="card-body">
        <div class="form-section-title">📋 Thông tin phiếu nhập</div>
        <div class="form-grid">
          <div class="fg"><label>Mã phiếu nhập</label><input id="si-code" value="{{ $nextCode }}" readonly style="background:var(--bg);color:var(--text-3);"/></div>
          <div class="fg"><label>Ngày nhập *</label><input id="si-date" type="date" value="{{ date('Y-m-d') }}"/></div>
          <div class="fg form-full"><label>Nhà cung cấp *</label>
            <select id="si-supplier">
              <option>Aus Fresh Co. (Úc)</option>
              <option>Thai Mango Export (Thái Lan)</option>
              <option>NZ Kiwi Ltd. (New Zealand)</option>
              <option>USA Grape Inc. (Hoa Kỳ)</option>
              <option>Vườn Đà Lạt (Việt Nam)</option>
            </select>
          </div>
          <div class="fg"><label>Số hóa đơn NCC</label><input id="si-invoice" placeholder="VD: INV-2025-0089"/></div>
          <div class="fg"><label>Hình thức thanh toán</label><select id="si-payment"><option>Chuyển khoản</option><option>Tiền mặt</option><option>Công nợ 30 ngày</option></select></div>
          <div class="fg form-full"><label>Ghi chú</label><textarea id="si-notes" placeholder="Ghi chú về lô hàng này..."></textarea></div>
        </div>
      </div>
    </div>

    <!-- Products to stock in -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">📦 Danh sách hàng nhập</div>
        <button class="btn btn-primary btn-sm" onclick="addStockRow()">+ Thêm sản phẩm</button>
      </div>
      <div class="card-body" style="padding-top:8px;">
        <div class="table-wrap">
          <table id="stock-table" style="min-width:520px;">
            <thead><tr><th>Sản phẩm</th><th>Số lượng</th><th>Đơn vị</th><th>Giá nhập</th><th>Thành tiền</th><th></th></tr></thead>
            <tbody id="stock-rows">
              <tr class="stock-row">
                <td>
                  <select class="stock-product-sel" onchange="calcStockRow(this)" style="border:1.5px solid var(--border);border-radius:var(--r-sm);padding:7px 10px;font-size:.82rem;outline:none;width:100%;background:white;">
                    @foreach($products as $p)
                      <option value="{{ $p->id }}" data-unit="{{ $p->unit }}" data-price="{{ $p->price * 0.7 }}">{{ $p->name }}</option>
                    @endforeach
                  </select>
                </td>
                <td><input type="number" class="stock-qty" value="100" onchange="calcStockRow(this)" style="border:1.5px solid var(--border);border-radius:var(--r-sm);padding:7px 10px;font-size:.82rem;outline:none;width:80px;"/></td>
                <td><span class="stock-unit" style="font-size:.82rem;color:var(--text-3);">kg</span></td>
                <td><input type="number" class="stock-price" value="45000" onchange="calcStockRow(this)" style="border:1.5px solid var(--border);border-radius:var(--r-sm);padding:7px 10px;font-size:.82rem;outline:none;width:100px;"/></td>
                <td><span class="stock-subtotal" style="font-weight:700;color:var(--orange);">4.500.000đ</span></td>
                <td><button class="action-btn" onclick="removeStockRow(this)" style="color:var(--red);">🗑️</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="si-total-block">
          <div class="si-total-row"><span>Tổng số mặt hàng</span><span id="si-item-count">1 mặt hàng</span></div>
          <div class="si-total-row grand"><span>Tổng giá trị nhập</span><span id="si-grand-total" style="color:var(--orange);font-weight:700;">4.500.000đ</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- SIDE -->
  <div class="si-side">
    <div class="card mb-12">
      <div class="card-header"><div class="card-title">📊 Tồn kho hiện tại</div></div>
      <div class="card-body" style="padding-top:8px;">
        <div class="si-stock-preview">
          @foreach($products->take(4) as $p)
            @php
              $stock = 250 - ($p->sold_count % 250);
              if ($p->code === 'orange') $stock = 245;
              if ($p->code === 'strawberry') $stock = 42;
              if ($p->code === 'kiwi') $stock = 5;
              if ($p->code === 'grape') $stock = 0;
              
              $percent = min(100, max(0, ($stock / 300) * 100));
              $fill = 'sf-high';
              if ($stock < 15) $fill = 'sf-low';
              elseif ($stock < 50) $fill = 'sf-mid';
            @endphp
            <div class="ssp-item">
              <div class="ssp-icon" style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;background:var(--bg);">
                🍇
              </div>
              <div class="ssp-info"><div class="ssp-name">{{ $p->name }}</div><div class="ssp-stock" style="{{ $stock < 15 ? 'color:var(--red);' : '' }}">{{ $stock }} {{ $p->unit }} còn lại</div></div>
              <div class="ssp-bar-wrap"><div class="stock-bar"><div class="stock-fill {{ $fill }}" style="width:{{ $percent }}%"></div></div></div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function addStockRow() {
    var tbody = document.getElementById('stock-rows');
    if (!tbody) return;
    var row = document.createElement('tr');
    row.className = 'stock-row';
    
    var optionsHtml = '';
    @foreach($products as $p)
      optionsHtml += '<option value="{{ $p->id }}" data-unit="{{ $p->unit }}" data-price="{{ $p->price * 0.7 }}">{{ $p->name }}</option>';
    @endforeach

    row.innerHTML =
      '<td><select class="stock-product-sel" onchange="calcStockRow(this)" style="border:1.5px solid var(--border);border-radius:var(--r-sm);padding:7px 10px;font-size:.82rem;outline:none;width:100%;background:white;">'
      + optionsHtml
      + '</select></td>'
      + '<td><input type="number" class="stock-qty" value="20" onchange="calcStockRow(this)" style="border:1.5px solid var(--border);border-radius:var(--r-sm);padding:7px 10px;font-size:.82rem;outline:none;width:80px;"/></td>'
      + '<td><span class="stock-unit" style="font-size:.82rem;color:var(--text-3);">kg</span></td>'
      + '<td><input type="number" class="stock-price" value="50000" onchange="calcStockRow(this)" style="border:1.5px solid var(--border);border-radius:var(--r-sm);padding:7px 10px;font-size:.82rem;outline:none;width:100px;"/></td>'
      + '<td><span class="stock-subtotal" style="font-weight:700;color:var(--orange);">1.000.000đ</span></td>'
      + '<td><button class="action-btn" onclick="removeStockRow(this)" style="color:var(--red);">🗑️</button></td>';
    tbody.appendChild(row);
    
    // Cập nhật lại đơn vị mặc định cho dòng mới thêm
    var sel = row.querySelector('.stock-product-sel');
    calcStockRow(sel);
    
    updateStockTotal();
    showToast('Đã thêm dòng sản phẩm', 'info');
  }

  function removeStockRow(btn) {
    var row = btn.closest('tr');
    if (row) row.parentNode.removeChild(row);
    updateStockTotal();
  }

  function calcStockRow(inp) {
    var row = inp.closest('tr');
    if (!row) return;
    
    var sel = row.querySelector('.stock-product-sel');
    var opt = sel.options[sel.selectedIndex];
    var unit = opt.getAttribute('data-unit') || 'kg';
    row.querySelector('.stock-unit').textContent = unit;
    
    var qty = parseFloat(row.querySelector('.stock-qty').value) || 0;
    var price = parseFloat(row.querySelector('.stock-price').value) || 0;
    var sub = qty * price;
    row.querySelector('.stock-subtotal').textContent = sub.toLocaleString('vi-VN') + 'đ';
    updateStockTotal();
  }

  function updateStockTotal() {
    var rows = document.querySelectorAll('.stock-row');
    var total = 0;
    rows.forEach(function(r) {
      var qty = parseFloat((r.querySelector('.stock-qty') || {}).value) || 0;
      var price = parseFloat((r.querySelector('.stock-price') || {}).value) || 0;
      total += qty * price;
    });
    var cnt = document.getElementById('si-item-count');
    var gt = document.getElementById('si-grand-total');
    if (cnt) cnt.textContent = rows.length + ' mặt hàng';
    if (gt) gt.textContent = total.toLocaleString('vi-VN') + 'đ';
  }

  // Khởi động tính toán dòng đầu tiên
  document.addEventListener("DOMContentLoaded", function() {
    var initialSel = document.querySelector('.stock-product-sel');
    if (initialSel) calcStockRow(initialSel);
  });

  function submitStockInForm() {
    var code = document.getElementById('si-code').value;
    var date = document.getElementById('si-date').value;
    var supplier = document.getElementById('si-supplier').value;
    var invoice = document.getElementById('si-invoice').value;
    var payment = document.getElementById('si-payment').value;
    var notes = document.getElementById('si-notes').value;
    
    var products = [];
    var totalValue = 0;
    document.querySelectorAll('.stock-row').forEach(function(row) {
      var sel = row.querySelector('.stock-product-sel');
      var pid = sel.value;
      var name = sel.options[sel.selectedIndex].text;
      var qty = parseFloat(row.querySelector('.stock-qty').value) || 0;
      var price = parseFloat(row.querySelector('.stock-price').value) || 0;
      var unit = row.querySelector('.stock-unit').textContent;
      totalValue += qty * price;
      
      products.push({
        product_id: pid,
        name: name,
        quantity: qty,
        unit: unit,
        price: price
      });
    });

    // Thực hiện gửi AJAX
    fetch('{{ route("admin.inventory.stock-in.store") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({
        stock_in_code: code,
        date: date,
        supplier: supplier,
        invoice_number: invoice,
        payment_method: payment,
        notes: notes,
        total_value: totalValue,
        products: products
      })
    })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        showToast('✅ Đã xác nhận nhập kho thành công!', 'success');
        setTimeout(function() { location.href = '{{ route("admin.inventory") }}'; }, 900);
      } else {
        showToast('Lỗi khi lưu phiếu nhập kho', 'error');
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Đã xảy ra lỗi kết nối', 'error');
    });
  }
</script>
@endpush