<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\PriceCrawlStorageService;
use Illuminate\Console\Command;

class ImportFruitPricesCommand extends Command
{
    /**
     * Tên lệnh Artisan CLI
     */
    protected $signature = 'prices:import 
                            {file : Đường dẫn file CSV trên MinIO S3 (ví dụ: crawls/fruit_prices_all_....csv) hoặc đường dẫn file local}';

    /**
     * Mô tả lệnh
     */
    protected $description = 'Đọc file CSV báo giá đã chỉnh sửa (từ MinIO hoặc Local) và cập nhật giá mới vào Database sản phẩm';

    /**
     * Thực thi lệnh
     */
    public function handle(PriceCrawlStorageService $storageService): int
    {
        $filePath = $this->argument('file');

        $this->info("=========================================================");
        $this->info(" ĐỒNG BỘ GIÁ MỚI TỪ FILE CSV VÀO DATABASE");
        $this->info(" File nguồn: {$filePath}");
        $this->info("=========================================================");

        try {
            $rows = $storageService->readCsvFile($filePath);
        } catch (\Throwable $e) {
            $this->error("Không thể đọc file CSV: " . $e->getMessage());
            return Command::FAILURE;
        }

        if (empty($rows)) {
            $this->warn("File CSV rỗng hoặc không đúng cấu trúc.");
            return Command::FAILURE;
        }

        $updatedCount = 0;
        $skippedCount = 0;
        $invalidPriceCount = 0;

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            $code = trim($row['db_matched_code'] ?? '');
            $newPriceRaw = trim($row['new_price'] ?? '');

            // Nếu không điền new_price hoặc không có db_matched_code
            if (empty($newPriceRaw)) {
                $skippedCount++;
                continue;
            }

            // Làm sạch số tiền giá mới
            $newPrice = (float) preg_replace('/[^\d\.]/', '', $newPriceRaw);

            if ($newPrice <= 0) {
                $invalidPriceCount++;
                continue;
            }

            // Tìm sản phẩm trong DB bằng mã code
            $product = Product::where('code', $code)->first();
            if (!$product) {
                // Thử tìm theo tên nếu mã code không có
                $name = trim($row['db_matched_name'] ?? '');
                if ($name) {
                    $product = Product::where('name', $name)->first();
                }
            }

            if ($product) {
                // Nếu giá mới khác giá hiện tại -> Cập nhật
                if ((float)$product->price !== $newPrice) {
                    $product->original_price = $product->price; // Lưu giá cũ làm giá gốc
                    $product->price = $newPrice;
                    $product->save();
                    $updatedCount++;
                }
            } else {
                $skippedCount++;
            }
        }

        $bar->finish();
        $this->line("\n");

        $this->info("=========================================================");
        $this->info(" KẾT QUẢ ĐỒNG BỘ CẬP NHẬT GIÁ");
        $this->info("=========================================================");
        $this->line("Số sản phẩm đã cập nhật giá mới: <fg=green;options=bold>{$updatedCount}</>");
        $this->line("Số sản phẩm bỏ qua (không điền new_price/không match): <fg=yellow;options=bold>{$skippedCount}</>");

        if ($invalidPriceCount > 0) {
            $this->line("Số dòng có giá mới không hợp lệ: <fg=red;options=bold>{$invalidPriceCount}</>");
        }

        $this->info("=========================================================\n");

        return Command::SUCCESS;
    }
}
