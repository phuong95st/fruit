@extends('admin.layouts.app')

@section('title', 'Thêm sản phẩm mới')
@section('page_title', 'Thêm sản phẩm')

@section('content')
<form id="productForm" method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
  @csrf
  <div class="page-header">
    <div class="ph-left">
      <div class="ph-eyebrow">Sản phẩm</div>
      <h2>Thêm sản phẩm mới</h2>
    </div>
    <div class="ph-right">
      <button type="button" class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.products') }}'">← Quay lại</button>
      <button type="submit" form="productForm" class="btn btn-primary btn-sm">💾 Lưu sản phẩm</button>
    </div>
  </div>

  <div class="add-product-grid" style="display:grid;grid-template-columns:2fr 1fr;gap:16px;align-items:start;">
    <div>
      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">📝 Thông tin cơ bản</div>
          <div class="form-grid">
            <div class="fg form-full">
              <label>Tên sản phẩm *</label>
              <input type="text" name="name" placeholder="VD: Cam Navel Úc" required value="{{ old('name') }}"/>
            </div>
            <div class="fg">
              <label>Danh mục *</label>
              <select name="category">
                <option value="Nhập khẩu">Nhập khẩu</option>
                <option value="Tươi lẻ">Tươi lẻ</option>
                <option value="Giỏ quà">Giỏ quà</option>
              </select>
            </div>
            <div class="fg">
              <label>Xuất xứ *</label>
              <input type="text" name="origin" placeholder="VD: Úc, Việt Nam" required value="{{ old('origin') }}"/>
            </div>
            <div class="fg form-full">
              <label>Mô tả ngắn & chi tiết</label>
              <textarea name="desc" style="min-height:100px;" placeholder="Nhập mô tả sản phẩm...">{{ old('desc') }}</textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">💰 Giá & Kho hàng</div>
          <div class="form-grid">
            <div class="fg">
              <label>Giá bán *</label>
              <div class="input-group">
                <input type="number" name="price" placeholder="65000" required value="{{ old('price') }}"/>
                <div class="input-addon">đ</div>
              </div>
            </div>
            <div class="fg">
              <label>Đơn vị tính *</label>
              <select name="unit">
                <option value="kg">kg</option>
                <option value="hộp">hộp</option>
                <option value="túi">túi</option>
                <option value="trái">trái</option>
                <option value="giỏ">giỏ</option>
              </select>
            </div>
            <div class="fg">
              <label>Giá gốc (để tính % giảm giá)</label>
              <div class="input-group">
                <input type="number" name="original_price" placeholder="75000" value="{{ old('original_price') }}"/>
                <div class="input-addon">đ</div>
              </div>
            </div>
            <div class="fg">
              <label>Quy cách đóng gói</label>
              <input type="text" name="pack" placeholder="VD: Mua theo kg, hộp 500g" value="{{ old('pack') }}"/>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right sidebar options -->
    <div>
      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">🖼️ Hình ảnh sản phẩm</div>
          <div class="img-upload-area" style="position: relative; cursor: pointer; border: 2px dashed var(--border); border-radius: var(--radius-md); padding: 20px; text-align: center;">
            <input type="file" name="image" accept="image/*" id="imageInput" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewImage(event)"/>
            <div class="iu-icon">📁</div>
            <p><strong>Nhấp để tải ảnh lên</strong></p>
            <p>hoặc kéo thả vào đây</p>
            <p style="margin-top:6px;font-size:.72rem;">PNG, JPG, WEBP · Tối đa 4MB</p>
          </div>
          <div class="img-preview-grid mt-8" id="imagePreviewContainer" style="display: none; justify-content: flex-start;">
            <div class="img-preview" style="position: relative; width: 100px; height: 100px; border-radius: var(--radius-sm); border: 1px solid var(--border); overflow: hidden;">
              <img id="imagePreview" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;"/>
              <button type="button" onclick="removePreview()" style="position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; width: 18px; height: 18px; line-height: 18px; text-align: center; font-size: 10px; cursor: pointer;">×</button>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">⚙️ Cài đặt hiển thị</div>
          <div style="display:flex;flex-direction:column;gap:14px;">
            <div class="flex justify-between items-center">
              <div>
                <div style="font-size:.85rem;font-weight:600;">Hiển thị trên web</div>
                <div style="font-size:.72rem;color:var(--text-3);">Khách hàng thấy sản phẩm</div>
              </div>
              <button type="button" class="toggle on" onclick="this.classList.toggle('on')"></button>
            </div>
            <div class="flex justify-between items-center">
              <div>
                <div style="font-size:.85rem;font-weight:600;">Nổi bật trang chủ</div>
                <div style="font-size:.72rem;color:var(--text-3);">Hiển thị ở phần "Yêu thích"</div>
              </div>
              <button type="button" class="toggle on" onclick="this.classList.toggle('on')"></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@section('scripts')
<script>
  function previewImage(event) {
      const input = event.target;
      const container = document.getElementById('imagePreviewContainer');
      const preview = document.getElementById('imagePreview');
      
      if (input.files && input.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
              preview.src = e.target.result;
              container.style.display = 'flex';
          }
          reader.readAsDataURL(input.files[0]);
      }
  }

  function removePreview() {
      const input = document.getElementById('imageInput');
      const container = document.getElementById('imagePreviewContainer');
      const preview = document.getElementById('imagePreview');
      
      input.value = '';
      preview.src = '';
      container.style.display = 'none';
  }
</script>
@endsection