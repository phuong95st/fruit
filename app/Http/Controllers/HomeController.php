<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // 5 sản phẩm nổi bật đầu tiên
        $featuredProducts = Product::whereIn('code', ['strawberry', 'mango', 'grape', 'orange', 'kiwi'])->get();
        
        // Giỏ quà và combo
        $baskets = Product::where('code', 'like', 'basket%')
            ->orWhereIn('code', ['strawberry', 'mango'])
            ->get();
            
        // Trái cây nhập khẩu
        $imports = Product::where('t1', 'Nhập khẩu')->get();

        return view('home', compact('featuredProducts', 'baskets', 'imports'));
    }
}
