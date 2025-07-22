<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAdjustment extends Model
{
    protected $table = 'product_adjustments';
    protected $fillable =[
        "adjustment_id", "product_id", "warehouse_id", "variant_id", "unit_cost", "qty", "action"
    ];

    public function product(){
        return $this->belongsTo(
            'App\Models\Product'
        );
    }

    public function warehouse(){
        return $this->belongsTo(
            'App\Models\Warehouse'
        );
    }
}
