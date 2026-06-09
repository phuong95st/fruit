<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // 1. Tìm kiếm theo tên hoặc mô tả
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('desc', 'like', "%{$search}%");
            });
        }

        // 2. Lọc theo tag chip từ thanh trượt ngang
        if ($request->filled('tag')) {
            $tag = $request->input('tag');
            if ($tag === 'trai-cay-tuoi') {
                $query->where('t1', 'Nội địa');
            } elseif ($tag === 'nhap-khau-uc-nz') {
                $query->where('origin', 'like', '%Úc%');
            } elseif ($tag === 'nhap-khau-my') {
                $query->where('origin', 'like', '%Hoa Kỳ%');
            } elseif ($tag === 'nhap-khau-thai') {
                $query->where('origin', 'like', '%Thái Lan%');
            } elseif ($tag === 'gio-qua') {
                $query->where('t1', 'Giỏ quà');
            } elseif ($tag === 'giam-gia') {
                $query->whereNotNull('original_price');
            } elseif ($tag === 'ban-chay') {
                $query->where('t2', 'like', '%Best%')->orWhere('sold_count', '>', 1000);
            }
        }

        // 3. Lọc theo danh mục ở sidebar (Mảng các danh mục chọn)
        if ($request->filled('categories')) {
            $cats = $request->input('categories'); // Array
            $query->where(function($q) use ($cats) {
                foreach ($cats as $cat) {
                    if ($cat === 'Tươi lẻ') {
                        $q->orWhere('t1', 'Nội địa');
                    } elseif ($cat === 'Nhập khẩu') {
                        $q->orWhere('t1', 'Nhập khẩu');
                    } elseif ($cat === 'Giỏ quà') {
                        $q->orWhere('t1', 'Giỏ quà');
                    }
                }
            });
        }

        // 4. Lọc theo xuất xứ ở sidebar
        if ($request->filled('origins')) {
            $origins = $request->input('origins'); // Array
            $query->where(function($q) use ($origins) {
                foreach ($origins as $origin) {
                    if ($origin === 'Việt Nam') {
                        $q->orWhere('origin', 'Việt Nam')->orWhere('origin', 'Đà Lạt')->orWhere('origin', 'Bình Thuận');
                    } elseif ($origin === 'Úc / NZ') {
                        $q->orWhere('origin', 'Úc')->orWhere('origin', 'New Zealand');
                    } else {
                        $q->orWhere('origin', 'like', "%{$origin}%");
                    }
                }
            });
        }

        // 5. Lọc theo khoảng giá
        $minPrice = $request->input('min_price', 0);
        $maxPrice = $request->input('max_price', 1000000);
        $query->whereBetween('price', [$minPrice, $maxPrice]);

        // 6. Lọc theo đánh giá
        if ($request->filled('rating')) {
            $rating = (float)$request->input('rating');
            $query->where('rating_value', '>=', $rating);
        }

        // 7. Sắp xếp
        $sort = $request->input('sort', 'popular');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'newest') {
            $query->orderBy('created_at', 'desc');
        } else {
            // Phổ biến nhất: sắp xếp theo lượt bán (sold_count)
            $query->orderBy('sold_count', 'desc');
        }

        $products = $query->paginate(12)->withQueryString();
        $totalCount = $query->count();

        return view('shop', compact('products', 'totalCount', 'minPrice', 'maxPrice'));
    }
}
