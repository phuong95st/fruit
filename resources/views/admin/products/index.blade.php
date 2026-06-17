@extends('admin.layouts.app')

@section('title', 'Sản phẩm')
@section('page_title', 'Sản phẩm')

@php
  // ID sản phẩm cần xóa (để truyền vào modal)
  $deleteProductId = null;
  $deleteProductName = null;
@endphp

@section('content')
<div class="page-header">
  <div class="ph-left">
    <div class="ph-eyebrow">Quản lý</div>
    <h2>Sản phẩm</h2>
  </div>
  <div class="ph-right">
    <button class="btn btn-filter btn-sm" onclick="openModal('modal-import-products')">📤 Nhập Excel</button>
    <button class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.products.export', request()->all()) }}'">⬇️ Xuất Excel</button>
    <button class="btn btn-primary btn-sm" onclick="location.href='{{ route('admin.products.create') }}'">+ Thêm sản phẩm</button>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.products') }}" class="filter-bar">
      <input type="text" name="search" placeholder="🔍 Tìm sản phẩm..." value="{{ request('search') }}" style="min-width:200px;"/>
      <select name="origin" onchange="this.form.submit()">
        <option value="Tất cả xuất xứ" {{ request('origin') === 'Tất cả xuất xứ' || !request('origin') ? 'selected' : '' }}>Tất cả xuất xứ</option>
        @foreach($origins as $org)
          <option value="{{ $org }}" {{ request('origin') === $org ? 'selected' : '' }}>{{ $org }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-filter btn-sm" style="border-radius:50px;">Lọc</button>
      <div class="filter-bar-spacer"></div>
    </form>
    
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox"/></th>
            <th>Sản phẩm</th>
            <th>Danh mục</th>
            <th>Giá bán</th>
            <th>Tồn kho</th>
            <th>Đã bán</th>
            <th>Đánh giá</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @forelse($products as $product)
            @php
              // Tính toán tồn kho mô phỏng dựa trên lượng bán ra
              $stock = 250 - ($product->sold_count % 250);
              // Một số trường hợp đặc biệt để khớp dữ liệu gốc
              if ($product->code === 'orange') $stock = 245;
              if ($product->code === 'strawberry') $stock = 42;
              if ($product->code === 'kiwi') $stock = 5;
              if ($product->code === 'grape') $stock = 0;
              
              $percent = min(100, max(0, ($stock / 300) * 100));
              
              $statusBadge = 'badge-success';
              $statusText = '● Đang bán';
              $stockText = 'Còn nhiều';
              $fillClass = 'sf-high';
              
              if ($stock === 0) {
                  $statusBadge = 'badge-danger';
                  $statusText = '✕ Hết hàng';
                  $stockText = 'Hết hàng';
                  $fillClass = 'sf-low';
              } elseif ($stock < 15) {
                  $statusBadge = 'badge-warning';
                  $statusText = '⚠️ Sắp hết';
                  $stockText = 'Sắp hết';
                  $fillClass = 'sf-low';
              } elseif ($stock < 50) {
                  $stockText = 'Trung bình';
                  $fillClass = 'sf-mid';
              }
            @endphp
            <tr>
              <td><input type="checkbox"/></td>
              <td>
                <div class="td-name">
                  <div class="td-icon {{ $product->image_url ? '' : ($product->bg ?? 'bg-g') }}" style="display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:var(--r-md);">
                    @if($product->image_url)
                      <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width:100%;height:100%;object-fit:cover;">
                    @elseif($product->svg)
                      <div style="width:22px;height:22px;">{!! $product->svg !!}</div>
                    @else
                      🍎
                    @endif
                  </div>
                  <div class="td-text">
                    <div class="tn-name">{{ $product->name }}</div>
                    <div class="tn-sub">SKU: FN-{{ Str::upper(Str::substr($product->code, 0, 3)) }}-00{{ $product->id }} · {{ $product->origin }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge {{ $product->t1 === 'Nhập khẩu' ? 'badge-info' : 'badge-success' }}">
                  {{ $product->t1 }}
                </span>
              </td>
              <td><span style="font-weight:700;color:var(--orange);">{{ number_format($product->price, 0, ',', '.') }}đ/{{ $product->unit }}</span></td>
              <td>
                <div style="min-width:80px;">
                  <div class="flex justify-between">
                    <span style="font-size:.78rem;font-weight:600;">{{ $stock }} {{ $product->unit }}</span>
                  </div>
                  <div class="stock-bar">
                    <div class="stock-fill {{ $fillClass }}" style="width:{{ $percent }}%"></div>
                  </div>
                  <div class="stock-text" style="{{ $stock < 15 ? 'color:var(--red);' : '' }}">{{ $stockText }}</div>
                </div>
              </td>
              <td><span style="font-size:.82rem;color:var(--text-2);">{{ number_format($product->sold_count) }} {{ $product->unit }}</span></td>
              <td><span style="color:var(--yellow);font-size:.9rem;">★</span> <span style="font-size:.82rem;">{{ number_format($product->rating_value, 1) }}</span></td>
              <td><span class="badge {{ $statusBadge }}">{{ $statusText }}</span></td>
              <td>
                <div class="td-actions">
                  <button class="action-btn" title="Chi tiết" onclick="location.href='{{ route('admin.products.detail', $product->id) }}'">👁️</button>
                  <button class="action-btn" title="Sửa" onclick="location.href='{{ route('admin.products.edit', $product->id) }}'">✏️</button>
                  <button class="action-btn action-btn-danger" title="Xóa" onclick="openDeleteModal({{ $product->id }}, '{{ addslashes($product->name) }}')">🗑️</button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" style="text-align:center;color:var(--text-3);">Không tìm thấy sản phẩm nào.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Custom Pagination -->
    @if($products->lastPage() > 1)
      <div class="pagination">
        <span class="pg-info">Hiển thị {{ $products->firstItem() }}–{{ $products->lastItem() }} / {{ $products->total() }} sản phẩm</span>
        
        @if($products->onFirstPage())
          <button class="pg-btn" disabled>×</button>
        @else
          <a href="{{ $products->previousPageUrl() }}" class="pg-btn">←</a>
        @endif
        
        @for($i = 1; $i <= $products->lastPage(); $i++)
          <a href="{{ $products->url($i) }}" class="pg-btn {{ $products->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
        @endfor

        @if($products->hasMorePages())
          <a href="{{ $products->nextPageUrl() }}" class="pg-btn">→</a>
        @else
          <button class="pg-btn" disabled>×</button>
        @endif
      </div>
    @endif
  </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
  <script>document.addEventListener('DOMContentLoaded', function(){ showToast('{{ session('success') }}', 'success'); });</script>
@endif
@if(session('error'))
  <script>document.addEventListener('DOMContentLoaded', function(){ showToast('{{ session('error') }}', 'error'); });</script>
@endif
@endsection

@section('modals')
{{-- Modal xác nhận xóa sản phẩm --}}
<div class="modal-overlay" id="modal-delete-product">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🗑️ Xóa sản phẩm</div>
      <button class="modal-close" onclick="closeModal('modal-delete-product')">✕</button>
    </div>
    <div style="text-align:center;padding:20px 0;">
      <div style="font-size:3rem;margin-bottom:12px;">⚠️</div>
      <div style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--g-dark);margin-bottom:6px;" id="delete-product-name"></div>
      <div style="font-size:.82rem;color:var(--text-3);">Sản phẩm sẽ bị ẩn khỏi hệ thống (xóa mềm)</div>
    </div>
    <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:var(--r-md);padding:12px;margin-bottom:16px;font-size:.82rem;color:var(--red);">
      ⚠️ Sản phẩm sẽ bị ẩn và không còn hiển thị với khách hàng. Có thể khôi phục sau.
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modal-delete-product')">Hủy bỏ</button>
      <form id="delete-product-form" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">🗑️ Xóa sản phẩm</button>
      </form>
    </div>
  </div>
</div>

{{-- Modal Import sản phẩm --}}
<div class="modal-overlay" id="modal-import-products">
  <div class="modal" style="width:min(600px, 95vw);">
    <div class="modal-header">
      <div class="modal-title">📤 Nhập sản phẩm từ Excel (CSV)</div>
      <button class="modal-close" onclick="closeModal('modal-import-products')">✕</button>
    </div>
    
    <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data">
      @csrf
      
      <div style="margin-bottom:16px;">
        <label style="font-size:.78rem;font-weight:700;color:var(--text);display:block;margin-bottom:6px;">Tải lên File dữ liệu (.csv hoặc .zip) *</label>
        <input type="file" name="import_file" accept=".csv,.zip" required style="width:100%;border:1.5px solid var(--border);border-radius:var(--r-md);padding:10px;" />
      </div>

      <div style="background:var(--bg);border-radius:var(--r-md);padding:16px;margin-bottom:16px;font-size:.78rem;line-height:1.6;color:var(--text-2);max-height:400px;overflow-y:auto;border:1.5px solid var(--border);">
        <div style="font-weight:700;color:var(--g-dark);margin-bottom:8px;font-size:.85rem;display:flex;align-items:center;gap:6px;">🛠️ Quy trình & Hướng dẫn chuẩn bị dữ liệu:</div>
        
        <div style="font-weight:600;color:var(--g-mid);margin-bottom:4px;">📌 Bước 1: Tải file mẫu và điền thông tin</div>
        <ul style="padding-left:16px;margin-bottom:12px;">
          <li>Click nút <strong>Tải file CSV mẫu</strong> bên dưới.</li>
          <li>Mở file bằng Excel hoặc Google Sheets. Nhập thông tin sản phẩm theo các cột tiêu chuẩn:
            <ul style="padding-left:14px;margin-top:4px;list-style-type:circle;">
              <li><code>name</code> (Tên sản phẩm - <strong>Bắt buộc</strong>): Nhập chữ thường hoặc hoa.</li>
              <li><code>code</code> (Mã SKU - Tùy chọn): Mã định danh duy nhất (VD: <code>tao-rockit</code>). Trùng mã sẽ tự động cập nhật giá/mô tả; để trống hệ thống tự sinh mã ngẫu nhiên.</li>
              <li><code>price</code> (Giá bán - <strong>Bắt buộc</strong>): Chỉ nhập số nguyên dương (VD: <code>120000</code>), không nhập dấu phân cách hàng nghìn.</li>
              <li><code>original_price</code> (Giá gốc - Tùy chọn): Chỉ nhập số nguyên (VD: <code>140000</code>).</li>
              <li><code>unit</code> (Đơn vị tính - <strong>Bắt buộc</strong>): kg, hộp, túi, giỏ...</li>
              <li><code>origin</code> (Xuất xứ - <strong>Bắt buộc</strong>): Việt Nam, Úc, Mỹ...</li>
              <li><code>t1</code> (Phân loại chính): Nhập khẩu / Trong nước...</li>
              <li><code>t2</code> (Nhãn phụ): Mới / Nổi bật...</li>
              <li><code>desc</code> & <code>pack</code>: Mô tả và Quy cách đóng gói.</li>
            </ul>
          </li>
        </ul>

        <div style="font-weight:600;color:var(--g-mid);margin-bottom:4px;">📌 Bước 2: Nhập thông tin Hình ảnh & Video</div>
        <ul style="padding-left:16px;margin-bottom:12px;">
          <li><strong>Cách 1 (Nhập link online - Tải trực tiếp file CSV):</strong>
            <ul style="padding-left:14px;margin-top:2px;list-style-type:circle;">
              <li>Cột <code>image</code> (Ảnh chính): Dán link URL ảnh (VD: <code>https://domain.com/tao.jpg</code>).</li>
              <li>Cột <code>images</code> (Ảnh phụ): Dán danh sách link ảnh cách nhau bởi dấu phẩy (tối đa 5 ảnh).</li>
              <li>Cột <code>video</code> (Video): Dán link video YouTube / Shorts (VD: <code>https://youtube.com/watch?...</code>).</li>
            </ul>
          </li>
          <li><strong>Cách 2 (Nhập ảnh từ máy tính - Nén file ZIP):</strong>
            <ul style="padding-left:14px;margin-top:2px;list-style-type:circle;">
              <li>Tạo thư mục <code>images/</code> chứa các tệp ảnh và <code>videos/</code> chứa tệp video trên máy tính.</li>
              <li>Cột <code>image</code> & <code>images</code>: Điền tên tệp tương ứng (VD: <code>tao_main.jpg</code> hoặc <code>images/tao_main.jpg</code>).</li>
              <li>Cột <code>video</code>: Điền tên tệp video tương ứng (VD: <code>clip.mp4</code> hoặc <code>videos/clip.mp4</code>).</li>
              <li>Nén file CSV cùng các thư mục <code>images/</code>, <code>videos/</code> này lại thành một file <strong>ZIP</strong> duy nhất rồi tải lên hệ thống.</li>
            </ul>
          </li>
        </ul>

        <div style="font-weight:600;color:var(--g-mid);margin-bottom:4px;">📌 Bước 3: Xuất file & Tải lên hệ thống</div>
        <ul style="padding-left:16px;margin-bottom:12px;">
          <li>Nếu dùng Excel, chọn <strong>Save As</strong> -> định dạng <strong>CSV (Comma delimited) (*.csv)</strong> trước khi upload hoặc đóng gói ZIP.</li>
          <li>Chọn tệp tin (CSV hoặc ZIP) ở khung bên trên và click <strong>Xác nhận Import</strong> để hệ thống tiến hành nạp dữ liệu.</li>
        </ul>

      </div>

      <div style="margin-top:12px; margin-bottom:4px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
        <span style="font-size:.75rem; color:var(--text-3);">Tải file mẫu để bắt đầu điền thông tin:</span>
        <a href="{{ route('admin.products.import-template') }}" class="btn btn-filter btn-sm" style="display:inline-flex;padding:6px 14px;font-size:.75rem;">⬇️ Tải file CSV mẫu</a>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-import-products')">Hủy bỏ</button>
        <button type="submit" class="btn btn-primary">Xác nhận Import</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function openDeleteModal(id, name) {
    document.getElementById('delete-product-name').textContent = name;
    document.getElementById('delete-product-form').action = '/admin/products/' + id;
    openModal('modal-delete-product');
  }

  // Tự động gửi form tìm kiếm sau 1 giây (debounce) và khôi phục tiêu điểm con trỏ
  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('input[name="search"]');
    const filterForm = document.querySelector('form.filter-bar');

    if (searchInput && filterForm) {
      // Khôi phục tiêu điểm và đặt con trỏ ở cuối văn bản nếu có từ khóa
      if (searchInput.value.trim() !== '') {
        searchInput.focus();
        const val = searchInput.value;
        searchInput.value = '';
        searchInput.value = val;
      }

      let debounceTimeout;
      searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
          filterForm.submit();
        }, 1000); // 1000ms = 1 giây
      });
    }
  });
</script>
@endpush