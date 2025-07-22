<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $fillable =[
        "reference_no", "warehouse_id", "document", "total_qty", "item",
         "note"
    ];

    public function warehouse(){
        return $this->belongsTo('\App\Models\Warehouse');
    }

    public function product(){
        return $this->belongsToMany('\App\Models\Product', 'product_adjustments')
                ->withPivot('qty');
    }
}
