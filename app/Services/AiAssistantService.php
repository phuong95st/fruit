<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiAssistantService
{
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-2.0-flash');
    }

    /**
     * Xử lý tin nhắn từ Admin / Chủ shop bằng AI hoặc Fallback NLP có hỗ trợ Hội thoại Xác nhận
     *
     * @param string $message Tin nhắn từ Zalo
     * @param string|null $senderId ID Zalo của người gửi để lưu trạng thái hội thoại
     * @return array Kết quả phân tích và hành động thực thi
     */
    public function processMessage(string $message, ?string $senderId = 'default_admin', ?string $imageUrl = null, ?string $videoUrl = null): array
    {
        $products = Product::withTrashed()->get();
        $senderId = $senderId ?: 'default_admin';
        $cacheKey = "zalo_pending_clarification_{$senderId}";
        $pendingImageKey = "zalo_pending_image_{$senderId}";
        $pendingTargetKey = "zalo_pending_image_target_{$senderId}";
        $pendingVideoTargetKey = "zalo_pending_video_target_{$senderId}";

        // Tự động nhận diện nếu link ảnh thực chất là link video của Zalo (video-stal, dlmd.me, .mp4...)
        if (empty($videoUrl) && !empty($imageUrl)) {
            if ($this->isYoutubeUrl($imageUrl) || preg_match('/(video-stal|dlmd\.me|\.(mp4|mov|webm|m4v|avi))/i', $imageUrl)) {
                $videoUrl = $imageUrl;
                $imageUrl = null;
            }
        }

        // 0.1. Nếu đang có trạng thái chờ video và nhận được videoUrl hoặc link YouTube hoặc bất kỳ media nào
        $youtubeUrlInMsg = $this->extractYoutubeUrl($message);
        $videoInput = !empty($videoUrl) ? $videoUrl : $youtubeUrlInMsg;

        if (empty($videoInput) && !empty($imageUrl) && Cache::has($pendingVideoTargetKey)) {
            $videoInput = $imageUrl;
            $imageUrl = null;
        }

        if (!empty($videoInput) && Cache::has($pendingVideoTargetKey)) {
            $targetData = Cache::get($pendingVideoTargetKey);
            $targetProduct = Product::withTrashed()->find($targetData['product_id'] ?? 0);
            if ($targetProduct) {
                Cache::forget($pendingVideoTargetKey);
                return $this->executeSelectedAction('UPDATE_VIDEO', [['id' => $targetProduct->id]], ['video_input' => $videoInput], $senderId);
            }
        }

        // 0.2. Nếu người dùng gửi ảnh mới kèm tin nhắn
        if (!empty($imageUrl)) {
            // Nếu trước đó đã nhớ sẵn sản phẩm cần thêm/đổi ảnh
            if (Cache::has($pendingTargetKey)) {
                $targetData = Cache::get($pendingTargetKey);
                $targetProduct = Product::withTrashed()->find($targetData['product_id'] ?? 0);
                if ($targetProduct) {
                    Cache::forget($pendingTargetKey);
                    Cache::forget($pendingImageKey);
                    $intent = $targetData['intent'] ?? 'ADD_IMAGE';
                    return $this->executeSelectedAction($intent, [['id' => $targetProduct->id]], ['image_url' => $imageUrl], $senderId);
                }
            }
        } else {
            // Chỉ lấy ảnh chờ trong cache KHI tin nhắn không phải là câu lệnh thay đổi thông tin khác
            $norm = $this->normalizePhonetic($message);
            $isOtherCommand = preg_match('/\b(gia|ban gia|dinh duong|mo ta|xuat xu|dong goi|xoa|khoi phuc|hom nay ban|menu|thuc don|video|clip|youtube)\b/iu', $norm);
            if (!$isOtherCommand && Cache::has($pendingImageKey)) {
                $imageUrl = Cache::get($pendingImageKey);
            } else {
                // Nếu người dùng ra lệnh mới -> Xóa ảnh chờ cũ
                Cache::forget($pendingImageKey);
            }
        }

        // 1. KIỂM TRA XEM CÓ CÂU HỎI XÁC NHẬN ĐANG CHỜ TRẢ LỜI KHÔNG
        if (Cache::has($cacheKey)) {
            $pending = Cache::get($cacheKey);
            $clarificationResult = $this->handleClarificationResponse($message, $pending, $cacheKey, $senderId);
            if ($clarificationResult !== null) {
                return $clarificationResult;
            }
        }

        // 2. Thử gọi Google Gemini API nếu có API Key
        if (!empty($this->apiKey)) {
            try {
                $aiResult = $this->callGeminiApi($message, $products);
                if ($aiResult && isset($aiResult['intent'])) {
                    return $this->executeAiActions($aiResult, $products, $senderId);
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini AI API call failed, falling back to Dynamic Local NLP: ' . $e->getMessage());
            }
        }

        // 3. Phân tích NLP thông minh nội bộ (Đọc trực tiếp DB + Tự động hỏi lại khi trùng nhiều loại)
        return $this->processLocalNlp($message, $products, $senderId, $imageUrl, $videoUrl);
    }

    /**
     * Xử lý phản hồi lựa chọn của Chủ Shop khi Bot đã hỏi lại (ví dụ: nhắn "1", "2", "cả 2", "hủy")
     */
    protected function handleClarificationResponse(string $message, array $pending, string $cacheKey, ?string $senderId = null): ?array
    {
        $cleanMsg = mb_strtolower(trim($message), 'UTF-8');
        $cleanNorm = preg_replace('/\s+/', ' ', $this->normalizePhonetic($cleanMsg));

        // Hủy yêu cầu (ví dụ: "hủy", "thôi", "hủy đi", "thôi hủy đi", "bỏ qua", "cancel", "không đổi nữa"...)
        if (preg_match('/\b(huy|thoi|bo qua|cancel|ko|khong|ko doi|khong doi|ko sua|khong sua|thoi huy|huy bo|thoi bo|bo di|huy di|thoi khong|ko can)\b/iu', $cleanNorm)) {
            Cache::forget($cacheKey);
            if (!empty($senderId)) {
                Cache::forget("zalo_pending_image_{$senderId}");
                Cache::forget("zalo_pending_image_target_{$senderId}");
                Cache::forget("zalo_pending_video_target_{$senderId}");
            }
            return [
                'success' => true,
                'intent' => 'CANCELLED',
                'reply_message' => "👌 Dạ em đã hủy yêu cầu thay đổi trước đó rồi ạ!",
                'updated_daily_count' => Product::where('is_daily', true)->count()
            ];
        }

        $options = $pending['options'] ?? [];
        $intent = $pending['intent'] ?? '';
        $payload = $pending['payload'] ?? [];
        $selectedProducts = [];

        // 1. Chọn tất cả (ví dụ: "tat ca", "all", "het", "ca", "ca 2", "ca 3", "ca 4", "ca 5"...)
        if (preg_match('/\b(tat ca|all|het|ca|ca hai|ca \d+|ca \d+ loai|ca \d+ mon)\b/iu', $cleanNorm) || in_array($cleanNorm, ['ca 2', 'ca hai', 'tat ca', 'all', 'het', 'ca 3', 'ca 4', 'ca 5'])) {
            $selectedProducts = $options;
        }
        // 2. Chọn theo một hoặc nhiều số thứ tự (ví dụ: "1", "2", "3", "1, 3", "1 va 2"...)
        elseif (preg_match_all('/\b(\d+)\b/', $cleanNorm, $allNumMatches) && !empty($allNumMatches[1])) {
            foreach ($allNumMatches[1] as $numStr) {
                $idx = (int)$numStr - 1;
                if (isset($options[$idx])) {
                    $selectedProducts[] = $options[$idx];
                }
            }
        }
        // 3. Chọn theo tên phân loại
        else {
            foreach ($options as $opt) {
                $p = Product::withTrashed()->find($opt['id']);
                if ($p) {
                    $pNorm = $this->normalizePhonetic($p->name);
                    if (str_contains($pNorm, $cleanNorm) || str_contains($cleanNorm, $pNorm)) {
                        $selectedProducts[] = $opt;
                    }
                }
            }
        }

        if (empty($selectedProducts)) {
            // Người dùng không chọn 1/2/cả 2 mà ra một câu lệnh mới hoàn toàn
            // -> Xóa ngay câu hỏi cũ để không bị nhận nhầm chéo sang hành động mới
            Cache::forget($cacheKey);
            return null;
        }

        // Đã chọn xong -> Xóa cache
        Cache::forget($cacheKey);

        // Thực thi hành động cho các sản phẩm đã chọn
        return $this->executeSelectedAction($intent, $selectedProducts, $payload, $senderId);
    }

    /**
     * Thực thi hành động sau khi đã xác nhận chính xác sản phẩm mục tiêu
     */
    protected function executeSelectedAction(string $intent, array $selectedItems, array $payload, ?string $senderId = null): array
    {
        if (!empty($senderId)) {
            Cache::forget("zalo_pending_image_{$senderId}");
            Cache::forget("zalo_pending_image_target_{$senderId}");
            Cache::forget("zalo_pending_video_target_{$senderId}");
            Cache::forget("zalo_pending_clarification_{$senderId}");
        }

        $confirmDetails = [];

        if ($intent === 'SET_DAILY' || $intent === 'ADD_DAILY' || $intent === 'RESTORE_PRODUCT') {
            $exactIds = $payload['exact_product_ids'] ?? [];
            $selectedIds = array_column($selectedItems, 'id');
            $allTargetIds = array_unique(array_merge($exactIds, $selectedIds));

            if ($intent === 'SET_DAILY') {
                Product::query()->update(['is_daily' => false]);
            }
            Product::withTrashed()->whereIn('id', $allTargetIds)->restore();
            Product::whereIn('id', $allTargetIds)->update(['is_daily' => true]);

            foreach ($allTargetIds as $tId) {
                $product = Product::withTrashed()->find($tId);
                if ($product) {
                    $icon = $this->getFruitIcon($product->code, $product->name);
                    $priceText = number_format($product->price, 0, ',', '.') . 'đ/' . ($product->unit ?: 'kg');
                    if ($intent === 'RESTORE_PRODUCT') {
                        $confirmDetails[] = "🟢 {$icon} {$product->name} (Mã: {$product->code}) ➔ Đã khôi phục và mở bán lại trên website ({$priceText})";
                    } else {
                        $confirmDetails[] = "🟢 {$icon} {$product->name} — {$priceText}";
                    }
                }
            }
        } else {
            foreach ($selectedItems as $item) {
                $product = Product::withTrashed()->find($item['id']);
                if (!$product) continue;

                $icon = $this->getFruitIcon($product->code, $product->name);
                $webUrl = url("/san-pham/{$product->slug}");

                switch ($intent) {
                    case 'UPDATE_DESC':
                        $oldDesc = $product->desc ?: '(Chưa có mô tả)';
                        $newDesc = $payload['new_desc'] ?? '';
                        $product->update(['desc' => $newDesc]);

                        $confirmDetails[] =
                            "🎯 {$icon} {$product->name} (Mã: {$product->code})\n" .
                            "📝 Mô tả mới: \"{$newDesc}\"\n" .
                            "🌐 Xem trực tiếp: {$webUrl}";
                        break;

                    case 'UPDATE_NUTRITION':
                        $newNutrition = $payload['new_nutrition'] ?? '';
                        $product->update(['nutrition' => $newNutrition]);

                        $confirmDetails[] =
                            "🎯 {$icon} {$product->name} (Mã: {$product->code})\n" .
                            "🥗 Dinh dưỡng mới: \"{$newNutrition}\"\n" .
                            "🌐 Xem trực tiếp: {$webUrl}";
                        break;

                    case 'UPDATE_PRICE':
                        $newPrice = $payload['new_price'] ?? 0;
                        $oldPriceText = number_format($product->price, 0, ',', '.') . 'đ';
                        $newPriceText = number_format($newPrice, 0, ',', '.') . 'đ';
                        $product->update(['price' => $newPrice]);

                        $confirmDetails[] =
                            "🎯 {$icon} {$product->name} (Mã: {$product->code})\n" .
                            "💰 Giá bán: {$oldPriceText} ➔ {$newPriceText}\n" .
                            "🌐 Xem trực tiếp: {$webUrl}";
                        break;

                    case 'UPDATE_ORIGIN':
                        $newOrigin = $payload['new_origin'] ?? '';
                        $product->update(['origin' => $newOrigin]);

                        $confirmDetails[] =
                            "🎯 {$icon} {$product->name}\n" .
                            "🌍 Xuất xứ mới: {$newOrigin}";
                        break;

                    case 'UPDATE_PACK':
                        $newPack = $payload['new_pack'] ?? '';
                        $product->update(['pack' => $newPack]);

                        $confirmDetails[] =
                            "🎯 {$icon} {$product->name}\n" .
                            "📦 Quy cách đóng gói: {$newPack}";
                        break;

                    case 'REMOVE_DAILY':
                        $product->update(['is_daily' => false]);
                        $confirmDetails[] = "🔴 {$icon} {$product->name} ➔ Đã ngừng bán hôm nay";
                        break;

                    case 'DELETE_PRODUCT':
                        $product->update(['is_daily' => false]);
                        $product->delete(); // Soft delete: ẩn và xóa hoàn toàn khỏi website
                        $confirmDetails[] = "🗑️ {$icon} {$product->name} (Mã: {$product->code}) ➔ Đã ngừng kinh doanh & xóa khỏi toàn bộ website";
                        break;

                    case 'UPDATE_IMAGE':
                        $imgUrl = $payload['image_url'] ?? '';
                        if (!empty($imgUrl)) {
                            $savedPath = $this->saveImageFromUrl($imgUrl, $product->image);
                            if ($savedPath) {
                                $product->update(['image' => $savedPath]);
                                $confirmDetails[] =
                                    "🎯 {$icon} {$product->name} (Mã: {$product->code})\n" .
                                    "🖼️ Đã thay đổi Ảnh chính (Ảnh đại diện) mới thành công!\n" .
                                    "🌐 Xem trực tiếp: {$webUrl}";
                            } else {
                                $confirmDetails[] = "⚠️ {$icon} {$product->name}: Không thể tải ảnh từ Zalo, vui lòng thử lại!";
                            }
                        }
                        break;

                    case 'ADD_IMAGE':
                        $imgUrl = $payload['image_url'] ?? '';
                        if (!empty($imgUrl)) {
                            $savedPath = $this->saveImageFromUrl($imgUrl);
                            if ($savedPath) {
                                $gallery = $product->images ?? [];
                                if (!in_array($savedPath, $gallery)) {
                                    $gallery[] = $savedPath;
                                }
                                $product->update(['images' => array_values($gallery)]);
                                $totalCount = count($gallery);
                                $confirmDetails[] =
                                    "🎯 {$icon} {$product->name} (Mã: {$product->code})\n" .
                                    "🖼️ Đã thêm 1 ảnh mới vào Danh sách ảnh phụ (Hiện có: {$totalCount} ảnh phụ)\n" .
                                    "🌐 Xem trực tiếp: {$webUrl}";
                            } else {
                                $confirmDetails[] = "⚠️ {$icon} {$product->name}: Không thể tải ảnh từ Zalo, vui lòng thử lại!";
                            }
                        }
                        break;

                    case 'UPDATE_VIDEO':
                        $videoInput = $payload['video_input'] ?? '';
                        $isYt = $this->isYoutubeUrl($videoInput);

                        if ($isYt) {
                            if ($product->video && !$product->is_youtube) {
                                $this->deleteS3File($product->video);
                            }
                            $product->update(['video' => $videoInput]);
                            $confirmDetails[] =
                                "🎯 {$icon} {$product->name} (Mã: {$product->code})\n" .
                                "🎬 Đã gắn Video YouTube: {$videoInput}\n" .
                                "🌐 Xem trực tiếp: {$webUrl}";
                        } else {
                            $savedVideoPath = $this->saveVideoFromUrl($videoInput, $product->video);
                            if ($savedVideoPath) {
                                $product->update(['video' => $savedVideoPath]);
                                $confirmDetails[] =
                                    "🎯 {$icon} {$product->name} (Mã: {$product->code})\n" .
                                    "🎬 Đã tải lên và gắn Video thành công trên MinIO S3!\n" .
                                    "🌐 Xem trực tiếp: {$webUrl}";
                            } else {
                                $confirmDetails[] = "⚠️ {$icon} {$product->name}: Không thể lưu file video, vui lòng thử lại!";
                            }
                        }
                        break;

                    case 'DELETE_VIDEO':
                        if ($product->video && !$product->is_youtube) {
                            $this->deleteS3File($product->video);
                        }
                        $product->update(['video' => null]);
                        $confirmDetails[] = "🗑️ {$icon} {$product->name} (Mã: {$product->code}) ➔ Đã xóa video khỏi sản phẩm";
                        break;
                }
            }
        }

        $updatedDaily = Product::where('is_daily', true)->get();

        if ($intent === 'UPDATE_VIDEO') {
            $reply = "✅ XÁC NHẬN ĐÃ GẮN VIDEO SẢN PHẨM THÀNH CÔNG:\n\n" . implode("\n\n─────────────────\n\n", $confirmDetails) .
                     "\n\n✨ Video đã được tích hợp và hiển thị trực tiếp trên website rồi Sếp nhé!";
        } elseif ($intent === 'DELETE_VIDEO') {
            $reply = "✅ XÁC NHẬN ĐÃ XÓA VIDEO SẢN PHẨM THÀNH CÔNG:\n\n" . implode("\n\n─────────────────\n\n", $confirmDetails) .
                     "\n\n✨ Video đã được gỡ bỏ khỏi website rồi Sếp nhé!";
        } elseif ($intent === 'UPDATE_IMAGE' || $intent === 'ADD_IMAGE') {
            $reply = "✅ XÁC NHẬN ĐÃ CẬP NHẬT ẢNH SẢN PHẨM THÀNH CÔNG:\n\n" . implode("\n\n─────────────────\n\n", $confirmDetails) .
                     "\n\n✨ Hình ảnh mới đã được hiển thị ngay lập tức trên website rồi Sếp nhé!";
        } elseif ($intent === 'RESTORE_PRODUCT') {
            $reply = "✅ XÁC NHẬN ĐÃ MỞ BÁN LẠI SẢN PHẨM THÀNH CÔNG:\n" . implode("\n", $confirmDetails) .
                     "\n\n✨ Sản phẩm đã được hiển thị và mở bán trở lại trên toàn bộ website rồi Sếp nhé!";
        } elseif ($intent === 'DELETE_PRODUCT') {
            $reply = "✅ XÁC NHẬN ĐÃ XÓA SẢN PHẨM KHỎI WEBSITE THÀNH CÔNG:\n" . implode("\n", $confirmDetails) .
                     "\n\n✨ Sản phẩm đã được ẩn và ngừng kinh doanh hoàn toàn trên toàn bộ website rồi Sếp nhé!";
        } elseif ($intent === 'REMOVE_DAILY') {
            $reply = "✅ XÁC NHẬN ĐÃ TẮT THÀNH CÔNG:\n" . implode("\n", $confirmDetails) . "\n\n" .
                     $this->buildDailyListReply("📋 Menu hôm nay hiện tại còn lại:", $updatedDaily);
        } elseif ($intent === 'ADD_DAILY') {
            $reply = "✅ XÁC NHẬN ĐÃ THÊM MÓN VÀO MENU HÔM NAY:\n" . implode("\n", $confirmDetails) . "\n\n" .
                     $this->buildDailyListReply("📋 Thực đơn hôm nay hiện tại có ({$updatedDaily->count()} món):", $updatedDaily);
        } elseif ($intent === 'SET_DAILY') {
            $reply = $this->buildDailyListReply("✅ XÁC NHẬN ĐÃ THIẾT LẬP MENU BÁN HÔM NAY ({$updatedDaily->count()} món):", $updatedDaily);
        } else {
            $reply = "✅ XÁC NHẬN ĐÃ CẬP NHẬT THÔNG TIN THÀNH CÔNG:\n\n" . implode("\n\n─────────────────\n\n", $confirmDetails) .
                     "\n\n✨ Toàn bộ dữ liệu mới đã được áp dụng ngay lên website rồi Sếp nhé!";
        }

        return [
            'success' => true,
            'intent' => $intent,
            'reply_message' => $reply,
            'updated_daily_count' => $updatedDaily->count()
        ];
    }

    /**
     * Tạo tin nhắn hỏi lại khi tìm thấy nhiều hơn 1 sản phẩm tương đồng
     */
    protected function askForClarification(array $matchedProducts, string $intent, array $payload, string $senderId, array $alreadyMatched = []): array
    {
        $options = [];
        $lines = ["⚠️ Sếp ơi, trong câu lệnh có món chưa cụ thể phân loại:"];

        if (!empty($alreadyMatched)) {
            $alreadyNames = [];
            foreach ($alreadyMatched as $p) {
                $icon = $this->getFruitIcon($p->code, $p->name);
                $alreadyNames[] = "{$icon} {$p->name}";
            }
            $lines[] = "(Đã nhận diện: " . implode(', ', $alreadyNames) . ")\n";
        }

        $lines[] = "👉 Hiện cửa hàng đang có " . count($matchedProducts) . " phân loại tương tự:";

        $i = 1;
        foreach ($matchedProducts as $p) {
            $icon = $this->getFruitIcon($p->code, $p->name);
            $priceText = number_format($p->price, 0, ',', '.') . 'đ/' . ($p->unit ?: 'kg');
            $statusText = $p->is_daily ? '🟢 Đang bán' : '⚪ Đang ẩn';
            $lines[] = "{$i}️⃣ {$icon} {$p->name} (Mã: {$p->code} | {$priceText} | {$statusText})";

            $options[] = [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name
            ];
            $i++;
        }

        $count = count($matchedProducts);
        $lines[] = "\n👉 Sếp vui lòng xác nhận muốn áp dụng cho phân loại nào ạ?";

        if ($count === 2) {
            $lines[] = "• Nhắn \"1\" để chọn món số 1";
            $lines[] = "• Nhắn \"2\" để chọn món số 2";
            $lines[] = "• Nhắn \"cả 2\" để chọn tất cả";
        } else {
            for ($k = 1; $k <= min($count, 5); $k++) {
                $lines[] = "• Nhắn \"{$k}\" để chọn món số {$k}";
            }
            if ($count > 5) {
                $lines[] = "• (hoặc nhắn số từ 1 đến {$count} để chọn món tương ứng)";
            }
            $lines[] = "• Nhắn \"tất cả\" (hoặc \"cả {$count}\") để chọn tất cả";
        }
        $lines[] = "• Nhắn \"hủy\" để bỏ qua";

        // Lưu câu hỏi xác nhận vào Cache trong 10 phút
        $cacheKey = "zalo_pending_clarification_{$senderId}";
        Cache::put($cacheKey, [
            'intent' => $intent,
            'options' => $options,
            'payload' => $payload,
            'created_at' => now()
        ], 600);

        return [
            'success' => true,
            'intent' => 'AWAITING_CLARIFICATION',
            'reply_message' => implode("\n", $lines),
            'updated_daily_count' => Product::where('is_daily', true)->count()
        ];
    }

    /**
     * Phân tích NLP nội bộ thông minh có xác nhận khi trùng loại
     */
    protected function processLocalNlp(string $message, $products, string $senderId, ?string $imageUrl = null, ?string $videoUrl = null): array
    {
        $rawMessage = trim($message);
        $normalized = $this->normalizePhonetic($rawMessage);

        // Chuẩn hóa: Nếu imageUrl thực chất là link YouTube hoặc Video
        if ($this->isYoutubeUrl($imageUrl) || ($imageUrl && preg_match('/(video-stal|dlmd\.me|\.(mp4|mov|webm|m4v|avi))/i', $imageUrl))) {
            $videoUrl = $imageUrl;
            $imageUrl = null;
        }

        if (empty($videoUrl)) {
            $videoUrl = $this->extractYoutubeUrl($rawMessage);
        }

        // ========================================================
        // 0. KIỂM TRA INTENT: THÊM MỚI SẢN PHẨM (CREATE_PRODUCT)
        // ========================================================
        $isCreateProduct = preg_match('/\b(them san pham moi|them mon moi|tao san pham|them moi san pham|them moi mon|them san pham|tao mon moi|them trai cay|nhap san pham moi|them mat hang|tao mat hang|them moi)\b/iu', $normalized)
            || (preg_match('/\b(them|tao|nhap)\b/iu', $normalized) && preg_match('/\b(san pham|mon|mat hang|trai cay)\b/iu', $normalized) && preg_match('/\b(gia|\d+k|\d+000|xuat xu|mo ta|don vi|quy cach)\b/iu', $normalized));

        $isNotCreate = preg_match('/\b(them anh|them hinh|them video|them clip|them vao menu|them vao thuc don|them vao danh sach|them vao hom nay|them mon vao)\b/iu', $normalized);

        if ($isCreateProduct && !$isNotCreate) {
            return $this->handleCreateProduct($rawMessage, $senderId, $imageUrl, $videoUrl);
        }

        // Tách Target Prompt (phần chỉ định sản phẩm) và New Value (phần nội dung mới)
        $split = $this->splitTargetAndNewValue($rawMessage, ['thanh', 'la', 'thay bang', 'sang', 'thanh:', 'la:', ':']);
        $targetPrompt = $split['target_part'];
        $extractedNewValue = $split['new_value'];

        // 1. TÌM SẢN PHẨM KHỚP TRONG PHẦN CHỈ ĐỊNH (Không bị nhầm với từ trong mô tả mới)
        $matchedCandidates = $this->findCandidateProducts($targetPrompt, $products);
        if (empty($matchedCandidates)) {
            $matchedCandidates = $this->findCandidateProducts($rawMessage, $products);
        }

        // ========================================================
        // 1.5. KIỂM TRA INTENT: VIDEO SẢN PHẨM (UPDATE_VIDEO / DELETE_VIDEO)
        // (Ưu tiên kiểm tra trước ảnh để không bị nhầm khi tin nhắn có link preview)
        // ========================================================
        $isVideoIntent = !empty($videoUrl) || preg_match('/\b(video|clip|phim|youtube|shorts|video san pham)\b/iu', $normalized);
        if ($isVideoIntent) {
            $isDeleteVideo = preg_match('/\b(xoa video|go video|bo video|tat video|khong dung video)\b/iu', $normalized);

            if ($isDeleteVideo) {
                if (!empty($matchedCandidates)) {
                    if (count($matchedCandidates) > 1) {
                        return $this->askForClarification($matchedCandidates, 'DELETE_VIDEO', [], $senderId);
                    }
                    $firstProduct = reset($matchedCandidates);
                    return $this->executeSelectedAction('DELETE_VIDEO', [['id' => $firstProduct->id]], []);
                }
            }

            $videoInput = !empty($videoUrl) ? $videoUrl : $this->extractYoutubeUrl($rawMessage);

            if (!empty($videoInput)) {
                if (!empty($matchedCandidates)) {
                    Cache::forget("zalo_pending_video_target_{$senderId}");

                    if (count($matchedCandidates) > 1) {
                        return $this->askForClarification($matchedCandidates, 'UPDATE_VIDEO', ['video_input' => $videoInput], $senderId);
                    }

                    $firstProduct = reset($matchedCandidates);
                    return $this->executeSelectedAction('UPDATE_VIDEO', [['id' => $firstProduct->id]], ['video_input' => $videoInput]);
                }
            } else {
                // Người dùng chỉ nhắn text yêu cầu (chưa có video đính kèm hay link YouTube)
                if (!empty($matchedCandidates)) {
                    if (count($matchedCandidates) > 1) {
                        return $this->askForClarification($matchedCandidates, 'UPDATE_VIDEO', ['video_input' => ''], $senderId);
                    }

                    $firstProduct = reset($matchedCandidates);
                    $icon = $this->getFruitIcon($firstProduct->code, $firstProduct->name);

                    // Lưu ngữ cảnh chờ nhận video cho sản phẩm này
                    Cache::put("zalo_pending_video_target_{$senderId}", [
                        'product_id' => $firstProduct->id,
                        'product_name' => $firstProduct->name,
                        'intent' => 'UPDATE_VIDEO'
                    ], now()->addMinutes(10));

                    return [
                        'success' => true,
                        'intent' => 'AWAITING_VIDEO_UPLOAD',
                        'reply_message' => "🎬 Dạ em đã ghi nhận yêu cầu gắn video cho {$icon} {$firstProduct->name} rồi ạ!\n\n👉 Sếp có thể:\n1️⃣ Gửi trực tiếp file video clip qua Zalo (định dạng mp4, mov...).\n2️⃣ Hoặc dán link YouTube / YouTube Shorts vào đây (ví dụ: https://youtube.com/shorts/...).\n\nEm sẽ tự động gắn ngay vào sản phẩm cho Sếp nhé!",
                        'updated_daily_count' => Product::where('is_daily', true)->count()
                    ];
                }

                return [
                    'success' => true,
                    'intent' => 'PROMPT_SEND_VIDEO',
                    'reply_message' => "🎬 Sếp vui lòng gửi file video hoặc dán link YouTube kèm tên sản phẩm như:\n👉 \"thêm video cho xoài cát thái: https://youtube.com/shorts/...\"\n👉 \"gắn video này vào kiwi xanh\"\n\nEm sẽ tự động áp dụng ngay lên website cho Sếp nhé!",
                    'updated_daily_count' => Product::where('is_daily', true)->count()
                ];
            }
        }

        // ========================================================
        // 1.6. KIỂM TRA INTENT: ĐỔI ẢNH ĐẠI DIỆN HOẶC THÊM ẢNH PHỤ (UPDATE_IMAGE / ADD_IMAGE)
        // ========================================================
        // 1. Nhận diện Ảnh chính (Avatar / Thumbnail)
        $isUpdateAvatar = preg_match('/\b(anh chinh|anh dai dien|anh bia|avatar|hinh chinh|hinh dai dien|anh dai dien chinh|doi anh chinh|thay anh chinh|doi anh dai dien|thay anh dai dien|cap nhat anh dai dien|doi avatar|thay avatar)\b/iu', $normalized);

        // 2. Nhận diện Ảnh phụ / Gallery (chỉ khi không yêu cầu ảnh chính)
        $isAddGallery = preg_match('/\b(anh phu|hinh phu|gallery|bo suu tap|danh sach anh|album|them vao gallery|them anh phu|them hinh phu)\b/iu', $normalized)
            || (preg_match('/\b(them anh|them \d+ anh|them mot anh|them hinh|bo sung anh|them vao)\b/iu', $normalized) && !$isUpdateAvatar);

        $isImageIntent = !empty($imageUrl) || $isAddGallery || $isUpdateAvatar || preg_match('/\b(anh|hinh anh|photo|image|picture)\b/iu', $normalized);

        if ($isImageIntent) {
            $imageIntent = $isUpdateAvatar ? 'UPDATE_IMAGE' : 'ADD_IMAGE';

            if (!empty($imageUrl)) {
                if (!empty($matchedCandidates)) {
                    Cache::forget("zalo_pending_image_{$senderId}");
                    Cache::forget("zalo_pending_image_target_{$senderId}");

                    if (count($matchedCandidates) > 1) {
                        return $this->askForClarification($matchedCandidates, $imageIntent, ['image_url' => $imageUrl], $senderId);
                    }

                    $firstProduct = reset($matchedCandidates);
                    return $this->executeSelectedAction($imageIntent, [['id' => $firstProduct->id]], ['image_url' => $imageUrl], $senderId);
                } elseif (empty($rawMessage) || in_array($normalized, ['gui anh', 'anh', 'hinh', 'photo', 'image'])) {
                    Cache::put("zalo_pending_image_{$senderId}", $imageUrl, now()->addMinutes(10));
                    return [
                        'success' => true,
                        'intent' => 'AWAITING_PRODUCT_FOR_IMAGE',
                        'reply_message' => "📸 Dạ em đã nhận được hình ảnh của Sếp rồi ạ!\n\n👉 Sếp muốn thêm ảnh này vào sản phẩm nào ạ?\n(Ví dụ: Sếp nhắn \"xoài cát thái\" hoặc \"thêm ảnh phụ kiwi xanh\")",
                        'updated_daily_count' => Product::where('is_daily', true)->count()
                    ];
                }
            } else {
                // Người dùng chỉ nhắn text yêu cầu nhưng chưa gửi kèm ảnh
                if (!empty($matchedCandidates)) {
                    if (count($matchedCandidates) > 1) {
                        return $this->askForClarification($matchedCandidates, $imageIntent, ['image_url' => ''], $senderId);
                    }

                    $firstProduct = reset($matchedCandidates);
                    $icon = $this->getFruitIcon($firstProduct->code, $firstProduct->name);

                    // Lưu trạng thái đang chờ nhận ảnh cho sản phẩm này
                    Cache::put("zalo_pending_image_target_{$senderId}", [
                        'product_id' => $firstProduct->id,
                        'product_name' => $firstProduct->name,
                        'intent' => $imageIntent
                    ], now()->addMinutes(10));

                    $actionName = ($imageIntent === 'UPDATE_IMAGE') ? 'đổi ảnh đại diện' : 'thêm ảnh vào danh sách';

                    return [
                        'success' => true,
                        'intent' => 'AWAITING_IMAGE_UPLOAD',
                        'reply_message' => "📸 Dạ em đã ghi nhận yêu cầu {$actionName} cho {$icon} {$firstProduct->name} rồi ạ!\n\n👉 Sếp hãy gửi bức ảnh qua Zalo ngay bây giờ (chọn ảnh và bấm gửi), em sẽ tự động lưu và áp dụng ngay cho sản phẩm nhé!",
                        'updated_daily_count' => Product::where('is_daily', true)->count()
                    ];
                }

                return [
                    'success' => true,
                    'intent' => 'PROMPT_SEND_IMAGE',
                    'reply_message' => "📸 Sếp vui lòng gửi kèm bức ảnh trên Zalo và nhập lời nhắn (caption) như:\n👉 \"thêm ảnh này vào xoài cát thái\"\n👉 \"đổi ảnh đại diện kiwi xanh\"\n\nEm sẽ tự động cập nhật ngay lập tức lên website cho Sếp nhé!",
                    'updated_daily_count' => Product::where('is_daily', true)->count()
                ];
            }
        }

        // ========================================================
        // 2. KIỂM TRA INTENT: SỬA THÔNG TIN DINH DƯỠNG (UPDATE_NUTRITION)
        // ========================================================
        if (preg_match('/(dinh duong|thong tin dinh duong|dinhduong|\bdd\b|duong chat|nutrition|kcal|calo|carbs?|vitamin|chat xo)/i', $normalized)) {
            $newNutrition = $extractedNewValue;
            if (empty($newNutrition) && preg_match('/(?:dinh duong|nutrition|duong chat)[:\s-]+(.*)/iu', $rawMessage, $m)) {
                $newNutrition = trim($m[1], " :\"'”’.,-\t\n\r");
            }

            if (!empty($newNutrition) && !empty($matchedCandidates)) {
                if (count($matchedCandidates) > 1) {
                    return $this->askForClarification($matchedCandidates, 'UPDATE_NUTRITION', ['new_nutrition' => $newNutrition], $senderId);
                }

                $firstProduct = reset($matchedCandidates);
                return $this->executeSelectedAction('UPDATE_NUTRITION', [['id' => $firstProduct->id]], ['new_nutrition' => $newNutrition]);
            }
        }

        // ========================================================
        // 3. KIỂM TRA INTENT: SỬA MÔ TẢ (UPDATE_DESC)
        // ========================================================
        if (preg_match('/(mo ta|cau mo ta|mota|\bmt\b|noi dung|bai viet|chi tiet|description|desc)/i', $normalized)) {
            $newDesc = $extractedNewValue;
            if (empty($newDesc) && preg_match('/(?:mo ta|noi dung|chi tiet|cau mo ta)[:\s-]+(.*)/iu', $rawMessage, $m)) {
                $newDesc = trim($m[1], " :\"'”’.,-\t\n\r");
            }

            if (!empty($newDesc) && !empty($matchedCandidates)) {
                if (count($matchedCandidates) > 1) {
                    return $this->askForClarification($matchedCandidates, 'UPDATE_DESC', ['new_desc' => $newDesc], $senderId);
                }

                $firstProduct = reset($matchedCandidates);
                return $this->executeSelectedAction('UPDATE_DESC', [['id' => $firstProduct->id]], ['new_desc' => $newDesc]);
            }
        }

        // ========================================================
        // 4. KIỂM TRA INTENT: SỬA XUẤT XỨ (UPDATE_ORIGIN)
        // ========================================================
        if (preg_match('/(xuat xu|xuatxu|\bxx\b|nguon goc|nhap tu|que|origin)/i', $normalized)) {
            $newOrigin = $extractedNewValue;
            if (!empty($newOrigin) && !empty($matchedCandidates)) {
                if (count($matchedCandidates) > 1) {
                    return $this->askForClarification($matchedCandidates, 'UPDATE_ORIGIN', ['new_origin' => $newOrigin], $senderId);
                }
                $firstProduct = reset($matchedCandidates);
                return $this->executeSelectedAction('UPDATE_ORIGIN', [['id' => $firstProduct->id]], ['new_origin' => $newOrigin]);
            }
        }

        // ========================================================
        // 5. KIỂM TRA INTENT: SỬA QUY CÁCH ĐÓNG GÓI (UPDATE_PACK)
        // ========================================================
        if (preg_match('/(dong goi|donggoi|\bdg\b|quy cach|quycach|hop|thung|tui|pack)/i', $normalized)) {
            $newPack = $extractedNewValue;
            if (!empty($newPack) && !empty($matchedCandidates)) {
                if (count($matchedCandidates) > 1) {
                    return $this->askForClarification($matchedCandidates, 'UPDATE_PACK', ['new_pack' => $newPack], $senderId);
                }
                $firstProduct = reset($matchedCandidates);
                return $this->executeSelectedAction('UPDATE_PACK', [['id' => $firstProduct->id]], ['new_pack' => $newPack]);
            }
        }

        // ========================================================
        // 6. KIỂM TRA INTENT: SỬA GIÁ BÁN (UPDATE_PRICE)
        // ========================================================
        if (preg_match('/(gia|ban gia|giam con|tang len|ha gia|sale|\bk\b|nghin|ngan|000)/i', $normalized) && !empty($matchedCandidates)) {
            $priceSearchText = !empty($extractedNewValue) ? $extractedNewValue : $rawMessage;
            if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(k|nghin|ngan|000|đ|vnd)?/i', $priceSearchText, $matches, PREG_SET_ORDER)) {
                $validPrice = null;
                foreach ($matches as $m) {
                    $rawNum = (float)str_replace([',', '.'], '', $m[1]);
                    $unit = strtolower($m[2] ?? '');

                    if (in_array($unit, ['k', 'nghin', 'ngan'])) {
                        $calc = $rawNum * 1000;
                    } elseif ($rawNum < 1000 && in_array($unit, ['000', 'đ', 'vnd'])) {
                        $calc = $rawNum * 1000;
                    } else {
                        $calc = $rawNum;
                    }

                    if ($calc >= 5000) {
                        $validPrice = $calc;
                        break;
                    }
                }

                if ($validPrice !== null) {
                    if (count($matchedCandidates) > 1) {
                        return $this->askForClarification($matchedCandidates, 'UPDATE_PRICE', ['new_price' => $validPrice], $senderId);
                    }
                    $firstProduct = reset($matchedCandidates);
                    return $this->executeSelectedAction('UPDATE_PRICE', [['id' => $firstProduct->id]], ['new_price' => $validPrice]);
                }
            }
        }

        // ========================================================
        // 7. KIỂM TRA LỆNH HỎI THỰC ĐƠN HÔM NAY (GET_STATUS)
        // ========================================================
        if (preg_match('/(dang ban|ban nhung mon gi|kiem tra menu|check menu|danh sach hom nay|thuc don|trang thai|\bcheck\b|\bhn\b.*ban gi)/i', $normalized)) {
            $currentDaily = Product::where('is_daily', true)->get();
            return [
                'success' => true,
                'intent' => 'GET_STATUS',
                'reply_message' => $this->buildDailyListReply('📋 Menu **"Hôm nay bán gì?"** hiện tại trên website đang có:', $currentDaily),
                'updated_daily_count' => $currentDaily->count()
            ];
        }

        // ========================================================
        // 8. KIỂM TRA LỆNH KHÔI PHỤC / MỞ BÁN LẠI (RESTORE_PRODUCT)
        // ========================================================
        $isRestore = preg_match('/\b(mo ban lai|ban lai|kinh doanh lai|khoi phuc|bat lai|phuc hoi|hien thi lai|mo lai|ban tro lai)\b/iu', $normalized);
        if ($isRestore) {
            $dailyAnalysis = $this->analyzeDailySentence($rawMessage, $products);
            $exactProducts = $dailyAnalysis['exact'];
            $ambiguousGroups = $dailyAnalysis['ambiguous'];

            if (!empty($ambiguousGroups)) {
                $firstAmbiguous = $ambiguousGroups[0];
                $exactIds = array_keys($exactProducts);
                return $this->askForClarification(
                    $firstAmbiguous,
                    'RESTORE_PRODUCT',
                    ['exact_product_ids' => $exactIds],
                    $senderId,
                    $exactProducts
                );
            }

            if (!empty($exactProducts)) {
                $options = array_map(fn($p) => ['id' => $p->id], $exactProducts);
                return $this->executeSelectedAction('RESTORE_PRODUCT', $options, []);
            }

            if (!empty($matchedCandidates)) {
                if (count($matchedCandidates) > 1) {
                    return $this->askForClarification($matchedCandidates, 'RESTORE_PRODUCT', [], $senderId);
                }
                $options = array_map(fn($p) => ['id' => $p->id], $matchedCandidates);
                return $this->executeSelectedAction('RESTORE_PRODUCT', $options, []);
            }
        }

        // ========================================================
        // 9. KIỂM TRA LỆNH XÓA VĨNH VIỄN KHỎI WEBSITE (DELETE_PRODUCT) VS TẮT MENU HÔM NAY (REMOVE_DAILY)
        // ========================================================
        $hasTodayScope = preg_match('/\b(hom nay|ngay hom nay|bua nay|nay|today|trong ngay|ngay mai)\b/iu', $normalized);

        // Lệnh Xóa vĩnh viễn / Ngừng kinh doanh hoàn toàn (phải có từ xóa sản phẩm, ngừng kinh doanh... và KHÔNG có từ "hôm nay")
        $isDelete = preg_match('/\b(xoa san pham|xoa khoi web|xoa khoi trang|xoa han|ngung kinh doanh|bo han san pham|xoa vinh vien|xoa bo)\b/iu', $normalized)
            || (preg_match('/\b(khong ban.*nua|ko ban.*nua|k ban.*nua)\b/iu', $normalized) && !$hasTodayScope && preg_match('/\b(san pham|mat hang|mon nay)\b/iu', $normalized));

        if ($isDelete && !empty($matchedCandidates)) {
            if (count($matchedCandidates) > 1) {
                return $this->askForClarification($matchedCandidates, 'DELETE_PRODUCT', [], $senderId);
            }

            $options = array_map(fn($p) => ['id' => $p->id], $matchedCandidates);
            return $this->executeSelectedAction('DELETE_PRODUCT', $options, [], $senderId);
        }

        // Lệnh Tắt bán trong ngày / Hết hàng (REMOVE_DAILY):
        $isRemoveDaily = preg_match('/\b(het|tat|tat mon|dung ban|ngung ban|ngung|bo mon|khong ban|ko ban|k ban|k muon ban|ko muon ban|khong muon ban|off|tam ngung|het hang|khong ban nua|ko ban nua)\b/iu', $normalized);
        if ($isRemoveDaily && !empty($matchedCandidates)) {
            if (count($matchedCandidates) > 1) {
                return $this->askForClarification($matchedCandidates, 'REMOVE_DAILY', [], $senderId);
            }

            $options = array_map(fn($p) => ['id' => $p->id], $matchedCandidates);
            return $this->executeSelectedAction('REMOVE_DAILY', $options, [], $senderId);
        }

        // ========================================================
        // 9. KIỂM TRA LỆNH CẬP NHẬT MENU BÁN HÔM NAY (SET_DAILY / ADD_DAILY)
        // ========================================================
        $dailyAnalysis = $this->analyzeDailySentence($rawMessage, $products);
        $exactProducts = $dailyAnalysis['exact'];
        $ambiguousGroups = $dailyAnalysis['ambiguous'];

        $isAddOnly = preg_match('/\b(them|them vao|bat them|co them|cho them|ban them|them mon|bo sung)\b|\+/iu', $normalized);
        $dailyIntent = $isAddOnly ? 'ADD_DAILY' : 'SET_DAILY';

        // Nếu có nhóm sản phẩm chưa đạt 100% (ví dụ: người dùng nói "xoài thái" trong khi có Xoài cát Thái và Xoài thái loại 2)
        if (!empty($ambiguousGroups)) {
            $firstAmbiguous = $ambiguousGroups[0];
            $exactIds = array_keys($exactProducts);
            
            return $this->askForClarification(
                $firstAmbiguous,
                $dailyIntent,
                ['exact_product_ids' => $exactIds],
                $senderId,
                $exactProducts
            );
        }

        if (!empty($exactProducts)) {
            if (!$isAddOnly) {
                Product::query()->update(['is_daily' => false]);
            }
            $options = array_map(fn($p) => ['id' => $p->id], $exactProducts);
            return $this->executeSelectedAction($dailyIntent, $options, []);
        }

        // ========================================================
        // 10. CHAT TỔNG QUÁT / HƯỚNG DẪN
        // ========================================================
        return [
            'success' => true,
            'intent' => 'CHAT_GENERAL',
            'reply_message' => "Dạ em chào Sếp! Em là Trợ lý AI Hoa Quả Sơn Tây. Sếp có thể ra lệnh cho em nhanh bằng các câu như:\n\n" .
                               "• Sửa câu mô tả của xoài thái thành: Xoài cát ngọt đậm, thơm lừng chuẩn vị Thái.\n" .
                               "• Thông tin dinh dưỡng cam xanh: Giàu Vitamin C, ít đường...\n" .
                               "• Hôm nay bán cam xanh, cam úc và kiwi xanh nhé\n" .
                               "• Tắt xoài thái đi\n" .
                               "• Hôm nay đang bán những món gì?",
            'updated_daily_count' => Product::where('is_daily', true)->count()
        ];
    }

    /**
     * Phân tích câu lệnh liệt kê bán hôm nay để phân loại rõ ràng:
     * - exact: các sản phẩm khớp chính xác 100%
     * - ambiguous: các nhóm sản phẩm chưa rõ ràng (< 100%) cần Confirm
     */
    protected function analyzeDailySentence(string $userMessage, $products): array
    {
        $uNorm = $this->normalizePhonetic($userMessage);
        $uClean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $uNorm);
        $rawWords = preg_split('/\s+/u', $uClean, -1, PREG_SPLIT_NO_EMPTY);
        $userLen = count($rawWords);

        if ($userLen === 0) return ['exact' => [], 'ambiguous' => []];

        $candidateMatches = [];
        $afterStopWords = [
            'di', 'nhe', 'nha', 'roi', 'nua', 'nho', 'nhung', 'ma', 'la', 'thanh', 'sang', 'gia', 'ban', 'het', 'off', 'tam', 'ngung', 'luon', 'voi', 'va'
        ];

        foreach ($products as $p) {
            $pNorm = $this->normalizePhonetic($p->name);
            $pClean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $pNorm);
            $pWords = preg_split('/\s+/u', $pClean, -1, PREG_SPLIT_NO_EMPTY);
            $pLen = count($pWords);
            if ($pLen === 0) continue;

            $isExactCleanMatch = false;
            $score = 0;
            $matchedWordCount = 0;
            $startIdx = -1;
            $endIdx = -1;

            // Tìm vị trí chuỗi $pClean trong mảng từ $rawWords
            for ($i = 0; $i <= $userLen - $pLen; $i++) {
                $slice = array_slice($rawWords, $i, $pLen);
                if ($slice === $pWords) {
                    $nextWord = $rawWords[$i + $pLen] ?? null;
                    $hasUnmatchedQualifier = false;

                    if ($nextWord && !in_array($nextWord, $afterStopWords)) {
                        foreach ($products as $otherP) {
                            if ($otherP->id === $p->id) continue;
                            $otherNorm = $this->normalizePhonetic($otherP->name);
                            $otherWords = preg_split('/\s+/u', $otherNorm, -1, PREG_SPLIT_NO_EMPTY);
                            if (in_array($nextWord, $otherWords) || levenshtein($nextWord, 'sai') <= 1) {
                                $hasUnmatchedQualifier = true;
                                break;
                            }
                        }
                    }

                    if (!$hasUnmatchedQualifier) {
                        $isExactCleanMatch = true;
                        $score = 1000 + ($pLen * 100);
                        $matchedWordCount = $pLen;
                    } else {
                        $score = 500 + ($pLen * 50);
                        $matchedWordCount = $pLen;
                    }
                    $startIdx = $i;
                    $endIdx = $i + $pLen - 1;
                    break;
                }
            }

            if (!$isExactCleanMatch) {
                $matchedIndices = [];
                $firstMatchIdx = -1;
                $lastMatchIdx = -1;
                foreach ($rawWords as $wIdx => $uW) {
                    foreach ($pWords as $pIdx => $pW) {
                        if ($uW === $pW && !in_array($pIdx, $matchedIndices)) {
                            $matchedIndices[] = $pIdx;
                            if ($firstMatchIdx === -1) $firstMatchIdx = $wIdx;
                            $lastMatchIdx = $wIdx;
                        }
                    }
                }

                if (in_array(0, $matchedIndices)) {
                    $wordRecall = count($matchedIndices) / $pLen;
                    if ($wordRecall >= 0.4) {
                        $score = ($wordRecall * 100) + (count($matchedIndices) * 50);
                        $matchedWordCount = count($matchedIndices);
                    }
                    $startIdx = $firstMatchIdx;
                    $endIdx = $lastMatchIdx;
                }
            }

            if ($score > 0) {
                $candidateMatches[] = [
                    'product' => $p,
                    'name' => $p->name,
                    'score' => $score,
                    'is_exact' => $isExactCleanMatch,
                    'matched_words' => $matchedWordCount,
                    'pLen' => $pLen,
                    'start' => $startIdx >= 0 ? $startIdx : 0,
                    'end' => $endIdx >= 0 ? $endIdx : $userLen - 1,
                ];
            }
        }

        // Gom nhóm theo vùng từ trong câu (Span Clustering)
        $clusters = [];
        foreach ($candidateMatches as $cand) {
            $matchedCluster = -1;
            $candTokens = range($cand['start'], $cand['end']);

            foreach ($clusters as $cIdx => $clust) {
                $clustTokens = range($clust['start'], $clust['end']);
                if (!empty(array_intersect($candTokens, $clustTokens))) {
                    $matchedCluster = $cIdx;
                    break;
                }
            }

            if ($matchedCluster >= 0) {
                $clusters[$matchedCluster]['items'][] = $cand;
                $clusters[$matchedCluster]['start'] = min($clusters[$matchedCluster]['start'], $cand['start']);
                $clusters[$matchedCluster]['end'] = max($clusters[$matchedCluster]['end'], $cand['end']);
            } else {
                $clusters[] = [
                    'start' => $cand['start'],
                    'end' => $cand['end'],
                    'items' => [$cand]
                ];
            }
        }

        $exactProducts = [];
        $ambiguousGroups = [];

        foreach ($clusters as $clust) {
            $items = $clust['items'];

            // 1. Chỉ khi có sản phẩm đạt 100% khớp tuyệt đối
            $exactMatches = array_filter($items, fn($it) => $it['is_exact']);
            if (count($exactMatches) === 1) {
                $topFull = reset($exactMatches);
                $exactProducts[$topFull['product']->id] = $topFull['product'];
                continue;
            } elseif (count($exactMatches) > 1) {
                uasort($exactMatches, fn($a, $b) => $b['pLen'] <=> $a['pLen']);
                $exactList = array_values($exactMatches);
                if ($exactList[0]['pLen'] > $exactList[1]['pLen']) {
                    $exactProducts[$exactList[0]['product']->id] = $exactList[0]['product'];
                    continue;
                }
            }

            // 2. Không có sản phẩm nào đạt 100% khớp tuyệt đối -> Yêu cầu xác nhận
            $ambiguousGroups[] = array_map(fn($it) => $it['product'], $items);
        }

        return [
            'exact' => $exactProducts,
            'ambiguous' => $ambiguousGroups
        ];
    }

    /**
     * Tìm tất cả các sản phẩm ứng viên có thể khớp với câu nói của người dùng
     * Áp dụng nguyên tắc an toàn: Khớp 100% mới chọn; nếu lệch từ hoặc mơ hồ -> Hỏi lại xác nhận
     */
    protected function findCandidateProducts(string $userMessage, $products): array
    {
        $uNorm = $this->normalizePhonetic($userMessage);
        $uClean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $uNorm);
        $rawWords = preg_split('/\s+/u', $uClean, -1, PREG_SPLIT_NO_EMPTY);
        $userLen = count($rawWords);

        if ($userLen === 0) return [];

        $scoredProducts = [];

        // Các từ dừng / trợ từ sau tên sản phẩm (không phải từ định danh phân loại)
        $afterStopWords = [
            'di', 'nhe', 'nha', 'roi', 'nua', 'nho', 'nhung', 'ma', 'la', 'thanh', 'sang', 'gia', 'ban', 'het', 'off', 'tam', 'ngung', 'luon', 'voi'
        ];

        foreach ($products as $idx => $p) {
            $pNorm = $this->normalizePhonetic($p->name);
            $pClean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $pNorm);
            $pWords = preg_split('/\s+/u', $pClean, -1, PREG_SPLIT_NO_EMPTY);
            $pLen = count($pWords);

            if ($pLen === 0) continue;

            $isExactCleanMatch = false;
            $matchedWordCount = 0;
            $score = 0;

            // Tìm vị trí chuỗi $pClean trong mảng từ $rawWords
            for ($i = 0; $i <= $userLen - $pLen; $i++) {
                $slice = array_slice($rawWords, $i, $pLen);
                if ($slice === $pWords) {
                    // Khớp đúng chính xác 100% từng từ!
                    // Kiểm tra xem từ liền sau có phải là từ định danh phân loại khác không (ví dụ "si", "gon" trong Dưa Hấu Sài Gòn)
                    $nextWord = $rawWords[$i + $pLen] ?? null;
                    $hasUnmatchedQualifier = false;

                    if ($nextWord && !in_array($nextWord, $afterStopWords)) {
                        foreach ($products as $otherP) {
                            if ($otherP->id === $p->id) continue;
                            $otherNorm = $this->normalizePhonetic($otherP->name);
                            $otherWords = preg_split('/\s+/u', $otherNorm, -1, PREG_SPLIT_NO_EMPTY);
                            if (in_array($nextWord, $otherWords) || levenshtein($nextWord, 'sai') <= 1) {
                                $hasUnmatchedQualifier = true;
                                break;
                            }
                        }
                    }

                    if (!$hasUnmatchedQualifier) {
                        $isExactCleanMatch = true;
                        $score = 1000 + ($pLen * 100);
                        $matchedWordCount = $pLen;
                    } else {
                        // Có từ định danh đi kèm mà sản phẩm ngắn này không khớp hết -> Mơ hồ, không thể coi là 100%
                        $score = 500 + ($pLen * 50);
                        $matchedWordCount = $pLen;
                    }
                    break;
                }
            }

            if (!$isExactCleanMatch) {
                // Khớp từng từ một (Partial match)
                $matchedIndices = [];
                foreach ($pWords as $pIdx => $pW) {
                    if (in_array($pW, $rawWords)) {
                        $matchedWordCount++;
                        $matchedIndices[] = $pIdx;
                    }
                }

                if (in_array(0, $matchedIndices)) {
                    $wordRecall = $matchedWordCount / $pLen;
                    if ($wordRecall >= 0.4) {
                        $score = ($wordRecall * 100) + ($matchedWordCount * 50);
                    }
                }
            }

            $pKey = $p->id ?: ('idx_' . $idx);

            if ($score > 0 && $matchedWordCount > 0) {
                $scoredProducts[$pKey] = [
                    'product' => $p,
                    'score' => $score,
                    'is_exact' => $isExactCleanMatch,
                    'matched_words' => $matchedWordCount,
                    'p_len' => $pLen,
                    'name_length' => mb_strlen($p->name, 'UTF-8')
                ];
            }
        }

        if (empty($scoredProducts)) return [];

        // 1. Nếu có đúng 1 sản phẩm đạt 100% khớp tuyệt đối (không bị dính từ định danh phân loại phía sau)
        $exactMatches = array_filter($scoredProducts, fn($sp) => $sp['is_exact']);
        if (count($exactMatches) === 1) {
            $topExact = reset($exactMatches);
            return [$topExact['product']];
        } elseif (count($exactMatches) > 1) {
            // Nếu có nhiều sản phẩm cùng khớp, ưu tiên sản phẩm có tên dài nhất
            uasort($exactMatches, fn($a, $b) => $b['p_len'] <=> $a['p_len']);
            $exactList = array_values($exactMatches);
            if ($exactList[0]['p_len'] > $exactList[1]['p_len']) {
                return [$exactList[0]['product']];
            }
        }

        // 2. KHÔNG có sản phẩm nào đạt 100% tuyệt đối (hoặc có sự mơ hồ / phân loại phụ) -> Trả về tất cả để HỎI LẠI
        return array_map(fn($sp) => $sp['product'], $scoredProducts);
    }

    /**
     * Chuẩn hóa ngữ âm tiếng Việt: Bỏ dấu, chữ thường
     */
    protected function normalizePhonetic(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $accents = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];
        foreach ($accents as $nonAccent => $pattern) {
            $str = preg_replace("/($pattern)/iu", $nonAccent, $str);
        }

        return $str;
    }

    /**
     * Chuẩn hóa từ ngữ: Chữ thường, bỏ dấu, chuẩn hóa lỗi bàn phím/phát âm
     */
    protected function normalizeWord(string $w): string
    {
        $w = mb_strtolower(trim($w), 'UTF-8');
        
        $accents = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];
        foreach ($accents as $nonAccent => $pattern) {
            $w = preg_replace("/($pattern)/iu", $nonAccent, $w);
        }

        $w = preg_replace('/[^\w]/u', '', $w);

        if (str_starts_with($w, 's') && strlen($w) > 2) {
            $w = 'x' . substr($w, 1);
        }
        $w = str_replace(['q', 'w'], 'w', $w);

        return $w;
    }

    /**
     * So khớp từ ngữ (Word similarity) có hỗ trợ sai lệch 1 ký tự và loại trừ từ dừng
     */
    protected function wordSimilarity(string $w1, string $w2): float
    {
        if ($w1 === $w2) return 1.0;

        $stopWords = [
            'hang', 'san', 'pham', 'mon', 'trai', 'qua', 'toi', 'thay', 'gan', 'het', 'roi',
            'muon', 'ban', 'nua', 'cho', 'cua', 'nay', 'them', 'tat', 'va',
            'thanh', 'sang', 'la', 'doi', 'sua', 'gia', 'mo', 'ta', 'dinh', 'duong', 'thong', 'tin', 'ngay'
        ];
        if (in_array($w1, $stopWords) || in_array($w2, $stopWords)) {
            return 0.0;
        }

        if (strlen($w1) < 3 || strlen($w2) < 3) return 0.0;

        $lev = levenshtein($w1, $w2);
        if ($lev === 1 && min(strlen($w1), strlen($w2)) >= 4) {
            return 0.85;
        }

        return 0.0;
    }

    /**
     * Trích xuất giá trị mới sau các từ khóa chỉ định
     */
    protected function extractNewValue(string $message, array $keywords, ?Product $product = null): ?string
    {
        $lower = mb_strtolower($message, 'UTF-8');
        $norm = $this->removeVietnameseAccents($lower);

        foreach ($keywords as $kw) {
            $kwNorm = $this->removeVietnameseAccents(mb_strtolower($kw, 'UTF-8'));
            $pos = mb_strpos($norm, $kwNorm);
            if ($pos !== false) {
                $extracted = mb_substr($message, $pos + mb_strlen($kwNorm));
                $clean = trim($extracted, " :\"'”’.,-\t\n\r");

                if (!empty($clean) && $product) {
                    $clean = $this->cleanExtractedValue($clean, $product);
                }

                if (!empty($clean)) {
                    return $clean;
                }
            }
        }

        if (preg_match('/["“\'](.*?)["”\']/u', $message, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * Tách câu lệnh thành 2 phần: Target Prompt (chỉ định sản phẩm) và New Value (nội dung mới)
     */
    protected function splitTargetAndNewValue(string $message, array $delimiters = ['thanh', 'la', 'thay bang', 'sang', 'thanh:', 'la:', ':']): array
    {
        $lower = mb_strtolower($message, 'UTF-8');
        $norm = $this->removeVietnameseAccents($lower);

        foreach ($delimiters as $delim) {
            $delimNorm = $this->removeVietnameseAccents(mb_strtolower($delim, 'UTF-8'));
            $pos = mb_strpos($norm, $delimNorm);
            if ($pos !== false) {
                $targetPart = trim(mb_substr($message, 0, $pos), " ,-\t\n\r");
                $newValPart = trim(mb_substr($message, $pos + mb_strlen($delimNorm)), " :\"'”’.,-\t\n\r");

                if (!empty($targetPart) && !empty($newValPart)) {
                    return [
                        'target_part' => $targetPart,
                        'new_value' => $newValPart
                    ];
                }
            }
        }

        return [
            'target_part' => $message,
            'new_value' => null
        ];
    }

    /**
     * Làm sạch giá trị trích xuất bằng cách xóa tiền tố tên quả
     */
    protected function cleanExtractedValue(string $text, Product $product): string
    {
        $clean = trim($text, " :\"'”’.,-\t\n\r");
        $pNorm = $this->removeVietnameseAccents(mb_strtolower($product->name, 'UTF-8'));
        $cleanNorm = $this->removeVietnameseAccents(mb_strtolower($clean, 'UTF-8'));

        if (str_starts_with($cleanNorm, $pNorm)) {
            $clean = trim(mb_substr($clean, mb_strlen($product->name)), " :\"'”’.,-\t\n\r");
        }

        return $clean;
    }

    /**
     * Tải và lưu ảnh từ Zalo CDN vào MinIO S3 hoặc Local storage
     */
    protected function saveImageFromUrl(string $imageUrl, ?string $oldPath = null): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'header' => "Accept: image/*\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $imageContent = @file_get_contents($imageUrl, false, $context);
            if (empty($imageContent) && function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $imageUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
                $imageContent = curl_exec($ch);
                curl_close($ch);
            }

            if (empty($imageContent)) {
                Log::error("Không thể tải dữ liệu ảnh từ URL: {$imageUrl}");
                return null;
            }

            // Đoán đuôi file ảnh từ binary mime
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $imageContent);
            finfo_close($finfo);

            $ext = match ($mime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => 'jpg',
            };

            $filename = time() . '_' . Str::random(10) . '.' . $ext;
            $storagePath = 'products/' . $filename;

            // Upload chuẩn lên MinIO S3 Storage
            $s3 = Storage::disk('s3');
            $client = $s3->getClient();
            $bucket = config('filesystems.disks.s3.bucket');

            if (!$client->doesBucketExist($bucket)) {
                $client->createBucket(['Bucket' => $bucket]);
                $policy = json_encode([
                    'Version' => '2012-10-17',
                    'Statement' => [
                        [
                            'Sid' => 'PublicRead',
                            'Effect' => 'Allow',
                            'Principal' => '*',
                            'Action' => ['s3:GetObject'],
                            'Resource' => ["arn:aws:s3:::{$bucket}/*"]
                        ]
                    ]
                ]);
                $client->putBucketPolicy([
                    'Bucket' => $bucket,
                    'Policy' => $policy,
                ]);
            }

            if ($oldPath) {
                $s3->delete($oldPath);
            }

            $s3->put($storagePath, $imageContent, 'public');
            return $storagePath;
        } catch (\Throwable $e) {
            Log::error('Lỗi khi tải và lưu ảnh từ Zalo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Trích xuất URL YouTube / YouTube Shorts từ văn bản
     */
    protected function extractYoutubeUrl(string $text): ?string
    {
        if (preg_match('/(https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?|shorts\/)|youtu\.be\/)[^\s]+)/iu', $text, $m)) {
            return trim($m[1], " :\"'”’.,-\t\n\r");
        }
        return null;
    }

    /**
     * Kiểm tra một URL có phải YouTube / YouTube Shorts không
     */
    protected function isYoutubeUrl(?string $url): bool
    {
        if (empty($url)) return false;
        return (bool) preg_match(
            '/^https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?|shorts\/)|youtu\.be\/)/i',
            $url
        );
    }

    /**
     * Xóa file khỏi MinIO S3
     */
    protected function deleteS3File(?string $path): void
    {
        if (!$path) return;
        try {
            Storage::disk('s3')->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Không thể xóa file MinIO: ' . $e->getMessage());
        }
    }

    /**
     * Tải và lưu file video từ Zalo CDN hoặc URL vào MinIO S3
     */
    protected function saveVideoFromUrl(string $videoUrl, ?string $oldVideo = null): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $videoContent = @file_get_contents($videoUrl, false, $context);
            if (empty($videoContent) && function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $videoUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
                $videoContent = curl_exec($ch);
                curl_close($ch);
            }

            if (empty($videoContent)) {
                Log::error("Không thể tải dữ liệu video từ URL: {$videoUrl}");
                return null;
            }

            $filename = time() . '_' . Str::random(10) . '.mp4';
            $storagePath = 'products/videos/' . $filename;

            $s3 = Storage::disk('s3');
            $client = $s3->getClient();
            $bucket = config('filesystems.disks.s3.bucket');

            if (!$client->doesBucketExist($bucket)) {
                $client->createBucket(['Bucket' => $bucket]);
            }

            if ($oldVideo && !$this->isYoutubeUrl($oldVideo)) {
                $this->deleteS3File($oldVideo);
            }

            $s3->put($storagePath, $videoContent, 'public');
            return $storagePath;
        } catch (\Throwable $e) {
            Log::error('Lỗi khi lưu video lên MinIO: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sinh văn bản danh sách món bán hôm nay định dạng đẹp mắt kèm icon & giá tiền
     */
    protected function buildDailyListReply(string $title, $products): string
    {
        if ($products->isEmpty()) {
            return $title . "\n(Hiện tại chưa có sản phẩm nào được bật bán trong ngày)";
        }

        $lines = [$title];
        $i = 1;
        foreach ($products as $p) {
            $icon = $this->getFruitIcon($p->code, $p->name);
            $priceText = number_format($p->price, 0, ',', '.') . 'đ/' . ($p->unit ?: 'kg');
            $lines[] = "{$i}. {$icon} {$p->name} — {$priceText}";
            $i++;
        }

        $lines[] = "\n🌐 Khách hàng vào trang chủ http://127.0.0.1:8000 là thấy ngay rồi ạ!";
        return implode("\n", $lines);
    }

    /**
     * Lấy icon trái cây tương ứng với đầy đủ các loại quả phổ biến tại Việt Nam & Nhập khẩu
     */
    protected function getFruitIcon(string $code, string $name): string
    {
        $code = strtolower($code);
        $name = mb_strtolower($name, 'UTF-8');
        $norm = $this->normalizePhonetic($name);

        // 1. Giỏ quà / Hộp quà trái cây
        if (str_contains($code, 'basket') || str_contains($code, 'gift') || str_contains($norm, 'gio qua') || str_contains($norm, 'hop qua') || str_contains($norm, 'gio trai cay')) return '🧺';

        // 2. Các quả có tên kép dễ trùng (ưu tiên quét trước)
        if (str_contains($code, 'dragon') || str_contains($norm, 'thanh long')) return '🌺';
        if (str_contains($code, 'mangosteen') || str_contains($norm, 'mang cut')) return '🟣';
        if (str_contains($code, 'blueberr') || str_contains($norm, 'viet quat')) return '🫐';
        if (str_contains($code, 'raspberr') || str_contains($norm, 'mam xoi')) return '🫐';
        if (str_contains($code, 'rambutan') || str_contains($norm, 'chom chom')) return '🍓';
        if (str_contains($code, 'pineapple') || str_contains($norm, 'thom') || str_contains($norm, 'khom') || str_contains($norm, 'dua mat') || str_contains($norm, 'dua md2')) return '🍍';
        if (str_contains($code, 'watermelon') || str_contains($norm, 'dua hau') || str_contains($norm, 'dua do')) return '🍉';
        if (str_contains($code, 'cantaloupe') || str_contains($code, 'melon') || str_contains($norm, 'dua luoi') || str_contains($norm, 'dua le') || str_contains($norm, 'dua gang') || str_contains($norm, 'hoang kim')) return '🍈';
        if (str_contains($code, 'coconut') || str_contains($norm, 'dua xiem') || str_contains($norm, 'dua sap') || str_contains($norm, 'nuoc dua')) return '🥥';
        if (str_contains($code, 'durian') || str_contains($norm, 'sau rieng') || str_contains($norm, 'ri6') || str_contains($norm, 'musang')) return '🍈';
        if (str_contains($code, 'jackfruit') || str_contains($norm, 'mit')) return '🍈';
        if (str_contains($code, 'pomelo') || str_contains($code, 'grapefruit') || str_contains($norm, 'buoi')) return '🍈';
        if (str_contains($code, 'guava') || preg_match('/\b(oi|qua oi|trai oi)\b/iu', $norm)) return '🍐';

        // 3. Quả truyền thống Việt Nam
        if (str_contains($code, 'lychee') || preg_match('/\b(vai|vai thieu)\b/iu', $norm)) return '🍓';
        if (str_contains($code, 'longan') || preg_match('/\b(nhan|nhan long|nhan xuong)\b/iu', $norm)) return '🌰';
        if (str_contains($code, 'plum') || preg_match('/\b(man|man hau|man tam hoa|man an phuoc)\b/iu', $norm)) return '🫐';
        if (str_contains($code, 'persimmon') || preg_match('/\b(hong|hong gion|hong xiem|sapoche)\b/iu', $norm)) return '🍅';
        if (str_contains($code, 'pomegranate') || preg_match('/\b(luu|qua luu|trai luu)\b/iu', $norm)) return '🍎';

        // 4. Các loại quả thông dụng khác & Nhập khẩu
        if (str_contains($code, 'straw') || str_contains($norm, 'dau')) return '🍓';
        if (str_contains($code, 'grape') || str_contains($norm, 'nho')) return '🍇';
        if (str_contains($code, 'mango') || str_contains($norm, 'xoai') || str_contains($norm, 'soai')) return '🥭';
        if (str_contains($code, 'lemon') || str_contains($norm, 'chanh')) return '🍋';
        if (str_contains($code, 'kiwi') || str_contains($norm, 'kiwi')) return '🥝';
        if (str_contains($code, 'green_apple') || str_contains($norm, 'tao xanh')) return '🍏';
        if (str_contains($code, 'apple') || str_contains($norm, 'tao')) return '🍎';
        if (str_contains($code, 'cherry') || str_contains($code, 'cherries') || str_contains($norm, 'cherry') || str_contains($norm, 'anh dao')) return '🍒';
        if (str_contains($code, 'peach') || str_contains($norm, 'dao')) return '🍑';
        if (str_contains($code, 'pear') || preg_match('/\b(le|qua le|trai le)\b/iu', $norm)) return '🍐';
        if (str_contains($code, 'banana') || str_contains($norm, 'chuoi')) return '🍌';
        if (str_contains($code, 'avocado') || preg_match('/\b(bo|bo 034|bo sap|bo booth)\b/iu', $norm)) return '🥑';
        if (str_contains($code, 'orange') || str_contains($norm, 'cam') || str_contains($norm, 'quyt') || str_contains($norm, 'tac') || str_contains($norm, 'quat')) return '🍊';

        // 5. Hạt dinh dưỡng & Sấy
        if (str_contains($code, 'nut') || str_contains($norm, 'hat') || str_contains($norm, 'say')) return '🥜';

        return '🥑';
    }



    /**
     * Xóa dấu tiếng Việt phục vụ so sánh văn bản
     */
    protected function removeVietnameseAccents(string $str): string
    {
        $accents = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];
        foreach ($accents as $nonAccent => $pattern) {
            $str = preg_replace("/($pattern)/iu", $nonAccent, $str);
        }
        return $str;
    }

    /**
     * Xử lý thêm mới một sản phẩm qua câu lệnh tự nhiên
     */
    protected function handleCreateProduct(string $rawMessage, string $senderId, ?string $imageUrl = null, ?string $videoUrl = null): array
    {
        // Chuẩn hóa dấu phẩy, chấm phẩy, xuống dòng để chia nhỏ câu thành các mệnh đề (clauses)
        $cleanMessage = preg_replace('/[;\n]+/u', ',', $rawMessage);
        $rawClauses = explode(',', $cleanMessage);
        $clauses = array_values(array_filter(array_map('trim', $rawClauses)));

        $price = null;
        $unit = 'kg';
        $pack = null;
        $origin = 'Việt Nam';
        $desc = null;
        $nutrition = null;

        $remainingClauses = [];

        foreach ($clauses as $clause) {
            // 1. Kiểm tra clause Giá bán (Price)
            if (preg_match('/(?:gi[áa]|gi[áa]\s*b[áa]n|gi[áa]\s*ti[ềe]n)?\s*(?:l[àa]|:)?\s*(\d+(?:[.,]\d+)?)\s*(k|ngh[ìi]n|ng[àa]n|000|đ|vnd)?(?:\s*\/\s*([a-zA-Z0-9\p{L}\s]+))?/iu', $clause, $pm) && !empty($pm[1])) {
                $rawNum = (float)str_replace([',', '.'], '', $pm[1]);
                $uStr = mb_strtolower($pm[2] ?? '', 'UTF-8');

                if (in_array($uStr, ['k', 'nghin', 'ngàn', 'ngan', 'nghìn'])) {
                    $calc = $rawNum * 1000;
                } elseif ($rawNum < 1000 && in_array($uStr, ['000', 'đ', 'vnd'])) {
                    $calc = $rawNum * 1000;
                } else {
                    $calc = $rawNum;
                }

                if ($calc >= 1000) {
                    $price = $calc;
                    if (!empty($pm[3])) {
                        $unit = trim($pm[3], " ,.;-:\t\n\r");
                    }
                    continue;
                }
            }

            // 2. Kiểm tra clause Xuất xứ (Origin)
            if (preg_match('/(?:xu[ấa]t\s*x[ứu]|ngu[ồo]n\s*g[ốo]c|nh[ậa]p\s*kh[ẩảa]u|nh[ậa]p\s*t[ừu]|h[àa]ng\s*nh[ậa]p\s*kh[ẩảa]u|h[àa]ng\s*nh[ậa]p|h[àa]ng|qu[êe]|origin)\s*(?:l[àa]|t[ừu]|ở|:|-)?\s*(.*)/iu', $clause, $om)) {
                $val = trim($om[1], " ,.;-:\t\n\r");
                $val = preg_replace('/^(?:l[àa]|t[ừu]|ở|t[ạa]i)\s*/iu', '', $val);
                if (!empty($val)) {
                    $origin = mb_convert_case($val, MB_CASE_TITLE, 'UTF-8');
                    continue;
                }
            }

            // 3. Kiểm tra clause Mô tả (Desc)
            if (preg_match('/(?:m[ôo]\s*t[ảa]|n[ộo]i\s*dung|chi\s*ti[ếe]t|c[âa]u\s*m[ôo]\s*t[ảa]|desc)\s*(?:l[àa]|:|-)?\s*(.*)/iu', $clause, $dm)) {
                $val = trim($dm[1], " ,.;-:\t\n\r");
                if (!empty($val)) {
                    $desc = $val;
                    continue;
                }
            }

            // 4. Kiểm tra clause Dinh dưỡng (Nutrition)
            if (preg_match('/(?:dinh\s*d[ưu][ỡo]ng|th[ôo]ng\s*tin\s*dinh\s*d[ưu][ỡo]ng|d[ưu][ỡo]ng\s*ch[ấa]t|nutrition)\s*(?:l[àa]|:|-)?\s*(.*)/iu', $clause, $nm)) {
                $val = trim($nm[1], " ,.;-:\t\n\r");
                if (!empty($val)) {
                    $nutrition = $val;
                    continue;
                }
            }

            // 5. Kiểm tra clause Đơn vị / Quy cách
            if (preg_match('/(?:đ[ơo]n\s*v[ịi]|d\/v|unit)\s*(?:l[àa]|:|-)?\s*(.*)/iu', $clause, $um)) {
                $val = trim($um[1], " ,.;-:\t\n\r");
                if (!empty($val)) {
                    $unit = $val;
                    continue;
                }
            }
            if (preg_match('/(?:quy\s*c[áa]ch|đ[óo]ng\s*g[óo]i|pack)\s*(?:l[àa]|:|-)?\s*(.*)/iu', $clause, $pkm)) {
                $val = trim($pkm[1], " ,.;-:\t\n\r");
                if (!empty($val)) {
                    $pack = $val;
                    continue;
                }
            }

            $remainingClauses[] = $clause;
        }

        $pack = $pack ?: $unit;

        // 6. Bóc tách Tên sản phẩm từ clause còn lại
        $candidateNameClause = trim(implode(' ', $remainingClauses));

        // Nếu có mệnh đề chỉ định tên: "có tên là", "tên là", "đặt tên là", "gọi là", "tên:" -> Lấy trực tiếp
        if (preg_match('/(?:c[óo]\s+)?(?:[đd][ặa]t\s+)?t[êe]n\s+(?:l[àa]|:)?\s*(.*)/iu', $candidateNameClause, $tm)) {
            $cleanName = $tm[1];
        } else {
            // Xóa các tổ hợp tiền tố: "thêm giúp tôi 1 sản phẩm mới", "tôi muốn thêm", "thêm mới sản phẩm"...
            $prefixPattern = '/^\s*(?:t[ôo]i\s+)?(?:mu[ốo]n\s+)?(?:h[aã]y\s+)?(?:th[êe]m|t[ạa]o|nh[ậa]p)?\s*(?:gi[úu]p\s+(?:t[ôo]i|m[ìi]nh|em)|cho\s+(?:t[ôo]i|m[ìi]nh|em)|h[ộo]\s+(?:t[ôo]i|m[ìi]nh|em))?\s*(?:th[êe]m|t[ạa]o|nh[ậa]p)?\s*(?:\d+|m[ộo]t|v[àa]i)?\s*(?:s[ảa]n\s*ph[ẩảaầấâ]m|m[óo]n|m[ặa]t\s*h[àa]ng|tr[áa]i\s*c[âa]y|qu[ảa]|tr[áa]i)?\s*(?:m[ớo]i)?\s*(?:l[àa]|:|-)?\s*/iu';
            $cleanName = preg_replace($prefixPattern, '', $candidateNameClause);
        }

        // Xóa từ khóa rác ở đầu
        $cleanName = preg_replace('/^\s*(?:s[ảa]n\s*ph[ẩảaầấâ]m|m[óo]n|m[ặa]t\s*h[àa]ng|tr[áa]i\s*c[âa]y|qu[ảa]|tr[áa]i|m[ớo]i|l[àa])[:\s-]*/iu', '', $cleanName);

        // Xóa các hậu tố phụ: "vào danh sách sản phẩm giúp tôi", "vào menu", "giúp tôi", "hộ tôi"...
        $noiseSuffixes = [
            '/\s+v[àa]o\s+danh\s+s[áa]ch\s+s[ảa]n\s*ph[ẩảaầấâ]m\s+gi[úu]p\s+t[ôo]i.*/iu',
            '/\s+v[àa]o\s+danh\s+s[áa]ch\s+s[ảa]n\s*ph[ẩảaầấâ]m.*/iu',
            '/\s+v[àa]o\s+danh\s+s[áa]ch.*/iu',
            '/\s+v[àa]o\s+menu.*/iu',
            '/\s+v[àa]o\s+th[ựu]c\s+[đd][ơo]n.*/iu',
            '/\s+l[êe]n\s+web(?:site)?.*/iu',
            '/\s+l[êe]n\s+trang\s+ch[ủu].*/iu',
            '/\s+gi[úu]p\s+(?:t[ôo]i|m[ìi]nh|em).*/iu',
            '/\s+h[ộo]\s+(?:t[ôo]i|m[ìi]nh|em).*/iu',
            '/\s+cho\s+(?:t[ôo]i|m[ìi]nh|em).*/iu',
            '/\s+nh[éey].*/iu',
            '/\s+nha.*/iu',
        ];
        foreach ($noiseSuffixes as $pattern) {
            $cleanName = preg_replace($pattern, '', $cleanName);
        }

        $cleanName = preg_replace('/[,;:\-\t\n\r]+/u', ' ', $cleanName);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));

        $name = mb_convert_case($cleanName, MB_CASE_TITLE, 'UTF-8');

        if (empty($name)) {
            return [
                'success' => false,
                'intent' => 'CREATE_PRODUCT_FAILED',
                'reply_message' => "⚠️ Sếp vui lòng nêu rõ tên sản phẩm cần thêm nhé!\n\nVí dụ:\n👉 \"Thêm sản phẩm Dưa hấu, giá 50k/kg, xuất xứ Sài Gòn, mô tả Dưa đỏ ngọt mát, đơn vị kg\"",
                'updated_daily_count' => Product::where('is_daily', true)->count()
            ];
        }

        if ($price === null) {
            $price = 50000;
        }

        if (empty($desc)) {
            $desc = "{$name} tươi ngon hảo hạng, chuẩn vị tự nhiên, chọn lọc kỹ lưỡng, đảm bảo vệ sinh an toàn thực phẩm.";
        }

        // 7. Xử lý Ảnh & Video nếu gửi kèm
        $savedImage = null;
        if (!empty($imageUrl)) {
            $savedImage = $this->saveImageFromUrl($imageUrl);
        }

        $videoPath = null;
        $ytUrl = $this->extractYoutubeUrl($rawMessage);
        if (!empty($ytUrl)) {
            $videoPath = $ytUrl;
        } elseif (!empty($videoUrl)) {
            $videoPath = $videoUrl;
        }

        // 8. Tạo Slug độc nhất
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'san-pham-' . Str::lower(Str::random(4));
        }
        $slug = $baseSlug;
        $counter = 1;
        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $code = Str::lower(Str::random(6));

        // 9. Lưu vào Database
        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'code' => $code,
            'price' => $price,
            'original_price' => null,
            'unit' => $unit,
            'pack' => $pack,
            'origin' => $origin,
            'desc' => $desc,
            'nutrition' => $nutrition,
            'is_daily' => true,
            'image' => $savedImage,
            'images' => null,
            'video' => $videoPath,
            'ic' => 'fi-g',
            'bg' => 'bg-g',
            'svg' => '<path d="M12 2C6.48 2 3 6 3 10c0 5.25 9 13 9 13s9-7.75 9-13c0-4-3.48-8-9-8z"/>',
            't1' => 'Mới',
            't2' => 'Nổi bật',
            'rating_stars' => 5,
            'rating_value' => 5.0,
            'rating_text' => '5.0 (Xuất sắc)',
            'reviews_count' => 10,
            'sold_count' => 20,
        ]);

        $icon = $this->getFruitIcon($product->code, $product->name);
        $priceText = number_format($product->price, 0, ',', '.') . 'đ/' . $product->unit;
        $webUrl = url("/san-pham/{$product->slug}");

        // Xóa sạch mọi cache cũ
        Cache::forget("zalo_pending_image_{$senderId}");
        Cache::forget("zalo_pending_image_target_{$senderId}");
        Cache::forget("zalo_pending_video_target_{$senderId}");
        Cache::forget("zalo_pending_clarification_{$senderId}");

        $reply = "🎉 XÁC NHẬN ĐÃ TẠO MỚI SẢN PHẨM THÀNH CÔNG:\n\n" .
                 "🎯 {$icon} {$product->name} (Mã: {$product->code})\n" .
                 "💰 Giá bán: {$priceText}\n" .
                 "📍 Xuất xứ: {$product->origin}\n" .
                 "📦 Quy cách: {$product->pack}\n" .
                 "📝 Mô tả: \"{$product->desc}\"\n" .
                 "🟢 Trạng thái: Đang mở bán trên trang chủ\n" .
                 "🌐 Xem trực tiếp: {$webUrl}\n\n" .
                 "✨ Sản phẩm đã được hiển thị ngay lập tức trên website rồi Sếp nhé!";

        return [
            'success' => true,
            'intent' => 'CREATE_PRODUCT',
            'product_id' => $product->id,
            'reply_message' => $reply,
            'updated_daily_count' => Product::where('is_daily', true)->count()
        ];
    }
}
