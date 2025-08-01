<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    //protected $table = 'suppliers';
    protected $fillable =[

        "name", "image", "company_name", "vat_number",
        "email", "phone_number", "address", "city",
        "state", "postal_code", "country", "is_active"

    ];

    public function product()
    {
    	return $this->hasMany('App\Models\Product');
    }
}
