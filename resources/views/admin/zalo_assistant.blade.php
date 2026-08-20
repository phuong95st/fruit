@extends('admin.layouts.app')

@section('title', 'Quản Trị Zalo Bot Trợ Lý')

@section('content')
<div class="content-header" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">🤖 Quản Trị Nick Trợ Lý Zalo</h1>
        <p style="color: #64748b; font-size: 14px;">Bảng điều khiển quét mã QR, theo dõi trạng thái kết nối và phân quyền theo SĐT Database.</p>
    </div>
    <div id="lockActionContainer" style="{{ $isUnlocked ? '' : 'display: none;' }}">
        <button type="button" onclick="lockSecurityPin()" class="btn" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
            🔒 Khóa Bảo Mật
        </button>
    </div>
</div>

<!-- ======================================================== -->
<!-- 1. MÀN HÌNH KHÓA BẢO MẬT MASTER PIN (Nếu chưa mở khóa) -->
<!-- ======================================================== -->
<div id="pinLockScreen" style="{{ $isUnlocked ? 'display: none;' : '' }} max-width: 380px; margin: 60px auto; background: #ffffff; border-radius: 18px; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; padding: 32px 28px; text-align: center;">
    <div style="width: 54px; height: 54px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 16px;">
        🔐
    </div>
    <h2 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px;">Mã PIN Quản Trị</h2>

    <form id="pinForm" onsubmit="submitPin(event)" style="display: flex; flex-direction: column; gap: 14px;">
        <div>
            <input type="password" id="masterPinInput" maxlength="10" placeholder="••••" style="width: 100%; padding: 12px 16px; border: 2px solid #cbd5e1; border-radius: 10px; font-size: 20px; text-align: center; letter-spacing: 6px; outline: none; font-weight: 700;" autocomplete="off" autofocus required>
            <div id="pinErrorMsg" style="color: #ef4444; font-size: 13px; margin-top: 6px; display: none;"></div>
        </div>
        <button type="submit" id="btnUnlock" style="width: 100%; background: #2563eb; color: #ffffff; padding: 11px; border: none; border-radius: 10px; font-size: 14.5px; font-weight: 600; cursor: pointer; transition: 0.2s;">
            Mở Khóa
        </button>
    </form>
</div>

