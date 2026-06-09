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
        $code = strtoupper(trim($request->input('coupon_code')));
        
        if ($code === 'FRUIT10') {
            session()->put('coupon', [
                'code' => 'FRUIT10',
                'discount_percent' => 10
            ]);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Áp dụng mã giảm giá 10% thành công!',
                    'totals' => $this->getCartTotals()
                ]);
            }
            return redirect()->route('cart.index')->with('success', 'Áp dụng mã giảm giá thành công.');
        }
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không hợp lệ.'
            ]);
        }
        return redirect()->route('cart.index')->with('error', 'Mã giảm giá không hợp lệ.');
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
        if ($coupon && $coupon['code'] === 'FRUIT10') {
            $discount = round($subtotal * 0.1);
        }
        
        $total = $subtotal - $discount;
        
        return [
            'subtotal' => number_format($subtotal, 0, ',', '.') . 'đ',
            'discount' => '-' . number_format($discount, 0, ',', '.') . 'đ',
            'total' => number_format($total, 0, ',', '.') . 'đ'
        ];
    }
}
