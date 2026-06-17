<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'code',
        'name',
        'price',
        'original_price',
        'unit',
        'origin',
        'badge',
        'badge_text',
        'ic',
        'bg',
        'svg',
        't1',
        't2',
        'desc',
        'pack',
        'rating_text',
        'rating_stars',
        'rating_value',
        'reviews_count',
        'sold_count',
        'nutrition',
        'is_daily',
        'image',
        'images',
        'video',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * Định dạng giá bán VND
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', '.') . 'đ';
    }

    /**
     * Định dạng giá gốc VND
     */
    public function getFormattedOriginalPriceAttribute()
    {
        return $this->original_price ? number_format($this->original_price, 0, ',', '.') . 'đ' : null;
    }

    /**
     * Tính phần trăm giảm giá
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->original_price && $this->original_price > $this->price) {
            $discount = (($this->original_price - $this->price) / $this->original_price) * 100;
            return '-' . round($discount) . '%';
        }
        return null;
    }

    /**
     * Lấy URL ảnh sản phẩm từ MinIO S3 hoặc fallback
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }
            return rtrim(config('filesystems.disks.s3.url'), '/') . '/' . ltrim($this->image, '/');
        }
        return null;
    }

    /**
     * Lấy danh sách URL các ảnh phụ
     */
    public function getImagesUrlsAttribute()
    {
        if (empty($this->images)) return [];
        $baseUrl = rtrim(config('filesystems.disks.s3.url'), '/');
        return array_map(function ($path) use ($baseUrl) {
            if (filter_var($path, FILTER_VALIDATE_URL)) return $path;
            return $baseUrl . '/' . ltrim($path, '/');
        }, $this->images);
    }

    /**
     * Kiểm tra video có phải YouTube / YouTube Shorts không
     */
    public function getIsYoutubeAttribute(): bool
    {
        return $this->video && (
            str_contains($this->video, 'youtube.com') ||
            str_contains($this->video, 'youtu.be')
        );
    }

    /**
     * Trả về URL trực tiếp của video (file upload hoặc URL gốc YouTube).
     */
    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->video) return null;
        // YouTube / Shorts — trả nguyên URL gốc
        if ($this->is_youtube) return $this->video;
        // File path — ghép S3 base URL
        if (filter_var($this->video, FILTER_VALIDATE_URL)) return $this->video;
        return rtrim(config('filesystems.disks.s3.url'), '/') . '/' . ltrim($this->video, '/');
    }

    /**
     * Trả về URL embed chuẩn để nhúng vào <iframe>.
     * - youtube.com/watch?v=ID   → youtube.com/embed/ID
     * - youtu.be/ID              → youtube.com/embed/ID
     * - youtube.com/shorts/ID    → youtube.com/embed/ID
     * - File video               → null (dùng <video> tag thay thế)
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        if (!$this->video) return null;

        // Shorts: youtube.com/shorts/ID
        if (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/', $this->video, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        // Watch: youtube.com/watch?v=ID
        if (preg_match('/youtube\.com\/watch\?(?:.*&)?v=([a-zA-Z0-9_-]+)/', $this->video, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        // Short link: youtu.be/ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $this->video, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        return null; // Không phải YouTube → dùng <video>
    }


    /**
     * Sinh dữ liệu cấu trúc JSON-LD chuẩn SEO cho AI & Google Search
     */
    public function toJsonLd($comments = null)
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->name,
            'description' => $this->desc ?: $this->name,
            'sku' => $this->code,
            'offers' => [
                '@type' => 'Offer',
                'url' => route('product.show', $this->slug),
                'priceCurrency' => 'VND',
                'price' => (float)$this->price,
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => 'https://schema.org/InStock',
                'seller' => [
                    '@type' => 'Store',
                    'name' => 'Hoa quả Sơn Tây'
                ]
            ]
        ];

        if ($this->reviews_count > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float)$this->rating_value,
                'reviewCount' => (int)$this->reviews_count,
                'bestRating' => 5,
                'worstRating' => 1
            ];
        }

        if ($comments && $comments->isNotEmpty()) {
            $reviewData = [];
            foreach ($comments as $comment) {
                $reviewData[] = [
                    '@type' => 'Review',
                    'author' => [
                        '@type' => 'Person',
                        'name' => $comment->author_name
                    ],
                    'datePublished' => $comment->created_at->toIso8601String(),
                    'reviewBody' => $comment->content,
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (int)$comment->rating,
                        'bestRating' => 5,
                        'worstRating' => 1
                    ]
                ];
            }
            $data['review'] = $reviewData;
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Get the comments for the product.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