<!-- ======================================================== -->
<!-- 2. BẢNG ĐIỀU KHIỂN CHÍNH (Sau khi đã mở khóa PIN) -->
<!-- ======================================================== -->
<div id="unlockedDashboard" style="{{ $isUnlocked ? '' : 'display: none;' }}">
    
    <div style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 24px; align-items: start;">
        
        <!-- CỘT TRÁI: THÔNG TIN NICK & KHUNG QUÉT QR TRỰC TIẾP -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- Card 1: Trạng thái Nick Trợ Lý Zalo -->
            <div style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 22px;">📱</span>
                        <div>
                            <h2 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">Trạng Thái Nick Trợ Lý</h2>
                            <span id="liveStatusBadge" style="display: inline-block; margin-top: 4px; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 12px; background: #fef2f2; color: #dc2626;">
                                🔴 Đang kiểm tra kết nối...
                            </span>
                        </div>
                    </div>
                    <button type="button" onclick="checkGatewayStatus()" style="background: none; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 12px; cursor: pointer; color: #475569; font-weight: 500;">
                        🔄 Làm mới
                    </button>
                </div>

                <!-- Thông tin Nick đang kết nối -->
                <div id="accountInfoBox" style="display: flex; align-items: center; gap: 16px; padding: 14px; background: #f8fafc; border-radius: 12px; margin-bottom: 20px;">
                    <div id="accAvatar" style="width: 52px; height: 52px; border-radius: 50%; background: #0068ff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; overflow: hidden; flex-shrink: 0;">
                        🤖
                    </div>
                    <div style="flex: 1;">
                        <div id="accName" style="font-weight: 700; font-size: 15px; color: #1e293b;">Chưa có tài khoản kết nối</div>
                        <div id="accUid" style="font-size: 12.5px; color: #64748b; margin-top: 2px;">Vui lòng bấm nút bên dưới để tạo mã QR đăng nhập</div>
                    </div>
                </div>

                <!-- Các nút hành động -->
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" onclick="startGenerateQr()" id="btnGenerateQr" style="flex: 1; background: #0068ff; color: #ffffff; border: none; border-radius: 10px; padding: 12px 18px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,104,255,0.25);">
                        <span>📸</span> Tạo Mã QR Đăng Nhập / Đổi Nick
                    </button>
                    <button type="button" onclick="disconnectAssistant()" id="btnDisconnect" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; font-size: 14px; font-weight: 600; cursor: pointer; display: none;">
                        ❌ Đăng Xuất Nick
                    </button>
                </div>
            </div>

            <!-- Card 2: Khung Quét Mã QR Trực Tuyến (Hiển thị khi bấm Tạo QR) -->
            <div id="qrScannerCard" style="display: none; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 2px solid #0068ff; padding: 24px; text-align: center;">
                <div style="font-weight: 700; font-size: 17px; color: #1e293b; margin-bottom: 6px;">📸 Quét Mã QR Bằng App Zalo Trên Điện Thoại</div>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 18px;">
                    Mở Zalo trên điện thoại của <b>Nick Trợ Lý</b> $\rightarrow$ Chọn icon <b>Quét mã QR</b> $\rightarrow$ Quét mã bên dưới $\rightarrow$ Bấm <b>Đăng nhập</b>.
                </p>

                <div style="display: inline-block; padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 16px; position: relative;">
                    <div id="qrLoadingSpinner" style="width: 220px; height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                        <div style="font-size: 28px;">⏳</div>
                        <div style="font-size: 13px; color: #64748b; font-weight: 500;">Đang kết nối máy chủ Zalo...</div>
                    </div>
                    <img id="qrImageElement" src="" alt="Zalo Login QR Code" style="display: none; width: 220px; height: 220px; border-radius: 8px; object-fit: contain;">
                </div>

                <div id="qrStatusMessage" style="font-weight: 600; font-size: 14px; color: #0284c7; margin-bottom: 12px;">
                    Đang khởi tạo mã...
                </div>

                <div style="display: flex; justify-content: center; gap: 12px;">
                    <button type="button" onclick="startGenerateQr()" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 14px; font-size: 13px; cursor: pointer; font-weight: 500;">
                        🔄 Tạo lại mã mới
                    </button>
                    <button type="button" onclick="hideQrScanner()" style="background: none; color: #64748b; border: none; font-size: 13px; cursor: pointer;">
                        Đóng khung này
                    </button>
                </div>
            </div>

        </div>

        <!-- CỘT PHẢI: DANH SÁCH SĐT ADMIN ĐƯỢC PHÂN QUYỀN TRONG DATABASE -->
        <div style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                <div>
                    <h2 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">👑 Danh Sách Admin (Từ Database)</h2>
                    <p style="font-size: 12.5px; color: #64748b; margin-top: 3px;">Chỉ những tài khoản Admin có SĐT mới được phép nhắn tin ra lệnh cho Bot.</p>
                </div>
                <a href="{{ route('page.profile') }}" target="_blank" style="font-size: 12px; color: #2563eb; text-decoration: none; font-weight: 600; border: 1px solid #bfdbfe; background: #eff6ff; padding: 5px 10px; border-radius: 8px;">
                    ✏️ Sửa SĐT của bạn
                </a>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                    <thead>
                        <tr style="background: #f8fafc; color: #475569; border-bottom: 2px solid #e2e8f0; text-align: left;">
                            <th style="padding: 10px 12px;">Họ Tên Admin</th>
                            <th style="padding: 10px 12px;">Số Điện Thoại</th>
                            <th style="padding: 10px 12px; text-align: center;">Quyền Zalo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adminPhones as $adm)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 12px;">
                                <div style="font-weight: 600; color: #1e293b;">{{ $adm->name }}</div>
                                <div style="font-size: 12px; color: #94a3b8;">{{ $adm->email }}</div>
                            </td>
                            <td style="padding: 10px 12px; font-weight: 600; color: #0284c7;">
                                {{ $adm->phone }}
                            </td>
                            <td style="padding: 10px 12px; text-align: center;">
                                <span style="background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 12px; font-size: 11.5px; font-weight: 600;">
                                    ✅ Cho phép
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="padding: 24px; text-align: center; color: #94a3b8; font-style: italic;">
                                Chưa có tài khoản Admin nào có Số điện thoại trong Database.<br>
                                <a href="{{ route('page.profile') }}" style="color: #2563eb; font-weight: 600; margin-top: 6px; display: inline-block;">Cập nhật SĐT ngay tại đây</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Hướng dẫn sử dụng nhanh -->
            <div style="margin-top: 24px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 16px;">
                <div style="font-weight: 700; font-size: 13.5px; color: #166534; margin-bottom: 6px;">💡 Mẫu câu lệnh nhắn cho Bot:</div>
                <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #15803d; line-height: 1.6;">
                    <li>👉 <i>"Hôm nay bán dâu tây, nho đen và cam úc nhé"</i></li>
                    <li>👉 <i>"Hôm nay có thêm xoài cát"</i></li>
                    <li>👉 <i>"Hết dâu tây rồi tắt dâu đi em"</i></li>
                    <li>👉 <i>"Hôm nay đang bán những món gì?"</i></li>
                </ul>
            </div>
        </div>

    </div>

