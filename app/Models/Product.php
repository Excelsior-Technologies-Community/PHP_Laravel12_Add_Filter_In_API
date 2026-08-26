<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'details',
        'image',
        'size',
        'color',
        'category',
        'price',
        'stock',
        'status',
        'featured',
        'discount_percent',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'featured' => 'boolean',
        'discount_percent' => 'decimal:2',
    ];

    /**
     * Calculate final price after discount.
     */
    public function getFinalPriceAttribute()
    {
        $discount = ($this->price * $this->discount_percent) / 100;

        return round($this->price - $discount, 2);
    }

    /**
     * Check whether product is in stock.
     */
    public function getInStockAttribute()
    {
        return $this->stock > 0;
    }

    /**
     * Check whether product is low stock.
     */
    public function getLowStockAttribute()
    {
        return $this->stock > 0 && $this->stock <= 5;
    }
}
