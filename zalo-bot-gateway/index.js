const fs = require('fs');
const path = require('path');
const http = require('http');
const axios = require('axios');
const readline = require('readline');
const qrcode = require('qrcode-terminal');
require('dotenv').config();

const { Zalo, LoginQRCallbackEventType } = require('zca-js');

const GATEWAY_PORT = parseInt(process.env.GATEWAY_PORT || '3001', 10);
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL || 'http://127.0.0.1:8000/api/zalo/webhook';
const LARAVEL_ADMIN_PHONE_URL = process.env.LARAVEL_ADMIN_PHONE_URL || 'http://127.0.0.1:8000/api/zalo/admin-phone';
const ZALO_BOT_SECRET = process.env.ZALO_BOT_SECRET || 'fruitnest_secret_key_2026';
const SESSION_FILE = path.join(__dirname, 'session.json');

// Biến trạng thái toàn cục
let activeApi = null;
let currentAccountInfo = null;
let lastKeepAliveTime = null;
let allowedAdminIds = new Set();
let adminUserInfo = { uid: null, phone: null, name: 'Chủ Shop' };

// Trạng thái phiên QR
let qrSession = {
    status: 'idle', // 'idle' | 'generating' | 'waiting_scan' | 'scanned' | 'confirmed' | 'expired' | 'error',
    qrImage: null,
    qrCode: null,
    error: null,
    updatedAt: Date.now()
};

console.clear();
console.log('====================================================');
console.log('🤖 ZALO BOT GATEWAY — TRỢ LÝ AI HOA QUẢ SƠN TÂY');
console.log('====================================================');
console.log(`🔗 Webhook URL  : ${LARAVEL_WEBHOOK_URL}`);
console.log(`🌐 HTTP API Port: ${GATEWAY_PORT} (Phục vụ Web Admin)`);
console.log(`🛡️ Phân quyền   : Tự động theo SĐT Admin trong Database`);
console.log('----------------------------------------------------\n');

/**
 * Tự động nhận diện 1 Admin duy nhất từ SĐT trong Database Laravel
 */
async function resolveAdminAccount(api) {
    if (!api) return;

    let adminPhone = (process.env.ZALO_ADMIN_PHONE || '').replace(/[^0-9]/g, '');

    // Nếu chưa có trong .env, tự động truy vấn từ Database Laravel
    if (!adminPhone) {
        try {
            const response = await axios.get(LARAVEL_ADMIN_PHONE_URL, {
                headers: { 'X-Bot-Secret': ZALO_BOT_SECRET },
                params: { secret: ZALO_BOT_SECRET },
                timeout: 8000
            });
            if (response.data && response.data.success && response.data.phone) {
                adminPhone = String(response.data.phone).replace(/[^0-9]/g, '');
            }
        } catch (err) {
            // Bỏ qua lỗi kết nối
        }
    }

    if (adminPhone && typeof api.findUser === 'function') {
        try {
            const userData = await api.findUser(adminPhone);
            if (userData && userData.uid) {
                const uid = String(userData.uid);
                allowedAdminIds.clear();
                allowedAdminIds.add(uid);
                adminUserInfo = {
                    uid,
                    phone: adminPhone,
                    name: userData.displayName || userData.zalo_name || 'Chủ Shop'
                };
                console.log(`🛡️ [Phân Quyền] Đã nhận diện Admin duy nhất: "${adminUserInfo.name}" (SĐT: ${adminPhone} | UID: ${uid})`);
                return;
            }
        } catch (e) {
            console.log(`⚠️ Không tìm thấy tài khoản Zalo với SĐT: ${adminPhone}`);
        }
    }

    if (allowedAdminIds.size === 0) {
        console.log(`⚠️ [Phân Quyền] Chưa nhận diện được SĐT Admin từ Database. Bot sẽ từ chối mọi tin nhắn để bảo mật.`);
    }
}

/**
 * Kiểm tra xem URL hoặc tin nhắn có phải là Video của Zalo hay không
 */
