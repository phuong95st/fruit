<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\StockIn;
use App\Models\StockInItem;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // Dashboard
    public function dashboard()
    {
        $today = Carbon::today();
        
        // Thống kê thẻ
        $todayRevenue = Order::whereDate('created_at', $today)->where('status', '!=', 'Đã hủy')->sum('total_price');
        $newOrdersCount = Order::whereDate('created_at', $today)->count();
        $totalCustomers = Customer::count();
        
        // Tỉ lệ hoàn hàng
        $totalOrdersCount = Order::count();
        $returnedOrdersCount = Order::where('status', 'Hoàn hàng')->count();
        $returnRate = $totalOrdersCount > 0 ? round(($returnedOrdersCount / $totalOrdersCount) * 100, 1) : 0;

        // Đơn hàng gần đây
        $recentOrders = Order::latest()->take(5)->get();

        // Sản phẩm bán chạy (Top Products)
        $topProducts = Product::orderBy('sold_count', 'desc')->take(5)->get();

        // Biểu đồ doanh thu 7 ngày qua
        $revenueData = [];
        $orderData = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = 'Thứ ' . ($date->dayOfWeek === 0 ? 'CN' : ($date->dayOfWeek + 1));
            
            $dayRevenue = Order::whereDate('created_at', $date->toDateString())
                ->where('status', '!=', 'Đã hủy')
                ->sum('total_price');
            $dayOrders = Order::whereDate('created_at', $date->toDateString())->count();
            
            // Đơn vị triệu đồng để hiển thị gọn
            $revenueData[] = round($dayRevenue / 1000000, 1);
            $orderData[] = $dayOrders;
        }

        // Hoạt động gần đây
        $activities = [
            ['text' => 'Nguyễn Hương vừa đặt đơn 320.000đ', 'time' => '2 phút trước', 'dot_color' => 'var(--accent)'],
            ['text' => 'Kho hàng: Cam Navel Úc vừa được nhập thêm 100kg', 'time' => '1 giờ trước', 'dot_color' => 'var(--orange)'],
            ['text' => 'Hệ thống đã tự động gửi SMS thông báo giao hàng cho đơn #FN-08098', 'time' => '3 giờ trước', 'dot_color' => 'var(--blue)'],
            ['text' => 'Ngô Linh yêu cầu hoàn hàng cho đơn #FN-08120', 'time' => '5 giờ trước', 'dot_color' => 'var(--red)'],
            ['text' => 'Admin đã cập nhật giá bán sản phẩm Dâu tây Đà Lạt', 'time' => '1 ngày trước', 'dot_color' => 'var(--purple)'],
        ];

        return view('admin.dashboard', compact(
            'todayRevenue', 'newOrdersCount', 'totalCustomers', 'returnRate',
            'recentOrders', 'topProducts', 'labels', 'revenueData', 'orderData', 'activities'
        ));
    }

    // Phân tích
    public function analytics()
    {
        // Dữ liệu biểu đồ 31 ngày doanh thu
        $days = [];
        $revenueData = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->day;
            
            $dayRevenue = Order::whereDate('created_at', $date->toDateString())
                ->where('status', '!=', 'Đã hủy')
                ->sum('total_price');
            $revenueData[] = round($dayRevenue / 1000000, 1); // Đơn vị triệu đồng
        }

        return view('admin.analytics', compact('days', 'revenueData'));
    }

    // Danh sách đơn hàng
    public function orders(Request $request)
    {
        $query = Order::query();

        // Tìm kiếm
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('order_code', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->search . '%');
            });
        }

        // Lọc trạng thái
        if ($request->filled('status') && $request->status !== 'Tất cả trạng thái') {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10);

        return view('admin.orders.index', compact('orders'));
    }

    // Chi tiết đơn hàng
    public function orderDetail($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    // Danh sách sản phẩm
    public function products(Request $request)
    {
        $query = Product::query();

        // Tìm kiếm
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('origin', 'like', '%' . $request->search . '%');
            });
        }

        // Lọc xuất xứ
        if ($request->filled('origin') && $request->origin !== 'Tất cả xuất xứ') {
            $query->where('origin', $request->origin);
        }

        $products = $query->latest()->paginate(8);

        return view('admin.products.index', compact('products'));
    }

    // Thêm sản phẩm (Form)
    public function productCreate()
    {
        return view('admin.products.create');
    }

    // Lưu sản phẩm mới
    public function productStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'unit' => 'required|string',
            'origin' => 'required|string',
            'image' => 'nullable|image|max:4096',
        ]);

        $slug = Str::slug($request->name);
        $code = Str::lower(Str::random(6));

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            try {
                $s3 = \Illuminate\Support\Facades\Storage::disk('s3');
                $client = $s3->getClient();
                $bucket = config('filesystems.disks.s3.bucket');
                $this->ensureMinioBucket($client, $bucket);
                
                $s3->putFileAs('products', $file, $filename, 'public');
                $imagePath = 'products/' . $filename;
            } catch (\Exception $e) {
                \Log::error('MinIO upload failed: ' . $e->getMessage());
                // Fallback to local public disk if S3 fails
                $filename = $file->store('products', 'public');
                $imagePath = $filename;
            }
        }

        Product::create([
            'name' => $request->name,
            'price' => $request->price,
            'original_price' => $request->original_price,
            'unit' => $request->unit,
            'origin' => $request->origin,
            'desc' => $request->desc,
            'slug' => $slug,
            'code' => $code,
            'pack' => $request->pack ?? $request->unit,
            'image' => $imagePath,
            'ic' => 'fi-g',
            'bg' => 'bg-g',
            'svg' => '<path d="M12 2C6.48 2 3 6 3 10c0 5.25 9 13 9 13s9-7.75 9-13c0-4-3.48-8-9-8z"/>',
            't1' => 'Mới',
            't2' => 'Nổi bật',
        ]);

        return redirect()->route('admin.products')->with('success', 'Đã thêm sản phẩm thành công');
    }

    // Chi tiết sản phẩm
    public function productDetail($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.show', compact('product'));
    }

    // Bật/tắt bán trong ngày
    public function toggleDaily(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->is_daily = !$product->is_daily;
        $product->save();

        return response()->json([
            'success' => true,
            'is_daily' => $product->is_daily,
            'message' => 'Đã cập nhật trạng thái bán trong ngày thành công!'
        ]);
    }

    // Kho hàng
    public function inventory()
    {
        $products = Product::all();
        $stockIns = StockIn::latest()->take(5)->get();
        return view('admin.inventory.index', compact('products', 'stockIns'));
    }

    // Nhập kho (Form)
    public function stockIn()
    {
        $products = Product::all();
        $nextCode = 'NK-' . date('Ymd') . '-' . str_pad(StockIn::count() + 1, 3, '0', STR_PAD_LEFT);
        return view('admin.inventory.stock_in', compact('products', 'nextCode'));
    }

    // Lưu nhập kho
    public function stockInStore(Request $request)
    {
        // Lưu phiếu nhập
        $stockIn = StockIn::create([
            'stock_in_code' => $request->stock_in_code,
            'date' => $request->date ?? date('Y-m-d'),
            'supplier' => $request->supplier,
            'invoice_number' => $request->invoice_number,
            'payment_method' => $request->payment_method ?? 'Chuyển khoản',
            'notes' => $request->notes,
            'total_items' => count($request->products ?? []),
            'total_value' => $request->total_value ?? 0,
        ]);

        // Lưu chi tiết
        if ($request->has('products')) {
            foreach ($request->products as $p) {
                StockInItem::create([
                    'stock_in_id' => $stockIn->id,
                    'product_id' => $p['product_id'] ?? null,
                    'product_name' => $p['name'],
                    'quantity' => $p['quantity'],
                    'unit' => $p['unit'] ?? 'kg',
                    'price' => $p['price'],
                    'subtotal' => $p['quantity'] * $p['price'],
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    // Danh sách khách hàng
    public function customers(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $customers = $query->paginate(10);
        return view('admin.customers.index', compact('customers'));
    }

    // Chi tiết khách hàng
    public function customerDetail($id)
    {
        $customer = Customer::with('orders')->findOrFail($id);
        
        // Biểu đồ chi tiêu 6 tháng
        $spendLabels = ['Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12', 'Tháng 1'];
        $spendData = [180, 240, 195, 380, 450, 295]; // Giá trị mặc định mô phỏng biểu đồ

        return view('admin.customers.show', compact('customer', 'spendLabels', 'spendData'));
    }

    // Danh sách Voucher
    public function vouchers()
    {
        $vouchers = Voucher::latest()->get();
        return view('admin.vouchers.index', compact('vouchers'));
    }

    // Tạo Voucher mới
    public function voucherStore(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:vouchers|max:50',
            'discount_type' => 'required|string',
            'discount_value' => 'required|numeric',
        ]);

        Voucher::create([
            'code' => Str::upper($request->code),
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'min_order_value' => $request->min_order_value ?? 0,
            'quantity' => $request->quantity ?? 0,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('admin.vouchers')->with('success', 'Tạo Voucher thành công');
    }

    // Cài đặt hệ thống
    public function settings()
    {
        return view('admin.settings.index');
    }

    // Sửa sản phẩm (Form)
    public function productEdit($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.edit', compact('product'));
    }

    // Cập nhật sản phẩm
    public function productUpdate(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'unit' => 'required|string',
            'origin' => 'required|string',
            'image' => 'nullable|image|max:4096',
        ]);

        $slug = Str::slug($request->name);
        
        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            try {
                $s3 = \Illuminate\Support\Facades\Storage::disk('s3');
                $client = $s3->getClient();
                $bucket = config('filesystems.disks.s3.bucket');
                $this->ensureMinioBucket($client, $bucket);
                
                // Xóa ảnh cũ trên s3 nếu có
                if ($product->image) {
                    $s3->delete($product->image);
                }
                
                $s3->putFileAs('products', $file, $filename, 'public');
                $imagePath = 'products/' . $filename;
            } catch (\Exception $e) {
                \Log::error('MinIO update image failed: ' . $e->getMessage());
                // Fallback
                $filename = $file->store('products', 'public');
                $imagePath = $filename;
            }
        }

        $product->update([
            'name' => $request->name,
            'price' => $request->price,
            'original_price' => $request->original_price,
            'unit' => $request->unit,
            'origin' => $request->origin,
            'desc' => $request->desc,
            'slug' => $slug,
            'pack' => $request->pack ?? $request->unit,
            'image' => $imagePath,
        ]);

        return redirect()->route('admin.products.detail', $product->id)->with('success', 'Đã cập nhật sản phẩm thành công');
    }

    private function ensureMinioBucket($client, $bucket)
    {
        try {
            if (!$client->doesBucketExist($bucket)) {
                $client->createBucket([
                    'Bucket' => $bucket,
                ]);
            }
            
            $policy = json_encode([
                'Version' => '2012-10-17',
                'Statement' => [
                    [
                        'Sid' => 'PublicRead',
                        'Effect' => 'Allow',
                        'Principal' => '*',
                        'Action' => ['s3:GetObject'],
                        'Resource' => ["arn:aws:s3:::{$bucket}/*"]
                    ]
                ]
            ]);
            
            $client->putBucketPolicy([
                'Bucket' => $bucket,
                'Policy' => $policy,
            ]);
        } catch (\Exception $e) {
            \Log::error('MinIO bucket setup failed: ' . $e->getMessage());
        }
    }
}
