<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable =[

        "name", "phone", "email", "address", "is_active"
    ];

    public function product()
    {
    	return $this->belongsToMany('App\Models\Product', 'product_warehouse')
                ->withPivot('qty');
    }
}
