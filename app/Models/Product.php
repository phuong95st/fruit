<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
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
     * Sinh dữ liệu cấu trúc JSON-LD chuẩn SEO cho AI & Google Search
     */
    public function toJsonLd()
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

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