</div>

<script>
let qrPollingTimer = null;

// 1. Xử lý mở khóa bằng PIN
async function submitPin(e) {
    e.preventDefault();
    const pinInput = document.getElementById('masterPinInput');
    const errorMsg = document.getElementById('pinErrorMsg');
    const btn = document.getElementById('btnUnlock');
    const pin = pinInput.value.trim();

    if (!pin) return;
    btn.disabled = true;
    btn.textContent = 'Đang xác thực...';
    errorMsg.style.display = 'none';

    try {
        const response = await fetch('{{ route("admin.zalo-assistant.verify-pin") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ pin: pin })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('pinLockScreen').style.display = 'none';
            document.getElementById('unlockedDashboard').style.display = 'block';
            document.getElementById('lockActionContainer').style.display = 'block';
            checkGatewayStatus();
        } else {
            errorMsg.textContent = data.message || 'Mã PIN không đúng!';
            errorMsg.style.display = 'block';
            pinInput.value = '';
            pinInput.focus();
        }
    } catch (err) {
        errorMsg.textContent = 'Lỗi kết nối: ' + err.message;
        errorMsg.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = '🔓 Mở Khóa Quản Trị';
    }
}

// 2. Khóa lại màn hình
async function lockSecurityPin() {
    try {
        await fetch('{{ route("admin.zalo-assistant.lock") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        location.reload();
    } catch (e) {
        location.reload();
    }
}

// 3. Kiểm tra trạng thái kết nối hiện tại của Zalo Gateway
async function checkGatewayStatus() {
    const badge = document.getElementById('liveStatusBadge');
    const accName = document.getElementById('accName');
    const accUid = document.getElementById('accUid');
    const accAvatar = document.getElementById('accAvatar');
    const btnDisconnect = document.getElementById('btnDisconnect');

    try {
        const response = await fetch('{{ route("admin.zalo-assistant.status") }}');
        const data = await response.json();

        if (data.is_connected) {
            badge.style.background = '#dcfce7';
            badge.style.color = '#15803d';
            badge.innerHTML = '🟢 Đang Online (Keep-Alive hoạt động)';

            const name = (data.account && data.account.name) ? data.account.name : 'Nick Trợ Lý Zalo';
            const uid = (data.account && data.account.uid) ? data.account.uid : 'Active';
            accName.textContent = name;
            accUid.innerHTML = `Mã Zalo ID: <code>${uid}</code> | Trạng thái: Sẵn sàng nhận lệnh`;
            
            if (data.account && data.account.avatar) {
                accAvatar.innerHTML = `<img src="${data.account.avatar}" style="width: 100%; height: 100%; object-fit: cover;">`;
            } else {
                accAvatar.innerHTML = '🤖';
            }

            btnDisconnect.style.display = 'inline-block';
        } else {
            badge.style.background = '#fef2f2';
            badge.style.color = '#dc2626';
            badge.innerHTML = '🔴 Chưa kết nối / Cần quét mã QR';

            accName.textContent = 'Chưa có tài khoản kết nối';
            accUid.textContent = 'Vui lòng bấm nút "Tạo Mã QR" để kết nối Nick Trợ lý';
            accAvatar.innerHTML = '🤖';
            btnDisconnect.style.display = 'none';
        }
    } catch (err) {
        badge.style.background = '#fff7ed';
        badge.style.color = '#c2410c';
        badge.innerHTML = '⚠️ Không thể kết nối Gateway (Port 3001)';
    }
}

// 4. Kích hoạt sinh mã QR mới
async function startGenerateQr() {
    const qrCard = document.getElementById('qrScannerCard');
    const spinner = document.getElementById('qrLoadingSpinner');
    const qrImg = document.getElementById('qrImageElement');
    const qrMsg = document.getElementById('qrStatusMessage');

    qrCard.style.display = 'block';
    spinner.style.display = 'flex';
    qrImg.style.display = 'none';
    qrMsg.textContent = '⏳ Đang kết nối Zalo để tạo mã QR mới...';

    try {
        const response = await fetch('{{ route("admin.zalo-assistant.generate-qr") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success && data.qr_image) {
            spinner.style.display = 'none';
            qrImg.src = data.qr_image;
            qrImg.style.display = 'block';
            qrMsg.innerHTML = '📸 <b>Hãy mở Zalo trên điện thoại quét mã QR bên trên!</b>';

            // Bắt đầu theo dõi tiến độ quét
            startQrStatusPolling();
        } else {
            qrMsg.textContent = '⚠️ ' + (data.error || data.message || 'Không thể tạo mã QR lúc này. Vui lòng thử lại.');
        }
    } catch (err) {
        qrMsg.textContent = '⚠️ Lỗi gọi Gateway: ' + err.message;
    }
}

// 5. Polling theo dõi trạng thái quét QR
function startQrStatusPolling() {
    if (qrPollingTimer) clearInterval(qrPollingTimer);

    qrPollingTimer = setInterval(async () => {
        try {
            const response = await fetch('{{ route("admin.zalo-assistant.status") }}');
            const data = await response.json();
            const qrMsg = document.getElementById('qrStatusMessage');

            const status = data.qr_session_status || data.status;

            if (status === 'scanned') {
                qrMsg.innerHTML = '👀 <b>Đã quét mã! Vui lòng bấm [ĐĂNG NHẬP] trên điện thoại...</b>';
            } else if (status === 'confirmed') {
                clearInterval(qrPollingTimer);
                qrMsg.innerHTML = '✅ <b>ĐĂNG NHẬP THÀNH CÔNG! Đang cập nhật...</b>';
                setTimeout(() => {
                    hideQrScanner();
                    checkGatewayStatus();
                }, 1500);
            } else if (status === 'expired') {
                qrMsg.innerHTML = '⏰ <b>Mã QR đã hết hạn. Đang tự động tạo lại mã mới...</b>';
                startGenerateQr();
            }
        } catch (e) {}
    }, 1500);
}

function hideQrScanner() {
    if (qrPollingTimer) clearInterval(qrPollingTimer);
    document.getElementById('qrScannerCard').style.display = 'none';
}

// 6. Đăng xuất Nick Trợ Lý
async function disconnectAssistant() {
    if (!confirm('Bạn có chắc chắn muốn ngắt kết nối Nick Trợ Lý này để đổi nick khác không?')) return;

    try {
        const response = await fetch('{{ route("admin.zalo-assistant.disconnect") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await response.json();
        alert(data.message || 'Đã hủy kết nối.');
        checkGatewayStatus();
    } catch (err) {
        alert('Lỗi: ' + err.message);
    }
}

// Khởi chạy khi vào trang (nếu đã mở khóa)
@if($isUnlocked)
document.addEventListener('DOMContentLoaded', () => {
    checkGatewayStatus();
});
@endif
</script>
@endsection
