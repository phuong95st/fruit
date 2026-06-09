@extends('layouts.app')

@section('title', 'Dịch Vụ Đặc Biệt | Lễ Tết · Tâm Linh · Sự Kiện | FruitNest')
@section('meta_description', 'FruitNest cung cấp dịch vụ đặt làm giỏ quà, mâm quả, đĩa quả thắp hương, mâm lễ chùa, cưới hỏi theo yêu cầu riêng và khoảng giá.')

@section('content')
<div class="page active" id="page-services">
<div class="wrap page-wrap">
  <div class="breadcrumb">
    <a class="bc-link" href="{{ route('home') }}">Trang chủ</a>
    <span class="bc-sep">›</span>
    <span class="bc-cur">Dịch vụ đặc biệt</span>
  </div>
  
  <div style="background:linear-gradient(135deg,#7a5c00,#b8860b);border-radius:var(--radius-lg);padding:16px 20px;color:#fff;margin-bottom:10px;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.75;margin-bottom:4px;">Dịch vụ đặc biệt</div>
    <h1 style="font-family:'Merriweather',serif;font-size:var(--fs-2xl);font-weight:700;margin-bottom:6px; color:#fff;">Lễ tết · Tâm linh · Sự kiện</h1>
    <p style="font-size:var(--fs-base);opacity:.85;max-width:600px; margin:0;">Chuyên nhận làm giỏ quà, mâm quả, đĩa quả cho mọi dịp lễ tết, ma chay, cưới hỏi, thắp hương gia đình và đi lễ đền chùa. Đặt theo khoảng giá và yêu cầu cụ thể.</p>
  </div>

  <!-- Service Navigation Tabs -->
  <div class="service-tabs">
    <button class="service-tab on" onclick="switchServiceTab(this,'sv-tet')">Lễ tết</button>
    <button class="service-tab" onclick="switchServiceTab(this,'sv-tamlinh')">Tâm linh & Thắp hương</button>
    <button class="service-tab" onclick="switchServiceTab(this,'sv-cuoihoi')">Cưới hỏi</button>
    <button class="service-tab" onclick="switchServiceTab(this,'sv-machay')">Ma chay</button>
    <button class="service-tab" onclick="switchServiceTab(this,'sv-custom')">Đặt theo yêu cầu</button>
  </div>

  <!-- Tab Content: Lễ tết -->
  <div id="sv-tet" class="service-panel">
    <div class="service-hero-card">
      <div class="shc-icon si-gold"><svg viewBox="0 0 24 24"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/></svg></div>
      <div class="shc-text">
        <h2 class="shc-title">Giỏ quà Tết & Quà tặng ngày lễ</h2>
        <p class="shc-desc">Chuyên thiết kế giỏ quà Tết Nguyên Đán, Tết Trung Thu, ngày Quốc tế Phụ nữ 8/3, ngày Nhà giáo Việt Nam 20/11 sang trọng và trang nhã, cam kết hoa quả nhập khẩu tươi ngon.</p>
        <div class="shc-tags"><span class="shc-tag">Hoa quả nhập khẩu</span><span class="shc-tag">Nơ & Ruy băng lụa</span><span class="shc-tag">Tặng thiệp đi kèm</span></div>
      </div>
    </div>
    
    <h3 style="font-family:'Merriweather',serif;font-size:var(--fs-lg);color:var(--n900);margin-bottom:8px;">Các mức giá phổ biến</h3>
    <div class="price-range-cards">
      <div class="prc-card" onclick="alert('Hãy điền vào form đặt hàng bên dưới hoặc liên hệ hotline để nhận tư vấn giỏ quà 350k')">
        <div class="prc-img"><span style="font-size:40px;">🎁</span></div>
        <div class="prc-price-range">350.000đ</div>
        <div class="prc-name">Giỏ quà Ấm Áp</div>
        <div class="prc-desc">Hoa quả tươi nội địa & cam ngọt</div>
      </div>
      <div class="prc-card" onclick="alert('Hãy điền vào form đặt hàng bên dưới hoặc liên hệ hotline để nhận tư vấn giỏ quà 650k')">
        <div class="prc-img"><span style="font-size:40px;">🧺</span></div>
        <div class="prc-price-range">650.000đ</div>
        <div class="prc-name">Giỏ quà Sum Vầy</div>
        <div class="prc-desc">Kết hợp lê Hàn Quốc & táo Mỹ</div>
      </div>
      <div class="prc-card" onclick="alert('Hãy điền vào form đặt hàng bên dưới hoặc liên hệ hotline để nhận tư vấn giỏ quà 1 triệu')">
        <div class="prc-img"><span style="font-size:40px;">🌟</span></div>
        <div class="prc-price-range">1.000.000đ</div>
        <div class="prc-name">Giỏ quà Thịnh Vượng</div>
        <div class="prc-desc">100% trái cây nhập khẩu thượng hạng</div>
      </div>
      <div class="prc-card" onclick="alert('Hãy điền vào form đặt hàng bên dưới hoặc liên hệ hotline để nhận tư vấn giỏ quà VIP')">
        <div class="prc-img"><span style="font-size:40px;">👑</span></div>
        <div class="prc-price-range">Trên 1.500.000đ</div>
        <div class="prc-name">Giỏ quà Hoàng Gia</div>
        <div class="prc-desc">Nho mẫu đơn, cherry Mỹ & lê vàng</div>
      </div>
    </div>
  </div>

  <!-- Tab Content: Tâm linh -->
  <div id="sv-tamlinh" class="service-panel" style="display:none;">
    <div class="service-hero-card">
      <div class="shc-icon si-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div>
      <div class="shc-text">
        <h2 class="shc-title">Mâm lễ & Đĩa quả thắp hương</h2>
        <p class="shc-desc">Chuẩn bị đĩa hoa quả ngũ quả dâng gia tiên mùng 1, ngày rằm hoặc sắp mâm lễ cúng đi chùa, cúng động thổ, khai trương chuẩn phong tục tập quán Việt Nam.</p>
        <div class="shc-tags"><span class="shc-tag">Mâm ngũ quả phong thủy</span><span class="shc-tag">Hoa tươi đính kèm</span><span class="shc-tag">Vận chuyển lạnh siêu tốc</span></div>
      </div>
    </div>
  </div>

  <!-- Tab Content: Cưới hỏi -->
  <div id="sv-cuoihoi" class="service-panel" style="display:none;">
    <div class="service-hero-card">
      <div class="shc-icon si-purple"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></div>
      <div class="shc-text">
        <h2 class="shc-title">Mâm quả cưới hỏi & Kết rồng phượng</h2>
        <p class="shc-desc">Chuyên làm tráp quả cưới hỏi kết đôi, tráp rồng phượng kết tinh xảo bằng hoa quả tươi cho ngày cưới đại hỷ trọn vẹn ý nghĩa.</p>
        <div class="shc-tags"><span class="shc-tag">Tráp ăn hỏi</span><span class="shc-tag">Kết rồng phượng nghệ thuật</span><span class="shc-tag">Bao trọn phụ kiện chữ hỷ</span></div>
      </div>
    </div>
  </div>

  <!-- Tab Content: Ma chay -->
  <div id="sv-machay" class="service-panel" style="display:none;">
    <div class="service-hero-card">
      <div class="shc-icon si-green"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg></div>
      <div class="shc-text">
        <h2 class="shc-title">Giỏ quả phúng viếng & Chia buồn</h2>
        <p class="shc-desc">Thiết kế giỏ hoa quả tang lễ trang trọng, sử dụng trái cây tươi sạch màu sắc nhã nhặn kèm ruy băng đen/tím chia buồn thành kính.</p>
        <div class="shc-tags"><span class="shc-tag">Giao tang lễ đúng giờ</span><span class="shc-tag">Trang trọng kính viếng</span><span class="shc-tag">Trái cây chất lượng</span></div>
      </div>
    </div>
  </div>

  <!-- Tab Content: Custom Request Form -->
  <div id="sv-custom" class="service-panel" style="display:none;">
    <div class="request-form">
      <h2 class="request-form-title">Yêu cầu báo giá dịch vụ riêng</h2>
      <form onsubmit="alert('Đã nhận yêu cầu! Chúng tôi sẽ gọi lại ngay cho bạn.'); return false;">
        <div class="form-row-2">
          <div class="form-group"><label class="form-label">Họ và tên *</label><input class="form-input" placeholder="Nguyễn Văn A" required/></div>
          <div class="form-group"><label class="form-label">Số điện thoại *</label><input class="form-input" placeholder="0909 123 456" required/></div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Loại dịch vụ</label>
            <select class="form-input">
              <option>Giỏ quà biếu tặng</option>
              <option>Đĩa quả thắp hương</option>
              <option>Tráp cưới hỏi</option>
              <option>Mâm quả viếng</option>
              <option>Khác</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Mức giá ước lượng (VNĐ)</label><input class="form-input" placeholder="Ví dụ: 800.000"/></div>
        </div>
        <div class="form-group"><label class="form-label">Mô tả chi tiết yêu cầu của bạn</label><textarea class="form-input" rows="4" placeholder="Ví dụ: Tôi muốn đặt giỏ quà gồm lê Hàn Quốc, nho Mỹ, táo, có kèm ruy băng đỏ và viết thiệp chúc mừng sinh nhật đối tác..."></textarea></div>
        <button type="submit" class="btn btn-primary">Gửi yêu cầu báo giá</button>
      </form>
    </div>
  </div>

  <!-- FAQ Section -->
  <div class="service-faq" style="margin-top:20px;">
    <div style="padding:12px; font-weight:700; font-size:var(--fs-lg); background:var(--g50); color:var(--g900); border-bottom:1px solid var(--n100);">Câu hỏi thường gặp (FAQ)</div>
    
    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        <span>1. Tôi cần đặt trước bao lâu đối với giỏ quà/mâm quả?</span>
        <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="faq-a">Với giỏ quà tặng tiêu chuẩn và đĩa quả thắp hương, chúng tôi có thể chuẩn bị và giao ngay trong 2 giờ. Đối với tráp cưới hỏi rồng phượng tinh xảo, quý khách vui lòng liên hệ trước 2-3 ngày để được phục vụ tốt nhất.</div>
    </div>
    
    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        <span>2. Trái cây trong giỏ có đảm bảo tươi ngon và đúng cam kết không?</span>
        <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="faq-a">FruitNest cam kết 100% trái cây được đóng giỏ là trái cây tươi ngon tuyển chọn trong ngày. Nếu khách hàng phát hiện trái cây hỏng, dập úng, chúng tôi cam kết hoàn tiền 100% hoặc đổi mới ngay lập tức.</div>
    </div>
    
    <div class="faq-item">
      <div class="faq-q" onclick="toggleFaq(this)">
        <span>3. Cửa hàng có xuất hóa đơn đỏ (VAT) cho doanh nghiệp không?</span>
        <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="faq-a">Có, chúng tôi hỗ trợ xuất hóa đơn điện tử VAT cho các cơ quan, đoàn thể và doanh nghiệp khi đặt hàng quà tặng số lượng lớn.</div>
    </div>
  </div>
</div>
</div>
@endsection

@section('scripts')
<script>
    const svTabs = ['sv-tet', 'sv-tamlinh', 'sv-cuoihoi', 'sv-machay', 'sv-custom'];
    
    function switchServiceTab(btn, id) {
        document.querySelectorAll('.service-tab').forEach(t => t.classList.remove('on'));
        btn.classList.add('on');
        
        svTabs.forEach(t => {
            const el = document.getElementById(t);
            if (el) el.style.display = 'none';
        });
        
        const target = document.getElementById(id);
        if (target) target.style.display = 'block';
    }
    
    function toggleFaq(el) {
        el.classList.toggle('open');
        const ans = el.nextElementSibling;
        ans.classList.toggle('show');
    }

    // Auto switch tab if URL contains hash
    window.addEventListener('DOMContentLoaded', () => {
        const hash = window.location.hash;
        if (hash) {
            const tabId = hash.substring(1);
            const tabBtn = Array.from(document.querySelectorAll('.service-tab')).find(btn => {
                return btn.getAttribute('onclick').includes(tabId);
            });
            if (tabBtn) {
                switchServiceTab(tabBtn, tabId);
            }
        }
    });
</script>
@endsection
