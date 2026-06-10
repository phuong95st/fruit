<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Hiển thị trang giỏ hàng
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        
        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        // Tính toán mã giảm giá
        $discount = 0;
        $coupon = session()->get('coupon');
        if ($coupon && $coupon['code'] === 'FRUIT10') {
            $discount = round($subtotal * 0.1); // Giảm 10%
        }
        
        $total = $subtotal - $discount;
        
        return view('cart', compact('cart', 'subtotal', 'discount', 'total'));
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function add(Request $request)
    {
        $productId = $request->input('product_id');
        $quantity = (int)$request->input('quantity', 1);
        
        $product = Product::findOrFail($productId);
        $cart = session()->get('cart', []);
        
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float)$product->price,
                'unit' => $product->unit,
                'code' => $product->code,
                'ic' => $product->ic,
                'bg' => $product->bg,
                'svg' => $product->svg,
                'image_url' => $product->image_url,
                'quantity' => $quantity
            ];
        }
        
        session()->put('cart', $cart);
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã thêm ' . $product->name . ' vào giỏ hàng.',
                'cart_count' => $this->getCartCount()
            ]);
        }
        
        return redirect()->route('cart.index')->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function update(Request $request)
    {
        $productId = $request->input('product_id');
        $quantity = (int)$request->input('quantity');
        
        $cart = session()->get('cart', []);
        
        if (isset($cart[$productId]) && $quantity > 0) {
            $cart[$productId]['quantity'] = $quantity;
            session()->put('cart', $cart);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã cập nhật số lượng.',
                    'item_total' => number_format($cart[$productId]['price'] * $quantity, 0, ',', '.') . 'đ',
                    'cart_count' => $this->getCartCount(),
                    'totals' => $this->getCartTotals()
                ]);
            }
        }
        
        return redirect()->route('cart.index');
    }

    /**
     * Xóa sản phẩm khỏi giỏ hàng
     */
    public function remove(Request $request)
    {
        $productId = $request->input('product_id');
        $cart = session()->get('cart', []);
        
        if (isset($cart[$productId])) {
            $name = $cart[$productId]['name'];
            unset($cart[$productId]);
            session()->put('cart', $cart);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa ' . $name . ' khỏi giỏ hàng.',
                    'cart_count' => $this->getCartCount(),
                    'totals' => $this->getCartTotals()
                ]);
            }
        }
        
        return redirect()->route('cart.index')->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    /**
     * Áp dụng mã giảm giá
     */
    public function applyCoupon(Request $request)
    {
        // 1. Kiểm tra xem người dùng đã đăng nhập chưa
        if (!auth()->check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập để áp dụng mã giảm giá.'
                ]);
            }
            return redirect()->route('cart.index')->with('error', 'Vui lòng đăng nhập để áp dụng mã giảm giá.');
        }

        $code = strtoupper(trim($request->input('coupon_code')));
        
        // 2. Tìm voucher trong cơ sở dữ liệu
        $voucher = \App\Models\Voucher::where('code', $code)->first();
        if (!$voucher) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá không tồn tại.'
                ]);
            }
            return redirect()->route('cart.index')->with('error', 'Mã giảm giá không tồn tại.');
        }

        // 3. Kiểm tra hạn sử dụng
        if ($voucher->expires_at && \Carbon\Carbon::parse($voucher->expires_at)->isPast()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá này đã hết hạn.'
                ]);
            }
            return redirect()->route('cart.index')->with('error', 'Mã giảm giá này đã hết hạn.');
        }

        // 4. Kiểm tra số lượng
        if ($voucher->quantity <= 0) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá này đã hết lượt sử dụng.'
                ]);
            }
            return redirect()->route('cart.index')->with('error', 'Mã giảm giá này đã hết lượt sử dụng.');
        }

        // 5. Kiểm tra giá trị đơn hàng tối thiểu
        $cart = session()->get('cart', []);
        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        if ($subtotal < $voucher->min_order_value) {
            $msg = 'Đơn hàng tối thiểu ' . number_format($voucher->min_order_value, 0, ',', '.') . 'đ để áp dụng mã này.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg
                ]);
            }
            return redirect()->route('cart.index')->with('error', $msg);
        }

        // 6. Lưu vào session
        session()->put('coupon', [
            'code' => $voucher->code,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float)$voucher->discount_value
        ]);
        
        $discountText = $voucher->discount_type === 'percent' ? (int)$voucher->discount_value . '%' : number_format($voucher->discount_value, 0, ',', '.') . 'đ';
        $successMsg = 'Áp dụng mã giảm giá ' . $discountText . ' thành công!';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'totals' => $this->getCartTotals()
            ]);
        }
        return redirect()->route('cart.index')->with('success', $successMsg);
    }

    /**
     * Trả về tổng số lượng sản phẩm trong giỏ hàng
     */
    private function getCartCount()
    {
        $cart = session()->get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Trả về các giá trị tổng kết của giỏ hàng dạng đã định dạng
     */
    private function getCartTotals()
    {
        $cart = session()->get('cart', []);
        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $discount = 0;
        $coupon = session()->get('coupon');
        if ($coupon) {
            if ($coupon['discount_type'] === 'percent') {
                $discount = round($subtotal * ($coupon['discount_value'] / 100));
            } else {
                $discount = min($coupon['discount_value'], $subtotal);
            }
        }
        
        $total = max(0, $subtotal - $discount);
        
        return [
            'subtotal' => number_format($subtotal, 0, ',', '.') . 'đ',
            'discount' => '-' . number_format($discount, 0, ',', '.') . 'đ',
            'total' => number_format($total, 0, ',', '.') . 'đ'
        ];
    }
}
