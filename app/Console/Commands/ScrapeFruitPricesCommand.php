<?php

namespace App\Console\Commands;

use App\Services\PriceCrawlStorageService;
use App\Services\ScraperService;
use Illuminate\Console\Command;

class ScrapeFruitPricesCommand extends Command
{
    /**
     * Tên lệnh Artisan CLI
     */
    protected $signature = 'prices:scrape 
                            {--site=all : Tên site cần cào (all, kleverfruits, bonnie, halafruit, delifruit, traicayngocchau)}
                            {--output= : Tên file CSV đầu ra mong muốn}';

    /**
     * Mô tả lệnh
     */
    protected $description = 'Cào TOÀN BỘ sản phẩm hoa quả từ 5 website đối thủ, lưu file CSV và tự động upload lên MinIO Storage';

    /**
     * Thực thi lệnh
     */
    public function handle(ScraperService $scraperService, PriceCrawlStorageService $storageService): int
    {
        $site = $this->option('site') ?: 'all';
        $customOutput = $this->option('output');

        $this->info("=========================================================");
        $this->info(" BẮT ĐẦU CÀO DATA GIÁ HOA QUẢ THỊ TRƯỜNG");
        $this->info(" Site chỉ định: " . strtoupper($site));
        $this->info("=========================================================");

        $this->output->write("Đang thực hiện cào dữ liệu từ các website... ");
        $scrapedData = $scraperService->scrape($site);
        $this->info("HOÀN THÀNH!");

        $totalItems = count($scrapedData);
        if ($totalItems === 0) {
            $this->warn("Không thu thập được sản phẩm nào. Vui lòng kiểm tra lại kết nối mạng hoặc cấu hình website.");
            return Command::FAILURE;
        }

        $matchedCount = count(array_filter($scrapedData, fn($item) => !empty($item['db_matched_code'])));

        $this->info("\n--- Thống kê kết quả ---");
        $this->line("Tổng số sản phẩm cào được: <fg=green;options=bold>{$totalItems}</>");
        $this->line("Số sản phẩm khớp mã với DB: <fg=yellow;options=bold>{$matchedCount}</>");

        // Đóng gói CSV & Upload MinIO
        $this->output->write("\nĐang tạo file CSV UTF-8 BOM & Upload lên MinIO Storage... ");
        $result = $storageService->exportAndUploadToMinio($scrapedData, $customOutput);
        $this->info("HOÀN THÀNH!");

        $this->info("\n=========================================================");
        $this->info(" KẾT QUẢ XUẤT FILE & MINIO STORAGE");
        $this->info("=========================================================");
        $this->line("File Name      : <comment>{$result['filename']}</comment>");
        $this->line("File Local     : <comment>{$result['local_path']}</comment>");
        $this->line("Path MinIO S3  : <comment>{$result['s3_path']}</comment>");

        if ($result['minio_uploaded']) {
            $this->info("Trạng thái MinIO: <fg=green;options=bold>Upload THÀNH CÔNG!</>");
            $this->line("MinIO Direct URL: <href={$result['minio_url']}>{$result['minio_url']}</>");
        } else {
            $this->warn("Trạng thái MinIO: Chưa upload được lên MinIO (File đã lưu sẵn ở Local đĩa).");
        }

        $this->info("\n👉 Bạn có thể tải file CSV về, điền giá mới vào cột 'new_price', sau đó chạy:");
        $this->comment("   php artisan prices:import crawls/{$result['filename']}");
        $this->info("=========================================================\n");

        return Command::SUCCESS;
    }
}
