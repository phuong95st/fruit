<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiPriceAnalyzerService;

class AnalyzeFruitPricesCommand extends Command
{
    /**
     * Tên lệnh Artisan
     */
    protected $signature = 'prices:analyze';

    /**
     * Mô tả lệnh
     */
    protected $description = 'Chạy Batch cào giá 3 đối thủ thị trường & dùng Gemini AI phân tích giá hoa quả đơn lẻ tối ưu lợi nhuận';

    /**
     * Thực thi lệnh
     */
    public function handle(GeminiPriceAnalyzerService $analyzerService)
    {
        $this->info("=========================================================");
        $this->info(" BẮT ĐẦU BATCH CÀO DATA & GEMINI AI PHÂN TÍCH GIÁ THỊ TRƯỜNG");
        $this->info(" Lịch chạy: 12:00 PM Hàng Ngày");
        $this->info("=========================================================");

        $this->output->write("Đang cào dữ liệu đối thủ & kết nối Gemini AI phân tích..");

        try {
            $result = $analyzerService->analyzeSingleProducts();

            $this->output->writeln(" HOÀN THÀNH!");
            $this->info("\n--- THỐNG KÊ KẾT QUẢ ---");
            $this->info("Thời gian phân tích : {$result['analyzed_at']}");
            $this->info("Số sản phẩm đơn lẻ  : {$result['total_single_products']} sản phẩm");
            
            $this->info("\n👉 Bạn có thể truy cập Admin Dashboard để xem báo cáo Gemini AI và duyệt áp dụng giá bán mới vào Database!");
            $this->info("=========================================================");
            return 0;
        } catch (\Throwable $e) {
            $this->error("\nLỗi khi thực thi phân tích giá Gemini AI: " . $e->getMessage());
            return 1;
        }
    }
}
