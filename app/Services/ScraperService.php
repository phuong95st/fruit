<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService
{
    /**
     * User-Agent giả lập trình duyệt để tránh bị chặn 403
     */
    protected string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

    /**
     * Cào dữ liệu từ 3 website chỉ định: Fuji Fruit, Tâm Fruit, Deli Fruit
     */
    public function scrape(string $targetSite = 'all'): array
    {
        $sites = [
            'fujifruit' => [
                'name' => 'Fuji Fruit',
                'handler' => 'scrapeFujiFruit',
            ],
            'tamfruit' => [
                'name' => 'Tâm Fruit',
                'handler' => 'scrapeTamFruit',
            ],
            'delifruit' => [
                'name' => 'Deli Fruit',
                'handler' => 'scrapeDeliFruit',
            ],
        ];

        // Lấy tất cả sản phẩm hiện có trong DB để làm tham chiếu so sánh
        $dbProducts = Product::all();

        $allResults = [];

        foreach ($sites as $key => $siteInfo) {
            if ($targetSite !== 'all' && $targetSite !== $key) {
                continue;
            }

            Log::info("Đang cào dữ liệu từ cửa hàng: {$siteInfo['name']}");
            
            try {
                $method = $siteInfo['handler'];
                $scrapedItems = $this->$method();

                foreach ($scrapedItems as $item) {
                    $matched = $this->findMatchingDbProduct($item['name'], $dbProducts);

                    $allResults[] = [
                        'source_website' => $siteInfo['name'],
                        'crawled_product_name' => $item['name'],
                        'crawled_price' => $item['price'],
                        'crawled_original_price' => $item['original_price'] ?? null,
                        'crawled_unit' => $item['unit'] ?? 'Kg',
                        'crawled_description' => $item['desc'] ?? '',
                        'crawled_url' => $item['url'] ?? '',
                        'crawled_image_url' => $item['image'] ?? null,
                        'db_matched_code' => $matched ? $matched->code : '',
                        'db_matched_name' => $matched ? $matched->name : '',
                        'db_current_price' => $matched ? $matched->price : '',
                        'new_price' => '', // Để trống cho AI/Reviewer đề xuất giá bán tốt nhất
                    ];
                }
            } catch (\Throwable $e) {
                Log::error("Lỗi khi cào dữ liệu từ site {$siteInfo['name']}: " . $e->getMessage());
            }
        }

        return $allResults;
    }

    /**
     * Gửi HTTP GET request với User-Agent chuẩn
     */
    protected function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
            ])
            ->withoutVerifying()
            ->timeout(15)
            ->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            Log::warning("Fetch HTML thất bại cho URL {$url}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Cào Fuji Fruit (fujifruit.com.vn) qua các danh mục & phân trang WooCommerce
     */
    protected function scrapeFujiFruit(): array
    {
        $allItems = [];
        $baseUrl = 'https://fujifruit.com.vn';
        $categories = config('services.scraper.fujifruit', [
            "{$baseUrl}/danh-muc/hoa-qua-nhap-khau/",
            "{$baseUrl}/danh-muc/gio-hoa-qua-nhap-khau/",
            "{$baseUrl}/danh-muc/hoa-qua-viet-nam/",
        ]);

        foreach ($categories as $catUrl) {
            for ($page = 1; $page <= 10; $page++) {
                $pageUrl = $page === 1 ? $catUrl : "{$catUrl}page/{$page}/";
                $html = $this->fetchHtml($pageUrl);
                if (!$html) break;

                $crawler = new Crawler($html);
                $items = $this->parseFujiPage($crawler, $pageUrl);

                if (empty($items)) break;
                $allItems = array_merge($allItems, $items);
            }
        }

        return $this->uniqueByName($allItems);
    }

    /**
     * Parser cho trang Fuji Fruit
     */
    protected function parseFujiPage(Crawler $crawler, string $baseUrl): array
    {
        $items = [];

        $crawler->filter('.product, li.product, .product-small, .product-item')->each(function (Crawler $node) use (&$items, $baseUrl) {
            try {
                $titleNode = $node->filter('.name, .product-title, .title, h3, h2, a');
                $priceNode = $node->filter('.price, .amount, span.price');

                if ($titleNode->count() > 0 && $priceNode->count() > 0) {
                    $nameRaw = trim($titleNode->first()->text());
                    $priceRaw = trim($priceNode->first()->text());

                    $name = trim(preg_replace('/(\d[\d\.\,]*\s*VND|\d[\d\.\,]*\s*đ)/iu', '', $nameRaw));
                    $price = $this->parsePrice($priceRaw);

                    if ($price > 0 && strlen($name) > 3 && !in_array($name, ['Xem chi tiết', 'Bộ lọc tìm kiếm'])) {
                        $linkNode = $node->filter('a');
                        $link = $linkNode->count() > 0 ? $this->makeAbsoluteUrl($linkNode->first()->attr('href'), $baseUrl) : $baseUrl;

                        $imgNode = $node->filter('img');
                        $img = $imgNode->count() > 0 ? $this->makeAbsoluteUrl($imgNode->first()->attr('src') ?? $imgNode->first()->attr('data-src'), $baseUrl) : null;

                        $items[] = [
                            'name' => $name,
                            'price' => $price,
                            'original_price' => null,
                            'unit' => $this->detectUnit($name),
                            'desc' => "Trái cây tươi nhập khẩu Fuji Fruit: {$name}",
                            'url' => $link,
                            'image' => $img,
                        ];
                    }
                }
            } catch (\Throwable $e) {}
        });

        return $items;
    }

    /**
     * Cào Tâm Fruit (tamfruit.vn) qua quét trang chi tiết sản phẩm chuẩn 100%
     */
    protected function scrapeTamFruit(): array
    {
        $allItems = [];
        $baseUrl = 'https://tamfruit.vn';
        $categoryUrls = config('services.scraper.tamfruit', [
            "{$baseUrl}/trai-cay-nhap-khau/",
            "{$baseUrl}/khay-set-hoa-qua/",
            "{$baseUrl}/combo-trai-cay-2-nguoi/",
            "{$baseUrl}/combo-trai-cay-3-nguoi/",
            "{$baseUrl}/combo-trai-cay-5-nguoi/",
            "{$baseUrl}/",
        ]);

        $productLinks = [];

        foreach ($categoryUrls as $catUrl) {
            $html = $this->fetchHtml($catUrl);
            if (!$html) continue;

            $crawler = new Crawler($html);
            $crawler->filter('a')->each(function (Crawler $node) use (&$productLinks) {
                $href = $node->attr('href');
                $text = trim($node->text());

                if ($href && preg_match('/tamfruit\.vn\/([\w\-]+)\/$/', $href, $m) && !empty($text) && strlen($text) > 4) {
                    $slug = $m[1];
                    $ignoredSlugs = ['trai-cay-nhap-khau', 'khay-set-hoa-qua', 'combo-trai-cay-2-nguoi', 'combo-trai-cay-3-nguoi', 'gioi-thieu', 'lien-he', 'tin-tuc', 'cua-hang', 'thap-huong-le-chua', 'gio-qua-tet', 'dam-ngo', 'dam-tang', 'dam-gio', 'phu-nu-viet-nam-20-10', 'nha-giao-viet-nam-20-11', 'mung-khai-truong', 'sinh-nhat', 'quoc-te-phu-nu-08-03'];

                    if (!in_array($slug, $ignoredSlugs) && !in_array($text, ['Chọn mua', 'Xem chi tiết', '0 Giỏ hàng', 'QUÀ TẶNG TRÁI CÂY'])) {
                        $productLinks[$href] = $text;
                    }
                }
            });
        }

        // Lấy dữ liệu chi tiết (giá thực, giá gốc, mô tả) của từng sản phẩm
        $count = 0;
        foreach ($productLinks as $url => $fallbackTitle) {
            if ($count >= 60) break; // Giới hạn quét chi tiết để chạy nhanh

            $detailHtml = $this->fetchHtml($url);
            if (!$detailHtml) continue;

            $dCrawler = new Crawler($detailHtml);
            
            $titleNode = $dCrawler->filter('h1.product_title, h1.entry-title, h1');
            $title = $titleNode->count() > 0 ? trim($titleNode->first()->text()) : $fallbackTitle;

            $metaPriceNode = $dCrawler->filter('meta[property="og:price:amount"], meta[property="product:price:amount"]');
            $price = $metaPriceNode->count() > 0 ? floatval($metaPriceNode->first()->attr('content')) : 0;

            if ($price <= 0) {
                $priceNode = $dCrawler->filter('.price ins .amount, p.price .amount, span.price .amount');
                if ($priceNode->count() > 0) {
                    $price = $this->parsePrice($priceNode->first()->text());
                }
            }

            $oldPriceNode = $dCrawler->filter('.price del .amount');
            $oldPrice = $oldPriceNode->count() > 0 ? $this->parsePrice($oldPriceNode->first()->text()) : null;

            $descNode = $dCrawler->filter('.woocommerce-product-details__short-description, meta[property="og:description"]');
            $desc = '';
            if ($descNode->count() > 0) {
                $desc = $descNode->first()->nodeName() === 'meta' ? $descNode->first()->attr('content') : $descNode->first()->text();
            }

            if ($price > 0) {
                $allItems[] = [
                    'name' => $title,
                    'price' => $price,
                    'original_price' => $oldPrice > $price ? $oldPrice : null,
                    'unit' => $this->detectUnit($title),
                    'desc' => trim($desc) ?: "Set/Giỏ trái cây cao cấp Tâm Fruit: {$title}",
                    'url' => $url,
                    'image' => null,
                ];
                $count++;
            }
        }

        return $this->uniqueByName($allItems);
    }

    /**
     * Cào Deli Fruit (delifruit.vn) qua quét trang chi tiết chuẩn 100%
     */
    protected function scrapeDeliFruit(): array
    {
        $allItems = [];
        $baseUrl = 'https://delifruit.vn';
        $categoryUrls = config('services.scraper.delifruit', [
            "{$baseUrl}/trai-cay-nhap-khau",
            "{$baseUrl}/tao",
            "{$baseUrl}/cherry",
            "{$baseUrl}/viet-quat",
            "{$baseUrl}/kiwi",
            "{$baseUrl}/quyt",
            "{$baseUrl}/dau-tay",
            "{$baseUrl}/dua-luoi",
            "{$baseUrl}/gio-trai-cay",
            "{$baseUrl}/bo-hoa-trai-cay",
            "{$baseUrl}/hop-trai-cay",
        ]);

        $productLinks = [];

        foreach ($categoryUrls as $catUrl) {
            $html = $this->fetchHtml($catUrl);
            if (!$html) continue;

            $crawler = new Crawler($html);
            $crawler->filter('a')->each(function (Crawler $node) use (&$productLinks) {
                $href = $node->attr('href');
                $text = trim($node->text());

                if ($href && str_starts_with($href, 'https://delifruit.vn/') && !empty($text) && strlen($text) > 4) {
                    $slug = str_replace('https://delifruit.vn/', '', rtrim($href, '/'));
                    $navSlugs = ['tin', 'tu-van', 'khach-hang', 'gioi-thieu', 'lien-he', 'bao-chi-noi-ve-delifruit', 'phuong-thuc-thanh-toan', 'chinh-sach-bao-hanh', 'chinh-sach-bao-mat', 'chinh-sach-su-dung', 'trai-cay-nhap-khau', 'gio-trai-cay', 'bo-hoa-trai-cay', 'hop-trai-cay', 'trai-cay-cat-san', 'cherry', 'sau-rieng', 'dau-tay', 'dua-luoi', 'hong', 'trai-cay-khac', 'san-pham-khac', 'hat-dinh-duong', 'trai-cay-say', 'sua-hat', 'nuoc-ep', 'banh-keo', 'khuyen-mai'];

                    if (!in_array($slug, $navSlugs) && !str_contains($text, 'Trang chủ') && !str_contains($text, 'Thêm vào giỏ')) {
                        $productLinks[$href] = $text;
                    }
                }
            });
        }

        // Lấy dữ liệu chi tiết cho Deli Fruit
        $count = 0;
        foreach ($productLinks as $url => $fallbackTitle) {
            if ($count >= 60) break; // Giới hạn quét chi tiết

            $detailHtml = $this->fetchHtml($url);
            if (!$detailHtml) continue;

            $dCrawler = new Crawler($detailHtml);

            $titleNode = $dCrawler->filter('h1.product-title, h1.entry-title, h1');
            $title = $titleNode->count() > 0 ? trim($titleNode->first()->text()) : $fallbackTitle;

            $priceNode = $dCrawler->filter('.price ins .amount, p.price .amount, span.price .amount, .price .amount');
            $price = $priceNode->count() > 0 ? $this->parsePrice($priceNode->first()->text()) : 0;

            if ($price <= 0) {
                $metaPriceNode = $dCrawler->filter('meta[property="og:price:amount"], meta[property="product:price:amount"]');
                $price = $metaPriceNode->count() > 0 ? floatval($metaPriceNode->first()->attr('content')) : 0;
            }

            $oldPriceNode = $dCrawler->filter('.price del .amount');
            $oldPrice = $oldPriceNode->count() > 0 ? $this->parsePrice($oldPriceNode->first()->text()) : null;

            $descNode = $dCrawler->filter('.woocommerce-product-details__short-description, meta[property="og:description"]');
            $desc = '';
            if ($descNode->count() > 0) {
                $desc = $descNode->first()->nodeName() === 'meta' ? $descNode->first()->attr('content') : $descNode->first()->text();
            }

            if ($price > 0) {
                $allItems[] = [
                    'name' => $title,
                    'price' => $price,
                    'original_price' => $oldPrice > $price ? $oldPrice : null,
                    'unit' => $this->detectUnit($title),
                    'desc' => trim($desc) ?: "Trái cây nhập khẩu / Giỏ hoa quả DeliFruit: {$title}",
                    'url' => $url,
                    'image' => null,
                ];
                $count++;
            }
        }

        return $this->uniqueByName($allItems);
    }

    /**
     * Chuyển chuỗi giá tiền dạng text thành số nguyên
     */
    protected function parsePrice(string $priceStr): float
    {
        if (preg_match('/(\d[\d\.\,]{2,12})\s*(đ|vnd|vnđ|\$|₫)?/iu', $priceStr, $m)) {
            $cleanNumber = preg_replace('/[^\d]/', '', $m[1]);
            $val = (float) $cleanNumber;

            if ($val > 50000000) {
                if (preg_match('/^\d{5,8}/', $cleanNumber, $subMatch)) {
                    return (float) $subMatch[0];
                }
            }

            return $val;
        }

        $clean = preg_replace('/[^\d]/', '', $priceStr);
        $val = (float) ($clean ?: 0);

        if ($val > 50000000) {
            if (preg_match('/^\d{5,8}/', $clean, $subMatch)) {
                return (float) $subMatch[0];
            }
        }

        return $val;
    }

    /**
     * Tự động nhận diện đơn vị tính dựa trên tên sản phẩm
     */
    protected function detectUnit(string $productName): string
    {
        $lower = mb_strtolower($productName, 'UTF-8');
        if (str_contains($lower, 'hộp') || str_contains($lower, 'box')) return 'Hộp';
        if (str_contains($lower, 'khay') || str_contains($lower, 'set')) return 'Khay/Set';
        if (str_contains($lower, 'giỏ') || str_contains($lower, 'lẵng')) return 'Giỏ';
        if (str_contains($lower, 'bó')) return 'Bó';
        if (str_contains($lower, 'quả') || str_contains($lower, 'trái')) return 'Quả';
        if (str_contains($lower, 'kg') || str_contains($lower, 'kilo')) return 'Kg';
        if (str_contains($lower, 'g') || str_contains($lower, 'gram')) return 'Gram';
        return 'Kg';
    }

    /**
     * Chuyển relative URL thành absolute URL
     */
    protected function makeAbsoluteUrl(?string $url, string $baseUrl): string
    {
        if (empty($url)) return $baseUrl;
        if (filter_var($url, FILTER_VALIDATE_URL)) return $url;

        $parseBase = parse_url($baseUrl);
        $scheme = $parseBase['scheme'] ?? 'https';
        $host = $parseBase['host'] ?? '';

        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $url;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Loại bỏ các sản phẩm trùng tên trong kết quả cào
     */
    protected function uniqueByName(array $items): array
    {
        $unique = [];
        foreach ($items as $item) {
            $key = mb_strtolower(trim($item['name']), 'UTF-8');
            if (!isset($unique[$key])) {
                $unique[$key] = $item;
            }
        }
        return array_values($unique);
    }

    /**
     * Tìm sản phẩm trong DB trùng/gần nhất với tên sản phẩm cào được
     */
    protected function findMatchingDbProduct(string $crawledName, $dbProducts): ?Product
    {
        $crawledClean = $this->normalizeName($crawledName);

        foreach ($dbProducts as $product) {
            $dbClean = $this->normalizeName($product->name);

            if ($crawledClean === $dbClean || str_contains($crawledClean, $dbClean) || str_contains($dbClean, $crawledClean)) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Chuẩn hóa tên để so sánh (bỏ dấu tiếng Việt, viết thường, bỏ khoảng trắng thừa)
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
}
