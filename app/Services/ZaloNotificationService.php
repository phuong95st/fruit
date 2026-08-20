<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaloNotificationService
{
    /**
     * Gửi thông báo đơn hàng mới đặt thành công về Zalo của Chủ Shop (Admin)
     *
     * @param Order $order Model đơn hàng vừa tạo
     * @param array $cartItems Danh sách sản phẩm trong giỏ hàng
     * @return bool
     */
    public static function sendOrderNotification(Order $order, array $cartItems = []): bool
    {
        try {
            $orderCode = $order->order_code ?: ('ID_' . $order->id);
            $cacheKey = "zalo_order_notified_{$orderCode}";

            // Chống gửi trùng lặp nếu đơn hàng đã được gửi thông báo trong 5 phút qua
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                Log::info("Zalo Notification: Đơn hàng #{$orderCode} đã được gửi thông báo, bỏ qua gửi lặp.");
                return true;
            }
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, 300);

            $gatewayUrl = config('services.zalo.gateway_url') ?: env('ZALO_GATEWAY_API_URL', 'http://127.0.0.1:3001');
            $secret = config('services.zalo.bot_secret') ?: env('ZALO_BOT_SECRET', 'fruitnest_secret_key_2026');

            $items = [];
            if (!empty($cartItems)) {
                foreach ($cartItems as $item) {
                    $items[] = [
                        'product_name' => $item['name'] ?? 'Sản phẩm',
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => (float)($item['price'] ?? 0),
                        'subtotal' => (float)(($item['price'] ?? 0) * ($item['quantity'] ?? 1))
                    ];
                }
            } else {
                foreach ($order->items as $item) {
                    $items[] = [
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'unit_price' => (float)$item->unit_price,
                        'subtotal' => (float)$item->subtotal
                    ];
                }
            }

            $payload = [
                'secret' => $secret,
                'order' => [
                    'order_code' => $order->order_code,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'delivery_address' => $order->delivery_address,
                    'total_price' => (float)$order->total_price,
                    'payment_method' => $order->payment_method,
                    'notes' => $order->notes,
                    'items' => $items
                ]
            ];

            // Gửi HTTP POST sang Gateway với timeout 3s an toàn
            $response = Http::timeout(3)->post("{$gatewayUrl}/api/send-order-notify", $payload);

            if ($response->successful() && $response->json('success')) {
                Log::info("Zalo Notification: Đã gửi thông báo đơn hàng #{$order->order_code} thành công về Zalo Admin.");
                return true;
            } else {
                Log::warning("Zalo Notification Gateway Reply: " . $response->body());
                return false;
            }
        } catch (\Throwable $e) {
            // Không throw exception để không làm gián đoạn trải nghiệm mua hàng
            Log::warning("Zalo Notification Error: " . $e->getMessage());
            return false;
        }
    }
}
