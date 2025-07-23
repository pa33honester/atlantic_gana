<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Returns extends Model
{
	protected $table = 'returns';
    protected $fillable =[
        "reference_no", 
        "user_id", 
        "sale_id", 
        "cash_register_id", 
        "customer_id", 
        "warehouse_id", 
        "account_id", 
        "currency_id", 
        "exchange_rate", 
        "item", 
        "total_qty", 
        "total_discount", 
        "total_tax", 
        "total_price",
        "order_tax_rate", 
        "order_tax", 
        "grand_total", 
        "document", 
        "return_note", 
        "staff_note",
        "call_on",
        "report_times"
    ];

    public function sale()
    {
        return $this->belongsTo('App\Models\Sale');
    }

    public function customer()
    {
    	return $this->belongsTo('App\Models\Customer');
    }

    public function warehouse()
    {
    	return $this->belongsTo('App\Models\Warehouse');
    }

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }

   public function products()
    {
        return $this->belongsToMany('\App\Models\Product', 'product_returns', 'return_id')
                    ->withPivot('qty', 'net_unit_price', 'tax');
    }
}
