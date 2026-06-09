<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    use HasFactory;

    protected $table = 'stock_ins';

    protected $fillable = [
        'stock_in_code',
        'date',
        'supplier',
        'invoice_number',
        'payment_method',
        'notes',
        'total_items',
        'total_value',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StockInItem::class, 'stock_in_id');
    }
}
