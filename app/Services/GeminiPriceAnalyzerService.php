<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiPriceAnalyzerService
{
    protected ScraperService $scraperService;

    public function __construct(ScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    /**
     * Chạy phân tích giá Gemini AI cho các sản phẩm hoa quả đơn lẻ
     */
    public function analyzeSingleProducts(): array
    {
        @set_time_limit(300);
        @ini_set("max_execution_time", "300");
        // 1. Cào dữ liệu từ 3 đối thủ (Fuji Fruit, Tâm Fruit, Deli Fruit)
        $scrapedItems = $this->scraperService->scrape('all');

        // 2. Lấy tất cả sản phẩm trong Database
        $dbProducts = Product::all();

        // 3. Lọc chỉ lấy các sản phẩm đơn lẻ (bỏ qua giỏ quà, hộp quà, set quà)
        $singleDbProducts = $dbProducts->filter(function ($p) {
            return $this->isSingleProduct($p->name);
        });

        // 4. Nhóm dữ liệu đối thủ theo sản phẩm DB
        $analysisPayload = [];

        foreach ($singleDbProducts as $product) {
            $matchedCompetitors = [];
            $prices = [];

            foreach ($scrapedItems as $item) {
                if (!$this->isSingleProduct($item['crawled_product_name'])) {
                    continue;
                }

                if ($item['db_matched_code'] === $product->code || $this->isSimilarName($product->name, $item['crawled_product_name'])) {
                    $matchedCompetitors[] = [
                        'store' => $item['source_website'],
                        'name' => $item['crawled_product_name'],
                        'price' => (float)$item['crawled_price'],
                        'url' => $item['crawled_url'] ?? '',
                    ];
                    if ($item['crawled_price'] > 0) {
                        $prices[] = (float)$item['crawled_price'];
                    }
                }
            }

            $minPrice = !empty($prices) ? min($prices) : 0;
            $maxPrice = !empty($prices) ? max($prices) : 0;
            $avgPrice = !empty($prices) ? array_sum($prices) / count($prices) : 0;

            $analysisPayload[] = [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'current_price' => (float)$product->price,
                'unit' => $product->unit ?? 'Kg',
                'competitor_min_price' => $minPrice,
                'competitor_max_price' => $maxPrice,
                'competitor_avg_price' => $avgPrice,
                'competitors' => $matchedCompetitors,
            ];
        }

        // 5. Gọi Gemini AI API để phân tích giá tối ưu lợi nhuận
        $aiRecommendations = $this->callGeminiApi($analysisPayload);

        // 6. Tổng hợp kết quả phân tích cuối cùng
        $result = [
            'analyzed_at' => date('Y-m-d H:i:s'),
            'total_single_products' => count($analysisPayload),
            'items' => $aiRecommendations,
        ];

        // 7. Lưu trữ kết quả phân tích theo ngày
        $this->saveAnalysisResult($result);

        return $result;
    }

    /**
     * Gọi Gemini AI API với Prompt Chuyên Gia Chiến Lược Giá Bán Lẻ Hoa Quả
     */
    protected function callGeminiApi(array $payload): array
    {
        $apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-1.5-flash'));

        if (empty($apiKey)) {
            Log::warning("Chưa cấu hình GEMINI_API_KEY trong .env. Đang sử dụng thuật toán phân tích thị trường dự phòng.");
            return $this->fallbackSmartAnalysis($payload);
        }

        $prompt = "Bạn là Chuyên gia Chiến lược Định giá Bán lẻ Hoa quả Nhập khẩu & Nông sản tại Việt Nam.\n";
        $prompt .= "Hãy phân tích danh sách giá sản phẩm hoa quả đơn lẻ hiện tại của cửa hàng chúng tôi so với các đối thủ thị trường (Fuji Fruit, Tâm Fruit, Deli Fruit) để đề xuất mức giá bán mới tối ưu hóa lợi nhuận tốt nhất nhưng vẫn giữ tính cạnh tranh.\n\n";
        $prompt .= "Yêu cầu định giá:\n";
        $prompt .= "1. 'suggested_price': Giá đề xuất mới (VNĐ), tròn số tới hàng nghìn (ví dụ 89000, 150000, 299000).\n";
        $prompt .= "2. 'reasoning': Lý do phân tích ngắn gọn 1-2 câu giải thích vì sao chọn mức giá này để tối ưu lợi nhuận và thu hút khách.\n";
        $prompt .= "3. Trả về ĐÚNG định dạng JSON array với cấu trúc các trường sau:\n";
        $prompt .= "[\n";
        $prompt .= "  {\n";
        $prompt .= "    \"id\": 1,\n";
        $prompt .= "    \"code\": \"apple\",\n";
        $prompt .= "    \"name\": \"Táo Fuji Mỹ\",\n";
        $prompt .= "    \"current_price\": 90000,\n";
        $prompt .= "    \"suggested_price\": 89000,\n";
        $prompt .= "    \"reasoning\": \"Giá đối thủ cạnh tranh dao động từ 89k-120k. Giảm xuống 89k để chiếm ưu thế giá rẻ nhất thị trường mà vẫn đảm bảo lợi nhuận.\"\n";
        $prompt .= "  }\n";
        $prompt .= "]\n\n";
        $prompt .= "Dữ liệu sản phẩm cần phân tích:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        try {
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->withoutVerifying()
                ->timeout(30)
                ->post($apiUrl, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'topP' => 0.8,
                    ]
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $aiText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Bóc tách JSON từ văn bản phản hồi của Gemini
                if (preg_match('/\[\s*\{.*\}\s*\]/s', $aiText, $match)) {
                    $parsedAi = json_decode($match[0], true);
                    if (is_array($parsedAi)) {
                        return $this->mergeAiWithPayload($payload, $parsedAi);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("Lỗi khi kết nối Gemini AI API: " . $e->getMessage());
        }

        return $this->fallbackSmartAnalysis($payload);
    }

    /**
     * Ghép dữ liệu phân tích của Gemini AI với thông tin đối thủ
     */
    protected function mergeAiWithPayload(array $payload, array $aiParsed): array
    {
        $aiMap = [];
        foreach ($aiParsed as $item) {
            if (isset($item['id'])) {
                $aiMap[$item['id']] = $item;
            }
        }

        $results = [];
        foreach ($payload as $item) {
            $aiInfo = $aiMap[$item['id']] ?? null;

            $suggested = $aiInfo['suggested_price'] ?? null;
            if (!$suggested || $suggested <= 0) {
                $suggested = $item['competitor_min_price'] > 0 ? $item['competitor_min_price'] : $item['current_price'];
            }

            $reasoning = $aiInfo['reasoning'] ?? null;
            if (!$reasoning) {
                $reasoning = "Gemini AI khuyến nghị điều chỉnh giá dựa trên biến động giá đối thủ thị trường.";
            }

            $item['suggested_price'] = (float)$suggested;
            $item['reasoning'] = $reasoning;
            $results[] = $item;
        }

        return $results;
    }

    /**
     * Thuật toán phân tích dự phòng khi chưa cấu hình Gemini API Key
     */
    protected function fallbackSmartAnalysis(array $payload): array
    {
        $results = [];
        foreach ($payload as $item) {
            $min = $item['competitor_min_price'];
            $max = $item['competitor_max_price'];
            $current = $item['current_price'];

            if ($min > 0) {
                if ($current > $max) {
                    $suggested = round(($min + $max) / 2 / 1000) * 1000;
                    $reasoning = "Giá hiện tại của bạn (" . number_format($current) . "đ) đang cao hơn thị trường (" . number_format($min) . "đ - " . number_format($max) . "đ). Khuyến nghị điều chỉnh về " . number_format($suggested) . "đ để tăng tốc độ bán hàng và giữ biên lợi nhuận tốt.";
                } elseif ($current < $min) {
                    $suggested = round($min * 0.98 / 1000) * 1000;
                    $reasoning = "Giá hiện tại của bạn (" . number_format($current) . "đ) thấp hơn đối thủ (" . number_format($min) . "đ). Khuyến nghị tăng nhẹ lên " . number_format($suggested) . "đ để tối ưu lợi nhuận mà vẫn có giá tốt nhất thị trường.";
                } else {
                    $suggested = round($min * 0.99 / 1000) * 1000;
                    $reasoning = "Giá của bạn (" . number_format($current) . "đ) đang nằm trong khoảng thị trường. Đề xuất đặt " . number_format($suggested) . "đ để vừa cạnh tranh vừa tối ưu biên lợi nhuận.";
                }
            } else {
                $suggested = $current;
                $reasoning = "Chưa ghi nhận giá đối thủ cho sản phẩm này. Giữ nguyên giá bán hiện tại để đảm bảo lợi nhuận.";
            }

            $item['suggested_price'] = (float)$suggested;
            $item['reasoning'] = $reasoning;
            $results[] = $item;
        }

        return $results;
    }

    /**
     * Kiểm tra xem sản phẩm có phải là hoa quả đơn lẻ không (lọc bỏ giỏ quà, hộp quà, set quà)
     */
    protected function isSingleProduct(string $name): bool
    {
        $lower = mb_strtolower($name, 'UTF-8');

        $excludedKeywords = [
            'giỏ', 'lẵng', 'hộp quà', 'set quà', 'bó hoa', 'mâm lễ', 'combo', 'quà tặng',
            'danh mục', 'giỏ hàng', 'kính viếng', 'thắp hương', 'chia buồn', 'khai trương'
        ];

        foreach ($excludedKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return false;
            }
        }

        return true;
    }

    /**
     * So sánh xem 2 tên sản phẩm có cùng loại trái cây không
     */
    protected function isSimilarName(string $name1, string $name2): bool
    {
        $clean1 = $this->normalizeName($name1);
        $clean2 = $this->normalizeName($name2);

        return str_contains($clean1, $clean2) || str_contains($clean2, $clean1);
    }

    /**
     * Chuẩn hóa tên sản phẩm
     */
    protected function normalizeName(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
        $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
        $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
        $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
        $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
        $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
        $str = preg_replace('/(đ)/', 'd', $str);
        $str = preg_replace('/[^a-z0-9\s]/', '', $str);
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    /**
     * Lưu kết quả phân tích theo ngày
     */
    protected function saveAnalysisResult(array $result): void
    {
        try {
            $dir = storage_path('app/ai_price_analyses');
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $dateFilename = 'analysis_' . date('Y_m_d') . '.json';
            file_put_contents($dir . '/' . $dateFilename, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            file_put_contents($dir . '/latest.json', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::error("Không thể lưu file báo cáo Gemini AI: " . $e->getMessage());
        }
    }

    /**
     * Lấy báo cáo phân tích mới nhất
     */
    public function getLatestAnalysis(): ?array
    {
        $latestPath = storage_path('app/ai_price_analyses/latest.json');
        if (file_exists($latestPath)) {
            $json = json_decode(file_get_contents($latestPath), true);
            if (is_array($json)) {
                return $json;
            }
        }
        return null;
    }
}
