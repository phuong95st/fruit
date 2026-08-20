@extends('admin.layouts.app')

@section('title', 'Chỉnh sửa sản phẩm ' . $product->name)
@section('page_title', 'Chỉnh sửa sản phẩm')

@section('content')
<form id="productForm" method="POST" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data">
  @csrf

  <div class="page-header">
    <div class="ph-left">
      <div class="ph-eyebrow">Sản phẩm</div>
      <h2>Chỉnh sửa: {{ $product->name }}</h2>
    </div>
    <div class="ph-right">
      <button type="button" class="btn btn-ghost btn-sm" onclick="location.href='{{ route('admin.products.detail', $product->id) }}'">← Quay lại</button>
      <button type="submit" form="productForm" class="btn btn-primary btn-sm">💾 Lưu thay đổi</button>
    </div>
  </div>

  @if($errors->any())
    <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.3);border-radius:var(--r-md);padding:12px 16px;margin-bottom:16px;font-size:.82rem;color:var(--red);">
      <strong>⚠️ Có lỗi:</strong>
      <ul style="margin:6px 0 0 16px;">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="add-product-grid" style="display:grid;grid-template-columns:2fr 1fr;gap:16px;align-items:start;">
    {{-- LEFT COLUMN --}}
    <div>
      {{-- Thông tin cơ bản --}}
      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">📝 Thông tin cơ bản</div>
          <div class="form-grid">
            <div class="fg form-full">
              <label>Tên sản phẩm *</label>
              <input type="text" name="name" placeholder="VD: Cam Navel Úc" required value="{{ old('name', $product->name) }}"/>
            </div>
            <div class="fg">
              <label>Danh mục *</label>
              <select name="category">
                <option value="Nhập khẩu" {{ $product->category === 'Nhập khẩu' ? 'selected' : '' }}>Nhập khẩu</option>
                <option value="Tươi lẻ" {{ $product->category === 'Tươi lẻ' ? 'selected' : '' }}>Tươi lẻ</option>
                <option value="Giỏ quà" {{ $product->category === 'Giỏ quà' ? 'selected' : '' }}>Giỏ quà</option>
              </select>
            </div>
            <div class="fg">
              <label>Xuất xứ *</label>
              <input type="text" name="origin" placeholder="VD: Úc, Việt Nam" required value="{{ old('origin', $product->origin) }}"/>
            </div>
            <div class="fg form-full">
              <label>Mô tả ngắn & chi tiết</label>
              <textarea name="desc" style="min-height:100px;" placeholder="Nhập mô tả sản phẩm...">{{ old('desc', $product->desc) }}</textarea>
            </div>
            <div class="fg form-full">
              <label>Thông tin dinh dưỡng</label>
              <textarea name="nutrition" style="min-height:80px;" placeholder="VD: Mỗi 100g: 32 kcal · Carbs 7.7g ...">{{ old('nutrition', $product->nutrition) }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- Giá & Kho hàng --}}
      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">💰 Giá & Kho hàng</div>
          <div class="form-grid">
            <div class="fg">
              <label>Giá bán *</label>
              <div class="input-group">
                <input type="number" name="price" placeholder="65000" required value="{{ old('price', $product->price) }}"/>
                <div class="input-addon">đ</div>
              </div>
            </div>
            <div class="fg">
              <label>Đơn vị tính *</label>
              <select name="unit">
                <option value="kg" {{ $product->unit === 'kg' ? 'selected' : '' }}>kg</option>
                <option value="hộp" {{ $product->unit === 'hộp' ? 'selected' : '' }}>hộp</option>
                <option value="túi" {{ $product->unit === 'túi' ? 'selected' : '' }}>túi</option>
                <option value="trái" {{ $product->unit === 'trái' ? 'selected' : '' }}>trái</option>
                <option value="giỏ" {{ $product->unit === 'giỏ' ? 'selected' : '' }}>giỏ</option>
              </select>
            </div>
            <div class="fg">
              <label>Giá gốc (để tính % giảm giá)</label>
              <div class="input-group">
                <input type="number" name="original_price" placeholder="75000" value="{{ old('original_price', $product->original_price) }}"/>
                <div class="input-addon">đ</div>
              </div>
            </div>
            <div class="fg">
              <label>Quy cách đóng gói</label>
              <input type="text" name="pack" placeholder="VD: Mua theo kg, hộp 500g" value="{{ old('pack', $product->pack) }}"/>
            </div>
          </div>
        </div>
      </div>

      {{-- ẢNH PHỤ --}}
      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">🖼️ Ảnh sản phẩm phụ <span style="font-size:.72rem;color:var(--text-3);font-weight:400;">(tối đa 5 ảnh, mỗi ảnh ≤ 5MB — hiển thị trong trang chi tiết)</span></div>

          {{-- Ảnh phụ hiện tại --}}
          @php $existingImages = $product->images ?? []; @endphp
          <div id="existing-images-container" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
            @foreach($existingImages as $idx => $imgPath)
              @php $imgUrl = $product->images_urls[$idx] ?? null; @endphp
              @if($imgUrl)
                <div class="sub-img-item" id="sub-img-{{ $idx }}" style="position:relative;width:90px;height:90px;border-radius:var(--r-md);border:2px solid var(--border);overflow:hidden;">
                  <img src="{{ $imgUrl }}" style="width:100%;height:100%;object-fit:cover;" alt="Ảnh phụ {{ $idx+1 }}">
                  <input type="hidden" name="existing_images[]" value="{{ $imgPath }}" id="existing-input-{{ $idx }}">
                  <button type="button" onclick="removeExistingImage({{ $idx }})" style="position:absolute;top:2px;right:2px;background:rgba(239,68,68,.85);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:11px;line-height:20px;text-align:center;cursor:pointer;display:flex;align-items:center;justify-content:center;">×</button>
                </div>
              @endif
            @endforeach
          </div>

          {{-- Upload ảnh phụ mới --}}
          <div id="sub-images-upload-area" style="position:relative;border:2px dashed var(--border);border-radius:var(--r-md);padding:16px;text-align:center;cursor:pointer;transition:all .2s;background:var(--bg);" onmouseenter="this.style.borderColor='var(--g-light)'" onmouseleave="this.style.borderColor='var(--border)'">
            <input type="file" name="images[]" accept="image/*" multiple id="subImagesInput" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" onchange="previewSubImages(event)"/>
            <div style="font-size:1.5rem;margin-bottom:4px;">📷</div>
            <p><strong>Nhấp để thêm ảnh phụ</strong></p>
            <p style="font-size:.72rem;color:var(--text-3);">PNG, JPG, WEBP · Tối đa 5MB/ảnh · Tổng tối đa 5 ảnh</p>
          </div>
          <div id="sub-images-new-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
        </div>
      </div>

      {{-- VIDEO --}}
      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">🎬 Video sản phẩm
            <span style="font-size:.72rem;color:var(--text-3);font-weight:400;">(MP4/WebM ≤ 100MB · hoặc link YouTube/Shorts)</span>
          </div>

          {{-- Video hiện tại --}}
          @if($product->video)
            <div id="current-video-container" style="margin-bottom:14px;">
              @if($product->is_youtube)
                {{-- YouTube embed --}}
                <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:var(--r-md);background:#000;">
                  <iframe src="{{ $product->video_embed_url }}?rel=0"
                    style="position:absolute;top:0;left:0;width:100%;height:100%;"
                    frameborder="0" allowfullscreen loading="lazy"></iframe>
                </div>
                <div style="margin-top:8px;display:flex;align-items:center;gap:8px;">
                  <span style="font-size:.72rem;background:rgba(255,0,0,.1);color:#c00;border-radius:50px;padding:2px 10px;font-weight:700;">▶ YouTube</span>
                  <span style="font-size:.72rem;color:var(--text-3);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $product->video }}</span>
                </div>
              @else
                {{-- File video --}}
                <video src="{{ $product->video_url }}" controls
                  style="width:100%;max-height:200px;border-radius:var(--r-md);border:1px solid var(--border);background:#000;"></video>
                <div style="margin-top:6px;display:flex;align-items:center;gap:8px;">
                  <span style="font-size:.72rem;background:rgba(76,175,128,.1);color:var(--g-mid);border-radius:50px;padding:2px 10px;font-weight:700;">📁 File</span>
                </div>
              @endif

              <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;cursor:pointer;color:var(--red);margin-top:10px;">
                <input type="checkbox" name="remove_video" value="1" id="removeVideoCheck" onchange="toggleVideoUpload(this.checked)">
                Xóa video hiện tại và thay bằng video khác
              </label>
            </div>
          @endif

          {{-- Tabs chọn phương thức (ẩn nếu đã có video và chưa tick xóa) --}}
          <div id="video-input-panel" style="{{ $product->video ? 'display:none;' : '' }}">
            @php $isYt = $product->is_youtube; @endphp
            <input type="hidden" name="video_source" id="videoSourceInput" value="{{ $isYt ? 'youtube' : 'upload' }}">

            {{-- Tab buttons --}}
            <div style="display:flex;gap:0;border:1.5px solid var(--border);border-radius:var(--r-md);overflow:hidden;margin-bottom:14px;">
              <button type="button" id="tab-upload-btn" onclick="switchVideoTab('upload')"
                style="flex:1;padding:9px;border:none;font-size:.8rem;font-weight:600;cursor:pointer;{{ !$isYt ? 'background:var(--g-dark);color:#fff;' : 'background:var(--bg);color:var(--text-2);' }} transition:all .2s;">
                📁 Upload file
              </button>
              <button type="button" id="tab-youtube-btn" onclick="switchVideoTab('youtube')"
                style="flex:1;padding:9px;border:none;font-size:.8rem;font-weight:600;cursor:pointer;{{ $isYt ? 'background:var(--g-dark);color:#fff;' : 'background:var(--bg);color:var(--text-2);' }} transition:all .2s;">
                ▶ YouTube / Shorts
              </button>
            </div>

            {{-- Panel: Upload file --}}
            <div id="panel-upload" style="{{ !$isYt ? '' : 'display:none;' }}">
              <div id="video-upload-area" style="position:relative;border:2px dashed var(--border);border-radius:var(--r-md);padding:16px;text-align:center;cursor:pointer;transition:all .2s;background:var(--bg);" onmouseenter="this.style.borderColor='var(--g-light)'" onmouseleave="this.style.borderColor='var(--border)'">
                <input type="file" name="video" accept="video/mp4,video/webm,video/ogg,video/quicktime" id="videoInput" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" onchange="previewVideo(event)"/>
                <div style="font-size:1.5rem;margin-bottom:4px;">🎥</div>
                <p><strong>Nhấp để tải video lên</strong></p>
                <p style="font-size:.72rem;color:var(--text-3);">MP4, WebM, MOV · Tối đa 100MB</p>
              </div>
              <div id="video-new-preview" style="display:none;margin-top:10px;">
                <video id="videoPreviewEl" controls style="width:100%;max-height:200px;border-radius:var(--r-md);border:1px solid var(--border);background:#000;"></video>
                <button type="button" onclick="removeVideoPreview()" style="margin-top:6px;font-size:.78rem;color:var(--red);background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;">× Bỏ chọn video</button>
              </div>
            </div>

            {{-- Panel: YouTube URL --}}
            <div id="panel-youtube" style="{{ $isYt ? '' : 'display:none;' }}">
              <div class="fg">
                <label style="font-size:.8rem;font-weight:700;color:var(--text);">Link YouTube hoặc YouTube Shorts</label>
                <input type="url" name="video_embed_url" id="youtubeUrlInput"
                  value="{{ $isYt ? $product->video : '' }}"
                  placeholder="https://youtube.com/watch?v=... hoặc https://youtu.be/... hoặc https://youtube.com/shorts/..."
                  style="border:1.5px solid var(--border);border-radius:var(--r-md);padding:10px 12px;font-size:.875rem;outline:none;transition:all .2s;color:var(--text);"
                  oninput="previewYouTube(this.value)"/>
                <div style="font-size:.72rem;color:var(--text-3);margin-top:4px;">
                  Hỗ trợ: youtube.com/watch?v=ID · youtu.be/ID · youtube.com/shorts/ID
                </div>
              </div>
              {{-- Preview YouTube iframe --}}
              <div id="youtube-preview-wrap" style="display:none;margin-top:12px;border-radius:var(--r-md);overflow:hidden;border:1.5px solid var(--g-light);position:relative;padding-bottom:56.25%;height:0;">
                <iframe id="youtube-preview-iframe" src="" frameborder="0" allowfullscreen loading="lazy"
                  style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
              </div>
              <div id="youtube-invalid-msg" style="display:none;margin-top:8px;font-size:.78rem;color:var(--red);">
                ⚠️ Link không đúng định dạng YouTube. Vui lòng kiểm tra lại.
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div>
      {{-- ẢNH CHÍNH --}}
      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">🖼️ Ảnh chính (đại diện) <span style="font-size:.72rem;color:var(--text-3);font-weight:400;">≤ 5MB</span></div>

          {{-- Ảnh chính hiện tại --}}
          @if($product->image_url)
            <div id="current-main-img" style="margin-bottom:10px;position:relative;width:100%;aspect-ratio:1;border-radius:var(--r-md);overflow:hidden;border:2px solid var(--border);">
              <img id="mainImgCurrent" src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width:100%;height:100%;object-fit:cover;">
              <button type="button" onclick="removeMainImage()" style="position:absolute;top:6px;right:6px;background:rgba(239,68,68,.85);color:#fff;border:none;border-radius:50%;width:24px;height:24px;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;">×</button>
            </div>
            <input type="hidden" name="remove_image" value="0" id="removeImageFlag">
          @endif

          {{-- Vùng upload ảnh chính --}}
          <div id="main-img-upload-area" style="position:relative;border:2px dashed var(--border);border-radius:var(--r-md);padding:20px;text-align:center;cursor:pointer;{{ $product->image_url ? 'display:none;' : '' }}transition:all .2s;background:var(--bg);" onmouseenter="this.style.borderColor='var(--g-light)'" onmouseleave="this.style.borderColor='var(--border)'">
            <input type="file" name="image" accept="image/*" id="mainImageInput" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" onchange="previewMainImage(event)"/>
            <div class="iu-icon">📁</div>
            <p><strong>Nhấp để thay đổi ảnh chính</strong></p>
            <p>hoặc kéo thả vào đây</p>
            <p style="margin-top:6px;font-size:.72rem;">PNG, JPG, WEBP · Tối đa 5MB</p>
          </div>

          {{-- Preview ảnh chính mới --}}
          <div id="main-img-new-preview" style="display:none;position:relative;margin-top:8px;aspect-ratio:1;border-radius:var(--r-md);overflow:hidden;border:2px solid var(--g-light);">
            <img id="mainImgPreview" src="" alt="Preview" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" onclick="removeMainPreview()" style="position:absolute;top:6px;right:6px;background:rgba(239,68,68,.85);color:#fff;border:none;border-radius:50%;width:24px;height:24px;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;">×</button>
          </div>
        </div>
      </div>

      {{-- Cài đặt hiển thị --}}
      <div class="card mb-16">
        <div class="card-body">
          <div class="form-section-title">⚙️ Cài đặt hiển thị</div>
          <div style="display:flex;flex-direction:column;gap:14px;">
            <div class="flex justify-between items-center">
              <div>
                <div style="font-size:.85rem;font-weight:600;">Hiển thị trên web</div>
                <div style="font-size:.72rem;color:var(--text-3);">Khách hàng thấy sản phẩm</div>
              </div>
              <input type="hidden" name="is_active" id="isActiveInput" value="{{ $product->trashed() ? '0' : '1' }}">
              <button type="button" id="isActiveToggle" class="toggle {{ $product->trashed() ? '' : 'on' }}" onclick="toggleIsActive()"></button>
            </div>
            <div class="flex justify-between items-center">
              <div>
                <div style="font-size:.85rem;font-weight:600;">Nổi bật trang chủ</div>
                <div style="font-size:.72rem;color:var(--text-3);">Hiển thị ở phần "Yêu thích"</div>
              </div>
              <button type="button" class="toggle on" onclick="this.classList.toggle('on')"></button>
            </div>
            <div class="flex justify-between items-center">
              <div>
                <div style="font-size:.85rem;font-weight:600;">Bán trong ngày</div>
                <div style="font-size:.72rem;color:var(--text-3);">Hiển thị trong mục "Bán trong ngày"</div>
              </div>
              <input type="hidden" name="is_daily" id="isDailyInput" value="{{ old('is_daily', $product->is_daily) ? '1' : '0' }}">
              <button type="button" id="isDailyToggle" class="toggle {{ old('is_daily', $product->is_daily) ? 'on' : '' }}" onclick="toggleIsDaily()"></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
  // ========== ẢNH CHÍNH ==========
  function previewMainImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('mainImgPreview').src = e.target.result;
      document.getElementById('main-img-new-preview').style.display = 'block';
      document.getElementById('main-img-upload-area').style.display = 'none';
      // Ẩn ảnh cũ nếu có
      const cur = document.getElementById('current-main-img');
      if (cur) cur.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  function removeMainPreview() {
    document.getElementById('mainImageInput').value = '';
    document.getElementById('mainImgPreview').src = '';
    document.getElementById('main-img-new-preview').style.display = 'none';
    // Hiện lại ảnh cũ nếu có, hoặc vùng upload
    const cur = document.getElementById('current-main-img');
    if (cur) {
      cur.style.display = 'block';
    } else {
      document.getElementById('main-img-upload-area').style.display = 'block';
    }
  }

  function removeMainImage() {
    // Ẩn ảnh cũ, hiện vùng upload
    const cur = document.getElementById('current-main-img');
    if (cur) cur.style.display = 'none';
    document.getElementById('main-img-upload-area').style.display = 'block';
    // Đánh dấu cần xóa ảnh cũ
    const flag = document.getElementById('removeImageFlag');
    if (flag) flag.value = '1';
  }

  // ========== ẢNH PHỤ ==========
  let newSubFiles = []; // Lưu trữ các đối tượng File mới đã tích lũy

  function previewSubImages(event) {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;

    // Lọc kích thước tối đa 5MB mỗi ảnh
    const validFiles = [];
    for (let file of files) {
      if (file.size > 5 * 1024 * 1024) {
        showToast(`Ảnh "${file.name}" vượt quá 5MB và đã bị bỏ qua.`, 'error');
      } else {
        validFiles.push(file);
      }
    }

    const existingCount = document.querySelectorAll('#existing-images-container .sub-img-item:not(.deleted)').length;
    const currentNewCount = newSubFiles.length;
    const allowed = 5 - existingCount - currentNewCount;

    if (validFiles.length > allowed) {
      showToast(`Chỉ nhận thêm tối đa ${allowed} ảnh phụ nữa.`, 'warning');
      newSubFiles = newSubFiles.concat(validFiles.slice(0, allowed));
    } else {
      newSubFiles = newSubFiles.concat(validFiles);
    }

    // Cập nhật lại input.files và giao diện preview
    syncSubImagesInput();
    renderSubImagesPreview();
  }

  function renderSubImagesPreview() {
    const container = document.getElementById('sub-images-new-preview');
    container.innerHTML = '';

    newSubFiles.forEach((file, idx) => {
      // 1. Tạo div preview đồng bộ để giữ nguyên thứ tự hiển thị của các ảnh
      const div = document.createElement('div');
      div.id = `new-sub-preview-${idx}`;
      div.style.cssText = 'position:relative;width:90px;height:90px;border-radius:8px;border:2px solid var(--g-light);overflow:hidden;';
      div.innerHTML = `
        <img id="new-sub-img-${idx}" src="" style="width:100%;height:100%;object-fit:cover;display:none;" alt="Ảnh phụ mới ${idx+1}">
        <div id="new-sub-loading-${idx}" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:var(--text-3);background:var(--bg);">...</div>
        <button type="button" onclick="removeNewSubImage(${idx})"
          style="position:absolute;top:2px;right:2px;background:rgba(239,68,68,.85);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:10;">×</button>
        <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.5);color:#fff;font-size:.6rem;text-align:center;padding:2px;z-index:5;">Mới</div>
      `;
      container.appendChild(div);

      // 2. Đọc file bất đồng bộ và gán src hình ảnh sau
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = document.getElementById(`new-sub-img-${idx}`);
        const loading = document.getElementById(`new-sub-loading-${idx}`);
        if (img) {
          img.src = e.target.result;
          img.style.display = 'block';
        }
        if (loading) {
          loading.style.display = 'none';
        }
      };
      reader.readAsDataURL(file);
    });
  }

  function removeNewSubImage(idx) {
    newSubFiles.splice(idx, 1);
    syncSubImagesInput();
    renderSubImagesPreview();
    showToast('Đã xóa ảnh phụ mới chọn.', 'info');
  }

  function syncSubImagesInput() {
    const dt = new DataTransfer();
    newSubFiles.forEach(file => dt.items.add(file));
    document.getElementById('subImagesInput').files = dt.files;
  }

  function removeExistingImage(idx) {
    const container = document.getElementById(`sub-img-${idx}`);
    if (container) {
      container.classList.add('deleted');
      container.style.display = 'none';
    }
    // Xóa input hidden
    const input = document.getElementById(`existing-input-${idx}`);
    if (input) input.disabled = true;
  }

  // Visual drag-and-drop feedback & form submit handler
  document.addEventListener('DOMContentLoaded', () => {
    // Khởi tạo CKEditor
    const initCKEditor = (selector) => {
      const el = document.querySelector(selector);
      if (!el) return;
      ClassicEditor
        .create(el, {
          toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'outdent', 'indent', '|', 'blockQuote', 'undo', 'redo' ]
        })
        .then(editor => {
          // Gán phím Tab để thụt dòng (indent), Shift + Tab để lùi dòng (outdent)
          editor.keystrokes.set('Tab', (data, cancel) => {
            cancel(); // Luôn chặn chuyển focus mặc định của trình duyệt
            const indentList = editor.commands.get('indentList');
            const indent = editor.commands.get('indent');
            if (indentList && indentList.isEnabled) {
              editor.execute('indentList');
            } else if (indent && indent.isEnabled) {
              editor.execute('indent');
            } else {
              // Chèn khoảng trắng thụt lề khi các lệnh thụt lề bị vô hiệu hóa
              editor.model.change(writer => {
                writer.insertText('    ', editor.model.document.selection.getFirstPosition());
              });
            }
          });
          editor.keystrokes.set('Shift+Tab', (data, cancel) => {
            cancel(); // Luôn chặn chuyển focus mặc định của trình duyệt
            const outdentList = editor.commands.get('outdentList');
            const outdent = editor.commands.get('outdent');
            if (outdentList && outdentList.isEnabled) {
              editor.execute('outdentList');
            } else if (outdent && outdent.isEnabled) {
              editor.execute('outdent');
            }
          });
        })
        .catch(error => {
          console.error(error);
        });
    };

    initCKEditor('textarea[name="desc"]');
    initCKEditor('textarea[name="nutrition"]');

    const subArea = document.getElementById('sub-images-upload-area');
    const subInput = document.getElementById('subImagesInput');
    if (subInput && subArea) {
      subInput.addEventListener('dragenter', () => {
        subArea.style.borderColor = 'var(--g-mid)';
        subArea.style.background = 'rgba(76,175,128,.05)';
      });
      subInput.addEventListener('dragleave', () => {
        subArea.style.borderColor = 'var(--border)';
        subArea.style.background = 'var(--bg)';
      });
      subInput.addEventListener('drop', () => {
        subArea.style.borderColor = 'var(--border)';
        subArea.style.background = 'var(--bg)';
      });
    }

    // Clear inactive video input on form submit based on selected video source
    const productForm = document.getElementById('productForm');
    if (productForm) {
      productForm.addEventListener('submit', (e) => {
        const videoSourceInput = document.getElementById('videoSourceInput');
        if (videoSourceInput) {
          const source = videoSourceInput.value;
          if (source === 'upload') {
            const ytInput = document.getElementById('youtubeUrlInput');
            if (ytInput) ytInput.value = '';
          } else if (source === 'youtube') {
            const viInput = document.getElementById('videoInput');
            if (viInput) viInput.value = '';
          }
        }
      });
    }
  });

  // ========== VIDEO ==========
  function previewVideo(event) {
    const file = event.target.files[0];
    if (!file) return;

    const maxSize = 100 * 1024 * 1024; // 100MB
    if (file.size > maxSize) {
      showToast('Video vượt quá 100MB!', 'error');
      event.target.value = '';
      return;
    }

    const url = URL.createObjectURL(file);
    document.getElementById('videoPreviewEl').src = url;
    document.getElementById('video-new-preview').style.display = 'block';
    document.getElementById('video-upload-area').style.display = 'none';
  }

  function removeVideoPreview() {
    document.getElementById('videoInput').value = '';
    document.getElementById('videoPreviewEl').src = '';
    document.getElementById('video-new-preview').style.display = 'none';
    document.getElementById('video-upload-area').style.display = 'block';
  }

  function toggleVideoUpload(checked) {
    const panel = document.getElementById('video-input-panel');
    if (checked) {
      panel.style.display = 'block';
    } else {
      panel.style.display = 'none';
    }
  }

  // ===== YOUTUBE TAB =====
  function switchVideoTab(tab) {
    const uploadBtn  = document.getElementById('tab-upload-btn');
    const ytBtn      = document.getElementById('tab-youtube-btn');
    const panelUp    = document.getElementById('panel-upload');
    const panelYt    = document.getElementById('panel-youtube');
    const activeStyle = 'background:var(--g-dark);color:#fff;';
    const inactiveStyle = 'background:var(--bg);color:var(--text-2);';

    if (tab === 'upload') {
      uploadBtn.style.cssText  += activeStyle;
      ytBtn.style.cssText      += inactiveStyle;
      panelUp.style.display    = 'block';
      panelYt.style.display    = 'none';
      document.getElementById('videoSourceInput').value = 'upload';
    } else {
      ytBtn.style.cssText      += activeStyle;
      uploadBtn.style.cssText  += inactiveStyle;
      panelYt.style.display    = 'block';
      panelUp.style.display    = 'none';
      document.getElementById('videoSourceInput').value = 'youtube';
    }
  }

  function extractYouTubeId(url) {
    let m;
    // Shorts
    m = url.match(/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/);
    if (m) return m[1];
    // watch?v=
    m = url.match(/youtube\.com\/watch\?(?:.*&)?v=([a-zA-Z0-9_-]+)/);
    if (m) return m[1];
    // youtu.be
    m = url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
    if (m) return m[1];
    return null;
  }

  function previewYouTube(url) {
    const wrap    = document.getElementById('youtube-preview-wrap');
    const iframe  = document.getElementById('youtube-preview-iframe');
    const invalid = document.getElementById('youtube-invalid-msg');
    const id = extractYouTubeId(url.trim());

    if (id) {
      iframe.src = 'https://www.youtube.com/embed/' + id + '?rel=0';
      wrap.style.display    = 'block';
      invalid.style.display = 'none';
    } else if (url.trim().length > 10) {
      wrap.style.display    = 'none';
      invalid.style.display = 'block';
      iframe.src = '';
    } else {
      clearYouTubePreview();
    }
  }

  function clearYouTubePreview() {
    const wrap    = document.getElementById('youtube-preview-wrap');
    const iframe  = document.getElementById('youtube-preview-iframe');
    const invalid = document.getElementById('youtube-invalid-msg');
    if (wrap) wrap.style.display = 'none';
    if (iframe) iframe.src = '';
    if (invalid) invalid.style.display = 'none';
  }

  function toggleIsActive() {
    const input = document.getElementById('isActiveInput');
    const btn = document.getElementById('isActiveToggle');
    if (input.value === '1') {
      input.value = '0';
      btn.classList.remove('on');
    } else {
      input.value = '1';
      btn.classList.add('on');
    }
  }

  function toggleIsDaily() {
    const input = document.getElementById('isDailyInput');
    const btn = document.getElementById('isDailyToggle');
    if (input.value === '1') {
      input.value = '0';
      btn.classList.remove('on');
    } else {
      input.value = '1';
      btn.classList.add('on');
    }
  }

  @if(session('success'))
  document.addEventListener('DOMContentLoaded', function(){ showToast('{{ session('success') }}', 'success'); });
  @endif
</script>
@endpush