function isVideoLink(url, contextObj = {}, data = {}) {
    if (!url) return false;
    const strUrl = String(url).toLowerCase();
    if (strUrl.includes('youtube.com') || strUrl.includes('youtu.be')) return true;
    if (/\.(mp4|mov|webm|m4v|avi|mkv|3gp|flv)(\?.*)?$/i.test(strUrl)) return true;
    if (strUrl.includes('video-stal') || strUrl.includes('dlmd.me') || strUrl.includes('video.zdn.vn') || strUrl.includes('/video/')) return true;
    if (contextObj.type === 'video' || contextObj.action === 'video' || contextObj.msgType === 'chat.video') return true;
    if (data.msgType === 'chat.video' || data.msgType === 3 || data.type === 'video' || data.action === 'video') return true;
    return false;
}

/**
 * Trích xuất nội dung văn bản, URL hình ảnh và URL video từ tin nhắn Zalo
 */
function extractMessageAndMedia(msg) {
    let text = '';
    let imageUrl = null;
    let videoUrl = null;

    const data = msg.data || {};
    const content = data.content;

    // 1. Nếu content là object (ảnh / video / media)
    if (content && typeof content === 'object') {
        text = content.title || content.description || content.caption || content.text || '';
        const url = content.href || content.normalUrl || content.oriUrl || content.hdUrl || content.url || content.videoUrl || content.thumb || null;
        if (url) {
            if (isVideoLink(url, content, data)) {
                videoUrl = url;
            } else {
                imageUrl = url;
            }
        }
    } else if (typeof content === 'string') {
        text = content;
    }

    // 2. Tìm media từ các trường data gốc
    if (!imageUrl && !videoUrl) {
        const url = data.href || data.normalUrl || data.oriUrl || data.hdUrl || data.url || data.videoUrl || data.thumb || null;
        if (url) {
            if (isVideoLink(url, {}, data)) {
                videoUrl = url;
            } else {
                imageUrl = url;
            }
        }
    }

    // 3. Tìm media từ params nếu là JSON string
    if (!imageUrl && !videoUrl && data.params) {
        try {
            const parsed = typeof data.params === 'string' ? JSON.parse(data.params) : data.params;
            const url = parsed.url || parsed.hdUrl || parsed.href || parsed.videoUrl || parsed.thumbUrl || null;
            if (url) {
                if (isVideoLink(url, parsed, data)) {
                    videoUrl = url;
                } else {
                    imageUrl = url;
                }
            }
            if (!text && parsed.title) text = parsed.title;
        } catch (e) {}
    }

    // 4. Tìm media từ attachments mảng
    if (!imageUrl && !videoUrl && Array.isArray(data.attachments) && data.attachments.length > 0) {
        const att = data.attachments[0];
        const url = att.url || att.href || att.hdUrl || att.videoUrl || att.thumb || null;
        if (url) {
            if (isVideoLink(url, att, data)) {
                videoUrl = url;
            } else {
                imageUrl = url;
            }
        }
    }

    // 5. Trích dẫn media
    if (!imageUrl && !videoUrl && data.quote && data.quote.content) {
        const q = data.quote.content;
        if (typeof q === 'object') {
            const url = q.href || q.normalUrl || q.oriUrl || q.videoUrl || q.thumb || null;
            if (url) {
                if (isVideoLink(url, q, data)) {
                    videoUrl = url;
                } else {
                    imageUrl = url;
                }
            }
        }
    }

    text = String(text || '').trim();
    if (text === '[object Object]') text = '';

    return { text, imageUrl, videoUrl };
}

/**
 * Khởi tạo listener tin nhắn và cơ chế Keep-Alive cho API đã đăng nhập
 */
