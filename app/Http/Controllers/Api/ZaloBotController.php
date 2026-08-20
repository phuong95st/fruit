<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZaloBotController extends Controller
{
    protected AiAssistantService $aiService;

    public function __construct(AiAssistantService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Tiếp nhận Webhook từ Zalo Bot Gateway (NodeJS)
     */
    public function webhook(Request $request)
    {
        // 1. Kiểm tra bí mật kết nối nội bộ
        $incomingSecret = $request->input('secret') ?: $request->header('X-Bot-Secret');
        $expectedSecret = env('ZALO_BOT_SECRET', 'fruitnest_secret_key_2026');

        if (!empty($expectedSecret) && $incomingSecret !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid bot secret'
            ], 401);
        }

        $senderId = (string)$request->input('sender_id');
        $senderName = $request->input('sender_name', 'Chủ Shop');
        $message = trim((string)$request->input('message', ''));
        $imageUrl = $request->input('image_url') ? (string)$request->input('image_url') : null;
        $videoUrl = $request->input('video_url') ? (string)$request->input('video_url') : null;

        if (empty($message) && empty($imageUrl) && empty($videoUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Tin nhắn trống'
            ]);
        }

        // 2. Lệnh tra cứu ID hoặc thiết lập nhanh
        if (in_array(strtolower($message), ['/id', '/myid', 'id', 'myid'])) {
            return response()->json([
                'success' => true,
                'reply_message' => "🆔 Mã Zalo ID của bạn là:\n`{$senderId}`\n\nBạn có thể dán mã này vào file `.env` dòng `ZALO_ADMIN_ID={$senderId}` để cấp quyền Chủ Shop nhé!"
            ]);
        }

        // 3. Kiểm tra quyền hạn Chủ Shop (Nếu có cấu hình ở Laravel)
        $configuredAdminIds = env('ZALO_ADMIN_ID');
        if (!empty($configuredAdminIds)) {
            $allowedIds = array_map('trim', explode(',', $configuredAdminIds));
            // Nếu có cấu hình nhưng ID không khớp và không gửi kèm secret hợp lệ
            if (!in_array($senderId, $allowedIds) && $incomingSecret !== $expectedSecret) {
                Log::info("Zalo Bot: Bỏ qua tin nhắn từ người lạ - ID: {$senderId} - Name: {$senderName}");
                return response()->json([
                    'success' => false,
                    'message' => 'Người gửi không thuộc danh sách quản trị viên'
                ]);
            }
        }

        // 4. Gọi Service AI phân tích câu lệnh và thực thi cập nhật Database
        try {
            $result = $this->aiService->processMessage($message, $senderId, $imageUrl, $videoUrl);
            
            Log::info("Zalo Bot: Đã xử lý lệnh thành công từ [{$senderName} ({$senderId})]: '{$message}' -> Intent: {$result['intent']}");

            return response()->json([
                'success' => true,
                'reply_message' => $result['reply_message'],
                'intent' => $result['intent'],
                'updated_daily_count' => $result['updated_daily_count'] ?? 0
            ]);
        } catch (\Throwable $e) {
            Log::error('Zalo Bot Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'reply_message' => '⚠️ Dạ hệ thống gặp chút trục trặc khi xử lý yêu cầu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cung cấp Số điện thoại của tài khoản Admin trong Database
     */
    public function getAdminPhone(Request $request)
    {
        $incomingSecret = $request->input('secret') ?: $request->header('X-Bot-Secret');
        $expectedSecret = env('ZALO_BOT_SECRET', 'fruitnest_secret_key_2026');

        if (!empty($expectedSecret) && $incomingSecret !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid bot secret'
            ], 401);
        }

        $admin = \App\Models\User::where('is_admin', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->first();

        $phone = $admin ? $admin->phone : env('ZALO_ADMIN_PHONE', '');

        return response()->json([
            'success' => true,
            'phone' => $phone
        ]);
    }
}
