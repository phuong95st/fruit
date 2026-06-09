<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class StaticPageController extends Controller
{
    public function about()
    {
        return view('static.about');
    }

    public function policy()
    {
        return view('static.policy');
    }

    public function contact()
    {
        return view('static.contact');
    }

    public function services()
    {
        return view('services');
    }

    public function auth()
    {
        return view('auth');
    }

    public function orders()
    {
        // Lấy danh sách đơn hàng thực tế từ cơ sở dữ liệu
        $orders = Order::with('items.product')->orderBy('created_at', 'desc')->get();
        return view('orders', compact('orders'));
    }

    public function orderDetail($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        return view('order_detail', compact('order'));
    }
}
