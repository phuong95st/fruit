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

    public function orders(Request $request)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $orders = Order::with('items.product')
                ->where('customer_phone', $user->phone)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $phone = $request->input('lookup_phone');
            if (!$phone && session('last_order')) {
                $phone = session('last_order')['phone'];
            }

            if ($phone) {
                $orders = Order::with('items.product')
                    ->where('customer_phone', $phone)
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $orders = collect();
            }
        }
        return view('orders', compact('orders'));
    }

    public function orderDetail($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        return view('order_detail', compact('order'));
    }
}
