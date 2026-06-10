<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Lưu bình luận sản phẩm
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'content' => 'required|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $product = Product::findOrFail($request->product_id);

        $data = [
            'product_id' => $product->id,
            'content' => $request->content,
            'rating' => $request->input('rating', 5),
        ];

        if (Auth::check()) {
            $user = Auth::user();
            $data['user_id'] = $user->id;
            $data['author_name'] = $user->name;
        } else {
            // Khách chưa đăng nhập bắt buộc phải có họ tên
            $request->validate([
                'author_name' => 'required|string|max:100',
            ], [
                'author_name.required' => 'Vui lòng nhập họ và tên của bạn để gửi bình luận.'
            ]);
            $data['user_id'] = null;
            $data['author_name'] = $request->author_name;
        }

        Comment::create($data);

        // Cập nhật lại số lượng đánh giá của sản phẩm để đồng bộ SEO & hiển thị
        $product->reviews_count += 1;
        
        // Tính toán lại rating trung bình
        $avgRating = Comment::where('product_id', $product->id)->avg('rating');
        if ($avgRating) {
            $product->rating_value = number_format($avgRating, 1);
            $product->rating_stars = (int)round($avgRating);
        }
        
        $product->save();

        return back()->with('success', 'Gửi bình luận đánh giá thành công!');
    }
}
