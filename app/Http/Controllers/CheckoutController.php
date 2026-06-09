<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;

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
        $orderId = 'HQST-' . date('Ymd') . '-' . rand(10000, 99999);

        // 1. Tạo hoặc lấy thông tin khách hàng dựa trên SĐT
        $customer = Customer::where('phone', $request->input('phone'))->first();
        if (!$customer) {
            $customer = Customer::create([
                'name' => $request->input('fullname'),
                'email' => $request->input('email') ?: ($request->input('phone') . '@hoaquasontay.vn'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address') . ', ' . $request->input('district') . ', ' . $request->input('city'),
                'level' => 'Thành viên',
            ]);
        }

        // Cập nhật tổng chi tiêu và số lượng đơn hàng của khách hàng
        $customer->total_spending += $total;
        $customer->total_orders += 1;
        $customer->save();

        // 2. Lưu đơn hàng vào database
        $dbOrder = Order::create([
            'order_code' => $orderId,
            'customer_id' => $customer->id,
            'customer_name' => $request->input('fullname'),
            'customer_phone' => $request->input('phone'),
            'delivery_address' => $request->input('address') . ', ' . $request->input('district') . ', ' . $request->input('city'),
            'total_price' => $total,
            'status' => 'Chờ xử lý',
            'payment_method' => $request->input('payment_method') === 'banking' ? 'Chuyển khoản' : 'COD',
            'notes' => $request->input('notes'),
        ]);

        // 3. Lưu chi tiết các mặt hàng vào database
        foreach ($cart as $id => $item) {
            OrderItem::create([
                'order_id' => $dbOrder->id,
                'product_id' => $id,
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity'],
            ]);
        }

        // Lưu thông tin đơn vừa đặt vào session để trang Success hiển thị
        session()->put('last_order', [
            'id' => $orderId,
            'db_id' => $dbOrder->id,
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