async function setupZaloListener(api) {
    activeApi = api;

    // Lấy thông tin tài khoản đang đăng nhập
    try {
        if (typeof api.getOwnId === 'function') {
            const ownId = await api.getOwnId();
            currentAccountInfo = {
                uid: ownId,
                name: 'Zalo Bot',
                avatar: null
            };
        }
    } catch (e) {
        currentAccountInfo = {
            uid: 'Online',
            name: 'Trợ Lý Hoa Quả Sơn Tây',
            avatar: null
        };
    }

    // Nhận diện Admin duy nhất ngay
    await resolveAdminAccount(api);

    // Lắng nghe tin nhắn đến
    api.listener.on('message', async (msg) => {
        try {
            const senderId = String(msg.data?.uidFrom || msg.sender?.id || msg.threadId || '');
            const threadId = msg.threadId;

            const { text: content, imageUrl, videoUrl } = extractMessageAndMedia(msg);

            if (!content && !imageUrl && !videoUrl) return;

            let logMedia = '';
            if (imageUrl) logMedia += ` [Kèm ảnh: ${imageUrl.substring(0, 45)}...]`;
            if (videoUrl) logMedia += ` [Kèm video: ${videoUrl.substring(0, 45)}...]`;

            console.log(`\n📩 [Zalo] Nhận tin nhắn từ [ID: ${senderId}]: "${content}"${logMedia}`);

            // Lệnh tra cứu ID
            if (['/id', '/myid', 'id', 'myid'].includes(content.toLowerCase())) {
                const replyText = `🆔 Mã Zalo ID của bạn là:\n${senderId}\n\nBạn có thể điền mã này hoặc số điện thoại vào file cấu hình .env (ZALO_ADMIN_PHONE hoặc ZALO_ADMIN_ID) để cấp quyền Admin duy nhất nhé!`;
                await api.sendMessage({ msg: replyText }, threadId);
                console.log(`📤 Đã gửi mã ID cho người dùng.`);
                return;
            }

            // BỘ LỌC BẢO MẬT: Kiểm tra quyền Chủ Shop duy nhất theo SĐT Database
            if (allowedAdminIds.size === 0) {
                await resolveAdminAccount(api);
            }

            if (allowedAdminIds.size === 0) {
                console.log(`⛔ BỎ QUA: Chưa nhận diện được SĐT Admin từ Database. Bot từ chối tin nhắn từ [ID: ${senderId}] để bảo vệ hệ thống.`);
                return;
            }

            if (!allowedAdminIds.has(senderId)) {
                console.log(`⛔ BỎ QUA: ID [${senderId}] không khớp với tài khoản Zalo của SĐT Admin trong Database.`);
                return;
            }

            console.log(`⏳ Đang chuyển tiếp câu lệnh sang AI và Laravel Webhook...`);

            // Gửi sang Webhook Laravel
            const response = await axios.post(LARAVEL_WEBHOOK_URL, {
                sender_id: senderId,
                sender_name: msg.sender?.name || 'Chủ Shop',
                message: content,
                image_url: imageUrl,
                video_url: videoUrl,
                secret: ZALO_BOT_SECRET
            }, {
                headers: { 'Content-Type': 'application/json' },
                timeout: 30000
            });

            if (response.data && response.data.reply_message) {
                const replyText = response.data.reply_message;
                console.log(`🤖 AI phản hồi:\n${replyText}\n`);

                await api.sendMessage({ msg: replyText }, threadId);
                console.log(`📤 Đã gửi tin nhắn xác nhận cho Chủ Shop trên Zalo thành công! ✅`);
            }

        } catch (err) {
            console.error(`❌ Lỗi xử lý tin nhắn:`, err.response?.data || err.message);
        }
    });

    api.listener.start();

    // Kích hoạt cơ chế Keep-Alive 15 phút/lần
    const KEEP_ALIVE_INTERVAL = 15 * 60 * 1000;
    setInterval(async () => {
        if (!activeApi) return;
        try {
            const now = new Date().toLocaleTimeString('vi-VN');
            if (typeof activeApi.keepAlive === 'function') {
                await activeApi.keepAlive();
            } else if (typeof activeApi.getSettings === 'function') {
                await activeApi.getSettings();
            }
            lastKeepAliveTime = now;
            console.log(`💓 [Keep-Alive lúc ${now}] Đã duy trì kết nối Zalo. Trạng thái: Online 🟢`);
        } catch (err) {
            console.warn(`⚠️ [Keep-Alive] Cảnh báo kết nối:`, err.message);
        }
    }, KEEP_ALIVE_INTERVAL);
}

