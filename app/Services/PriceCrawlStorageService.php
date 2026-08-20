<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PriceCrawlStorageService
{
    /**
     * Tên S3 Disk đã được cấu hình trong Laravel cho MinIO
     */
    protected string $disk = 's3';

    /**
     * Xuất mảng dữ liệu cào thành file Markdown (.md) chuyên nghiệp và đẩy lên MinIO S3
     */
    public function exportAndUploadToMinio(array $items, ?string $customFilename = null): array
    {
        $filename = $customFilename ?: 'fruit_prices_market_comparison_' . date('Y_m_d_His') . '.md';
        $s3Path = 'crawls/' . $filename;

        // 1. Sinh nội dung Markdown (.md) đẹp mắt chuẩn định dạng AI / Reviewer đọc
        $mdContent = $this->generateMarkdownReport($items);

        // 2. Lưu đĩa tạm local (storage/app/exports/)
        Storage::disk('local')->makeDirectory('exports');
        $localPath = 'exports/' . $filename;
        Storage::disk('local')->put($localPath, $mdContent);
        $fullLocalPath = Storage::disk('local')->path($localPath);

        // 3. Đẩy (Upload) trực tiếp lên MinIO S3 Storage
        $minioUrl = null;
        $minioSuccess = false;

        try {
            $s3 = Storage::disk($this->disk);
            
            try {
                $client = $s3->getClient();
                $bucket = config('filesystems.disks.s3.bucket', 'fruit');
                if (!$client->doesBucketExist($bucket)) {
                    $client->createBucket(['Bucket' => $bucket]);
                }
            } catch (\Throwable $e) {
                Log::warning("MinIO Bucket check warning: " . $e->getMessage());
            }

            $minioSuccess = $s3->put($s3Path, $mdContent, 'public');
            if ($minioSuccess) {
                $minioUrl = $s3->url($s3Path);
                Log::info("Đã upload thành công file Markdown (.md) lên MinIO tại: {$s3Path}");
            }
        } catch (\Throwable $e) {
            Log::error("Upload file Markdown lên MinIO thất bại: " . $e->getMessage());
        }

        return [
            'filename' => $filename,
            'local_path' => $fullLocalPath,
            's3_path' => $s3Path,
            'minio_url' => $minioUrl,
            'minio_uploaded' => $minioSuccess,
            'total_rows' => count($items),
        ];
    }

    /**
     * Sinh báo cáo Markdown dạng bảng chuẩn GitHub Flavored Markdown
     */
    protected function generateMarkdownReport(array $items): string
    {
        $nowStr = date('Y-m-d H:i:s');
        $totalCount = count($items);

        $stores = [
            'Fuji Fruit' => [],
            'Tâm Fruit' => [],
            'Deli Fruit' => [],
        ];

        $matchedItems = [];

        foreach ($items as $item) {
            $site = $item['source_website'] ?? 'Fuji Fruit';
            if (!isset($stores[$site])) {
                $stores[$site] = [];
            }
            $stores[$site][] = $item;

            if (!empty($item['db_matched_code'])) {
                $matchedItems[] = $item;
            }
        }

        $md = "# 🍎 Báo Cáo Cào Giá Thị Trường Hoa Quả & Phân Tích Đề Xuất Giá Bán\n\n";
        $md .= "> **Thời gian cào dữ liệu:** `{$nowStr}`  \n";
        $md .= "> **Tổng số sản phẩm thị trường thu thập được:** `{$totalCount}` sản phẩm từ 3 cửa hàng (`Fuji Fruit`, `Tâm Fruit`, `Deli Fruit`)  \n";
        $md .= "> **Mục đích:** File Markdown này được tối ưu hóa để AI / Người quản lý Review giá đối thủ và ghi đề xuất giá bán có lợi nhuận cao nhất vào cột `Giá Đề Xuất`.\n\n";

        $md .= "---\n\n";

        // Phần 1: Bảng khớp sản phẩm trực tiếp với DB của bạn
        if (!empty($matchedItems)) {
            $md .= "## 🎯 1. Bảng Khớp Giá Trực Tiếp Với Sản Phẩm Trong Database Của Bạn\n\n";
            $md .= "| STT | Mã SP DB | Tên SP Hệ Thống | Giá Hiện Tại (VNĐ) | Cửa Hàng Đối Thủ | Tên SP Đối Thủ | Giá Đối Thủ (VNĐ) | Đơn Vị | Giá Đề Xuất Tối Ưu (Sửa ở đây) |\n";
            $md .= "| :---: | :--- | :--- | :---: | :--- | :--- | :---: | :---: | :---: |\n";

            $stt = 1;
            foreach ($matchedItems as $m) {
                $priceFormatted = number_format((float)$m['db_current_price'], 0, ',', '.');
                $crawledFormatted = number_format((float)$m['crawled_price'], 0, ',', '.');
                $md .= "| {$stt} | `{$m['db_matched_code']}` | **{$m['db_matched_name']}** | {$priceFormatted}đ | {$m['source_website']} | {$m['crawled_product_name']} | **{$crawledFormatted}đ** | {$m['crawled_unit']} | | \n";
                $stt++;
            }
            $md .= "\n---\n\n";
        }

        // Phần 2: Bảng chi tiết sản phẩm của từng cửa hàng
        $storeIcons = [
            'Fuji Fruit' => '🍇',
            'Tâm Fruit' => '🍎',
            'Deli Fruit' => '🍊',
        ];

        $sectionIdx = !empty($matchedItems) ? 2 : 1;

        foreach ($stores as $storeName => $storeItems) {
            $icon = $storeIcons[$storeName] ?? '🛍️';
            $countStore = count($storeItems);

            $md .= "## {$icon} {$sectionIdx}. Danh Sách Sản Phẩm Cửa Hàng: {$storeName} ({$countStore} sản phẩm)\n\n";

            if (empty($storeItems)) {
                $md .= "*Chưa thu thập được sản phẩm nào cho cửa hàng này.*\n\n";
            } else {
                $md .= "| STT | Tên Sản Phẩm | Giá Cào Được (VNĐ) | Giá Gốc (VNĐ) | Đơn Vị | Mô Tả Sản Phẩm | Link Chi Tiết | Giá Đề Xuất (Sửa ở đây) |\n";
                $md .= "| :---: | :--- | :---: | :---: | :---: | :--- | :--- | :---: |\n";

                $stt = 1;
                foreach ($storeItems as $item) {
                    $priceStr = number_format((float)($item['crawled_price'] ?? 0), 0, ',', '.') . 'đ';
                    $origPriceStr = $item['crawled_original_price'] ? number_format((float)$item['crawled_original_price'], 0, ',', '.') . 'đ' : '-';
                    $cleanDesc = trim(preg_replace('/\s+/', ' ', $item['crawled_description'] ?? ''));
                    if (mb_strlen($cleanDesc) > 80) {
                        $cleanDesc = mb_substr($cleanDesc, 0, 80) . '...';
                    }
                    $cleanDesc = str_replace('|', '/', $cleanDesc);
                    $urlLink = !empty($item['crawled_url']) ? "[Xem Chi Tiết]({$item['crawled_url']})" : '-';

                    $md .= "| {$stt} | **{$item['crawled_product_name']}** | **{$priceStr}** | {$origPriceStr} | {$item['crawled_unit']} | {$cleanDesc} | {$urlLink} | | \n";
                    $stt++;
                }
            }

            $md .= "\n---\n\n";
            $sectionIdx++;
        }

        return $md;
    }

    /**
     * Đọc nội dung file Markdown/CSV từ đĩa local hoặc MinIO
     */
    public function readCsvFile(string $filePathOrS3Path): array
    {
        $content = null;

        if (Storage::disk($this->disk)->exists($filePathOrS3Path)) {
            $content = Storage::disk($this->disk)->get($filePathOrS3Path);
        } elseif (Storage::disk('local')->exists($filePathOrS3Path)) {
            $content = Storage::disk('local')->get($filePathOrS3Path);
        } elseif (file_exists($filePathOrS3Path)) {
            $content = file_get_contents($filePathOrS3Path);
        }

        if (!$content) {
            throw new \Exception("Không tìm thấy file tại: {$filePathOrS3Path}");
        }

        return [];
    }
}
