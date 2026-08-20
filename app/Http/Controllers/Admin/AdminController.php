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
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        // Lọc xuất xứ
        if ($request->filled('origin') && $request->origin !== 'Tất cả xuất xứ') {
            $query->where('origin', $request->origin);
        }

        $products = $query->latest()->paginate(8);
        
        // Lấy danh sách xuất xứ duy nhất để nạp vào dropdown bộ lọc
        $origins = Product::select('origin')->distinct()->pluck('origin')->filter()->values();

        return view('admin.products.index', compact('products', 'origins'));
    }

    // Xuất Excel (dưới dạng CSV UTF-8 BOM cho tương thích tốt) danh sách sản phẩm hiển thị theo bộ lọc
    public function productExport(Request $request)
    {
        $query = Product::query();

        // Tìm kiếm
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        // Lọc xuất xứ
        if ($request->filled('origin') && $request->origin !== 'Tất cả xuất xứ') {
            $query->where('origin', $request->origin);
        }

        $products = $query->latest()->get();

        $filename = "danh_sach_san_pham_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // Thêm UTF-8 BOM để Excel đọc được tiếng Việt
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Dòng tiêu đề
            fputcsv($file, [
                'ID',
                'Tên sản phẩm',
                'Mã sản phẩm (SKU)',
                'Phân loại',
                'Giá bán (đ)',
                'Giá gốc (đ)',
                'Đơn vị tính',
                'Xuất xứ',
                'Quy cách đóng gói',
                'Đã bán',
                'Đánh giá',
                'is_daily (Bán trong ngày)'
            ]);

            // Dòng dữ liệu
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    'FN-' . Str::upper(Str::substr($product->code, 0, 3)) . '-00' . $product->id,
                    $product->t1,
                    $product->price,
                    $product->original_price,
                    $product->unit,
                    $product->origin,
                    $product->pack,
                    $product->sold_count,
                    $product->rating_value,
                    $product->is_daily ? 'Có' : 'Không'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Tải file mẫu CSV để import sản phẩm
    public function productImportTemplate()
    {
        $filename = "mau_import_san_pham.csv";
        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'name',
                'code',
                'price',
                'original_price',
                'unit',
                'origin',
                'desc',
                'pack',
                't1',
                't2',
                'image',
                'images',
                'video'
            ]);

            // Dữ liệu mẫu 1: Dùng ZIP (đường dẫn ảnh tương đối)
            fputcsv($file, [
                'Táo Rockit New Zealand',
                'rockit-nz',
                '120000',
                '140000',
                'hộp',
                'New Zealand',
                'Táo Rockit giòn ngọt đặc trưng, đóng gói dạng ống tiện lợi.',
                'Ống 4 quả',
                'Nhập khẩu',
                'Nổi bật',
                'images/tao_rockit.jpg',
                'images/tao_rockit_1.jpg,images/tao_rockit_2.jpg',
                'videos/tao_rockit.mp4'
            ]);

            // Dữ liệu mẫu 2: Dùng URL (đường dẫn ảnh từ web)
            fputcsv($file, [
                'Cam Sành Tiền Giang',
                'cam-sanh-tg',
                '35000',
                '45000',
                'kg',
                'Việt Nam',
                'Cam sành mọng nước ngọt mát thích hợp làm nước ép.',
                'Túi 1kg',
                'Trong nước',
                'Mới',
                'https://picsum.photos/400/400?random=1',
                'https://picsum.photos/400/400?random=2,https://picsum.photos/400/400?random=3',
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Nhập sản phẩm từ file CSV hoặc ZIP chứa CSV và hình ảnh
    public function productImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,zip|max:51200',
        ]);

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());

        $zipTempFolder = null;
        $csvPath = null;

        if ($extension === 'zip') {
            // Giải nén ZIP
            $zipTempFolder = storage_path('app/temp_import_' . uniqid());
            if (!is_dir($zipTempFolder)) {
                mkdir($zipTempFolder, 0777, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) === true) {
                $zip->extractTo($zipTempFolder);
                $zip->close();
            } else {
                $this->deleteDir($zipTempFolder);
                return redirect()->back()->with('error', 'Không thể mở hoặc giải nén file ZIP.');
            }

            // Tìm file CSV trong thư mục giải nén
            $csvPath = $this->findFileRecursive($zipTempFolder, 'products.csv');
            if (!$csvPath) {
                $csvPath = $this->findCsvInDir($zipTempFolder);
            }

            if (!$csvPath) {
                $this->deleteDir($zipTempFolder);
                return redirect()->back()->with('error', 'Không tìm thấy file CSV dữ liệu trong file ZIP.');
            }
        } else {
            // File CSV trực tiếp
            $csvPath = $file->getRealPath();
        }

        // Đọc CSV
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            if ($zipTempFolder) $this->deleteDir($zipTempFolder);
            return redirect()->back()->with('error', 'Không thể mở file dữ liệu CSV.');
        }

        // Tự động phát hiện dấu phân cách (dấu phẩy , hoặc dấu chấm phẩy ;)
        $firstLine = fgets($handle);
        $delimiter = ',';
        if (str_contains($firstLine, ';')) {
            $delimiter = ';';
        }
        rewind($handle);

        // Đọc tiêu đề cột
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            if ($zipTempFolder) $this->deleteDir($zipTempFolder);
            return redirect()->back()->with('error', 'File dữ liệu CSV không có tiêu đề.');
        }

        // Làm sạch tiêu đề cột (xóa ký tự BOM UTF-8 nếu có, đổi sang chữ thường)
        $headers = array_map(function($h) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x7F-\x9F\xEF\xBB\xBF]/', '', $h)));
        }, $headers);

        $createdCount = 0;
        $updatedCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row) || count($row) < 3) continue;

            $rowData = array_combine(
                array_slice($headers, 0, count($row)),
                array_slice($row, 0, count($headers))
            );

            $name = isset($rowData['name']) ? trim($rowData['name']) : '';
            if (empty($name)) continue;

            $price = isset($rowData['price']) ? floatval(trim($rowData['price'])) : 0;
            if ($price <= 0) continue;

            $code = isset($rowData['code']) ? trim($rowData['code']) : '';
            $originalPrice = isset($rowData['original_price']) ? floatval(trim($rowData['original_price'])) : null;
            $unit = isset($rowData['unit']) ? trim($rowData['unit']) : 'kg';
            $origin = isset($rowData['origin']) ? trim($rowData['origin']) : 'Việt Nam';
            $desc = isset($rowData['desc']) ? trim($rowData['desc']) : null;
            $pack = isset($rowData['pack']) ? trim($rowData['pack']) : null;
            $t1 = isset($rowData['t1']) ? trim($rowData['t1']) : 'Mới';
            $t2 = isset($rowData['t2']) ? trim($rowData['t2']) : 'Nổi bật';

            // Xử lý tải lên ảnh chính
            $mainImagePath = null;
            if (isset($rowData['image'])) {
                $mainImagePath = $this->processImportImage(trim($rowData['image']), $zipTempFolder);
            }

            // Xử lý tải lên ảnh phụ (tối đa 5 ảnh)
            $subImagesPaths = [];
            if (isset($rowData['images']) && !empty(trim($rowData['images']))) {
                $imgList = explode(',', trim($rowData['images']));
                $imgList = array_slice($imgList, 0, 5); // Giới hạn tối đa 5 ảnh phụ
                foreach ($imgList as $imgItem) {
                    $imgItem = trim($imgItem);
                    if (!empty($imgItem)) {
                        $subPath = $this->processImportImage($imgItem, $zipTempFolder);
                        if ($subPath) {
                            $subImagesPaths[] = $subPath;
                        }
                    }
                }
            }

            // Xử lý video (file video trong ZIP hoặc link YouTube)
            $videoPath = null;
            if (isset($rowData['video'])) {
                $videoPath = $this->processImportVideo(trim($rowData['video']), $zipTempFolder);
            }

            // Dữ liệu lưu sản phẩm
            $productData = [
                'name'           => $name,
                'price'          => $price,
                'original_price' => $originalPrice ?: null,
                'unit'           => $unit,
                'origin'         => $origin,
                'desc'           => $desc,
                'pack'           => $pack ?: $unit,
                't1'             => $t1,
                't2'             => $t2,
                'slug'           => Str::slug($name),
                'ic'             => 'fi-g',
                'bg'             => 'bg-g',
                'svg'            => '<path d="M12 2C6.48 2 3 6 3 10c0 5.25 9 13 9 13s9-7.75 9-13c0-4-3.48-8-9-8z"/>',
            ];

            if ($mainImagePath) {
                $productData['image'] = $mainImagePath;
            }
            if (!empty($subImagesPaths)) {
                $productData['images'] = $subImagesPaths;
            }
            if ($videoPath) {
                $productData['video'] = $videoPath;
            }

            if (!empty($code)) {
                // Kiểm tra xem sản phẩm đã tồn tại hay chưa
                $product = Product::where('code', $code)->first();
                if ($product) {
                    $product->update($productData);
                    $updatedCount++;
                    continue;
                }
                $productData['code'] = $code;
            } else {
                $productData['code'] = Str::lower(Str::random(6));
            }

            Product::create($productData);
            $createdCount++;
        }

        fclose($handle);

        if ($zipTempFolder) {
            $this->deleteDir($zipTempFolder);
        }

        return redirect()->route('admin.products')->with(
            'success',
            "Nhập danh sách sản phẩm thành công! (Tạo mới: {$createdCount}, Cập nhật: {$updatedCount})"
        );
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
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric',
            'unit'             => 'required|string',
            'origin'           => 'required|string',
            'nutrition'        => 'nullable|string',
            'is_daily'         => 'nullable|boolean',
            'image'            => 'nullable|image|max:5120',
            'images.*'         => 'nullable|image|max:5120',
            'video'            => 'nullable|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:102400',
            'video_embed_url'  => 'nullable|url|max:500',
        ]);

        $slug = Str::slug($request->name);
        $code = Str::lower(Str::random(6));

        $imagePath   = $this->uploadFile($request->file('image'), 'products');
        $imagesPaths = $this->uploadMultipleFiles($request->file('images') ?? [], 'products', 5);

        // Video: ưu tiên file upload, sau đó mới xét URL YouTube
        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = $this->uploadFile($request->file('video'), 'products/videos', false);
        } elseif ($request->filled('video_embed_url') && $this->isYoutubeUrl($request->video_embed_url)) {
            $videoPath = $request->video_embed_url; // Lưu trực tiếp URL YouTube
        }

        Product::create([
            'name'           => $request->name,
            'price'          => $request->price,
            'original_price' => $request->original_price,
            'unit'           => $request->unit,
            'origin'         => $request->origin,
            'desc'           => $request->desc,
            'nutrition'      => $request->nutrition,
            'is_daily'       => $request->input('is_daily') == '1',
            'slug'           => $slug,
            'code'           => $code,
            'pack'           => $request->pack ?? $request->unit,
            'image'          => $imagePath,
            'images'         => $imagesPaths ?: null,
            'video'          => $videoPath,
            'ic'             => 'fi-g',
            'bg'             => 'bg-g',
            'svg'            => '<path d="M12 2C6.48 2 3 6 3 10c0 5.25 9 13 9 13s9-7.75 9-13c0-4-3.48-8-9-8z"/>',
            't1'             => 'Mới',
            't2'             => 'Nổi bật',
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
            'name'            => 'required|string|max:255',
            'price'           => 'required|numeric',
            'unit'            => 'required|string',
            'origin'          => 'required|string',
            'nutrition'       => 'nullable|string',
            'is_daily'        => 'nullable|boolean',
            'image'           => 'nullable|image|max:5120',
            'images.*'        => 'nullable|image|max:5120',
            'video'           => 'nullable|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:102400',
            'video_embed_url' => 'nullable|url|max:500',
        ]);

        $slug = Str::slug($request->name);

        // --- Ảnh chính ---
        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            $newPath = $this->uploadFile($request->file('image'), 'products', true, $product->image);
            if ($newPath) $imagePath = $newPath;
        } elseif ($request->input('remove_image')) {
            $imagePath = null;
        }

        // --- Ảnh phụ ---
        $existingImages = $request->input('existing_images', []);
        $newImages = $this->uploadMultipleFiles($request->file('images') ?? [], 'products', 5 - count($existingImages));
        $allImages = array_merge($existingImages, $newImages);
        $removedImages = array_diff($product->images ?? [], $allImages);
        foreach ($removedImages as $removedPath) {
            $this->deleteFile($removedPath);
        }

        // --- Video: ưu tiên file upload > YouTube URL > giữ nguyên ---
        $videoPath = $product->video;
        if ($request->hasFile('video')) {
            // Upload file mới, xóa video file cũ (không xóa nếu cũ là URL YouTube)
            $oldVideo = ($product->video && !$product->is_youtube) ? $product->video : null;
            $newVideoPath = $this->uploadFile($request->file('video'), 'products/videos', false, $oldVideo);
            if ($newVideoPath) $videoPath = $newVideoPath;
        } elseif ($request->filled('video_embed_url') && $this->isYoutubeUrl($request->video_embed_url)) {
            // Lưu URL YouTube, xóa file cũ nếu có
            if ($product->video && !$product->is_youtube) $this->deleteFile($product->video);
            $videoPath = $request->video_embed_url;
        } elseif ($request->input('remove_video')) {
            if ($product->video && !$product->is_youtube) $this->deleteFile($product->video);
            $videoPath = null;
        }

        $product->update([
            'name'           => $request->name,
            'price'          => $request->price,
            'original_price' => $request->original_price,
            'unit'           => $request->unit,
            'origin'         => $request->origin,
            'desc'           => $request->desc,
            'nutrition'      => $request->nutrition,
            'is_daily'       => $request->input('is_daily') == '1',
            'slug'           => $slug,
            'pack'           => $request->pack ?? $request->unit,
            'image'          => $imagePath,
            'images'         => count($allImages) > 0 ? $allImages : null,
            'video'          => $videoPath,
        ]);

        return redirect()->route('admin.products.detail', $product->id)->with('success', 'Đã cập nhật sản phẩm thành công');
    }

    // Xóa mềm sản phẩm
    public function productDestroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.products')->with('success', 'Đã xóa sản phẩm "' . $product->name . '" thành công');
    }

    /**
     * Kiểm tra một URL có phải YouTube / YouTube Shorts không.
     */
    private function isYoutubeUrl(string $url): bool
    {
        return (bool) preg_match(
            '/^https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?|shorts\/)|youtu\.be\/)/i',
            $url
        );
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

    /**
     * Upload một file lên S3 (MinIO) hoặc local disk.
     * @param  mixed   $file       UploadedFile hoặc null
     * @param  string  $folder     Thư mục lưu trữ (VD: 'products', 'products/videos')
     * @param  bool    $isImage    true = ảnh, false = video/file khác
     * @param  string|null $oldPath  Path cũ trên S3 cần xóa khi thay mới
     * @return string|null          Path lưu DB hoặc null nếu không có file
     */
    private function uploadFile($file, string $folder, bool $isImage = true, ?string $oldPath = null): ?string
    {
        if (!$file) return null;

        $ext      = $file->getClientOriginalExtension();
        $filename = time() . '_' . Str::random(10) . '.' . $ext;

        try {
            $s3     = \Illuminate\Support\Facades\Storage::disk('s3');
            $client = $s3->getClient();
            $bucket = config('filesystems.disks.s3.bucket');
            $this->ensureMinioBucket($client, $bucket);

            if ($oldPath) {
                $s3->delete($oldPath);
            }

            $s3->putFileAs($folder, $file, $filename, 'public');
            return $folder . '/' . $filename;
        } catch (\Exception $e) {
            \Log::error("Upload to MinIO [{$folder}] failed: " . $e->getMessage());
            // Fallback: local public disk
            $stored = $file->storeAs($folder, $filename, 'public');
            return $stored;
        }
    }

    /**
     * Upload nhiều file cùng lúc, giới hạn số lượng.
     * @param  array  $files    Mảng UploadedFile
     * @param  string $folder   Thư mục lưu
     * @param  int    $limit    Số file tối đa được upload
     * @return array            Mảng các path đã lưu
     */
    private function uploadMultipleFiles(array $files, string $folder, int $limit = 5): array
    {
        $paths = [];
        foreach (array_slice($files, 0, $limit) as $file) {
            if ($file && $file->isValid()) {
                $path = $this->uploadFile($file, $folder);
                if ($path) $paths[] = $path;
            }
        }
        return $paths;
    }

    /**
     * Xóa file khỏi S3 hoặc local disk.
     */
    private function deleteFile(?string $path): void
    {
        if (!$path) return;
        try {
            \Illuminate\Support\Facades\Storage::disk('s3')->delete($path);
        } catch (\Exception $e) {
            // Thử xóa từ local disk
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }
    }

    /**
     * Tải xuống hình ảnh từ URL hoặc trích xuất từ ZIP và lưu trữ lên S3/local.
     */
    private function processImportImage($imageField, $zipTempFolder = null): ?string
    {
        if (empty($imageField)) return null;

        $tempFile = null;
        $originalName = '';

        if (filter_var($imageField, FILTER_VALIDATE_URL)) {
            // Trường hợp 1: Đường dẫn URL - tải về máy chủ tạm thời
            try {
                $ctx = stream_context_create([
                    'http' => [
                        'timeout' => 15,
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                    ]
                ]);
                $content = @file_get_contents($imageField, false, $ctx);
                if ($content === false) {
                    \Log::warning("Failed to download image URL: " . $imageField);
                    return null;
                }

                $originalName = basename(parse_url($imageField, PHP_URL_PATH) ?: 'downloaded_image.jpg');
                $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg';
                if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                    $ext = 'jpg';
                }
                $originalName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $ext;

                $tempFile = tempnam(sys_get_temp_dir(), 'img_import_');
                file_put_contents($tempFile, $content);
            } catch (\Exception $e) {
                \Log::error("Error downloading image: " . $e->getMessage());
                return null;
            }
        } elseif ($zipTempFolder) {
            // Trường hợp 2: Upload file ZIP - tìm ảnh trong thư mục giải nén
            $filename = basename($imageField);
            $localPath = $this->findFileRecursive($zipTempFolder, $filename);
            if ($localPath && file_exists($localPath)) {
                $tempFile = $localPath;
                $originalName = $filename;
            }
        }

        if ($tempFile && file_exists($tempFile)) {
            $mimeType = mime_content_type($tempFile) ?: 'image/jpeg';
            // Tạo đối tượng UploadedFile tạm để tái sử dụng uploadFile()
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempFile,
                $originalName,
                $mimeType,
                null,
                true // Bật test mode để bỏ qua kiểm tra HTTP POST upload
            );

            $savedPath = $this->uploadFile($uploadedFile, 'products');

            // Xóa file tạm nếu tải về từ URL
            if (filter_var($imageField, FILTER_VALIDATE_URL)) {
                @unlink($tempFile);
            }

            return $savedPath;
        }

        return null;
    }

    /**
     * Tìm kiếm file đệ quy trong một thư mục dựa theo tên file.
     */
    private function findFileRecursive(string $dir, string $filename): ?string
    {
        if (!is_dir($dir)) return null;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $res = $this->findFileRecursive($path, $filename);
                if ($res) return $res;
            } elseif ($file === $filename) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Quét đệ quy tìm kiếm file CSV đầu tiên trong thư mục giải nén.
     */
    private function findCsvInDir(string $dir): ?string
    {
        if (!is_dir($dir)) return null;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $res = $this->findCsvInDir($path);
                if ($res) return $res;
            } elseif (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'csv') {
                return $path;
            }
        }
        return null;
    }

    /**
     * Xử lý tải lên video từ file ZIP hoặc liên kết YouTube.
     */
    private function processImportVideo($videoField, $zipTempFolder = null): ?string
    {
        if (empty($videoField)) return null;

        $videoField = trim($videoField);

        // Trường hợp 1: Nhúng link YouTube / Shorts
        if ($this->isYoutubeUrl($videoField)) {
            return $videoField;
        }

        // Trường hợp 2: Tải lên tệp video cục bộ trong file ZIP
        if ($zipTempFolder) {
            $filename = basename($videoField);
            $localPath = $this->findFileRecursive($zipTempFolder, $filename);
            if ($localPath && file_exists($localPath)) {
                $mimeType = mime_content_type($localPath) ?: 'video/mp4';
                
                $uploadedFile = new \Illuminate\Http\UploadedFile(
                    $localPath,
                    $filename,
                    $mimeType,
                    null,
                    true // test mode
                );

                // Tải lên tệp video (isImage = false)
                return $this->uploadFile($uploadedFile, 'products/videos', false);
            }
        }

        return null;
    }

    /**
     * Xóa đệ quy toàn bộ thư mục và file bên trong.
     */
    private function deleteDir(string $dirPath): void
    {
        if (!is_dir($dirPath)) return;
        $files = array_diff(scandir($dirPath), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dirPath/$file")) ? $this->deleteDir("$dirPath/$file") : @unlink("$dirPath/$file");
        }
        @rmdir($dirPath);
    }


    /**
     * Lấy báo cáo phân tích giá Gemini AI mới nhất
     */
    public function getAiPriceAnalysis(\App\Services\GeminiPriceAnalyzerService $analyzerService)
    {
        $analysis = $analyzerService->getLatestAnalysis();
        if (!$analysis) {
            return response()->json(['success' => false, 'message' => 'Chưa có dữ liệu phân tích AI ngày hôm nay.']);
        }
        return response()->json(['success' => true, 'data' => $analysis]);
    }

    /**
     * Kích hoạt chạy phân tích giá Gemini AI ngay lập tức từ Admin Dashboard
     */
    public function runAiPriceAnalysis(\App\Services\GeminiPriceAnalyzerService $analyzerService)
    {
        @set_time_limit(300);
        @ini_set("max_execution_time", "300");
        try {
            $analysis = $analyzerService->analyzeSingleProducts();
            return response()->json(['success' => true, 'message' => 'Đã chạy phân tích giá Gemini AI thành công!', 'data' => $analysis]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi chạy phân tích AI: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Áp dụng giá đề xuất (có cho phép người dùng chỉnh sửa thủ công) vào bảng products trong MySQL DB
     */
    public function applyAiPrices(Request $request)
    {
        $prices = $request->input('prices', []);

        if (empty($prices) || !is_array($prices)) {
            return response()->json(['success' => false, 'message' => 'Dữ liệu giá gửi lên không hợp lệ.'], 400);
        }

        $updatedCount = 0;

        foreach ($prices as $item) {
            $productId = $item['id'] ?? null;
            $newPrice = (float)($item['new_price'] ?? 0);

            if ($productId && $newPrice > 0) {
                $product = Product::find($productId);
                if ($product) {
                    $product->original_price = $product->price;
                    $product->price = $newPrice;
                    $product->save();
                    $updatedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã áp dụng giá mới thành công cho {$updatedCount} sản phẩm vào Database!",
            'updated_count' => $updatedCount
        ]);
    }
}
