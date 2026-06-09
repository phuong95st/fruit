<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StaticPageController;
use App\Models\Product;

// Trang chủ
Route::get('/', [HomeController::class, 'index'])->name('home');

// Cửa hàng & sản phẩm
Route::get('/cua-hang', [ShopController::class, 'index'])->name('shop.index');
Route::get('/san-pham/{slug}', [ProductController::class, 'show'])->name('product.show');

// Giỏ hàng
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/gio-hang/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/gio-hang/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/gio-hang/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');

// Thanh toán
Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/thanh-toan', [CheckoutController::class, 'placeOrder'])->name('checkout.place');
Route::get('/dat-hang-thanh-cong', [CheckoutController::class, 'success'])->name('checkout.success');

// Trang tĩnh & phụ trợ
Route::get('/gioi-thieu', [StaticPageController::class, 'about'])->name('page.about');
Route::get('/chinh-sach', [StaticPageController::class, 'policy'])->name('page.policy');
Route::get('/lien-he', [StaticPageController::class, 'contact'])->name('page.contact');
Route::get('/dich-vu', [StaticPageController::class, 'services'])->name('page.services');
Route::get('/tai-khoan', [StaticPageController::class, 'auth'])->name('page.auth');
Route::get('/don-hang', [StaticPageController::class, 'orders'])->name('page.orders');

// Dynamic XML Sitemap for SEO & AI Search bots
Route::get('/sitemap.xml', function () {
    $now = date('Y-m-d');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    // Các trang tĩnh
    $staticPages = [
        'home' => 1.0,
        'shop.index' => 0.8,
        'page.services' => 0.7,
        'page.about' => 0.6,
        'page.policy' => 0.5,
        'page.contact' => 0.5,
    ];
    
    foreach ($staticPages as $route => $priority) {
        $xml .= '<url>';
        $xml .= '<loc>' . route($route) . '</loc>';
        $xml .= '<lastmod>' . $now . '</lastmod>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>' . $priority . '</priority>';
        $xml .= '</url>';
    }
    
    // Các sản phẩm động từ Database
    $products = Product::all();
    foreach ($products as $product) {
        $xml .= '<url>';
        $xml .= '<loc>' . route('product.show', $product->slug) . '</loc>';
        $xml .= '<lastmod>' . $product->updated_at->format('Y-m-d') . '</lastmod>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>0.9</priority>';
        $xml .= '</url>';
    }
    
    $xml .= '</urlset>';
    
    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=utf-8'
    ]);
});