/**
 * Hàm khởi tạo tạo mã QR đăng nhập khi được gọi từ Web Admin hoặc CLI
 */
async function startQrLoginProcess() {
    const zalo = new Zalo({ logging: false });
    const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36';
    const qrFilePath = path.join(__dirname, 'qr.png');

    qrSession = {
        status: 'generating',
        qrImage: null,
        qrCode: null,
        error: null,
        updatedAt: Date.now()
    };

    try {
        const api = await zalo.loginQR({
            userAgent: userAgent,
            qrPath: qrFilePath
        }, async (event) => {
            if (event.type === LoginQRCallbackEventType.QRCodeGenerated) {
                qrSession.status = 'waiting_scan';
                qrSession.qrImage = event.data?.image ? `data:image/png;base64,${event.data.image}` : null;
                qrSession.qrCode = event.data?.code || null;
                qrSession.updatedAt = Date.now();

                if (event.data?.image) {
                    try { fs.writeFileSync(qrFilePath, Buffer.from(event.data.image, 'base64')); } catch (e) {}
                }
                if (event.data?.code) {
                    console.log('\n👉 QUÉT MÃ QR TRÊN MÀN HÌNH NÀY HOẶC TRÊN WEB ADMIN:');
                    qrcode.generate(event.data.code, { small: true });
                }

            } else if (event.type === LoginQRCallbackEventType.QRCodeScanned) {
                qrSession.status = 'scanned';
                qrSession.updatedAt = Date.now();
                console.log('👀 Đã quét mã QR thành công! Vui lòng bấm [Đăng nhập] trên điện thoại...');

            } else if (event.type === LoginQRCallbackEventType.GotLoginInfo) {
                try {
                    fs.writeFileSync(SESSION_FILE, JSON.stringify(event.data, null, 2));
                } catch (e) {}

            } else if (event.type === LoginQRCallbackEventType.QRCodeExpired) {
                qrSession.status = 'expired';
                qrSession.updatedAt = Date.now();
                console.log('⏰ Mã QR đã hết hạn, đang tạo lại...');
                event.actions.retry();

            } else if (event.type === LoginQRCallbackEventType.QRCodeDeclined) {
                qrSession.status = 'declined';
                qrSession.error = 'Bạn đã từ chối đăng nhập trên điện thoại';
                qrSession.updatedAt = Date.now();
            }
        });

        if (api) {
            qrSession.status = 'confirmed';
            qrSession.updatedAt = Date.now();
            console.log('\n✅ ĐĂNG NHẬP NICK TRỢ LÝ THÀNH CÔNG TỪ MÃ QR!');
            setupZaloListener(api);
            return { success: true };
        }

    } catch (err) {
        qrSession.status = 'error';
        qrSession.error = err.message;
        qrSession.updatedAt = Date.now();
        console.error('⚠️ Lỗi sinh mã QR:', err.message);
        throw err;
    }
}

/**
 * Khởi động HTTP Micro-API Server (Cổng 3001) phục vụ Web Admin
 */
