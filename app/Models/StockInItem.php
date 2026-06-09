<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInItem extends Model
{
    use HasFactory;

    protected $table = 'stock_in_items';

    protected $fillable = [
        'stock_in_id',
        'product_id',
        'product_name',
        'quantity',
        'unit',
        'price',
        'subtotal',
    ];

    public function stockIn()
    {
        return $this->belongsTo(StockIn::class, 'stock_in_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
