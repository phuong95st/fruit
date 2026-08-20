<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
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
// Hệ thống Xác thực (Auth)
Route::get('/tai-khoan', [AuthController::class, 'index'])->name('page.auth');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Thông tin cá nhân (Profile)
Route::middleware('auth')->group(function () {
    Route::get('/thong-tin-ca-nhan', [AuthController::class, 'profile'])->name('page.profile');
    Route::post('/thong-tin-ca-nhan', [AuthController::class, 'updateProfile'])->name('profile.update');
});

// Quên mật khẩu
Route::get('/quen-mat-khau', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/quen-mat-khau', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::get('/dat-lai-mat-khau', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/dat-lai-mat-khau', [AuthController::class, 'resetPassword'])->name('password.update');

// Giả lập Đăng nhập Mạng xã hội (Google & Facebook)
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/facebook', [AuthController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [AuthController::class, 'handleFacebookCallback'])->name('auth.facebook.callback');

// Bình luận sản phẩm
Route::post('/binh-luan', [CommentController::class, 'store'])->name('comment.store');
Route::get('/don-hang', [StaticPageController::class, 'orders'])->name('page.orders');
Route::get('/don-hang/{id}', [StaticPageController::class, 'orderDetail'])->name('page.orders.detail');

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

use App\Http\Controllers\Admin\AdminController;

// Admin Panel Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('admin.analytics');
    
    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/orders/{id}', [AdminController::class, 'orderDetail'])->name('admin.orders.detail');
    
    Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
    Route::get('/products/create', [AdminController::class, 'productCreate'])->name('admin.products.create');
    Route::get('/products/export', [AdminController::class, 'productExport'])->name('admin.products.export');
    Route::post('/products/import', [AdminController::class, 'productImport'])->name('admin.products.import');
    Route::get('/products/import-template', [AdminController::class, 'productImportTemplate'])->name('admin.products.import-template');
    Route::post('/products', [AdminController::class, 'productStore'])->name('admin.products.store');
    Route::get('/products/{id}', [AdminController::class, 'productDetail'])->name('admin.products.detail');
    Route::get('/products/{id}/edit', [AdminController::class, 'productEdit'])->name('admin.products.edit');
    Route::post('/products/{id}/update', [AdminController::class, 'productUpdate'])->name('admin.products.update');
    Route::delete('/products/{id}', [AdminController::class, 'productDestroy'])->name('admin.products.destroy');
    Route::post('/products/{id}/toggle-daily', [AdminController::class, 'toggleDaily'])->name('admin.products.toggle-daily');
    
    Route::get('/inventory', [AdminController::class, 'inventory'])->name('admin.inventory');
    Route::get('/inventory/stock-in', [AdminController::class, 'stockIn'])->name('admin.inventory.stock-in');
    Route::post('/inventory/stock-in', [AdminController::class, 'stockInStore'])->name('admin.inventory.stock-in.store');
    
    Route::get('/customers', [AdminController::class, 'customers'])->name('admin.customers');
    Route::get('/customers/{id}', [AdminController::class, 'customerDetail'])->name('admin.customers.detail');
    
    Route::get('/vouchers', [AdminController::class, 'vouchers'])->name('admin.vouchers');
    Route::post('/vouchers', [AdminController::class, 'voucherStore'])->name('admin.vouchers.store');
    
    Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');

    // Gemini AI Price Analysis Endpoints
    Route::get('/ai-price-analysis', [AdminController::class, 'getAiPriceAnalysis'])->name('admin.ai-price-analysis');
    Route::post('/run-ai-price-analysis', [AdminController::class, 'runAiPriceAnalysis'])->name('admin.run-ai-price-analysis');
    Route::post('/apply-ai-prices', [AdminController::class, 'applyAiPrices'])->name('admin.apply-ai-prices');
});
