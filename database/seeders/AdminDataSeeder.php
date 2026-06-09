<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\StockIn;
use App\Models\StockInItem;
use Carbon\Carbon;

class AdminDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo khách hàng
        $customers = [
            [
                'name' => 'Nguyễn Hương',
                'email' => 'huong.nguyen@example.com',
                'phone' => '0901 234 567',
                'address' => 'Quận 3, TP.HCM',
                'dob' => '1990-06-15',
                'level' => 'VIP',
                'total_spending' => 4200000,
                'total_orders' => 18,
                'rating' => 4.9,
            ],
            [
                'name' => 'Ngô Linh',
                'email' => 'linh.ngo@example.com',
                'phone' => '0912 345 678',
                'address' => 'Quận 1, TP.HCM',
                'dob' => '1995-09-20',
                'level' => 'Thành viên',
                'total_spending' => 1250000,
                'total_orders' => 5,
                'rating' => 4.7,
            ],
            [
                'name' => 'Phạm Tuấn',
                'email' => 'tuan.pham@example.com',
                'phone' => '0987 654 321',
                'address' => 'Bình Thạnh, TP.HCM',
                'dob' => '1988-12-05',
                'level' => 'Thành viên',
                'total_spending' => 850000,
                'total_orders' => 3,
                'rating' => 5.0,
            ],
            [
                'name' => 'Trần Long',
                'email' => 'long.tran@example.com',
                'phone' => '0909 888 777',
                'address' => 'Quận 7, TP.HCM',
                'dob' => '1992-03-10',
                'level' => 'Thành viên',
                'total_spending' => 450000,
                'total_orders' => 2,
                'rating' => 4.5,
            ]
        ];

        $customerModels = [];
        foreach ($customers as $c) {
            $customerModels[$c['name']] = Customer::create($c);
        }

        // Lấy danh sách sản phẩm để liên kết đơn hàng và nhập kho
        $strawberry = Product::where('code', 'strawberry')->first();
        $mango = Product::where('code', 'mango')->first();
        $grape = Product::where('code', 'grape')->first();
        $orange = Product::where('code', 'orange')->first();
        $kiwi = Product::where('code', 'kiwi')->first();
        $basket2 = Product::where('code', 'basket2')->first();

        // 2. Tạo đơn hàng và chi tiết đơn hàng
        // Đơn 1: #FN-08142 (Chuẩn bị)
        $order1 = Order::create([
            'order_code' => 'FN-08142',
            'customer_id' => $customerModels['Nguyễn Hương']->id,
            'customer_name' => 'Nguyễn Hương',
            'customer_phone' => '0901 234 567',
            'delivery_address' => 'Quận 3, TP.HCM',
            'total_price' => 294750,
            'status' => 'Chuẩn bị',
            'payment_method' => 'ATM',
            'notes' => 'Giao giờ hành chính',
            'created_at' => Carbon::now()->subMinutes(2),
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $strawberry ? $strawberry->id : null,
            'product_name' => 'Dâu tây Đà Lạt',
            'quantity' => 1,
            'unit_price' => 85000,
            'subtotal' => 85000,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $orange ? $orange->id : null,
            'product_name' => 'Cam Navel Úc',
            'quantity' => 2,
            'unit_price' => 65000,
            'subtotal' => 130000,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $mango ? $mango->id : null,
            'product_name' => 'Xoài cát Thái',
            'quantity' => 2,
            'unit_price' => 45000,
            'subtotal' => 90000,
        ]);

        // Đơn 2: #FN-08098 (Hoàn thành)
        $order2 = Order::create([
            'order_code' => 'FN-08098',
            'customer_id' => $customerModels['Ngô Linh']->id,
            'customer_name' => 'Ngô Linh',
            'customer_phone' => '0912 345 678',
            'delivery_address' => 'Quận 1, TP.HCM',
            'total_price' => 195000,
            'status' => 'Hoàn thành',
            'payment_method' => 'COD',
            'created_at' => Carbon::now()->subDay(),
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $grape ? $grape->id : null,
            'product_name' => 'Nho đen không hạt',
            'quantity' => 1,
            'unit_price' => 120000,
            'subtotal' => 120000,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $kiwi ? $kiwi->id : null,
            'product_name' => 'Kiwi xanh NZ',
            'quantity' => 1,
            'unit_price' => 75000,
            'subtotal' => 75000,
        ]);

        // Đơn 3: #FN-07941 (Hoàn thành)
        $order3 = Order::create([
            'order_code' => 'FN-07941',
            'customer_id' => $customerModels['Phạm Tuấn']->id,
            'customer_name' => 'Phạm Tuấn',
            'customer_phone' => '0987 654 321',
            'delivery_address' => 'Bình Thạnh, TP.HCM',
            'total_price' => 450000,
            'status' => 'Hoàn thành',
            'payment_method' => 'ATM',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        OrderItem::create([
            'order_id' => $order3->id,
            'product_id' => $basket2 ? $basket2->id : null,
            'product_name' => 'Giỏ quà Cao cấp',
            'quantity' => 1,
            'unit_price' => 450000,
            'subtotal' => 450000,
        ]);

        // Đơn 4: #FN-07820 (Hoàn thành)
        $order4 = Order::create([
            'order_code' => 'FN-07820',
            'customer_id' => $customerModels['Nguyễn Hương']->id,
            'customer_name' => 'Nguyễn Hương',
            'customer_phone' => '0901 234 567',
            'delivery_address' => 'Quận 3, TP.HCM',
            'total_price' => 175000,
            'status' => 'Hoàn thành',
            'payment_method' => 'COD',
            'created_at' => Carbon::now()->subDays(9),
        ]);

        OrderItem::create([
            'order_id' => $order4->id,
            'product_id' => $orange ? $orange->id : null,
            'product_name' => 'Cam Navel Úc',
            'quantity' => 2,
            'unit_price' => 65000,
            'subtotal' => 130000,
        ]);

        OrderItem::create([
            'order_id' => $order4->id,
            'product_id' => $mango ? $mango->id : null,
            'product_name' => 'Xoài cát Thái',
            'quantity' => 1,
            'unit_price' => 45000,
            'subtotal' => 45000,
        ]);

        // 3. Tạo Vouchers
        Voucher::create([
            'code' => 'FRUIT10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_value' => 200000,
            'quantity' => 100,
            'expires_at' => Carbon::now()->addMonth(),
        ]);

        Voucher::create([
            'code' => 'WELCOME20',
            'discount_type' => 'percent',
            'discount_value' => 20,
            'min_order_value' => 0,
            'quantity' => 500,
            'expires_at' => Carbon::now()->subMonth(), // Hết hạn
        ]);

        Voucher::create([
            'code' => 'TET2025',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'min_order_value' => 500000,
            'quantity' => 200,
            'expires_at' => Carbon::now()->addDays(15),
        ]);

        Voucher::create([
            'code' => 'SUMMER25',
            'discount_type' => 'percent',
            'discount_value' => 15,
            'min_order_value' => 250000,
            'quantity' => 150,
            'expires_at' => Carbon::now()->addDays(60),
        ]);

        // 4. Tạo Phiếu nhập kho
        $stock1 = StockIn::create([
            'stock_in_code' => 'NK-20250131-001',
            'date' => '2025-01-31',
            'supplier' => 'Aus Fresh Co. (Úc)',
            'invoice_number' => 'INV-2025-0089',
            'payment_method' => 'Chuyển khoản',
            'notes' => 'Lô cam Úc nhập khẩu mùa mới',
            'total_items' => 2,
            'total_value' => 7500000,
        ]);

        StockInItem::create([
            'stock_in_id' => $stock1->id,
            'product_id' => $orange ? $orange->id : null,
            'product_name' => 'Cam Navel Úc',
            'quantity' => 100,
            'unit' => 'kg',
            'price' => 45000,
            'subtotal' => 4500000,
        ]);

        StockInItem::create([
            'stock_in_id' => $stock1->id,
            'product_id' => $strawberry ? $strawberry->id : null,
            'product_name' => 'Dâu tây Đà Lạt',
            'quantity' => 50,
            'unit' => 'hộp',
            'price' => 60000,
            'subtotal' => 3000000,
        ]);

        // Thêm một số phiếu nhập kho cũ hơn
        StockIn::create([
            'stock_in_code' => 'NK-20250125-003',
            'date' => '2025-01-25',
            'supplier' => 'Aus Fresh Co. (Úc)',
            'payment_method' => 'Chuyển khoản',
            'total_items' => 1,
            'total_value' => 13500000,
        ]);

        StockIn::create([
            'stock_in_code' => 'NK-20250118-002',
            'date' => '2025-01-18',
            'supplier' => 'NZ Kiwi Ltd. (New Zealand)',
            'payment_method' => 'Công nợ 30 ngày',
            'total_items' => 1,
            'total_value' => 6200000,
        ]);
    }
}
