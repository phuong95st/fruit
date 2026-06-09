<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Hiển thị trang thanh toán
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống, không thể thanh toán.');
        }

        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $discount = 0;
        $coupon = session()->get('coupon');
        if ($coupon && $coupon['code'] === 'FRUIT10') {
            $discount = round($subtotal * 0.1);
        }

        $total = $subtotal - $discount;

        return view('checkout', compact('cart', 'subtotal', 'discount', 'total'));
    }

    /**
     * Xử lý xác nhận đặt hàng
     */
    public function placeOrder(Request $request)
    {
        // Validate dữ liệu gửi lên
        $request->validate([
            'fullname' => 'required|string|max:100',
            'phone' => 'required|string|max:15',
            'address' => 'required|string|max:255',
            'city' => 'required|string',
            'district' => 'required|string',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đã hết hạn hoặc trống.');
        }

        // Thực tế ở đây sẽ lưu Order vào Database.
        // Ở đây chúng ta giả lập lưu thông tin đơn hàng vào session để hiển thị ở trang thành công.
        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $discount = 0;
        $coupon = session()->get('coupon');
        if ($coupon && $coupon['code'] === 'FRUIT10') {
            $discount = round($subtotal * 0.1);
        }

        $total = $subtotal - $discount;
        $orderId = 'FN-' . date('Ymd') . '-' . rand(10000, 99999);

        // Lưu thông tin đơn vừa đặt vào session để trang Success hiển thị
        session()->put('last_order', [
            'id' => $orderId,
            'fullname' => $request->input('fullname'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address') . ', ' . $request->input('district') . ', ' . $request->input('city'),
            'total' => $total,
            'items' => $cart,
            'date' => date('d/m/Y H:i')
        ]);

        // Xóa giỏ hàng & mã giảm giá
        session()->forget('cart');
        session()->forget('coupon');

        return redirect()->route('checkout.success');
    }

    /**
     * Trang thông báo đặt hàng thành công
     */
    public function success()
    {
        $order = session()->get('last_order');
        if (!$order) {
            return redirect()->route('home');
        }

        return view('success', compact('order'));
    }
}