function startHttpServer() {
    const server = http.createServer(async (req, res) => {
        // CORS Headers
        res.setHeader('Access-Control-Allow-Origin', '*');
        res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        res.setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Bot-Secret');

        if (req.method === 'OPTIONS') {
            res.writeHead(204);
            res.end();
            return;
        }

        const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);

        // 1. GET /api/status - Trả về trạng thái kết nối hiện tại
        if (url.pathname === '/api/status' && req.method === 'GET') {
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({
                success: true,
                is_connected: Boolean(activeApi),
                account: currentAccountInfo,
                last_keep_alive: lastKeepAliveTime,
                allowed_admin_count: allowedAdminIds.size,
                qr_session_status: qrSession.status
            }));
            return;
        }

        // 2. POST /api/generate-qr - Kích hoạt sinh mã QR mới
        if (url.pathname === '/api/generate-qr' && req.method === 'POST') {
            // Chạy ngầm tiến trình tạo QR
            startQrLoginProcess().catch(() => {});
            
            // Đợi tối đa 3 giây để lấy mã QR đầu tiên
            let attempts = 0;
            const checkInterval = setInterval(() => {
                attempts++;
                if (qrSession.qrImage || qrSession.status === 'error' || attempts > 15) {
                    clearInterval(checkInterval);
                    res.writeHead(200, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({
                        success: Boolean(qrSession.qrImage),
                        status: qrSession.status,
                        qr_image: qrSession.qrImage,
                        qr_code: qrSession.qrCode,
                        error: qrSession.error
                    }));
                }
            }, 200);
            return;
        }

        // 3. GET /api/qr-status - Kiểm tra trạng thái tiến độ quét QR
        if (url.pathname === '/api/qr-status' && req.method === 'GET') {
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({
                success: true,
                status: qrSession.status,
                qr_image: qrSession.qrImage,
                is_connected: Boolean(activeApi),
                account: currentAccountInfo,
                error: qrSession.error
            }));
            return;
        }

        // 4. POST /api/disconnect - Đăng xuất Nick cũ
        if (url.pathname === '/api/disconnect' && req.method === 'POST') {
            try {
                if (fs.existsSync(SESSION_FILE)) {
                    fs.unlinkSync(SESSION_FILE);
                }
                activeApi = null;
                currentAccountInfo = null;
                qrSession = { status: 'idle', qrImage: null, qrCode: null, error: null, updatedAt: Date.now() };

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    success: true,
                    message: 'Đã hủy kết nối Nick Trợ lý thành công!'
                }));
                console.log('🔄 Đã hủy kết nối Nick Trợ lý theo yêu cầu từ Web Admin.');
            } catch (err) {
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, message: err.message }));
            }
            return;
        }

        // 5. POST /api/send-order-notify - Nhận thông báo đơn hàng từ website và gửi tới Zalo Chủ Shop
        if (url.pathname === '/api/send-order-notify' && req.method === 'POST') {
            let body = '';
            req.on('data', chunk => { body += chunk.toString(); });
            req.on('end', async () => {
                try {
                    const data = JSON.parse(body || '{}');
                    const order = data.order;

                    if (!activeApi) {
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: false, message: 'Nick Trợ lý Zalo hiện chưa đăng nhập' }));
                        return;
                    }

                    if (!order) {
                        res.writeHead(400, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: false, message: 'Thiếu thông tin đơn hàng' }));
                        return;
                    }

                    // Cơ chế chống gửi trùng đơn hàng trong vòng 2 phút
                    const orderKey = String(order.order_code || order.id || '');
                    if (orderKey && global.recentNotifiedOrders && global.recentNotifiedOrders.has(orderKey)) {
                        console.log(`⚠️ [Chống Trùng] Đơn hàng #${orderKey} đã được gửi thông báo trước đó, bỏ qua yêu cầu trùng lặp.`);
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true, message: 'Đơn hàng đã được gửi thông báo trước đó' }));
                        return;
                    }
                    if (!global.recentNotifiedOrders) global.recentNotifiedOrders = new Set();
                    if (orderKey) {
                        global.recentNotifiedOrders.add(orderKey);
                        setTimeout(() => global.recentNotifiedOrders.delete(orderKey), 120000);
                    }

                    // Định dạng danh sách mặt hàng không dùng markdown thừa
                    const itemsLines = (order.items || []).map((it, idx) => {
                        const priceText = Number(it.unit_price || it.price || 0).toLocaleString('vi-VN') + 'đ';
                        const subtotalText = Number(it.subtotal || ((it.price || 0) * (it.quantity || 1))).toLocaleString('vi-VN') + 'đ';
                        return `${idx + 1}. 📦 ${it.product_name || it.name}\n   └ Số lượng: ${it.quantity} × ${priceText} = ${subtotalText}`;
                    }).join('\n');

                    const totalText = Number(order.total_price || order.total || 0).toLocaleString('vi-VN') + 'đ';
                    const paymentMethod = order.payment_method || 'COD (Thu tiền khi nhận hàng)';
                    const notesText = order.notes ? `\n📝 Ghi chú của khách: "${order.notes}"` : '';

                    const notifyMsg =
