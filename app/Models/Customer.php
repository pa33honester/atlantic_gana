<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable =[
        "customer_group_id", 
        "user_id",
        "supplier_id", 
        "name", 
        "company_name",
        "email", 
        "phone_number", 
        "tax_no", "address", "city",
        "state", "postal_code", 
        "country", "points", 
        "deposit", "expense", 
        "wishlist", "is_active"
    ];

    public function customerGroup()
    {
        return $this->belongsTo('App\Models\CustomerGroup');
    }

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }

    public function discountPlans()
    {
        return $this->belongsToMany('App\Models\DiscountPlan', 'discount_plan_customers');
    }
}
