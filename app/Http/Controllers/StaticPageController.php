<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        // Ở đây hiển thị trang giả lập danh sách đơn hàng đã đặt
        return view('orders');
    }
}
