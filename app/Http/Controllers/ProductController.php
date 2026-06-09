<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        
        // Lấy sản phẩm liên quan (cùng phân loại t1, bỏ qua sản phẩm hiện tại, lấy tối đa 5)
        $relatedProducts = Product::where('t1', $product->t1)
            ->where('id', '!=', $product->id)
            ->take(5)
            ->get();

        // Nếu sản phẩm liên quan không đủ 5, lấy thêm các sản phẩm khác
        if ($relatedProducts->count() < 5) {
            $extraProducts = Product::where('id', '!=', $product->id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->take(5 - $relatedProducts->count())
                ->get();
            $relatedProducts = $relatedProducts->merge($extraProducts);
        }

        return view('detail', compact('product', 'relatedProducts'));
    }
}