`🔔 THÔNG BÁO: CÓ ĐƠN HÀNG MỚI! 🛍️
───────────────────────────
📋 Mã đơn hàng: #${order.order_code || order.id}
👤 Khách hàng: ${order.customer_name || order.fullname}
📞 Số điện thoại: ${order.customer_phone || order.phone}
📍 Địa chỉ nhận hàng: ${order.delivery_address || order.address}
💳 Hình thức: ${paymentMethod}

🛒 DANH SÁCH MÓN ĐẶT:
${itemsLines || '(Chưa có chi tiết món)'}

💰 TỔNG TIỀN THANH TOÁN: ${totalText}${notesText}
───────────────────────────
🌐 Vào xem đơn ngay: http://127.0.0.1:8000/admin/orders`;

                    console.log(`\n🔔 [Đơn Hàng Mới] Nhận được đơn hàng #${order.order_code || order.id} từ Web. Đang gửi tới Zalo Chủ Shop...`);

                    if (allowedAdminIds.size === 0 && activeApi) {
                        await resolveAdminAccount(activeApi);
                    }
                    const targetIds = Array.from(allowedAdminIds);

                    let sentCount = 0;
                    for (const adminId of targetIds) {
                        try {
                            await activeApi.sendMessage({ msg: notifyMsg }, adminId);
                            sentCount++;
                            console.log(`📤 Đã gửi thông báo đơn hàng đến Zalo Admin [ID: ${adminId}] ✅`);
                        } catch (sendErr) {
                            console.warn(`⚠️ Không thể gửi tới Admin [ID: ${adminId}]:`, sendErr.message);
                        }
                    }

                    res.writeHead(200, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({
                        success: true,
                        sent_count: sentCount,
                        message: `Đã gửi thông báo đơn hàng tới ${sentCount} tài khoản Admin Zalo`
                    }));

                } catch (err) {
                    console.error('❌ Lỗi xử lý gửi thông báo đơn hàng:', err.message);
                    res.writeHead(500, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: false, message: err.message }));
                }
            });
            return;
        }

        // Default 404
        res.writeHead(404, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, message: 'Endpoint not found' }));
    });

    server.listen(GATEWAY_PORT, '127.0.0.1', () => {
        console.log(`🚀 [Gateway Server] Đang lắng nghe Micro-API tại: http://127.0.0.1:${GATEWAY_PORT}`);
    });
}

/**
 * Luồng chính của ứng dụng
 */
async function main() {
    // 1. Khởi động Micro-API Server
    startHttpServer();

    // 2. Kiểm tra và tự động kết nối lại nếu đã có session.json
    if (fs.existsSync(SESSION_FILE)) {
        try {
            const credentials = JSON.parse(fs.readFileSync(SESSION_FILE, 'utf8'));
            if (credentials && credentials.cookie) {
                console.log('🔑 Tìm thấy phiên đăng nhập cũ, đang kết nối lại...');
                const zalo = new Zalo({ logging: false });
                const api = await zalo.login(credentials);
                console.log('✅ Kết nối lại phiên đăng nhập cũ thành công!');
                setupZaloListener(api);
                return;
            }
        } catch (e) {
            console.log('⚠️ Phiên đăng nhập cũ đã hết hạn.');
        }
    }

    console.log('💡 Chưa có phiên đăng nhập. Bạn có thể bấm "Tạo Mã QR" trên Web Admin bất cứ lúc nào!');
}

main();
