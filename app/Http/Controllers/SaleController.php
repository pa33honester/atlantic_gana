<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Tax;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Delivery;
use App\Models\PosSetting;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\PaymentWithCheque;
use App\Models\PaymentWithGiftCard;
use App\Models\PaymentWithCreditCard;
use App\Models\PaymentWithPaypal;
use App\Models\User;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\CashRegister;
use App\Models\Returns;
use App\Models\ProductReturn;
use App\Models\Expense;
use App\Models\ProductPurchase;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\RewardPointSetting;
use App\Models\CustomField;
use App\Models\Table;
use App\Models\Courier;
use App\Models\ExternalService;
use DB;
use Cache;
use App\Models\GeneralSetting;
use App\Models\MailSetting;
use Stripe\Stripe;
use NumberToWords\NumberToWords;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\SaleDetails;
use App\Mail\LogMessage;
use App\Mail\PaymentDetails;
use Mail;
use Srmklive\PayPal\Services\ExpressCheckout;
use Srmklive\PayPal\Services\AdaptivePayments;
use GeniusTS\HijriDate\Date;
use Illuminate\Support\Facades\Validator;
use App\Models\Currency;
use App\Models\ReturnPurchase;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use App\SMSProviders\TonkraSms;
use App\ViewModels\ISmsModel;
use PHPUnit\Framework\MockObject\Stub\ReturnSelf;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class SaleController extends Controller
{
    use \App\Traits\TenantInfo;
    use \App\Traits\MailInfo;

    private $_smsModel;

    public function __construct(ISmsModel $smsModel)
    {
        $this->_smsModel = $smsModel;
    }

    public function return_order($sale_id, $return_note, $staff_note){
        $data = [
                "sale_id"=> $sale_id,
                "total_qty" => 0,
                "total_discount"=> 0,
                "total_tax" => 0,
                "total_price" => 0,
                "item" => 0,
                "order_tax" => 0,
                "grand_total" => 0,
                "change_sale_status" => "1",
                "return_note" => $return_note,
                "staff_note" => $staff_note
            ];
                
            /// From View.Return.Create.Blade
            try {
                    $lims_product_sale_data = Product_Sale::where('sale_id', $sale_id)->get();
                    foreach($lims_product_sale_data as $product_sale) {
                        $product_data = DB::table('products')->find($product_sale->product_id);
                        if($product_sale->variant_id) {
                            $product_variant_data = ProductVariant::select('id', 'item_code')->FindExactProduct($product_data->id, $product_sale->variant_id)->first();
                            $product_variant_id = $product_variant_data->id;
                            $product_data->code = $product_variant_data->item_code;
                        }
                        else
                            $product_variant_id = null;
                        if($product_data->tax_method == 1){
                            $product_price = $product_sale->net_unit_price + ($product_sale->discount / $product_sale->qty);
                        }
                        elseif ($product_data->tax_method == 2) {
                            $product_price =($product_sale->total / $product_sale->qty) + ($product_sale->discount / $product_sale->qty);
                        }

                        $tax = DB::table('taxes')->where('rate',$product_sale->tax_rate)->first();
                        if($product_data->type == 'standard'){
                            $unit = DB::table('units')->select('unit_name')->find($product_sale->sale_unit_id);
                            $unit_name = $unit->unit_name;
                        }
                        else {
                            $unit_name = 'n/a';
                        }
                        $product_batch_data = ProductBatch::select('batch_no')->find($product_sale->product_batch_id);
                        if($product_batch_data){
                            $data["product_batch_id"] []= $product_sale->product_batch_id;
                        }
                        else
                            $data["product_batch_id"] []= 0;
                        
                        $data["actual_qty"] []= $product_sale->qty - $product_sale->return_qty;
                        $data["qty"] []= $product_sale->qty - $product_sale->return_qty;
                        $data["is_return"] []= $product_sale->id;
                        $data["product_code"] []= $product_data->code;
                        $data["product_sale_id"] []= $product_sale->id;
                        $data["product_id"] []= $product_data->id;
                        $data["product_variant_id"] []= $product_variant_id;
                        $data["product_price"] []= $product_price;
                        $data["sale_unit"] []= $unit_name;
                        $data["net_unit_price"] []= $product_sale->net_unit_price;
                        $data["discount"] []= $product_sale->discount;
                        $data["tax_rate"] []= $product_sale->tax_rate;
                        $data["tax"] []= $product_sale->tax;
                        $data["subtotal"] []= $product_sale->total;
                        $data["imei_number"] []= "";
                    }
                }
            catch (\Exception $e) {
                dd($e->getMessage());
            }

            /// From Controller.Return.Store
            try {
                    $lims_sale_data = Sale::select('id', 'reference_no','warehouse_id', 'total_qty', 'customer_id', 'biller_id', 'currency_id', 'exchange_rate', 'sale_status')->find($data['sale_id']);
                    $data['user_id'] = Auth::id();
                    $data['reference_no'] = $lims_sale_data['reference_no'];
                    $data['customer_id'] = $lims_sale_data->customer_id;
                    $data['warehouse_id'] = $lims_sale_data->warehouse_id;
                    $data['biller_id'] = $lims_sale_data->biller_id;
                    $data['currency_id'] = $lims_sale_data->currency_id;
                    $data['exchange_rate'] = $lims_sale_data->exchange_rate;
                    $cash_register_data = CashRegister::where([
                        ['user_id', $data['user_id']],
                        ['warehouse_id', $data['warehouse_id']],
                        ['status', true]
                    ])->first();
                    if ($cash_register_data)
                        $data['cash_register_id'] = $cash_register_data->id;
                    $lims_account_data = Account::where('is_default', true)->first();
                    $data['account_id'] = $lims_account_data->id;

                    $lims_return_data = Returns::create($data);
                    $lims_customer_data = Customer::find($data['customer_id']);
                    //collecting male data
                    $mail_data['email'] = $lims_customer_data->email;
                    $mail_data['reference_no'] = $lims_return_data->reference_no;
                    $mail_data['total_qty'] = $lims_return_data->total_qty;
                    $mail_data['total_price'] = $lims_return_data->total_price;
                    $mail_data['order_tax'] = $lims_return_data->order_tax;
                    $mail_data['order_tax_rate'] = $lims_return_data->order_tax_rate;
                    $mail_data['grand_total'] = $lims_return_data->grand_total;

                    $product_sale_ids = $data['is_return'];
                    $imei_number = $data['imei_number'];
                    $product_batch_id = $data['product_batch_id'];
                    $product_code = $data['product_code'];
                    $qty = $data['qty'];
                    $sale_unit = $data['sale_unit'];
                    $net_unit_price = $data['net_unit_price'];
                    $discount = $data['discount'];
                    $tax_rate = $data['tax_rate'];
                    $tax = $data['tax'];
                    $total = $data['subtotal'];
                    
                    // do something for each product in this sale-order
                    foreach ($product_sale_ids as $product_sale_id) {
                        $key = array_search($product_sale_id, $data['product_sale_id']);
                        $pro_id = $data['product_id'];
                        //return $key;
                        $lims_product_data = Product::find($pro_id);
                        $variant_id = null;
                        if ($sale_unit[$key] != 'n/a') {
                            $lims_sale_unit_data = Unit::where('unit_name', $sale_unit[$key])->first();
                            $sale_unit_id = $lims_sale_unit_data->id;
                            if ($lims_sale_unit_data->operator == '*')
                                $quantity = $qty[$key] * $lims_sale_unit_data->operation_value;
                            elseif ($lims_sale_unit_data->operator == '/')
                                $quantity = $qty[$key] / $lims_sale_unit_data->operation_value;

                            if ($lims_product_data->is_variant) {
                                $lims_product_variant_data = ProductVariant::
                                    select('id', 'variant_id', 'qty')
                                    ->FindExactProductWithCode($pro_id, $product_code[$key])
                                    ->first();
                                $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($pro_id, $lims_product_variant_data->variant_id, $data['warehouse_id'])->first();
                                $lims_product_variant_data->qty += $quantity;
                                $lims_product_variant_data->save();
                                $variant_data = Variant::find($lims_product_variant_data->variant_id);
                                $variant_id = $variant_data->id;
                            } elseif ($product_batch_id[$key]) {
                                $lims_product_warehouse_data = Product_Warehouse::where([
                                    ['product_batch_id', $product_batch_id[$key]],
                                    ['warehouse_id', $data['warehouse_id']]
                                ])->first();
                                $lims_product_batch_data = ProductBatch::find($product_batch_id[$key]);
                                //increase product batch quantity
                                $lims_product_batch_data->qty += $quantity;
                                $lims_product_batch_data->save();
                            } else
                                $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])->first();

                            $lims_product_data->qty += $quantity;
                            $lims_product_warehouse_data->qty += $quantity;

                            $lims_product_data->save();
                            $lims_product_warehouse_data->save();
                        } else {
                            if ($lims_product_data->type == 'combo') {
                                $product_list = explode(",", $lims_product_data->product_list);
                                if ($lims_product_data->variant_list)
                                    $variant_list = explode(",", $lims_product_data->variant_list);
                                else
                                    $variant_list = [];
                                $qty_list = explode(",", $lims_product_data->qty_list);
                                $price_list = explode(",", $lims_product_data->price_list);
                                foreach ($product_list as $index => $child_id) {
                                    $child_data = Product::find($child_id);
                                    if (count($variant_list) && $variant_list[$index]) {
                                        $child_product_variant_data = ProductVariant::where([
                                            ['product_id', $child_id],
                                            ['variant_id', $variant_list[$index]]
                                        ])->first();

                                        $child_warehouse_data = Product_Warehouse::where([
                                            ['product_id', $child_id],
                                            ['variant_id', $variant_list[$index]],
                                            ['warehouse_id', $data['warehouse_id']],
                                        ])->first();

                                        $child_product_variant_data->qty += $qty[$key] * $qty_list[$index];
                                        $child_product_variant_data->save();
                                    } else {
                                        $child_warehouse_data = Product_Warehouse::where([
                                            ['product_id', $child_id],
                                            ['warehouse_id', $data['warehouse_id']],
                                        ])->first();
                                    }

                                    $child_data->qty += $qty[$key] * $qty_list[$index];
                                    $child_warehouse_data->qty += $qty[$key] * $qty_list[$index];

                                    $child_data->save();
                                    $child_warehouse_data->save();
                                }
                            }
                            $sale_unit_id = 0;
                        }
                        //add imei number if available
                        if ($imei_number[$key]) {
                            if ($lims_product_warehouse_data->imei_number)
                                $lims_product_warehouse_data->imei_number .= ',' . $imei_number[$key];
                            else
                                $lims_product_warehouse_data->imei_number = $imei_number[$key];
                            $lims_product_warehouse_data->save();
                        }
                        if ($lims_product_data->is_variant)
                            $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
                        else
                            $mail_data['products'][$key] = $lims_product_data->name;

                        if ($sale_unit_id)
                            $mail_data['unit'][$key] = $lims_sale_unit_data->unit_code;
                        else
                            $mail_data['unit'][$key] = '';

                        $mail_data['qty'][$key] = $qty[$key];
                        $mail_data['total'][$key] = $total[$key];

                        // add to return list 
                        ProductReturn::insert(
                            ['return_id' => $lims_return_data->id, 'product_id' => $pro_id, 'product_batch_id' => $product_batch_id[$key], 'variant_id' => $variant_id, 'imei_number' => $imei_number[$key], 'qty' => $qty[$key], 'sale_unit_id' => $sale_unit_id, 'net_unit_price' => $net_unit_price[$key], 'discount' => $discount[$key], 'tax_rate' => $tax_rate[$key], 'tax' => $tax[$key], 'total' => $total[$key], 'created_at' => \Carbon\Carbon::now(), 'updated_at' => \Carbon\Carbon::now()]
                        );

                        // deduct the returned quantity of the product_sale
                        $product_sale_data = Product_Sale::where([
                            ['product_id', $pro_id],
                            ['sale_id', $data['sale_id']]
                        ])->select('id', 'return_qty')->first();
                        $product_sale_data->return_qty += $qty[$key];
                        
                        // push product back to the warehouse
                        {
                            $product_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $pro_id],
                                    ['warehouse_id', $data['warehouse_id']]
                            ])->select('id', 'qty')->first();
                            
                            $product_warehouse_data->qty += $qty[$key];
                            $product_warehouse_data->save();
                        }
                        
                        // update product quantity
                        $lims_product_data->qty -= $qty[$key];
                        $product_sale_data->save();

                        // update the total quantity of the sale
                        $lims_sale_data->total_qty -= $qty[$key];
                        $lims_sale_data->save();
                    }

                    // change sale status into 4(returned)
                    $message = 'Return created successfully';
                    $lims_sale_data->update(['sale_status' => 4]);

                    $mail_setting = MailSetting::latest()->first();
                    if ($mail_data['email'] && $mail_setting) {
                        $this->setMailInfo($mail_setting);
                        try {
                            Mail::to($mail_data['email'])->send(new \App\Mail\ReturnDetails($mail_data));
                        } catch (\Exception $e) {
                            $message = 'Return created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
                        }
                    }
                    // return redirect('return-sale')->with('message', $message);
                }
            catch (\Exception $e) {
                dd($e->getMessage());
            }
    }

    // krishna singh - https://linktr.ee/iamsinghkrishna
    // changed by dorian at 2025/6/5
    /**
     * Update the status of a sale based on the request data.
     * Handles confirmation, cancellation, and reporting of sales.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function updateStatus(Request $request)
    {
        $data = $request->input();
        $saleId = $data['sale_id'];
        $resType = $data['res_type'];
        $location = $data['location'];
        $resReason1 = $data['res_reason_1'];
        $resReason2 = $data['res_reason_2'];
        $resInfo = $data['res_info'];

        // Prepare sale details for update
        $saleDetails = [
            'res_type' => $resType,
            'location' => $location,
            'res_info' => $resInfo,
        ];

        // Check if a return record exists for this sale
        $returnData = Returns::where('sale_id', $saleId)->select('id')->first();

        // Handle each response type
        switch ($resType) {
            case 'confirm':
                $saleDetails['sale_status'] = 7;
                if ($returnData) {
                    (new ReturnController())->destroy($returnData->id);
                }
                break;

            case 'cancel':
                $saleDetails['res_reason'] = $resReason1;
                $saleDetails['sale_status'] = 11;
                if ($returnData) {
                    (new ReturnController())->destroy($returnData->id);
                }
                break;

            case 'report':
                $saleDetails['res_reason'] = $resReason2;
                $saleDetails['sale_status'] = 4;

                if ($returnData) {
                    // If a return already exists, do nothing further
                    break;
                }

                // Prepare data for creating a new return
                $requestData = [
                    'sale_id' => $saleId,
                    'total_qty' => 0,
                    'total_discount' => 0,
                    'total_tax' => 0,
                    'total_price' => 0,
                    'item' => 0,
                    'order_tax' => 0,
                    'grand_total' => 0,
                    'change_sale_status' => '1',
                    'return_note' => $resReason1,
                    'staff_note' => $resInfo,
                ];

                $productSales = Product_Sale::where('sale_id', $saleId)->get();
                foreach ($productSales as $productSale) {
                    $product = DB::table('products')->find($productSale->product_id);

                    // Get product variant if exists
                    if ($productSale->variant_id) {
                        $variant = ProductVariant::select('id', 'item_code')
                            ->FindExactProduct($product->id, $productSale->variant_id)
                            ->first();
                        $productVariantId = $variant->id;
                        $product->code = $variant->item_code;
                    } else {
                        $productVariantId = null;
                    }

                    // Calculate product price based on tax method
                    if ($product->tax_method == 1) {
                        $productPrice = $productSale->net_unit_price + ($productSale->discount / $productSale->qty);
                    } elseif ($product->tax_method == 2) {
                        $productPrice = ($productSale->total / $productSale->qty) + ($productSale->discount / $productSale->qty);
                    } else {
                        $productPrice = 0;
                    }

                    // Get unit name
                    if ($product->type == 'standard') {
                        $unit = DB::table('units')->select('unit_name')->find($productSale->sale_unit_id);
                        $unitName = $unit ? $unit->unit_name : 'n/a';
                    } else {
                        $unitName = 'n/a';
                    }

                    // Get product batch id
                    $batch = ProductBatch::select('batch_no')->find($productSale->product_batch_id);
                    $batchId = $batch ? $productSale->product_batch_id : 0;

                    // Fill request data arrays
                    $requestData['product_batch_id'][] = $batchId;
                    $requestData['actual_qty'][] = $productSale->qty - $productSale->return_qty;
                    $requestData['qty'][] = $productSale->qty - $productSale->return_qty;
                    $requestData['is_return'][] = $productSale->id;
                    $requestData['product_code'][] = $product->code;
                    $requestData['product_sale_id'][] = $productSale->id;
                    $requestData['product_id'][] = $product->id;
                    $requestData['product_variant_id'][] = $productVariantId;
                    $requestData['product_price'][] = $productPrice;
                    $requestData['sale_unit'][] = $unitName;
                    $requestData['net_unit_price'][] = $productSale->net_unit_price;
                    $requestData['discount'][] = $productSale->discount;
                    $requestData['tax_rate'][] = $productSale->tax_rate;
                    $requestData['tax'][] = $productSale->tax;
                    $requestData['subtotal'][] = $productSale->total;
                    $requestData['imei_number'][] = '';
                }

                // Store the return
                (new ReturnController())->store(new Request($requestData));
                break;
        }

        // Update the sale record
        Sale::where('id', $saleId)->update($saleDetails);
    }
   
    /**
     * Update sale status and related data based on order type (delivery, shipped, shipping, return_ship, cancel_order, reset_order).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function updateStatusFilters(Request $request)
    {
        $data = $request->input();
        $sale_id = $data["sale_id"];
        $orderType = $data["order_type"];
        $sale_details = [];

        switch ($orderType) {
            case "delivery":
                $sale_details["sale_status"] = 12;
                Sale::whereIn('id', $sale_id)->update($sale_details);
                return response()->json([
                    "code"  => 200,
                    "msg"   => "success"
                ]);

            case "shipped":
                $sale_details["sale_status"] = 8;
                break;

            case "shipping":
                $sale_details["sale_status"] = 9;
                $sale_details["shipping_cost"] = $data["shipping_cost"];
                $reference_no = Sale::select('reference_no')->find($sale_id)->reference_no;
                // Update shipping cost in purchase and sale tables
                Purchase::where('reference_no', $reference_no)->update(['shipping_cost' => $data["shipping_cost"]]);
                Sale::where('reference_no', $reference_no)->update(['shipping_cost' => $data["shipping_cost"]]);
                break;

            case "return_ship":
                $sale_details["sale_status"] = 4;
                $reference_no = Sale::select('reference_no')->find($sale_id)->reference_no;
                $warehouse_id = Sale::select('warehouse_id')->find($sale_id)->warehouse_id;
                $shipping_cost = Sale::select('shipping_cost')->find($sale_id)->shipping_cost;
                $return_shipping_cost = $data["return_shipping_cost"];

                // Update return shipping cost in purchase and sale tables
                Purchase::where('reference_no', $reference_no)->update(['return_shipping_cost' => $return_shipping_cost]);
                Sale::where('reference_no', $reference_no)->update(['return_shipping_cost' => $return_shipping_cost]);
                $sale_details["shipping_cost"] = $shipping_cost;

                // Update product warehouse and product sale return quantities
                $product_sales = Product_Sale::select('id', 'qty', 'return_qty', 'product_id')->where('sale_id', $sale_id)->get();
                foreach ($product_sales as $product_sale) {
                    $product_warehouse = Product_Warehouse::where([
                        ['product_id', $product_sale->product_id],
                        ['warehouse_id', $warehouse_id]
                    ])->first();
                    if ($product_warehouse) {
                        $product_warehouse->qty += $product_sale->qty - $product_sale->return_qty;
                        $product_warehouse->save();
                    }
                    $product_sale->return_qty += $product_sale->qty;
                    $product_sale->save();
                }

                // Create a return purchase record
                $purchase = Purchase::where('reference_no', $reference_no)->first();
                if ($purchase) {
                    $return_purchase_data = [
                        'reference_no' => $purchase->reference_no,
                        'supplier_id' => $purchase->supplier_id,
                        'warehouse_id' => $purchase->warehouse_id,
                        'user_id' => $purchase->user_id,
                        'purchase_id' => $purchase->id,
                        'account_id' => 1,
                        'currency_id' => $purchase->currency_id,
                        'shipping_cost' => $shipping_cost,
                        'return_shipping_cost' => $return_shipping_cost,
                        'exchange_rate' => $purchase->exchange_rate,
                        'item' => $purchase->item,
                        'total_qty' => $purchase->total_qty,
                        'total_discount' => $purchase->total_discount,
                        'total_tax' => $purchase->total_tax,
                        'total_cost' => $purchase->total_cost,
                        'order_tax_rate' => $purchase->order_tax_rate,
                        'order_tax' => $purchase->order_tax,
                        'grand_total' => $purchase->total_cost + ($purchase->order_tax - $purchase->order_discount),
                        'document' => $purchase->document,
                        'return_note' => $purchase->note,
                        'staff_note' => $purchase->note,
                    ];
                    ReturnPurchase::create($return_purchase_data);
                }
                break;

            case "cancel_order":
                $sale_details["sale_status"] = 11;
                $sale_details["res_type"] = "cancel_order";
                $sale_details["res_reason"] = $data["res_reason_1"];
                $sale_details["res_info"] = $data["res_info"];
                break;

            case "reset_order":
                $sale_details["sale_status"] = 7;
                break;
        }

        // Update the sale record
        Sale::where('id', $sale_id)->update($sale_details);
    }

    // krishna singh - https://linktr.ee/iamsinghkrishna
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';

            if ($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if ($request->input('sale_status'))
                $sale_status = $request->input('sale_status');
            else
                $sale_status = 0;

            if ($request->input('payment_status'))
                $payment_status = $request->input('payment_status');
            else
                $payment_status = 0;

             if ($request->input('location'))
                $location = $request->input('location');
            else
                $location = 0;

            if ($request->input('sale_type'))
                $sale_type = $request->input('sale_type');
            else
                $sale_type = 0;

            if ($request->input('supplier_id'))
                $supplier_id = $request->input('supplier_id');
            else
                $supplier_id = 0;

            if ($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            } else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d'))))));
                $ending_date = date("Y-m-d");
            }

            $lims_gift_card_list = GiftCard::where("is_active", true)->get();
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            $lims_courier_list = Courier::where('is_active', true)->get();
            $lims_supplier_list = Supplier::where('is_active', true)->get();
            $lims_general_setting_data = GeneralSetting::latest()->select('shipping_cost_list', 'return_shipping_cost_list')->first();
            $lims_shipping_cost_list = explode(',', $lims_general_setting_data->shipping_cost_list);
            $lims_return_shipping_cost_list = explode(',', $lims_general_setting_data->return_shipping_cost_list);

            if ($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];
            $numberOfInvoice = Sale::count();
            $custom_fields = CustomField::where([
                ['belongs_to', 'sale'],
                ['is_table', true]
            ])->pluck('name');
            $field_name = [];
            foreach ($custom_fields as $fieldName) {
                $field_name[] = str_replace(" ", "_", strtolower($fieldName));
            }
            $smsTemplates = SmsTemplate::all();
            return view('backend.sale.index', compact(
                'starting_date', 'ending_date', 'warehouse_id', 'sale_status', 'payment_status', 'location', 
                'sale_type', 'supplier_id', 'lims_gift_card_list', 
                'lims_pos_setting_data', 'lims_reward_point_setting_data', 
                'lims_account_list', 'lims_warehouse_list', 'all_permission', 'options', 
                'numberOfInvoice', 'custom_fields', 'field_name', 'lims_courier_list', 'lims_supplier_list', 
                'lims_shipping_cost_list', 
                'lims_return_shipping_cost_list', 
                'smsTemplates'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    /**
     * Fetch and format sales data for DataTables or similar UI components.
     * Applies filters, search, and sorting based on request parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void (outputs JSON directly)
     */
    public function saleData(Request $request)
    {
        // Define columns for ordering
        $columns = [
            0 => 'created_at',
            1 => 'updated_at',
            2 => 'reference_no',
            3 => 'grand_total',
            4 => 'paid_amount',
        ];

        $user = Auth::user();

        // Extract filters
        $filters = [
            'warehouse_id'   => $request->input('warehouse_id'),
            'supplier_id'    => $request->input('supplier_id'),
            'sale_status'    => $request->input('sale_status'),
            'location'       => $request->input('location'),
            'sale_type'      => $request->input('sale_type'),
            'start_date'     => $request->input('starting_date'),
            'end_date'       => $request->input('ending_date'),
        ];
       
        // Use Eloquent relationships and eager loading
        $query = Sale::with([
            'customer',
            'biller',
            'warehouse',
            'user',
            'products.supplier'
        ])
        ->whereDate('created_at', '>=', $filters['start_date'])
        ->whereDate('created_at', '<=', $filters['end_date']);

        // Apply user access restrictions
        if ($user->role_id > 2) {
            if (config('staff_access') == 'own') {
                $query->where('user_id', $user->id);
            } elseif (config('staff_access') == 'warehouse') {
                $query->where('warehouse_id', $user->warehouse_id);
            }
        }

        // Apply additional filters
        foreach (['warehouse_id', 'sale_status', 'location', 'sale_type'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        // Filter by supplier if needed
        if ($filters['supplier_id'] > 0) {
            $query->whereHas('products.supplier', function($q) use ($filters) {
                $q->where('suppliers.id', $filters['supplier_id']);
            });
        }

        // Total records before search
        $totalData = $query->count();
        $totalFiltered = $totalData;

        // Pagination and ordering
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $limit = ($limit == -1) ? $totalFiltered : $limit; // Fetch all if limit = -1
        $orderColumn = $columns[$request->input('order.0.column')];
        $orderDir = $request->input('order.0.dir', 'desc');

        // Fetch custom fields for sales
        $customFields = CustomField::where([
            ['belongs_to', 'sale'],
            ['is_table', true]
        ])->pluck('name');
        $fieldNames = [];
        foreach ($customFields as $fieldName) {
            $fieldNames[] = str_replace(" ", "_", strtolower($fieldName));
        }

        // Handle search
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            // Apply search filters
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_no', 'LIKE', "%{$searchValue}%")
                    ->orWhereHas('customer', function($q2) use ($searchValue) {
                        $q2->where('name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('phone_number', 'LIKE', "%{$searchValue}%");
                    });
                    // ->orWhereHas('biller', function($q2) use ($searchValue) {
                    //     $q2->where('name', 'LIKE', "%{$searchValue}%");
                    // })
                    // ->orWhereHas('products', function($q2) use ($searchValue) {
                    //     $q2->where('imei_number', 'LIKE', "%{$searchValue}%");
                    // });
            });

            $totalFiltered = $query->count();
        }

        // Fetch paginated sales
        $sales = $query
            ->offset($start)
            ->limit($limit)
            ->orderBy($orderColumn, $orderDir)
            ->get();
      
        $data = [];
        foreach ($sales as $key => $sale) {
            // Get product names and codes from the related products
            $product_names = $sale->products->pluck('name')->toArray();
            $product_codes = $sale->products->pluck('code')->toArray();

            // If you want supplier names as well:
            $supplier_names = $sale->products->pluck('supplier.name')->filter()->unique()->toArray();

            $nestedData = [
                'id' => $sale->id,
                'key'  => $sale->id,
                'product_name' => implode(",", $product_names),
                'product_code' => implode(",", $product_codes),
                'supplier' => $supplier_names,
                'date' => date(config('date_format') . ' h:i:s', strtotime($sale->created_at)),
                'updated_date' => date(config('date_format') . ' h:i:s', strtotime($sale->updated_at)),
                'reference_no' => $sale->reference_no,
                'biller' => $sale->biller->name ?? '',
                'customer' => $sale->customer->name . '<br>' . $sale->customer->phone_number,
                'customer_address' => $sale->customer->address . '<br>' . $sale->customer->city,
                'payment_method' => implode(",", Payment::where('sale_id', $sale->id)->pluck('paying_method')->toArray()),
                'sale_status' => $sale->sale_status,
                'location' => $sale->location,
                'grand_total' => number_format($sale->grand_total, config('decimal')),
                'returned_amount' => number_format(DB::table('returns')->where('sale_id', $sale->id)->sum('grand_total'), config('decimal')),
                'paid_amount' => number_format($sale->paid_amount, config('decimal')),
                'item' => $sale->total_qty,
                'shipping' => ($filters['sale_status'] != 4 ? $sale->shipping_cost : $sale->return_shipping_cost),
                'due' => number_format($sale->grand_total - $sale->paid_amount, config('decimal')),
            ];

            $statusMap = [
                1  => ['info',      'Fulfilled'],
                2  => ['danger',    'file.Pending'],
                3  => ['warning',   'file.Draft'],
                4  => ['warning',   'file.Returned'],
                5  => ['info',      'file.Processing'],
                6  => ['warning',   'Unpaid'],
                7  => ['success',   'Confirmed'],
                8  => ['primary',   'Shipped'],
                9  => ['info',      'Signed'],
                10 => ['warning',   'Refunded'],
                11 => ['danger',    'Cancelled'],
                12 => ['info',      'Receiving'],
            ];

            list($badgeClass, $statusText) = $statusMap[$sale->sale_status] ?? ['secondary', 'Unknown'];
            $nestedData['sale_status'] = '<div class="badge badge-' . $badgeClass . '">' . trans($statusText) . '</div>';

            foreach ($fieldNames as $fieldName) {
                $nestedData[$fieldName] = $sale->$fieldName;
            }

            // RBAC and options
            $role = Role::find($user->role_id);
            $nestedData['options'] = ' ';
            if ($sale->sale_status == 1) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-info" onclick="return_ship(' . $sale->id . ')">return</button>';
            }
            else if ($sale->sale_status == 4 && $role->hasPermissionTo('returned')) {
                $nestedData['options'] = ' ';
            }
            else if ($sale->sale_status == 6 && $role->hasPermissionTo('unpaid')) {
                $confirm_data = [
                    'id'                => $sale->id,
                    'order_number'      => $sale->reference_no,
                    'order_time'        => $sale->created_at,
                    'customer_id'       => $sale->customer->id,
                    'customer_name'     => $sale->customer->name,
                    'customer_phone'    => $sale->customer->phone_number,
                    'customer_address'  => $sale->customer->address,
                    'location'          => $sale->location,
                    'product_amount'    => 0,
                ];
                foreach($sale->products as $product){
                    $confirm_data['products'] [] = [
                        'id'            => $product->id,
                        'product_sale_id'=> $product->pivot->id,
                        'product_name'  => $product->name,
                        'img'           => explode(',', $product->image),
                        'price'         => $product->price,
                        'qty'           => $product->pivot->qty - $product->pivot->return_qty,
                        'amount'        => $product->pivot->total
                    ];
                    $confirm_data['product_amount'] += $product->pivot->total;
                }
                $confirm_json = htmlspecialchars(json_encode($confirm_data), ENT_QUOTES, 'UTF-8');
                $nestedData['options'] = 
                    '<div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . trans("file.action") . '
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                            <li>
                                <a href="#" class="btn btn-link view text-info" onclick="editx(' . $sale->id . ')">edit</a>
                            </li>
                            <li>
                                <a href="#" class="update-status btn btn-link text-success" data-confirm="' . $confirm_json . '" onclick="update_status(this)">confirm</a>
                            </li>
                            <li>
                                <a href="#" class="update-status btn btn-link text-danger" onclick="cancel_order(' . $sale->id . ')">cancel</a>
                            </li>
                        </ul>
                    </div>';
            } 
            else if ($sale->sale_status == 7 && $role->hasPermissionTo('confirmed')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-dark print-waybill"> Print Waybill </button>';
            } 
            else if ($sale->sale_status == 8 && $role->hasPermissionTo('shipped')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-info" onclick="update_shipping_fee(' . $sale->id . ', ' . $sale->shipping_cost . ')">sign</button>';
                $nestedData['options'] .= ' <button type="button" class="update-status btn btn-link text-info" onclick="return_ship(' . $sale->id . ')">return</button>';
            } 
            else if ($sale->sale_status == 9 && $role->hasPermissionTo('signed')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-primary" onclick="return_ship(' . $sale->id . ')">return</button>';
                $nestedData['options'] .= ' <button type="button" class="update-status btn btn-link text-info" onclick="update_shipping_fee(' . $sale->id . ', ' . $sale->shipping_cost . ')">revise</button>';
            } 
            else if ($sale->sale_status == 11 && $role->hasPermissionTo('cancelled')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-danger" onclick="reset_order(' . $sale->id . ')"><i class="fa fa-refresh"></i></button>';
            } 
            else if ($sale->sale_status == 12 && $role->hasPermissionTo('receiving')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-info" onclick="update_status_filters_shipped(' . $sale->id . ')">shipped</button>';
            }

            // Sale details for one-click view
            $coupon = Coupon::find($sale->coupon_id);
            $coupon_code = $coupon ? $coupon->code : null;
            $currency_code = $sale->currency_id ? Currency::select('code')->find($sale->currency_id)->code : 'N/A';

            $sale_status = $sale->sale_status; // You may want to use your badge logic here

            $nestedData['sale'] = [
                '[ "' . date(config('date_format'), strtotime($sale->created_at->toDateString())) . '"',
                ' "' . $sale->reference_no . '"',
                ' "' . $sale_status . '"',
                ' "' . $sale->biller->name . '"',
                ' "' . $sale->biller->company_name . '"',
                ' "' . $sale->biller->email . '"',
                ' "' . $sale->biller->phone_number . '"',
                ' "' . $sale->biller->address . '"',
                ' "' . $sale->biller->city . '"',
                ' "' . $sale->customer->name . '"',
                ' "' . $sale->customer->phone_number . '"',
                ' "' . $sale->customer->address . '"',
                ' "' . $sale->customer->city . '"',
                ' "' . $sale->id . '"',
                ' "' . $sale->total_tax . '"',
                ' "' . $sale->total_discount . '"',
                ' "' . $sale->total_price . '"',
                ' "' . $sale->order_tax . '"',
                ' "' . $sale->order_tax_rate . '"',
                ' "' . $sale->order_discount . '"',
                ' "' . ($filters['sale_status'] != 4 ? $sale->shipping_cost : $sale->return_shipping_cost) . '"',
                ' "' . $sale->grand_total . '"',
                ' "' . $sale->paid_amount . '"',
                ' "' . preg_replace('/[\\n\\r]/', '<br>', $sale->sale_note) . '"',
                ' "' . preg_replace('/[\\n\\r]/', '<br>', $sale->staff_note) . '"',
                ' "' . $sale->user->name . '"',
                ' "' . $sale->user->email . '"',
                ' "' . $sale->warehouse->name . '"',
                ' "' . $coupon_code . '"',
                ' "' . $sale->coupon_discount . '"',
                ' "' . $sale->document . '"',
                ' "' . $currency_code . '"',
                ' "' . $sale->exchange_rate . '"',
                ' "' . $sale->total_qty . '"]'
            ];

            $data[] = $nestedData;
        }

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];

        // Handle special case for search and status update
        if (!empty($searchValue)) {
            // This is the case of Barcode Scanner Input
            if(preg_match('/^E\d{6}\d{10}$/', $searchValue) === 1){
                if (!empty($sales) && sizeof($sales) == 1 && $sales[0]->reference_no == $searchValue && $sales[0]->sale_status == 12) {
                    Sale::where([
                        ['id', $sales[0]['id']]
                    ])->update([
                        'sale_status' => 8
                    ]);
                    $json_data['search_value'] = $json_data["data"][0]['sale_status'] = "<div class=\"badge badge-info\" id=\"pending-to-shipped\">Receiving</div>";
                }
            }            
        }
        return response()->json($json_data);
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-add')) {
            $lims_customer_list = Customer::where('is_active', true)->get();
            if (Auth::user()->role_id > 2) {
                $lims_warehouse_list = Warehouse::where([
                    ['is_active', true],
                    ['id', Auth::user()->warehouse_id]
                ])->get();
                $lims_biller_list = Biller::where([
                    ['is_active', true],
                    ['id', Auth::user()->biller_id]
                ])->get();
            } else {
                $lims_warehouse_list = Warehouse::where('is_active', true)->get();
                $lims_biller_list = Biller::where('is_active', true)->get();
            }

            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            if ($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];

            $currency_list = Currency::where('is_active', true)->get();
            $numberOfInvoice = Sale::count();
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();
            $lims_customer_group_all = CustomerGroup::where('is_active', true)->get();
            return view('backend.sale.create', compact('currency_list', 'lims_customer_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_pos_setting_data', 'lims_tax_list', 'lims_reward_point_setting_data', 'options', 'numberOfInvoice', 'custom_fields', 'lims_customer_group_all'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

     public function store(Request $request)
    {
        $data = $request->all();

        if (isset($request->reference_no)) {
            $this->validate($request, [
                'reference_no' => [
                    'max:191',
                    'required',
                    'unique:sales'
                ]
            ]);
        }

        // check product quantity negative
        foreach($data['product_id'] as $i => $product_id){
            if($data['qty'][$i] <= 0)
                return [
                    "code"  => "400",
                    "msg"   => "Product Quantity Nagative Error!"
                ];

            $product_warehouse_data = Product_Warehouse::where([
                ['product_id', $product_id],
                ['warehouse_id', $data['warehouse_id']]
            ])->select('id', 'qty')->first();
            
            if($product_warehouse_data->qty < $data['qty'][$i]){
                return [
                    "code"  => "400",
                    "msg"   => "Product Not Enough to Order!"
                ];
            }
        }

        $data['user_id'] = Auth::id();
        $data['payment_status'] = 1;
        $cash_register_data = CashRegister::where([
            ['user_id', $data['user_id']],
            ['warehouse_id', $data['warehouse_id']],
            ['status', true]
        ])->first();

        if ($cash_register_data)
            $data['cash_register_id'] = $cash_register_data->id;

        if (isset($data['created_at']))
            $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at']))) . ' ' . date("H:i:s");
        else
            $data['created_at'] = date("Y-m-d H:i:s");
        //return dd($data);

        //set the paid_amount value to $new_data variable
        $new_data['paid_amount'] = $data['paid_amount'];

        if (is_array($data['paid_amount'])) {
            $data['paid_amount'] = array_sum($data['paid_amount']);
        }

        if ($data['pos']) {
            if (!isset($data['reference_no']))
                $data['reference_no'] = 'E' . date('dmy') . time();

            foreach ($new_data['paid_amount'] as $paid_amount) {
                $balance = $data['grand_total'] - $paid_amount;
            }
            if (is_array($data['paid_amount'])) {
                $data['paid_amount'] = array_sum($data['paid_amount']);
            }
            if ($balance > 0 || $balance < 0)
                $data['payment_status'] = 2;
            else
                $data['payment_status'] = 4;

            if ($data['draft']) {
                $lims_sale_data = Sale::find($data['sale_id']);
                $lims_product_sale_data = Product_Sale::where('sale_id', $data['sale_id'])->get();
                foreach ($lims_product_sale_data as $product_sale_data) {
                    $product_sale_data->delete();
                }
                $lims_sale_data->delete();
            }
        } else {
            if (!isset($data['reference_no']))
                $data['reference_no'] = 'E' . date('dmy') . time();
        }

        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            }
            $data['document'] = $documentName;
        }
        if ($data['coupon_active']) {
            $lims_coupon_data = Coupon::find($data['coupon_id']);
            $lims_coupon_data->used += 1;
            $lims_coupon_data->save();
        }
        if (isset($data['table_id'])) {
            $latest_sale = Sale::whereNotNull('table_id')->whereDate('created_at', date('Y-m-d'))->where('warehouse_id', $data['warehouse_id'])->select('queue')->orderBy('id', 'desc')->first();
            if ($latest_sale)
                $data['queue'] = $latest_sale->queue + 1;
            else
                $data['queue'] = 1;
        }
        //inserting data to sales table

        $lims_sale_data = Sale::create($data);

        // add the $new_data variable value to $data['paid_amount'] variable
        $data['paid_amount'] = $new_data['paid_amount'];

        //inserting data for custom fields
        $custom_field_data = [];
        $custom_fields = CustomField::where('belongs_to', 'sale')->select('name', 'type')->get();
        foreach ($custom_fields as $type => $custom_field) {
            $field_name = str_replace(' ', '_', strtolower($custom_field->name));
            if (isset($data[$field_name])) {
                if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                    $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                else
                    $custom_field_data[$field_name] = $data[$field_name];
            }
        }

        if (count($custom_field_data))
            DB::table('sales')->where('id', $lims_sale_data->id)->update($custom_field_data);
        $lims_customer_data = Customer::find($data['customer_id']);
        $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
        //checking if customer gets some points or not
        if ($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active && $data['grand_total'] >= $lims_reward_point_setting_data->minimum_amount) {
            $point = (int) ($data['grand_total'] / $lims_reward_point_setting_data->per_point_amount);
            $lims_customer_data->points += $point;
            $lims_customer_data->save();
        }

        //collecting male data
        $mail_data['email'] = $lims_customer_data->email;
        $mail_data['reference_no'] = $lims_sale_data->reference_no;
        $mail_data['sale_status'] = $lims_sale_data->sale_status;
        $mail_data['payment_status'] = $lims_sale_data->payment_status;
        $mail_data['total_qty'] = $lims_sale_data->total_qty;
        $mail_data['total_price'] = $lims_sale_data->total_price;
        $mail_data['order_tax'] = $lims_sale_data->order_tax;
        $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
        $mail_data['order_discount'] = $lims_sale_data->order_discount;
        $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
        $mail_data['grand_total'] = $lims_sale_data->grand_total;
        $mail_data['paid_amount'] = $lims_sale_data->paid_amount;

        $product_id = $data['product_id'];
        $product_batch_id = $data['product_batch_id'];
        $imei_number = $data['imei_number'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $sale_unit = $data['sale_unit'];
        $net_unit_price = $data['net_unit_price'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_sale = [];

        $purc = 0;

        foreach ($product_id as $i => $id) {
            $lims_product_data = Product::where('id', $id)->first();
            $product_sale['variant_id'] = null;
            $product_sale['supplier_id'] = $lims_product_data->supplier_id;
            $product_sale['product_batch_id'] = null;
            // deduct product quantity
            {
                $product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['warehouse_id', $data['warehouse_id']]
                ])->select('id', 'qty')->first();
                
                $product_warehouse_data->qty -= $qty[$i];
                $product_warehouse_data->save();
            }

            if ($lims_product_data->type == 'combo' && $data['sale_status'] == 1) {
                if (!in_array('manufacturing', explode(',', config('addons')))) {
                    $product_list = explode(",", $lims_product_data->product_list);
                    $variant_list = explode(",", $lims_product_data->variant_list);
                    if ($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    $qty_list = explode(",", $lims_product_data->qty_list);
                    $price_list = explode(",", $lims_product_data->price_list);

                    foreach ($product_list as $key => $child_id) {
                        $child_data = Product::find($child_id);
                        if (count($variant_list) && $variant_list[$key]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$key]]
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$key]],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();

                            $child_product_variant_data->qty -= $qty[$i] * $qty_list[$key];
                            $child_product_variant_data->save();
                        } else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();
                        }

                        $child_data->qty -= $qty[$i] * $qty_list[$key];
                        $child_warehouse_data->qty -= $qty[$i] * $qty_list[$key];

                        $child_data->save();
                        $child_warehouse_data->save();
                    }
                }
            }

            if ($sale_unit[$i] != 'n/a') {
                $lims_sale_unit_data = Unit::where('unit_name', $sale_unit[$i])->first();
                $sale_unit_id = $lims_sale_unit_data->id;
                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($id, $product_code[$i])->first();
                    $product_sale['variant_id'] = $lims_product_variant_data->variant_id;
                }
                if ($lims_product_data->is_batch && $product_batch_id[$i]) {
                    $product_sale['product_batch_id'] = $product_batch_id[$i];
                }

                if ($data['sale_status'] == 1) {
                    if ($lims_sale_unit_data->operator == '*')
                        $quantity = $qty[$i] * $lims_sale_unit_data->operation_value;
                    elseif ($lims_sale_unit_data->operator == '/')
                        $quantity = $qty[$i] / $lims_sale_unit_data->operation_value;
                    //deduct quantity
                    $lims_product_data->qty = $lims_product_data->qty - $quantity;
                    $lims_product_data->save();
                    //deduct product variant quantity if exist
                    if ($lims_product_data->is_variant) {
                        $lims_product_variant_data->qty -= $quantity;
                        $lims_product_variant_data->save();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($id, $lims_product_variant_data->variant_id, $data['warehouse_id'])->first();
                    } elseif ($product_batch_id[$i]) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_batch_id', $product_batch_id[$i]],
                            ['warehouse_id', $data['warehouse_id']]
                        ])->first();
                        $lims_product_batch_data = ProductBatch::find($product_batch_id[$i]);
                        //deduct product batch quantity
                        $lims_product_batch_data->qty -= $quantity;
                        $lims_product_batch_data->save();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($id, $data['warehouse_id'])->first();
                    }
                    //deduct quantity from warehouse
                    $lims_product_warehouse_data->qty -= $quantity;
                    $lims_product_warehouse_data->save();
                }
            } else
                $sale_unit_id = 0;

            if ($product_sale['variant_id']) {
                $variant_data = Variant::select('name')->find($product_sale['variant_id']);
                $mail_data['products'][$i] = $lims_product_data->name . ' [' . $variant_data->name . ']';
            } else
                $mail_data['products'][$i] = $lims_product_data->name;
            //deduct imei number if available
            if ($imei_number[$i] && !str_contains($imei_number[$i], "null") && $data['sale_status'] == 1) {
                $imei_numbers = explode(",", $imei_number[$i]);
                $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                foreach ($imei_numbers as $number) {
                    if (($j = array_search($number, $all_imei_numbers)) !== false) {
                        unset($all_imei_numbers[$j]);
                    }
                }
                $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                $lims_product_warehouse_data->save();
            }
            if ($lims_product_data->type == 'digital')
                $mail_data['file'][$i] = url('/product/files') . '/' . $lims_product_data->file;
            else
                $mail_data['file'][$i] = '';
            if ($sale_unit_id)
                $mail_data['unit'][$i] = $lims_sale_unit_data->unit_code;
            else
                $mail_data['unit'][$i] = '';

            // krishna singh - https://linktr.ee/iamsinghkrishna

            // Products Sale Create
            $product_sale['sale_id'] = $lims_sale_data->id;
            $product_sale['product_id'] = $id;
            $product_sale['imei_number'] = $imei_number[$i];
            $product_sale['qty'] = $mail_data['qty'][$i] = $qty[$i];
            $product_sale['sale_unit_id'] = $sale_unit_id;
            $product_sale['net_unit_price'] = $net_unit_price[$i];
            $product_sale['discount'] = $discount[$i];
            $product_sale['tax_rate'] = $tax_rate[$i];
            $product_sale['tax'] = $tax[$i];
            $product_sale['total'] = $mail_data['total'][$i] = $total[$i];
            Product_Sale::create($product_sale);

            // Purchase Create
            // inserting data to purchase table (krishna)
            if($purc == 0){
                $purchase_data = [];
                $purchase_data['reference_no'] = $lims_sale_data->reference_no;
                $purchase_data['user_id'] = $lims_sale_data->user_id;
                $purchase_data['warehouse_id'] = $lims_sale_data->warehouse_id;
                $purchase_data['supplier_id'] = $lims_product_data->supplier_id;
                $purchase_data['currency_id'] = 1;
                $purchase_data['exchange_rate'] = $lims_sale_data->exchange_rate;
                $purchase_data['item'] = $lims_sale_data->item;
                $purchase_data['total_qty'] = $lims_sale_data->total_qty;
                $purchase_data['total_discount'] = $lims_sale_data->total_discount;
                $purchase_data['total_tax'] = $lims_sale_data->total_tax;
                $purchase_data['total_cost'] = $lims_sale_data->total_price;
                $purchase_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
                $purchase_data['order_tax'] = $lims_sale_data->order_tax;
                $purchase_data['order_discount'] = $lims_sale_data->order_discount;
                $purchase_data['shipping_cost'] = $lims_sale_data->shipping_cost;
                $purchase_data['grand_total'] = (($lims_sale_data->total_price - $lims_sale_data->order_discount) + ($lims_sale_data->shipping_cost + ($lims_sale_data->order_tax - $lims_sale_data->order_discount)));
                $purchase_data['paid_amount'] = $lims_sale_data->paid_amount;
                $purchase_data['status'] = 2;
                $purchase_data['payment_status'] = $lims_sale_data->payment_status;
                $purchase_data['document'] = $lims_sale_data->document;
                $purchase_data['note'] = $lims_sale_data->sale_note;
                $lims_purchase_data = Purchase::create($purchase_data);
                $purchase_id = $lims_purchase_data->id;
                $purc++;
            }
            // Product Purchase Create
            // inserting data into product purchase table (krishna)
            $product_purchase_data = [];
            $product_purchase_data['purchase_id'] = $purchase_id;
            $product_purchase_data['product_id'] = $id;
            $product_purchase_data['product_batch_id'] = 0;
            $product_purchase_data['variant_id'] = 0;
            $product_purchase_data['imei_number'] = $imei_number[$i];
            $product_purchase_data['qty'] = $qty[$i];
            $product_purchase_data['recieved'] = 0;
            $product_purchase_data['return_qty'] = 0;
            $product_purchase_data['purchase_unit_id'] = $sale_unit_id;
            $product_purchase_data['net_unit_cost'] = $net_unit_price[$i];
            $product_purchase_data['discount'] = $discount[$i];
            $product_purchase_data['tax_rate'] = $tax_rate[$i];
            $product_purchase_data['order_tax'] = 0;
            $product_purchase_data['tax'] = $tax[$i];
            $product_purchase_data['total'] = $total[$i];
            $lims_product_purchase_data = ProductPurchase::create($product_purchase_data);

            // Payment Create
            // inserting data into payment table (krishna)
            $payment_data = [];
            $payment_data['payment_reference'] = $lims_sale_data->reference_no;
            $payment_data['user_id'] = $lims_sale_data->user_id;
            $payment_data['purchase_id'] = $purchase_id;
            $payment_data['sale_id'] = $id;
            $payment_data['cash_register_id'] = 1;
            $payment_data['account_id'] = 1;
            $payment_data['payment_receiver'] = 1;
            $payment_data['amount'] = $total[$i];
            $payment_data['used_points'] = 0;
            $payment_data['change'] = 0;
            $payment_data['paying_method'] = 1;
            $payment_data['payment_note'] = $lims_sale_data->sale_note;
            $lims_payment_data = Payment::create($payment_data);

            // krishna singh - https://linktr.ee/iamsinghkrishna
        }
        if ($data['sale_status'] == 3)
            $message = 'Sale successfully added to draft';
        else
            $message = ' Sale created successfully';
        $mail_setting = MailSetting::latest()->first();
        if ($mail_data['email'] && $data['sale_status'] == 1 && $mail_setting) {
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
                /*$log_data['message'] = Auth::user()->name . ' has created a sale. Reference No: ' .$lims_sale_data->reference_no;
                $admin_email = 'ashfaqdev.php@gmail.com';
                Mail::to($admin_email)->send(new LogMessage($log_data));*/
            } catch (\Exception $e) {
                $message = ' Sale created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }

        if ($data['payment_status'] == 3 || $data['payment_status'] == 4 || ($data['payment_status'] == 2 && $data['pos'] && $data['paid_amount'] > 0)) {
            foreach ($data['paid_by_id'] as $key => $value) {
                if ($data['paid_amount'][$key] > 0) {
                    $lims_payment_data = new Payment();
                    $lims_payment_data->user_id = Auth::id();
                    $paying_method = '';

                    if ($data['paid_by_id'][$key] == 1)
                        $paying_method = 'Cash';
                    elseif ($data['paid_by_id'][$key] == 2) {
                        $paying_method = 'Gift Card';
                    } elseif ($data['paid_by_id'][$key] == 3)
                        $paying_method = 'Credit Card';
                    elseif ($data['paid_by_id'][$key] == 4)
                        $paying_method = 'Cheque';
                    elseif ($data['paid_by_id'][$key] == 5)
                        $paying_method = 'Paypal';
                    elseif ($data['paid_by_id'][$key] == 6)
                        $paying_method = 'Deposit';
                    elseif ($data['paid_by_id'][$key] == 7) {
                        $paying_method = 'Points';
                        $lims_payment_data->used_points = $data['used_points'];
                    } elseif ($data['paid_by_id'][$key] == 8) {
                        $paying_method = 'Pesapal';
                    } else {
                        $paying_method = $data['paid_by_id'][$key]; // For string values like 'Pesapal', 'Stripe', etc.
                    }

                    if ($cash_register_data)
                        $lims_payment_data->cash_register_id = $cash_register_data->id;
                    $lims_account_data = Account::where('is_default', true)->first();
                    $lims_payment_data->account_id = $lims_account_data->id;
                    $lims_payment_data->sale_id = $lims_sale_data->id;
                    $data['payment_reference'] = 'spr-' . date("Ymd") . '-' . date("his");
                    $lims_payment_data->payment_reference = $data['payment_reference'];
                    $lims_payment_data->amount = $data['paid_amount'][$key];
                    $lims_payment_data->change = $data['paying_amount'][0] - $data['paid_amount'][$key];
                    $lims_payment_data->paying_method = $paying_method;
                    $lims_payment_data->payment_note = $data['payment_note'];
                    if (isset($data['payment_receiver'])) {
                        $lims_payment_data->payment_receiver = $data['payment_receiver'];
                    }
                    $lims_payment_data->save();

                    if (isset($data['cash']) && $data['cash'] > 0 && isset($data['bank']) && $data['bank'])

                        $lims_payment_data = Payment::latest()->first();
                    $data['payment_id'] = $lims_payment_data->id;
                    $lims_pos_setting_data = PosSetting::latest()->first();
                    // Check Payment Method is Card
                    if ($paying_method == 'Credit Card') {
                        $cardDetails = [];
                        $cardDetails['card_number'] = $data['card_number'];
                        $cardDetails['card_holder_name'] = $data['card_holder_name'];
                        $cardDetails['card_type'] = $data['card_type'];
                        $data['charge_id'] = '12345';
                        $data['data'] = json_encode($cardDetails);

                        PaymentWithCreditCard::create($data);
                    } elseif ($paying_method == 'Gift Card') {
                        $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                        $lims_gift_card_data->expense += $data['paid_amount'][$key];
                        $lims_gift_card_data->save();
                        PaymentWithGiftCard::create($data);
                    } elseif ($paying_method == 'Cheque') {
                        PaymentWithCheque::create($data);
                    } elseif ($paying_method == 'Deposit') {
                        $lims_customer_data->expense += $data['paid_amount'][$key];
                        $lims_customer_data->save();
                    } elseif ($paying_method == 'Points') {
                        $lims_customer_data->points -= $data['used_points'];
                        $lims_customer_data->save();
                    } elseif ($paying_method == 'Pesapal') {
                        $redirectUrl = $this->submitOrderRequest($lims_customer_data, $data['paid_amount'][$key]); // Assume this returns a URL
                        $lims_customer_data->save();

                        return response()->json([
                            'payment_method' => 'pesapal',
                            'redirect_url' => $redirectUrl,
                        ]);
                    }
                }
            }
        }
    

        //sms send start
        $smsData = [];

        $smsTemplate = SmsTemplate::where('is_default', 1)->latest()->first();
        $smsProvider = ExternalService::where('active', true)->where('type', 'sms')->first();
        if ($smsProvider && $smsTemplate && $lims_pos_setting_data['send_sms'] == 1) {
            $smsData['type'] = 'onsite';
            $smsData['template_id'] = $smsTemplate['id'];
            $smsData['sale_status'] = $data['sale_status'];
            $smsData['payment_status'] = $data['payment_status'];
            $smsData['customer_id'] = $data['customer_id'];
            $smsData['reference_no'] = $data['reference_no'];
            $this->_smsModel->initialize($smsData);
        }
        //sms send end

        //api calling code
        if ($lims_sale_data->sale_status == '1' && isset($data['draft']) && $data['draft'])
            return redirect('sales/gen_invoice/' . $lims_sale_data->id);
        elseif ($lims_sale_data->sale_status == '1')
            return $lims_sale_data->id;
        elseif ($data['pos'])
            return redirect('pos')->with('message', $message);
        else
            return redirect('sales')->with('message', $message);

    }

    public function getSoldItem($id)
    {
        $sale = Sale::select('warehouse_id')->find($id);
        $product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $data = [];
        $data['amount'] = $sale->shipping_cost - $sale->sale_discount;
        $flag = 0;
        foreach ($product_sale_data as $key => $product_sale) {
            $product = Product::select('type', 'name', 'code', 'product_list', 'qty_list')->find($product_sale->product_id);
            $data[$key]['combo_in_stock'] = 1;
            $data[$key]['child_info'] = '';
            if ($product->type == 'combo') {
                $child_ids = explode(",", $product->product_list);
                $qty_list = explode(",", $product->qty_list);
                foreach ($child_ids as $index => $child_id) {
                    $child_product = Product::select('name', 'code')->find($child_id);

                    $child_stock = $child_product->initial_qty + $child_product->received_qty;
                    $required_stock = $qty_list[$index] * $product_sale->qty;
                    if ($required_stock > $child_stock) {
                        $data[$key]['combo_in_stock'] = 0;
                        $data[$key]['child_info'] = $child_product->name . '[' . $child_product->code . '] does not have enough stock. In stock: ' . $child_stock;
                        break;
                    }
                }
            }
            $data[$key]['product_id'] = $product_sale->product_id . '|' . $product_sale->variant_id;
            $data[$key]['type'] = $product->type;
            if ($product_sale->variant_id) {
                $variant_data = Variant::select('name')->find($product_sale->variant_id);
                $product_variant_data = ProductVariant::select('item_code')->where([
                    ['product_id', $product_sale->product_id],
                    ['variant_id', $product_sale->variant_id]
                ])->first();
                $data[$key]['name'] = $product->name . ' [' . $variant_data->name . ']';
                $product->code = $product_variant_data->item_code;
            } else
                $data[$key]['name'] = $product->name;
            $data[$key]['qty'] = $product_sale->qty;
            $data[$key]['code'] = $product->code;
            $data[$key]['sold_qty'] = $product_sale->qty;
            $product_warehouse = Product_Warehouse::where([
                ['product_id', $product_sale->product_id],
                ['warehouse_id', $sale->warehouse_id]
            ])->first();
            if ($product_warehouse) {
                $data[$key]['stock'] = $product_warehouse->qty;
            } else {
                $data[$key]['stock'] = $product->qty;
            }

            $data[$key]['unit_price'] = $product_sale->total / $product_sale->qty;
            $data[$key]['total_price'] = $product_sale->total;
            if ($product_sale->is_packing) {
                $data['amount'] = 0;
            } else {
                $flag = 1;
            }
            $data[$key]['is_packing'] = $product_sale->is_packing;
        }
        if ($flag)
            return $data;
        else
            return 'All the items of this sale has already been packed';
    }
    public function sendSMS(Request $request)
    {
        $data = $request->all();

        //sms send start
        // $smsTemplate = SmsTemplate::where('is_default',1)->latest()->first();

        $smsProvider = ExternalService::where('active', true)->where('type', 'sms')->first();
        if ($smsProvider) {
            $data['type'] = 'onsite';
            $this->_smsModel->initialize($data);
            return redirect()->back();
        }
        //sms send end
        else {
            return redirect()->back()->with('not_permitted', 'Please setup your SMS API first!');
        }
    }

    public function sendMail(Request $request)
    {
        $data = $request->all();
        $lims_sale_data = Sale::find($data['sale_id']);
        $lims_product_sale_data = Product_Sale::where('sale_id', $data['sale_id'])->get();
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $mail_setting = MailSetting::latest()->first();

        if (!$mail_setting) {
            return $this->setErrorMessage('Please Setup Your Mail Credentials First.');
        } else if ($lims_customer_data->email) {
            //collecting male data
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;

            foreach ($lims_product_sale_data as $key => $product_sale_data) {
                $lims_product_data = Product::find($product_sale_data->product_id);
                if ($product_sale_data->variant_id) {
                    $variant_data = Variant::select('name')->find($product_sale_data->variant_id);
                    $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
                } else
                    $mail_data['products'][$key] = $lims_product_data->name;
                if ($lims_product_data->type == 'digital')
                    $mail_data['file'][$key] = url('/product/files') . '/' . $lims_product_data->file;
                else
                    $mail_data['file'][$key] = '';
                if ($product_sale_data->sale_unit_id) {
                    $lims_unit_data = Unit::find($product_sale_data->sale_unit_id);
                    $mail_data['unit'][$key] = $lims_unit_data->unit_code;
                } else
                    $mail_data['unit'][$key] = '';

                $mail_data['qty'][$key] = $product_sale_data->qty;
                $mail_data['total'][$key] = $product_sale_data->qty;
            }
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
                return $this->setSuccessMessage('Mail sent successfully');
            } catch (\Exception $e) {
                return $this->setErrorMessage('Please Setup Your Mail Credentials First.');
            }
        } else
            return $this->setErrorMessage('Customer doesnt have email!');
    }

    public function whatsappNotificationSend(Request $request)
    {

        $data = $request->all();

        $lims_general_setting_data = GeneralSetting::latest()->first();
        $company = $lims_general_setting_data->company_name;
        // Find the customer by ID
        $customer = Customer::find($data['customer_id']);
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Find the sale record by sale_id
        $sale = Sale::find($data['sale_id']);
        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        $name = $customer->name;
        $phone = $customer->phone_number;
        $referenceNo = $sale->reference_no; // Get the reference number from the sale

        // Create personalized text message
        $text = urlencode(trans('file.Dear') . ' ' . $name . ', ' .
            trans('file.Thank you for your purchase! Your invoice number is') . ' ' . $referenceNo . "\n" .
            trans('file.If you have any questions or concerns, please don\'t hesitate to reach out to us. We are here to help!') . "\n" .
            trans('file.Best regards') . ",\n" .
            $company);

        // Construct WhatsApp URL with customer phone and personalized message
        $url = "https://web.whatsapp.com/send/?phone=$phone&text=$text";

        // Redirect to WhatsApp
        return redirect()->away($url);
    }

    public function paypalSuccess(Request $request)
    {
        $lims_sale_data = Sale::latest()->first();
        $lims_payment_data = Payment::latest()->first();
        $lims_product_sale_data = Product_Sale::where('sale_id', $lims_sale_data->id)->get();
        $provider = new ExpressCheckout;
        $token = $request->token;
        $payerID = $request->PayerID;
        $paypal_data['items'] = [];
        foreach ($lims_product_sale_data as $key => $product_sale_data) {
            $lims_product_data = Product::find($product_sale_data->product_id);
            $paypal_data['items'][] = [
                'name' => $lims_product_data->name,
                'price' => ($product_sale_data->total / $product_sale_data->qty),
                'qty' => $product_sale_data->qty
            ];
        }
        $paypal_data['items'][] = [
            'name' => 'order tax',
            'price' => $lims_sale_data->order_tax,
            'qty' => 1
        ];
        $paypal_data['items'][] = [
            'name' => 'order discount',
            'price' => $lims_sale_data->order_discount * (-1),
            'qty' => 1
        ];
        $paypal_data['items'][] = [
            'name' => 'shipping cost',
            'price' => $lims_sale_data->shipping_cost,
            'qty' => 1
        ];
        if ($lims_sale_data->grand_total != $lims_sale_data->paid_amount) {
            $paypal_data['items'][] = [
                'name' => 'Due',
                'price' => ($lims_sale_data->grand_total - $lims_sale_data->paid_amount) * (-1),
                'qty' => 1
            ];
        }

        $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
        $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
        $paypal_data['return_url'] = url('/sale/paypalSuccess');
        $paypal_data['cancel_url'] = url('/sale/create');

        $total = 0;
        foreach ($paypal_data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $paypal_data['total'] = $lims_sale_data->paid_amount;
        $response = $provider->getExpressCheckoutDetails($token);
        $response = $provider->doExpressCheckoutPayment($paypal_data, $token, $payerID);
        $data['payment_id'] = $lims_payment_data->id;
        $data['transaction_id'] = $response['PAYMENTINFO_0_TRANSACTIONID'];
        PaymentWithPaypal::create($data);
        return redirect('sales')->with('message', 'Sales created successfully');
    }

    public function paypalPaymentSuccess(Request $request, $id)
    {
        $lims_payment_data = Payment::find($id);
        $provider = new ExpressCheckout;
        $token = $request->token;
        $payerID = $request->PayerID;
        $paypal_data['items'] = [];
        $paypal_data['items'][] = [
            'name' => 'Paid Amount',
            'price' => $lims_payment_data->amount,
            'qty' => 1
        ];
        $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
        $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
        $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess');
        $paypal_data['cancel_url'] = url('/sale');

        $total = 0;
        foreach ($paypal_data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $paypal_data['total'] = $total;
        $response = $provider->getExpressCheckoutDetails($token);
        $response = $provider->doExpressCheckoutPayment($paypal_data, $token, $payerID);
        $data['payment_id'] = $lims_payment_data->id;
        $data['transaction_id'] = $response['PAYMENTINFO_0_TRANSACTIONID'];
        PaymentWithPaypal::create($data);
        return redirect('sales')->with('message', 'Payment created successfully');
    }

    public function getProduct($id)
    {
        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');
        if (config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
                ['product_warehouse.qty', '>', 0]
            ]);
        } else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id]
            ]);
        }

        $lims_product_warehouse_data = $query->whereNull('products.is_imei')
            ->whereNull('product_warehouse.variant_id')
            ->whereNull('product_warehouse.product_batch_id')
            ->select('product_warehouse.*', 'products.name', 'products.code', 'products.type', 'products.product_list', 'products.qty_list', 'products.is_embeded')
            ->get();
        //return $lims_product_warehouse_data;
        config()->set('database.connections.mysql.strict', false);
        \DB::reconnect(); //important as the existing connection if any would be in strict mode

        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');

        if (config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
                ['product_warehouse.qty', '>', 0]
            ]);
        } else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id]
            ]);
        }

        $lims_product_with_batch_warehouse_data = $query->whereNull('product_warehouse.variant_id')
            ->whereNotNull('product_warehouse.product_batch_id')
            ->select('product_warehouse.*', 'products.name', 'products.code', 'products.type', 'products.product_list', 'products.qty_list', 'products.is_embeded')
            ->groupBy('product_warehouse.product_id')
            ->get();

        //now changing back the strict ON
        config()->set('database.connections.mysql.strict', true);
        \DB::reconnect();

        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');
        if (config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
                ['product_warehouse.qty', '>', 0]
            ]);
        } else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
            ]);
        }
        $lims_product_with_variant_warehouse_data = $query->whereNotNull('product_warehouse.variant_id')
            ->select('product_warehouse.*', 'products.name', 'products.code', 'products.type', 'products.product_list', 'products.qty_list', 'products.is_embeded')
            ->get();

        $lims_product_with_imei_warehouse_data = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->where([
                ['products.is_active', true],
                ['products.is_imei', true],
                ['product_warehouse.warehouse_id', $id],
                ['product_warehouse.qty', '>', 0]
            ])
            ->whereNull('product_warehouse.variant_id')
            ->whereNotNull('product_warehouse.imei_number')
            ->select('product_warehouse.*', 'products.is_embeded')
            ->groupBy('product_warehouse.product_id')
            ->get();

        $product_code = [];
        $product_name = [];
        $product_qty = [];
        $product_type = [];
        $product_id = [];
        $product_list = [];
        $qty_list = [];
        $product_price = [];
        $batch_no = [];
        $product_batch_id = [];
        $expired_date = [];
        $is_embeded = [];
        $imei_number = [];

        //product without variant
        foreach ($lims_product_warehouse_data as $product_warehouse) {
            $product_qty[] = $product_warehouse->qty;
            $product_price[] = $product_warehouse->price;
            $product_code[] = $product_warehouse->code;
            $product_name[] = htmlspecialchars($product_warehouse->name);
            $product_type[] = $product_warehouse->type;
            $product_id[] = $product_warehouse->product_id;
            $product_list[] = $product_warehouse->product_list;
            $qty_list[] = $product_warehouse->qty_list;
            $batch_no[] = null;
            $product_batch_id[] = null;
            $expired_date[] = null;
            if ($product_warehouse->is_embeded)
                $is_embeded[] = $product_warehouse->is_embeded;
            else
                $is_embeded[] = 0;
            $imei_number[] = null;

        }
        //product with batches
        foreach ($lims_product_with_batch_warehouse_data as $product_warehouse) {
            $product_qty[] = $product_warehouse->qty;
            $product_price[] = $product_warehouse->price;
            $product_code[] = $product_warehouse->code;
            $product_name[] = htmlspecialchars($product_warehouse->name);
            $product_type[] = $product_warehouse->type;
            $product_id[] = $product_warehouse->product_id;
            $product_list[] = $product_warehouse->product_list;
            $qty_list[] = $product_warehouse->qty_list;
            $product_batch_data = ProductBatch::select('id', 'batch_no', 'expired_date')->find($product_warehouse->product_batch_id);
            $batch_no[] = $product_batch_data->batch_no;
            $product_batch_id[] = $product_batch_data->id;
            $expired_date[] = date(config('date_format'), strtotime($product_batch_data->expired_date));
            if ($product_warehouse->is_embeded)
                $is_embeded[] = $product_warehouse->is_embeded;
            else
                $is_embeded[] = 0;

            $imei_number[] = null;
        }
        //product with variant
        foreach ($lims_product_with_variant_warehouse_data as $product_warehouse) {
            $product_qty[] = $product_warehouse->qty;
            $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_warehouse->product_id, $product_warehouse->variant_id)->first();
            if ($lims_product_variant_data) {
                $product_code[] = $lims_product_variant_data->item_code;
                $product_name[] = htmlspecialchars($product_warehouse->name);
                $product_type[] = $product_warehouse->type;
                $product_id[] = $product_warehouse->product_id;
                $product_list[] = $product_warehouse->product_list;
                $qty_list[] = $product_warehouse->qty_list;
                $batch_no[] = null;
                $product_batch_id[] = null;
                $expired_date[] = null;
                if ($product_warehouse->is_embeded)
                    $is_embeded[] = $product_warehouse->is_embeded;
                else
                    $is_embeded[] = 0;

                $imei_number[] = null;

            }
        }

        //product with imei
        foreach ($lims_product_with_imei_warehouse_data as $product_warehouse) {
            $imei_numbers = explode(",", $product_warehouse->imei_number);
            foreach ($imei_numbers as $key => $number) {
                $product_qty[] = $product_warehouse->qty;
                $product_price[] = $product_warehouse->price;
                $lims_product_data = Product::find($product_warehouse->product_id);
                $product_code[] = $lims_product_data->code;
                $product_name[] = htmlspecialchars($lims_product_data->name);
                $product_type[] = $lims_product_data->type;
                $product_id[] = $lims_product_data->id;
                $product_list[] = $lims_product_data->product_list;
                $qty_list[] = $lims_product_data->qty_list;
                $batch_no[] = null;
                $product_batch_id[] = null;
                $expired_date[] = null;
                $is_embeded[] = 0;
                $imei_number[] = $number;
            }
        }

        //retrieve product with type of digital and service
        $lims_product_data = Product::whereNotIn('type', ['standard', 'combo'])->where('is_active', true)->get();
        foreach ($lims_product_data as $product) {
            $product_qty[] = $product->qty;
            $product_code[] = $product->code;
            $product_name[] = $product->name;
            $product_type[] = $product->type;
            $product_id[] = $product->id;
            $product_list[] = $product->product_list;
            $qty_list[] = $product->qty_list;
            $batch_no[] = null;
            $product_batch_id[] = null;
            $expired_date[] = null;
            $is_embeded[] = 0;
            $imei_number[] = null;

        }
        $product_data = [$product_code, $product_name, $product_qty, $product_type, $product_id, $product_list, $qty_list, $product_price, $batch_no, $product_batch_id, $expired_date, $is_embeded, $imei_number];
        //return $product_id;
        return $product_data;
    }

    public function posSale($id = '')
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-add')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';

            $lims_customer_list = Cache::remember('customer_list', 60 * 60 * 24, function () {
                return Customer::where('is_active', true)->get();
            });
            $lims_customer_group_all = Cache::remember('customer_group_list', 60 * 60 * 24, function () {
                return CustomerGroup::where('is_active', true)->get();
            });
            $lims_warehouse_list = Cache::remember('warehouse_list', 60 * 60 * 24 * 365, function () {
                return Warehouse::where('is_active', true)->get();
            });
            $lims_biller_list = Cache::remember('biller_list', 60 * 60 * 24 * 30, function () {
                return Biller::where('is_active', true)->get();
            });
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_tax_list = Cache::remember('tax_list', 60 * 60 * 24 * 30, function () {
                return Tax::where('is_active', true)->get();
            });

            $lims_pos_setting_data = Cache::remember('pos_setting', 60 * 60 * 24 * 30, function () {
                return PosSetting::latest()->first();
            });
            if ($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];
            $lims_brand_list = Cache::remember('brand_list', 60 * 60 * 24 * 30, function () {
                return Brand::where('is_active', true)->get();
            });
            $lims_category_list = Cache::remember('category_list', 60 * 60 * 24 * 30, function () {
                return Category::where('is_active', true)->get();
            });
            $lims_table_list = Cache::remember('table_list', 60 * 60 * 24 * 30, function () {
                return Table::where('is_active', true)->get();
            });

            $lims_coupon_list = Cache::remember('coupon_list', 60 * 60 * 24 * 30, function () {
                return Coupon::where('is_active', true)->get();
            });
            $flag = 0;

            $currency_list = Currency::where('is_active', true)->get();
            $numberOfInvoice = Sale::count();
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();

            if (isset($id)) {
                $lims_sale_data = Sale::find($id);
                $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
                return view('backend.sale.pos', compact('lims_sale_data', 'lims_product_sale_data', 'currency_list', 'role', 'all_permission', 'lims_customer_list', 'lims_customer_group_all', 'lims_warehouse_list', 'lims_reward_point_setting_data', 'lims_tax_list', 'lims_biller_list', 'lims_pos_setting_data', 'options', 'lims_brand_list', 'lims_category_list', 'lims_table_list', 'lims_coupon_list', 'flag', 'numberOfInvoice', 'custom_fields'));
            }

            return view('backend.sale.pos', compact('currency_list', 'role', 'all_permission', 'lims_customer_list', 'lims_customer_group_all', 'lims_warehouse_list', 'lims_reward_point_setting_data', 'lims_tax_list', 'lims_biller_list', 'lims_pos_setting_data', 'options', 'lims_brand_list', 'lims_category_list', 'lims_table_list', 'lims_coupon_list', 'flag', 'numberOfInvoice', 'custom_fields'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function recentSale()
    {
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $recent_sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')->where([
                ['sales.sale_status', 1],
                ['sales.user_id', Auth::id()]
            ])->orderBy('id', 'desc')->take(10)->get();
            return response()->json($recent_sale);
        } else {
            $recent_sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')->where('sale_status', 1)->orderBy('id', 'desc')->take(10)->get();
            return response()->json($recent_sale);
        }
    }

    public function recentDraft()
    {
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $recent_draft = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')->where([
                ['sales.sale_status', 3],
                ['sales.user_id', Auth::id()]
            ])->orderBy('id', 'desc')->take(10)->get();
            return response()->json($recent_draft);
        } else {
            $recent_draft = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')->where('sale_status', 3)->orderBy('id', 'desc')->take(10)->get();
            return response()->json($recent_draft);
        }
    }

    public function createSale($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-edit')) {
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_customer_group_all = CustomerGroup::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_sale_data = Sale::find($id);
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            $lims_product_list = Product::where([
                ['featured', 1],
                ['is_active', true]
            ])->get();
            foreach ($lims_product_list as $key => $product) {
                $images = explode(",", $product->image);
                if ($images[0])
                    $product->base_image = $images[0];
                else
                    $product->base_image = 'zummXD2dvAtI.png';
            }
            $product_number = count($lims_product_list);
            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_coupon_list = Coupon::where('is_active', true)->get();

            $currency_list = Currency::where('is_active', true)->get();

            return view('backend.sale.create_sale', compact('currency_list', 'lims_biller_list', 'lims_customer_list', 'lims_warehouse_list', 'lims_tax_list', 'lims_sale_data', 'lims_product_sale_data', 'lims_pos_setting_data', 'lims_brand_list', 'lims_category_list', 'lims_coupon_list', 'lims_product_list', 'product_number', 'lims_customer_group_all', 'lims_reward_point_setting_data'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function getProductByFilter($category_id, $brand_id)
    {
        $data = [];
        if (($category_id != 0) && ($brand_id != 0)) {
            $lims_product_list = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where([
                    ['products.is_active', true],
                    ['products.category_id', $category_id],
                    ['brand_id', $brand_id]
                ])->orWhere([
                        ['categories.parent_id', $category_id],
                        ['products.is_active', true],
                        ['brand_id', $brand_id]
                    ])->select('products.name', 'products.code', 'products.image')->get();
        } elseif (($category_id != 0) && ($brand_id == 0)) {
            $lims_product_list = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where([
                    ['products.is_active', true],
                    ['products.category_id', $category_id],
                ])->orWhere([
                        ['categories.parent_id', $category_id],
                        ['products.is_active', true]
                    ])->select('products.id', 'products.name', 'products.code', 'products.image', 'products.is_variant')->paginate(15);
        } elseif (($category_id == 0) && ($brand_id != 0)) {
            $lims_product_list = Product::where([
                ['brand_id', $brand_id],
                ['is_active', true]
            ])
                ->select('products.id', 'products.name', 'products.code', 'products.image', 'products.is_variant')
                ->paginate(15);
        } else
            $lims_product_list = Product::where('is_active', true)->get();

        $index = 0;
        foreach ($lims_product_list as $product) {
            if ($product->is_variant) {
                $lims_product_data = Product::select('id')->find($product->id);
                $lims_product_variant_data = $lims_product_data->variant()->orderBy('position')->get();
                foreach ($lims_product_variant_data as $key => $variant) {
                    $data['name'][$index] = $product->name . ' [' . $variant->name . ']';
                    $data['code'][$index] = $variant->pivot['item_code'];
                    $images = explode(",", $product->image);
                    $data['image'][$index] = $images[0];
                    $index++;
                }
            } else {
                $data['name'][$index] = $product->name;
                $data['code'][$index] = $product->code;
                $images = explode(",", $product->image);
                $data['image'][$index] = $images[0];
                $index++;
            }
        }

        return response()->json([
            'data' => $data,
            'next_page_url' => $lims_product_list->nextPageUrl(), // Return the next page URL for frontend to track
        ]);
    }

    public function getFeatured(Request $request)
    {
        $data = [];

        $lims_product_list = Product::where([
            ['is_active', true],
            ['featured', true]
        ])->select('products.id', 'products.name', 'products.code', 'products.image', 'products.is_variant')->paginate(15);

        $index = 0;
        foreach ($lims_product_list as $product) {
            if ($product->is_variant) {
                $lims_product_data = Product::select('id')->find($product->id);
                $lims_product_variant_data = $lims_product_data->variant()->orderBy('position')->get();
                foreach ($lims_product_variant_data as $key => $variant) {
                    $data['name'][$index] = $product->name . ' [' . $variant->name . ']';
                    $data['code'][$index] = $variant->pivot['item_code'];
                    $images = explode(",", $product->image);
                    $data['image'][$index] = $images[0];
                    $index++;
                }
            } else {
                $data['name'][$index] = $product->name;
                $data['code'][$index] = $product->code;
                $images = explode(",", $product->image);
                $data['image'][$index] = $images[0];
                $index++;
            }
        }
        return response()->json([
            'data' => $data,
            'next_page_url' => $lims_product_list->nextPageUrl(), // Return the next page URL for frontend to track
        ]);
    }

    public function getCustomerGroup($id)
    {
        $lims_customer_data = Customer::find($id);
        $lims_customer_group_data = CustomerGroup::find($lims_customer_data->customer_group_id);
        return $lims_customer_group_data->percentage;
    }

    public function limsProductSearch(Request $request)
    {
        $todayDate = date('Y-m-d');
        $product_data = explode("|", $request['data']);
        //return $product_data;
        // $product_code = explode("(", $request['data']);
        $product_info = explode("?", $request['data']);
        $customer_id = $product_info[1];
        // if(strpos($request['data'], '|')) {
        //     $product_info = explode("|", $request['data']);
        //     $embeded_code = $product_code[0];
        //     $product_code[0] = substr($embeded_code, 0, 7);
        //     $qty = substr($embeded_code, 7, 5) / 1000;
        // }
        // else {
        //     $product_code[0] = rtrim($product_code[0], " ");
        //     $qty = $product_info[2];
        // }
        if ($product_data[3][0]) {
            $product_info = explode("|", $request['data']);
            $embeded_code = $product_data[0];
            $product_data[0] = substr($embeded_code, 0, 7);
            $qty = substr($embeded_code, 7, 5) / 1000;
        } else {
            $qty = $product_info[2];
        }
        $product_variant_id = null;
        $all_discount = DB::table('discount_plan_customers')
            ->join('discount_plans', 'discount_plans.id', '=', 'discount_plan_customers.discount_plan_id')
            ->join('discount_plan_discounts', 'discount_plans.id', '=', 'discount_plan_discounts.discount_plan_id')
            ->join('discounts', 'discounts.id', '=', 'discount_plan_discounts.discount_id')
            ->where([
                ['discount_plans.is_active', true],
                ['discounts.is_active', true],
                ['discount_plan_customers.customer_id', $customer_id]
            ])
            ->select('discounts.*')
            ->get();
        // return $product_data[0];
        $lims_product_data = Product::where([
            ['code', $product_data[0]],
            ['is_active', true]
        ])->first();

        if (!$lims_product_data) {
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.*', 'product_variants.id as product_variant_id', 'product_variants.item_code', 'product_variants.additional_price')
                ->where([
                    ['product_variants.item_code', $product_data[0]],
                    ['products.is_active', true]
                ])->first();

            $product_variant_id = $lims_product_data->product_variant_id;
        }

        $product[] = $lims_product_data->name;
        if ($lims_product_data->is_variant) {
            $product[] = $lims_product_data->item_code;
            $lims_product_data->price += $lims_product_data->additional_price;
        } else
            $product[] = $lims_product_data->code;

        $no_discount = 1;
        foreach ($all_discount as $key => $discount) {
            $product_list = explode(",", $discount->product_list);
            $days = explode(",", $discount->days);

            if (($discount->applicable_for == 'All' || in_array($lims_product_data->id, $product_list)) && ($todayDate >= $discount->valid_from && $todayDate <= $discount->valid_till && in_array(date('D'), $days) && $qty >= $discount->minimum_qty && $qty <= $discount->maximum_qty)) {
                if ($discount->type == 'flat') {
                    $product[] = $lims_product_data->price - $discount->value;
                } elseif ($discount->type == 'percentage') {
                    $product[] = $lims_product_data->price - ($lims_product_data->price * ($discount->value / 100));
                }
                $no_discount = 0;
                break;
            } else {
                continue;
            }
        }

        if ($lims_product_data->promotion && $todayDate <= $lims_product_data->last_date && $no_discount) {
            $product[] = $lims_product_data->promotion_price;
        } elseif ($no_discount)
            $product[] = $lims_product_data->price;

        if ($lims_product_data->tax_id) {
            $lims_tax_data = Tax::find($lims_product_data->tax_id);
            $product[] = $lims_tax_data->rate;
            $product[] = $lims_tax_data->name;
        } else {
            $product[] = 0;
            $product[] = 'No Tax';
        }
        $product[] = $lims_product_data->tax_method;
        if ($lims_product_data->type == 'standard' || $lims_product_data->type == 'combo') {
            $units = Unit::where("base_unit", $lims_product_data->unit_id)
                ->orWhere('id', $lims_product_data->unit_id)
                ->get();
            $unit_name = array();
            $unit_operator = array();
            $unit_operation_value = array();
            foreach ($units as $unit) {
                if ($lims_product_data->sale_unit_id == $unit->id) {
                    array_unshift($unit_name, $unit->unit_name);
                    array_unshift($unit_operator, $unit->operator);
                    array_unshift($unit_operation_value, $unit->operation_value);
                } else {
                    $unit_name[] = $unit->unit_name;
                    $unit_operator[] = $unit->operator;
                    $unit_operation_value[] = $unit->operation_value;
                }
            }
            $product[] = implode(",", $unit_name) . ',';
            $product[] = implode(",", $unit_operator) . ',';
            $product[] = implode(",", $unit_operation_value) . ',';
        } else {
            $product[] = 'n/a' . ',';
            $product[] = 'n/a' . ',';
            $product[] = 'n/a' . ',';
        }
        $product[] = $lims_product_data->id;
        $product[] = $product_variant_id;
        $product[] = $lims_product_data->promotion;
        $product[] = $lims_product_data->is_batch;
        $product[] = $lims_product_data->is_imei;
        $product[] = $lims_product_data->is_variant;
        $product[] = $qty;
        $product[] = $lims_product_data->wholesale_price;
        $product[] = $lims_product_data->cost;
        $product[] = $product_data[2];
        $product[] = $lims_product_data->supplier_id;

        return $product;

    }

    public function checkDiscount(Request $request)
    {
        $qty = $request->input('qty');
        $customer_id = $request->input('customer_id');
        $warehouse_id = $request->input('warehouse_id');

        $lims_product_data = Product::select('id', 'price', 'promotion', 'promotion_price', 'last_date')->find($request->input('product_id'));
        $lims_product_warehouse_data = Product_Warehouse::where([
            ['product_id', $request->input('product_id')],
            ['warehouse_id', $warehouse_id]
        ])->first();
        if ($lims_product_warehouse_data && $lims_product_warehouse_data->price) {
            $lims_product_data->price = $lims_product_warehouse_data->price;
        }
        $todayDate = date('Y-m-d');
        $all_discount = DB::table('discount_plan_customers')
            ->join('discount_plans', 'discount_plans.id', '=', 'discount_plan_customers.discount_plan_id')
            ->join('discount_plan_discounts', 'discount_plans.id', '=', 'discount_plan_discounts.discount_plan_id')
            ->join('discounts', 'discounts.id', '=', 'discount_plan_discounts.discount_id')
            ->where([
                ['discount_plans.is_active', true],
                ['discounts.is_active', true],
                ['discount_plan_customers.customer_id', $customer_id]
            ])
            ->select('discounts.*')
            ->get();
        $no_discount = 1;
        foreach ($all_discount as $key => $discount) {
            $product_list = explode(",", $discount->product_list);
            $days = explode(",", $discount->days);

            if (($discount->applicable_for == 'All' || in_array($lims_product_data->id, $product_list)) && ($todayDate >= $discount->valid_from && $todayDate <= $discount->valid_till && in_array(date('D'), $days) && $qty >= $discount->minimum_qty && $qty <= $discount->maximum_qty)) {
                if ($discount->type == 'flat') {
                    $price = $lims_product_data->price - $discount->value;
                } elseif ($discount->type == 'percentage') {
                    $price = $lims_product_data->price - ($lims_product_data->price * ($discount->value / 100));
                }
                $no_discount = 0;
                break;
            } else {
                continue;
            }
        }

        if ($lims_product_data->promotion && $todayDate <= $lims_product_data->last_date && $no_discount) {
            $price = $lims_product_data->promotion_price;
        } elseif ($no_discount)
            $price = $lims_product_data->price;

        $data = [$price, $lims_product_data->promotion];
        return $data;
    }

    public function getGiftCard()
    {
        $gift_card = GiftCard::where("is_active", true)->whereDate('expired_date', '>=', date("Y-m-d"))->get(['id', 'card_no', 'amount', 'expense']);
        return json_encode($gift_card);
    }

    public function productSaleData($id)
    {
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        foreach ($lims_product_sale_data as $key => $product_sale_data) {
            $product = Product::find($product_sale_data->product_id);
            if ($product_sale_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                $product->code = $lims_product_variant_data->item_code;
            }
            $unit_data = Unit::find($product_sale_data->sale_unit_id);
            if ($unit_data) {
                $unit = $unit_data->unit_code;
            } else
                $unit = '';
            if ($product_sale_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no')->find($product_sale_data->product_batch_id);
                $product_sale[7][$key] = $product_batch_data->batch_no;
            } else
                $product_sale[7][$key] = 'N/A';
            $product_sale[0][$key] = $product->name . ' [' . $product->code . ']';
            $returned_imei_number_data = '';
            if ($product_sale_data->imei_number && !str_contains($product_sale_data->imei_number, "null")) {
                $product_sale[0][$key] .= '<br>IMEI or Serial Number: ' . $product_sale_data->imei_number;
                $returned_imei_number_data = DB::table('returns')
                    ->join('product_returns', 'returns.id', '=', 'product_returns.return_id')
                    ->where([
                        ['returns.sale_id', $id],
                        ['product_returns.product_id', $product_sale_data->product_id]
                    ])->select('product_returns.imei_number')
                    ->first();
            }
            $product_sale[1][$key] = $product_sale_data->qty;
            $product_sale[2][$key] = $unit;
            $product_sale[3][$key] = $product_sale_data->tax;
            $product_sale[4][$key] = $product_sale_data->tax_rate;
            $product_sale[5][$key] = $product_sale_data->discount;
            $product_sale[6][$key] = $product_sale_data->total;
            if ($returned_imei_number_data) {
                $product_sale[8][$key] = $product_sale_data->return_qty . '<br>IMEI or Serial Number: ' . $returned_imei_number_data->imei_number;
            } else
                $product_sale[8][$key] = $product_sale_data->return_qty;
            if ($product_sale_data->is_delivered)
                $product_sale[9][$key] = trans('file.Yes');
            else
                $product_sale[9][$key] = trans('file.No');
        }
        return $product_sale;
    }

    public function saleByCsv()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-add')) {
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $numberOfInvoice = Sale::count();
            return view('backend.sale.import', compact('lims_customer_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_tax_list', 'numberOfInvoice'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function importSale(Request $request)
    {
        //get the file
        $upload = $request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        //checking if this is a CSV file
        if ($ext != 'csv')
            return redirect()->back()->with('message', 'Please upload a CSV file');

        $filePath = $upload->getRealPath();
        $file_handle = fopen($filePath, 'r');
        $i = 0;
        //validate the file
        while (!feof($file_handle)) {
            $current_line = fgetcsv($file_handle);
            if ($current_line && $i > 0) {
                $product_data[] = Product::where('code', $current_line[0])->first();
                if (!$product_data[$i - 1])
                    return redirect()->back()->with('message', 'Product does not exist!');
                $unit[] = Unit::where('unit_code', $current_line[2])->first();
                if (!$unit[$i - 1] && $current_line[2] == 'n/a')
                    $unit[$i - 1] = 'n/a';
                elseif (!$unit[$i - 1]) {
                    return redirect()->back()->with('message', 'Sale unit does not exist!');
                }
                if (strtolower($current_line[5]) != "no tax") {
                    $tax[] = Tax::where('name', $current_line[5])->first();
                    if (!$tax[$i - 1])
                        return redirect()->back()->with('message', 'Tax name does not exist!');
                } else
                    $tax[$i - 1]['rate'] = 0;

                $qty[] = $current_line[1];
                $price[] = $current_line[3];
                $discount[] = $current_line[4];
            }
            $i++;
        }
        //return $unit;
        $data = $request->except('document');
        //$data['reference_no'] = 'sr-' . date("Ymd") . '-' . date("his");
        $data['reference_no'] = 'E' . date('dmy') . time();
        $data['user_id'] = Auth::user()->id;
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            }
            $data['document'] = $documentName;
        }
        $item = 0;
        $grand_total = $data['shipping_cost'];
        Sale::create($data);
        $lims_sale_data = Sale::latest()->first();
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);

        foreach ($product_data as $key => $product) {
            if ($product['tax_method'] == 1) {
                $net_unit_price = $price[$key] - $discount[$key];
                $product_tax = $net_unit_price * ($tax[$key]['rate'] / 100) * $qty[$key];
                $total = ($net_unit_price * $qty[$key]) + $product_tax;
            } elseif ($product['tax_method'] == 2) {
                $net_unit_price = (100 / (100 + $tax[$key]['rate'])) * ($price[$key] - $discount[$key]);
                $product_tax = ($price[$key] - $discount[$key] - $net_unit_price) * $qty[$key];
                $total = ($price[$key] - $discount[$key]) * $qty[$key];
            }
            if ($data['sale_status'] == 1 && $unit[$key] != 'n/a') {
                $sale_unit_id = $unit[$key]['id'];
                if ($unit[$key]['operator'] == '*')
                    $quantity = $qty[$key] * $unit[$key]['operation_value'];
                elseif ($unit[$key]['operator'] == '/')
                    $quantity = $qty[$key] / $unit[$key]['operation_value'];
                $product['qty'] -= $quantity;
                $product_warehouse = Product_Warehouse::where([
                    ['product_id', $product['id']],
                    ['warehouse_id', $data['warehouse_id']]
                ])->first();
                $product_warehouse->qty -= $quantity;
                $product->save();
                $product_warehouse->save();
            } else
                $sale_unit_id = 0;
            //collecting mail data
            $mail_data['products'][$key] = $product['name'];
            if ($product['type'] == 'digital')
                $mail_data['file'][$key] = url('/product/files') . '/' . $product['file'];
            else
                $mail_data['file'][$key] = '';
            if ($sale_unit_id)
                $mail_data['unit'][$key] = $unit[$key]['unit_code'];
            else
                $mail_data['unit'][$key] = '';

            $product_sale = new Product_Sale();
            $product_sale->sale_id = $lims_sale_data->id;
            $product_sale->product_id = $product['id'];
            $product_sale->qty = $mail_data['qty'][$key] = $qty[$key];
            $product_sale->sale_unit_id = $sale_unit_id;
            $product_sale->net_unit_price = number_format((float) $net_unit_price, config('decimal'), '.', '');
            $product_sale->discount = $discount[$key] * $qty[$key];
            $product_sale->tax_rate = $tax[$key]['rate'];
            $product_sale->tax = number_format((float) $product_tax, config('decimal'), '.', '');
            $product_sale->total = $mail_data['total'][$key] = number_format((float) $total, config('decimal'), '.', '');
            $product_sale->save();
            $lims_sale_data->total_qty += $qty[$key];
            $lims_sale_data->total_discount += $discount[$key] * $qty[$key];
            $lims_sale_data->total_tax += number_format((float) $product_tax, config('decimal'), '.', '');
            $lims_sale_data->total_price += number_format((float) $total, config('decimal'), '.', '');
        }
        $lims_sale_data->item = $key + 1;
        $lims_sale_data->order_tax = ($lims_sale_data->total_price - $lims_sale_data->order_discount) * ($data['order_tax_rate'] / 100);
        $lims_sale_data->grand_total = ($lims_sale_data->total_price + $lims_sale_data->order_tax + $lims_sale_data->shipping_cost) - $lims_sale_data->order_discount;
        $lims_sale_data->save();
        $message = 'Sale imported successfully';
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $mail_setting) {
            //collecting male data
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
            } catch (\Exception $e) {
                $message = 'Sale imported successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }
        return redirect('sales')->with('message', $message);
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-edit')) {
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_sale_data = Sale::find($id);
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            if ($lims_sale_data->exchange_rate)
                $currency_exchange_rate = $lims_sale_data->exchange_rate;
            else
                $currency_exchange_rate = 1;
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();
            return view('backend.sale.edit', compact('lims_customer_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_tax_list', 'lims_sale_data', 'lims_product_sale_data', 'currency_exchange_rate', 'custom_fields'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function editx($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-edit')) {
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_sale_data = Sale::find($id);
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            if ($lims_sale_data->exchange_rate)
                $currency_exchange_rate = $lims_sale_data->exchange_rate;
            else
                $currency_exchange_rate = 1;
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();
            return view('backend.sale.editx', compact('lims_customer_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_tax_list', 'lims_sale_data', 'lims_product_sale_data', 'currency_exchange_rate', 'custom_fields'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        $data = $request->except('document');
        //return dd($data);
        $document = $request->document;
        $lims_sale_data = Sale::find($id);

        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $this->fileDelete(public_path('documents/sale/'), $lims_sale_data->document);

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            }
            $data['document'] = $documentName;
        }
        $balance = $data['grand_total'] - $data['paid_amount'];
        if ($balance < 0 || $balance > 0)
            $data['payment_status'] = 2;
        else
            $data['payment_status'] = 4;

        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at']))) . ' ' . date("H:i:s");
        $product_id = $data['product_id'];
        $imei_number = $data['imei_number'];
        $product_batch_id = $data['product_batch_id'];
        $product_code = $data['product_code'];
        $product_variant_id = $data['product_variant_id'];
        $qty = $data['qty'];
        $sale_unit = $data['sale_unit'];
        $net_unit_price = $data['net_unit_price'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $old_product_id = [];
        $product_sale = [];
        foreach ($lims_product_sale_data as $key => $product_sale_data) {
            $old_product_id[] = $product_sale_data->product_id;
            $old_product_variant_id[] = null;
            $lims_product_data = Product::find($product_sale_data->product_id);

            if (($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo')) {
                if (!in_array('manufacturing', explode(',', config('addons')))) {
                    $product_list = explode(",", $lims_product_data->product_list);
                    $variant_list = explode(",", $lims_product_data->variant_list);
                    if ($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    $qty_list = explode(",", $lims_product_data->qty_list);

                    foreach ($product_list as $index => $child_id) {
                        $child_data = Product::find($child_id);
                        if (count($variant_list) && $variant_list[$index]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]]
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]],
                                ['warehouse_id', $lims_sale_data->warehouse_id],
                            ])->first();

                            $child_product_variant_data->qty += $product_sale_data->qty * $qty_list[$index];
                            $child_product_variant_data->save();
                        } else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $lims_sale_data->warehouse_id],
                            ])->first();
                        }

                        $child_data->qty += $product_sale_data->qty * $qty_list[$index];
                        $child_warehouse_data->qty += $product_sale_data->qty * $qty_list[$index];

                        $child_data->save();
                        $child_warehouse_data->save();
                    }
                }
            }

            if (($lims_sale_data->sale_status == 1) && ($product_sale_data->sale_unit_id != 0)) {
                $old_product_qty = $product_sale_data->qty;
                $lims_sale_unit_data = Unit::find($product_sale_data->sale_unit_id);
                if ($lims_sale_unit_data->operator == '*')
                    $old_product_qty = $old_product_qty * $lims_sale_unit_data->operation_value;
                else
                    $old_product_qty = $old_product_qty / $lims_sale_unit_data->operation_value;
                if ($product_sale_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_sale_data->product_id, $product_sale_data->variant_id, $lims_sale_data->warehouse_id)
                        ->first();
                    $old_product_variant_id[$key] = $lims_product_variant_data->id;
                    $lims_product_variant_data->qty += $old_product_qty;
                    $lims_product_variant_data->save();
                } elseif ($product_sale_data->product_batch_id) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_sale_data->product_id],
                        ['product_batch_id', $product_sale_data->product_batch_id],
                        ['warehouse_id', $lims_sale_data->warehouse_id]
                    ])->first();

                    $product_batch_data = ProductBatch::find($product_sale_data->product_batch_id);
                    $product_batch_data->qty += $old_product_qty;
                    $product_batch_data->save();
                } else
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_sale_data->product_id, $lims_sale_data->warehouse_id)
                        ->first();
                $lims_product_data->qty += $old_product_qty;
                $lims_product_warehouse_data->qty += $old_product_qty;

                //returning imei number if exist
                if (!str_contains($product_sale_data->imei_number, "null")) {
                    if ($lims_product_warehouse_data->imei_number)
                        $lims_product_warehouse_data->imei_number .= ',' . $product_sale_data->imei_number;
                    else
                        $lims_product_warehouse_data->imei_number = $product_sale_data->imei_number;
                }

                $lims_product_data->save();
                $lims_product_warehouse_data->save();
            } else {
                if ($product_sale_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_sale_data->product_id, $product_sale_data->variant_id, $lims_sale_data->warehouse_id)
                        ->first();
                    $old_product_variant_id[$key] = $lims_product_variant_data->id;
                }
            }

            if ($product_sale_data->variant_id && !(in_array($old_product_variant_id[$key], $product_variant_id))) {
                $product_sale_data->delete();
            } elseif (!(in_array($old_product_id[$key], $product_id)))
                $product_sale_data->delete();
        }
        //dealing with new products
        $product_variant_id = [];
        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);
            $product_sale['variant_id'] = null;
            if ($lims_product_data->type == 'combo' && $data['sale_status'] == 1) {
                if (!in_array('manufacturing', explode(',', config('addons')))) {
                    $product_list = explode(",", $lims_product_data->product_list);
                    $variant_list = explode(",", $lims_product_data->variant_list);
                    if ($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    $qty_list = explode(",", $lims_product_data->qty_list);

                    foreach ($product_list as $index => $child_id) {
                        $child_data = Product::find($child_id);
                        if (count($variant_list) && $variant_list[$index]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]],
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();

                            $child_product_variant_data->qty -= $qty[$key] * $qty_list[$index];
                            $child_product_variant_data->save();
                        } else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();
                        }


                        $child_data->qty -= $qty[$key] * $qty_list[$index];
                        $child_warehouse_data->qty -= $qty[$key] * $qty_list[$index];

                        $child_data->save();
                        $child_warehouse_data->save();
                    }
                }
            }
            if ($sale_unit[$key] != 'n/a') {
                $lims_sale_unit_data = Unit::where('unit_name', $sale_unit[$key])->first();
                $sale_unit_id = $lims_sale_unit_data->id;
                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($pro_id, $lims_product_variant_data->variant_id, $data['warehouse_id'])
                        ->first();
                    $product_sale['variant_id'] = $lims_product_variant_data->variant_id;
                    $product_variant_id[$key] = $lims_product_variant_data->id;
                } else {
                    $product_variant_id[$key] = Null;
                }

                if ($data['sale_status'] == 1) {
                    $new_product_qty = $qty[$key];
                    if ($lims_sale_unit_data->operator == '*') {
                        $new_product_qty = $new_product_qty * $lims_sale_unit_data->operation_value;
                    } else {
                        $new_product_qty = $new_product_qty / $lims_sale_unit_data->operation_value;
                    }

                    if ($product_sale['variant_id']) {
                        $lims_product_variant_data->qty -= $new_product_qty;
                        $lims_product_variant_data->save();
                    } elseif ($product_batch_id[$key]) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['product_batch_id', $product_batch_id[$key]],
                            ['warehouse_id', $data['warehouse_id']]
                        ])->first();

                        $product_batch_data = ProductBatch::find($product_batch_id[$key]);
                        $product_batch_data->qty -= $new_product_qty;
                        $product_batch_data->save();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])
                            ->first();
                    }
                    $lims_product_data->qty -= $new_product_qty;
                    $lims_product_warehouse_data->qty -= $new_product_qty;

                    //deduct imei number if available
                    if (!str_contains($imei_number[$key], "null")) {
                        $imei_numbers = explode(",", $imei_number[$key]);
                        $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                        foreach ($imei_numbers as $number) {
                            if (($j = array_search($number, $all_imei_numbers)) !== false) {
                                unset($all_imei_numbers[$j]);
                            }
                        }
                        $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                        $lims_product_warehouse_data->save();
                    }

                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }
            } else
                $sale_unit_id = 0;


            //collecting mail data
            if ($product_sale['variant_id']) {
                $variant_data = Variant::select('name')->find($product_sale['variant_id']);
                $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
            } else
                $mail_data['products'][$key] = $lims_product_data->name;

            if ($lims_product_data->type == 'digital')
                $mail_data['file'][$key] = url('/product/files') . '/' . $lims_product_data->file;
            else
                $mail_data['file'][$key] = '';
            if ($sale_unit_id)
                $mail_data['unit'][$key] = $lims_sale_unit_data->unit_code;
            else
                $mail_data['unit'][$key] = '';

            $product_sale['sale_id'] = $id;
            $product_sale['product_id'] = $pro_id;
            $product_sale['imei_number'] = $imei_number[$key];
            $product_sale['product_batch_id'] = $product_batch_id[$key];
            $product_sale['qty'] = $mail_data['qty'][$key] = $qty[$key];
            $product_sale['sale_unit_id'] = $sale_unit_id;
            $product_sale['net_unit_price'] = $net_unit_price[$key];
            $product_sale['discount'] = $discount[$key];
            $product_sale['tax_rate'] = $tax_rate[$key];
            $product_sale['tax'] = $tax[$key];
            $product_sale['total'] = $mail_data['total'][$key] = $total[$key];
            //return $old_product_variant_id;

            if ($product_sale['variant_id'] && in_array($product_variant_id[$key], $old_product_variant_id)) {
                Product_Sale::where([
                    ['product_id', $pro_id],
                    ['variant_id', $product_sale['variant_id']],
                    ['sale_id', $id]
                ])->update($product_sale);
            } elseif ($product_sale['variant_id'] === null && (in_array($pro_id, $old_product_id))) {
                Product_Sale::where([
                    ['sale_id', $id],
                    ['product_id', $pro_id]
                ])->update($product_sale);
            } else
                Product_Sale::create($product_sale);
        }
        //return $product_variant_id;
        $lims_sale_data->update($data);
        //inserting data for custom fields
        $custom_field_data = [];
        $custom_fields = CustomField::where('belongs_to', 'sale')->select('name', 'type')->get();
        foreach ($custom_fields as $type => $custom_field) {
            $field_name = str_replace(' ', '_', strtolower($custom_field->name));
            if (isset($data[$field_name])) {
                if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                    $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                else
                    $custom_field_data[$field_name] = $data[$field_name];
            }
        }
        if (count($custom_field_data))
            DB::table('sales')->where('id', $lims_sale_data->id)->update($custom_field_data);
        $lims_customer_data = Customer::find($data['customer_id']);
        $message = 'Sale updated successfully';
        //collecting mail data
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
            } catch (\Exception $e) {
                $message = 'Sale updated successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }

        return redirect('sales/' . $id . '/edit')->with('message', $message);
    }

    public function printLastReciept()
    {
        $sale = Sale::where('sale_status', 1)->latest()->first();
        return redirect()->route('sale.invoice', $sale->id);
    }

    public function genInvoice($id)
    {
        $lims_sale_data = Sale::find($id);
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        if (cache()->has('biller_list')) {
            $lims_biller_data = cache()->get('biller_list')->find($lims_sale_data->biller_id);
        } else {
            $lims_biller_data = Biller::find($lims_sale_data->biller_id);
        }
        if (cache()->has('warehouse_list')) {
            $lims_warehouse_data = cache()->get('warehouse_list')->find($lims_sale_data->warehouse_id);
        } else {
            $lims_warehouse_data = Warehouse::find($lims_sale_data->warehouse_id);
        }

        if (cache()->has('customer_list')) {
            $lims_customer_data = cache()->get('customer_list')->find($lims_sale_data->customer_id);
        } else {
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        }

        $lims_payment_data = Payment::where('sale_id', $id)->get();
        if (cache()->has('pos_setting')) {
            $lims_pos_setting_data = cache()->get('pos_setting');
        } else {
            $lims_pos_setting_data = PosSetting::select('invoice_option', 'thermal_invoice_size')->latest()->first();
        }

        $supportedIdentifiers = [
            'al',
            'fr_BE',
            'pt_BR',
            'bg',
            'cs',
            'dk',
            'nl',
            'et',
            'ka',
            'de',
            'fr',
            'hu',
            'id',
            'it',
            'lt',
            'lv',
            'ms',
            'fa',
            'pl',
            'ro',
            'sk',
            'es',
            'ru',
            'sv',
            'tr',
            'tk',
            'ua',
            'yo'
        ]; //ar, az, ku, mk - not supported

        $defaultLocale = \App::getLocale();
        $numberToWords = new NumberToWords();

        if (in_array($defaultLocale, $supportedIdentifiers))
            $numberTransformer = $numberToWords->getNumberTransformer($defaultLocale);
        else
            $numberTransformer = $numberToWords->getNumberTransformer('en');


        if (config('is_zatca')) {
            //generating base64 TLV format qrtext for qrcode
            $qrText = GenerateQrCode::fromArray([
                new Seller(config('company_name')), // seller name
                new TaxNumber(config('vat_registration_number')), // seller tax number
                new InvoiceDate($lims_sale_data->created_at->toDateString() . "T" . $lims_sale_data->created_at->toTimeString()), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
                new InvoiceTotalAmount(number_format((float) $lims_sale_data->grand_total, 4, '.', '')), // invoice total amount
                new InvoiceTaxAmount(number_format((float) ($lims_sale_data->total_tax + $lims_sale_data->order_tax), 4, '.', '')) // invoice tax amount
                // TODO :: Support others tags
            ])->toBase64();
        } else {
            $qrText = $lims_sale_data->reference_no;
        }
        if (is_null($lims_sale_data->exchange_rate)) {
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);
            $currency_code = cache()->get('currency')->code;
        } else {
            $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);
            $sale_currency = DB::table('currencies')->select('code')->where('id', $lims_sale_data->currency_id)->first();
            $currency_code = $sale_currency->code;
        }
        $paying_methods = Payment::where('sale_id', $id)->pluck('paying_method')->toArray();
        $paid_by_info = '';
        foreach ($paying_methods as $key => $paying_method) {
            if ($key)
                $paid_by_info .= ', ' . $paying_method;
            else
                $paid_by_info = $paying_method;
        }
        $sale_custom_fields = CustomField::where([
            ['belongs_to', 'sale'],
            ['is_invoice', true]
        ])->pluck('name');
        $customer_custom_fields = CustomField::where([
            ['belongs_to', 'customer'],
            ['is_invoice', true]
        ])->pluck('name');
        $product_custom_fields = CustomField::where([
            ['belongs_to', 'product'],
            ['is_invoice', true]
        ])->pluck('name');
        $returned_amount = DB::table('sales')
            ->join('returns', 'sales.id', '=', 'returns.sale_id')
            ->where([
                ['sales.customer_id', $lims_customer_data->id],
                ['sales.payment_status', '!=', 4]
            ])
            ->sum('returns.grand_total');
        $saleData = DB::table('sales')->where([
            ['customer_id', $lims_customer_data->id],
            ['payment_status', '!=', 4]
        ])
            ->selectRaw('SUM(grand_total) as grand_total,SUM(paid_amount) as paid_amount')
            ->first();
        $totalDue = $saleData->grand_total - $returned_amount - $saleData->paid_amount;
        if ($lims_pos_setting_data->invoice_option == 'A4') {
            return view('backend.sale.a4_invoice', compact('lims_sale_data', 'currency_code', 'lims_product_sale_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_customer_data', 'lims_payment_data', 'numberInWords', 'paid_by_info', 'sale_custom_fields', 'customer_custom_fields', 'product_custom_fields', 'qrText', 'totalDue'));
        } elseif ($lims_sale_data->sale_type == 'online') {
            return view('backend.sale.a4_invoice', compact('lims_sale_data', 'currency_code', 'lims_product_sale_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_customer_data', 'lims_payment_data', 'numberInWords', 'paid_by_info', 'sale_custom_fields', 'customer_custom_fields', 'product_custom_fields', 'qrText', 'totalDue'));
        } elseif ($lims_pos_setting_data->invoice_option == 'thermal' && $lims_pos_setting_data->thermal_invoice_size == '58') {
            return view('backend.sale.invoice58', compact('lims_sale_data', 'currency_code', 'lims_product_sale_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_customer_data', 'lims_payment_data', 'numberInWords', 'sale_custom_fields', 'customer_custom_fields', 'product_custom_fields', 'qrText', 'totalDue'));
        } else {
            return view('backend.sale.invoice', compact('lims_sale_data', 'currency_code', 'lims_product_sale_data', 'lims_biller_data', 'lims_warehouse_data', 'lims_customer_data', 'lims_payment_data', 'numberInWords', 'sale_custom_fields', 'customer_custom_fields', 'product_custom_fields', 'qrText', 'totalDue'));
        }
    }

    public function addPayment(Request $request)
    {
        $data = $request->all();
        if (!$data['amount'])
            $data['amount'] = 0.00;

        $lims_sale_data = Sale::find($data['sale_id']);
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $lims_sale_data->paid_amount += $data['amount'];
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if ($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;

        if ($data['paid_by_id'] == 1)
            $paying_method = 'Cash';
        elseif ($data['paid_by_id'] == 2)
            $paying_method = 'Gift Card';
        elseif ($data['paid_by_id'] == 3)
            $paying_method = 'Credit Card';
        elseif ($data['paid_by_id'] == 4)
            $paying_method = 'Cheque';
        elseif ($data['paid_by_id'] == 5)
            $paying_method = 'Paypal';
        elseif ($data['paid_by_id'] == 6)
            $paying_method = 'Deposit';
        elseif ($data['paid_by_id'] == 7)
            $paying_method = 'Points';


        $cash_register_data = CashRegister::where([
            ['user_id', Auth::id()],
            ['warehouse_id', $lims_sale_data->warehouse_id],
            ['status', true]
        ])->first();

        $lims_payment_data = new Payment();
        $lims_payment_data->user_id = Auth::id();
        $lims_payment_data->sale_id = $lims_sale_data->id;
        if ($cash_register_data)
            $lims_payment_data->cash_register_id = $cash_register_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $data['payment_reference'] = 'spr-' . date("Ymd") . '-' . date("his");
        $lims_payment_data->payment_reference = $data['payment_reference'];
        $lims_payment_data->amount = $data['amount'];
        $lims_payment_data->change = $data['paying_amount'] - $data['amount'];
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $lims_payment_data->payment_receiver = $data['payment_receiver'];
        $lims_payment_data->save();
        $lims_sale_data->save();

        $lims_payment_data = Payment::latest()->first();
        $data['payment_id'] = $lims_payment_data->id;

        if ($paying_method == 'Gift Card') {
            $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
            $lims_gift_card_data->expense += $data['amount'];
            $lims_gift_card_data->save();
            PaymentWithGiftCard::create($data);
        } elseif ($paying_method == 'Credit Card') {
            $lims_pos_setting_data = PosSetting::latest()->first();
            if ($lims_pos_setting_data->stripe_secret_key) {
                Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                $token = $data['stripeToken'];
                $amount = $data['amount'];

                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('customer_id', $lims_sale_data->customer_id)->first();

                if (!$lims_payment_with_credit_card_data) {
                    // Create a Customer:
                    $customer = \Stripe\Customer::create([
                        'source' => $token
                    ]);

                    // Charge the Customer instead of the card:
                    $charge = \Stripe\Charge::create([
                        'amount' => $amount * 100,
                        'currency' => 'usd',
                        'customer' => $customer->id,
                    ]);
                    $data['customer_stripe_id'] = $customer->id;
                } else {
                    $customer_id =
                        $lims_payment_with_credit_card_data->customer_stripe_id;

                    $charge = \Stripe\Charge::create([
                        'amount' => $amount * 100,
                        'currency' => 'usd',
                        'customer' => $customer_id, // Previously stored, then retrieved
                    ]);
                    $data['customer_stripe_id'] = $customer_id;
                }
                $data['customer_id'] = $lims_sale_data->customer_id;
                $data['charge_id'] = $charge->id;
                PaymentWithCreditCard::create($data);
            }
        } elseif ($paying_method == 'Cheque') {
            PaymentWithCheque::create($data);
        } elseif ($paying_method == 'Paypal') {
            $provider = new ExpressCheckout;
            $paypal_data['items'] = [];
            $paypal_data['items'][] = [
                'name' => 'Paid Amount',
                'price' => $data['amount'],
                'qty' => 1
            ];
            $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
            $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
            $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess/' . $lims_payment_data->id);
            $paypal_data['cancel_url'] = url('/sale');

            $total = 0;
            foreach ($paypal_data['items'] as $item) {
                $total += $item['price'] * $item['qty'];
            }

            $paypal_data['total'] = $total;
            $response = $provider->setExpressCheckout($paypal_data);
            return redirect($response['paypal_link']);
        } elseif ($paying_method == 'Deposit') {
            $lims_customer_data->expense += $data['amount'];
            $lims_customer_data->save();
        } elseif ($paying_method == 'Points') {
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $used_points = ceil($data['amount'] / $lims_reward_point_setting_data->per_point_amount);

            $lims_payment_data->used_points = $used_points;
            $lims_payment_data->save();

            $lims_customer_data->points -= $used_points;
            $lims_customer_data->save();
        }
        $message = 'Payment created successfully';
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['sale_reference'] = $lims_sale_data->reference_no;
            $mail_data['payment_reference'] = $lims_payment_data->payment_reference;
            $mail_data['payment_method'] = $lims_payment_data->paying_method;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_payment_data->amount;
            $mail_data['currency'] = config('currency');
            $mail_data['due'] = $balance;
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new PaymentDetails($mail_data));
            } catch (\Exception $e) {
                $message = 'Payment created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }

        }
        return redirect('sales')->with('message', $message);
    }

    public function getPayment($id)
    {
        $lims_payment_list = Payment::where('sale_id', $id)->get();
        $date = [];
        $payment_reference = [];
        $paid_amount = [];
        $paying_method = [];
        $payment_id = [];
        $payment_note = [];
        $gift_card_id = [];
        $cheque_no = [];
        $change = [];
        $paying_amount = [];
        $payment_receiver = [];
        $account_name = [];
        $account_id = [];

        foreach ($lims_payment_list as $payment) {
            $date[] = date(config('date_format'), strtotime($payment->created_at->toDateString())) . ' ' . $payment->created_at->toTimeString();
            $payment_reference[] = $payment->payment_reference;
            $paid_amount[] = $payment->amount;
            $change[] = $payment->change;
            $paying_method[] = $payment->paying_method;
            $paying_amount[] = $payment->amount + $payment->change;
            $payment_receiver[] = $payment->payment_receiver;
            if ($payment->paying_method == 'Gift Card') {
                $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                $gift_card_id[] = $lims_payment_gift_card_data->gift_card_id;
            } elseif ($payment->paying_method == 'Cheque') {
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                if ($lims_payment_cheque_data)
                    $cheque_no[] = $lims_payment_cheque_data->cheque_no;
                else
                    $cheque_no[] = null;
            } else {
                $cheque_no[] = $gift_card_id[] = null;
            }
            $payment_id[] = $payment->id;
            $payment_note[] = $payment->payment_note;
            $lims_account_data = Account::find($payment->account_id);
            $account_name[] = $lims_account_data->name;
            $account_id[] = $lims_account_data->id;
        }
        $payments[] = $date;
        $payments[] = $payment_reference;
        $payments[] = $paid_amount;
        $payments[] = $paying_method;
        $payments[] = $payment_id;
        $payments[] = $payment_note;
        $payments[] = $cheque_no;
        $payments[] = $gift_card_id;
        $payments[] = $change;
        $payments[] = $paying_amount;
        $payments[] = $account_name;
        $payments[] = $account_id;
        $payments[] = $payment_receiver;

        return $payments;
    }

    public function updatePayment(Request $request)
    {
        $data = $request->all();
        //return $data;
        $lims_payment_data = Payment::find($data['payment_id']);
        $lims_sale_data = Sale::find($lims_payment_data->sale_id);
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        //updating sale table
        $amount_dif = $lims_payment_data->amount - $data['edit_amount'];
        $lims_sale_data->paid_amount = $lims_sale_data->paid_amount - $amount_dif;
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if ($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;
        $lims_sale_data->save();

        if ($lims_payment_data->paying_method == 'Deposit') {
            $lims_customer_data->expense -= $lims_payment_data->amount;
            $lims_customer_data->save();
        } elseif ($lims_payment_data->paying_method == 'Points') {
            $lims_customer_data->points += $lims_payment_data->used_points;
            $lims_customer_data->save();
            $lims_payment_data->used_points = 0;
        }
        if ($data['edit_paid_by_id'] == 1)
            $lims_payment_data->paying_method = 'Cash';
        elseif ($data['edit_paid_by_id'] == 2) {
            if ($lims_payment_data->paying_method == 'Gift Card') {
                $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $data['payment_id'])->first();

                $lims_gift_card_data = GiftCard::find($lims_payment_gift_card_data->gift_card_id);
                $lims_gift_card_data->expense -= $lims_payment_data->amount;
                $lims_gift_card_data->save();

                $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                $lims_gift_card_data->expense += $data['edit_amount'];
                $lims_gift_card_data->save();

                $lims_payment_gift_card_data->gift_card_id = $data['gift_card_id'];
                $lims_payment_gift_card_data->save();
            } else {
                $lims_payment_data->paying_method = 'Gift Card';
                $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                $lims_gift_card_data->expense += $data['edit_amount'];
                $lims_gift_card_data->save();
                PaymentWithGiftCard::create($data);
            }
        } elseif ($data['edit_paid_by_id'] == 3) {
            $lims_pos_setting_data = PosSetting::latest()->first();
            if ($lims_pos_setting_data->stripe_secret_key) {
                Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                if ($lims_payment_data->paying_method == 'Credit Card') {
                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $lims_payment_data->id)->first();

                    \Stripe\Refund::create(array(
                        "charge" => $lims_payment_with_credit_card_data->charge_id,
                    ));

                    $customer_id =
                        $lims_payment_with_credit_card_data->customer_stripe_id;

                    $charge = \Stripe\Charge::create([
                        'amount' => $data['edit_amount'] * 100,
                        'currency' => 'usd',
                        'customer' => $customer_id
                    ]);
                    $lims_payment_with_credit_card_data->charge_id = $charge->id;
                    $lims_payment_with_credit_card_data->save();
                } else {
                    $token = $data['stripeToken'];
                    $amount = $data['edit_amount'];
                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('customer_id', $lims_sale_data->customer_id)->first();

                    if (!$lims_payment_with_credit_card_data) {
                        $customer = \Stripe\Customer::create([
                            'source' => $token
                        ]);

                        $charge = \Stripe\Charge::create([
                            'amount' => $amount * 100,
                            'currency' => 'usd',
                            'customer' => $customer->id,
                        ]);
                        $data['customer_stripe_id'] = $customer->id;
                    } else {
                        $customer_id =
                            $lims_payment_with_credit_card_data->customer_stripe_id;

                        $charge = \Stripe\Charge::create([
                            'amount' => $amount * 100,
                            'currency' => 'usd',
                            'customer' => $customer_id
                        ]);
                        $data['customer_stripe_id'] = $customer_id;
                    }
                    $data['customer_id'] = $lims_sale_data->customer_id;
                    $data['charge_id'] = $charge->id;
                    PaymentWithCreditCard::create($data);
                }
            }
            $lims_payment_data->paying_method = 'Credit Card';
        } elseif ($data['edit_paid_by_id'] == 4) {
            if ($lims_payment_data->paying_method == 'Cheque') {
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $data['payment_id'])->first();
                if ($lims_payment_cheque_data) {
                    $lims_payment_cheque_data->cheque_no = $data['edit_cheque_no'];
                    $lims_payment_cheque_data->save();
                } elseif ($data['edit_cheque_no']) {
                    PaymentWithCheque::create([
                        'payment_id' => $lims_payment_data->id,
                        'cheque_no' => $data['edit_cheque_no']
                    ]);
                }
            } else {
                $lims_payment_data->paying_method = 'Cheque';
                $data['cheque_no'] = $data['edit_cheque_no'];
                PaymentWithCheque::create($data);
            }
        } elseif ($data['edit_paid_by_id'] == 5) {
            //updating payment data
            $lims_payment_data->amount = $data['edit_amount'];
            $lims_payment_data->paying_method = 'Paypal';
            $lims_payment_data->payment_note = $data['edit_payment_note'];
            $lims_payment_data->save();

            $provider = new ExpressCheckout;
            $paypal_data['items'] = [];
            $paypal_data['items'][] = [
                'name' => 'Paid Amount',
                'price' => $data['edit_amount'],
                'qty' => 1
            ];
            $paypal_data['invoice_id'] = $lims_payment_data->payment_reference;
            $paypal_data['invoice_description'] = "Reference: {$paypal_data['invoice_id']}";
            $paypal_data['return_url'] = url('/sale/paypalPaymentSuccess/' . $lims_payment_data->id);
            $paypal_data['cancel_url'] = url('/sale');

            $total = 0;
            foreach ($paypal_data['items'] as $item) {
                $total += $item['price'] * $item['qty'];
            }

            $paypal_data['total'] = $total;
            $response = $provider->setExpressCheckout($paypal_data);
            return redirect($response['paypal_link']);
        } elseif ($data['edit_paid_by_id'] == 6) {
            $lims_payment_data->paying_method = 'Deposit';
            $lims_customer_data->expense += $data['edit_amount'];
            $lims_customer_data->save();
        } elseif ($data['edit_paid_by_id'] == 7) {
            $lims_payment_data->paying_method = 'Points';
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $used_points = ceil($data['edit_amount'] / $lims_reward_point_setting_data->per_point_amount);
            $lims_payment_data->used_points = $used_points;
            $lims_customer_data->points -= $used_points;
            $lims_customer_data->save();
        }
        //updating payment data
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->amount = $data['edit_amount'];
        $lims_payment_data->change = $data['edit_paying_amount'] - $data['edit_amount'];
        $lims_payment_data->payment_note = $data['edit_payment_note'];
        $lims_payment_data->payment_note = $data['edit_payment_note'];
        $lims_payment_data->payment_receiver = $data['payment_receiver'];
        $lims_payment_data->save();
        $message = 'Payment updated successfully';
        //collecting male data
        $mail_setting = MailSetting::latest()->first();
        if ($lims_customer_data->email && $mail_setting) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['sale_reference'] = $lims_sale_data->reference_no;
            $mail_data['payment_reference'] = $lims_payment_data->payment_reference;
            $mail_data['payment_method'] = $lims_payment_data->paying_method;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_payment_data->amount;
            $mail_data['currency'] = config('currency');
            $mail_data['due'] = $balance;
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new PaymentDetails($mail_data));
            } catch (\Exception $e) {
                $message = 'Payment updated successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }
        return redirect('sales')->with('message', $message);
    }

    public function deletePayment(Request $request)
    {
        $lims_payment_data = Payment::find($request['id']);
        $lims_sale_data = Sale::where('id', $lims_payment_data->sale_id)->first();
        $lims_sale_data->paid_amount -= $lims_payment_data->amount;
        $balance = $lims_sale_data->grand_total - $lims_sale_data->paid_amount;
        if ($balance > 0 || $balance < 0)
            $lims_sale_data->payment_status = 2;
        elseif ($balance == 0)
            $lims_sale_data->payment_status = 4;
        $lims_sale_data->save();

        if ($lims_payment_data->paying_method == 'Gift Card') {
            $lims_payment_gift_card_data = PaymentWithGiftCard::where('payment_id', $request['id'])->first();
            $lims_gift_card_data = GiftCard::find($lims_payment_gift_card_data->gift_card_id);
            $lims_gift_card_data->expense -= $lims_payment_data->amount;
            $lims_gift_card_data->save();
            $lims_payment_gift_card_data->delete();
        } elseif ($lims_payment_data->paying_method == 'Credit Card') {
            $lims_pos_setting_data = PosSetting::latest()->first();
            if ($lims_pos_setting_data->stripe_secret_key) {
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $request['id'])->first();
                Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                \Stripe\Refund::create(array(
                    "charge" => $lims_payment_with_credit_card_data->charge_id,
                ));

                $lims_payment_with_credit_card_data->delete();
            }
        } elseif ($lims_payment_data->paying_method == 'Cheque') {
            $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $request['id'])->first();
            $lims_payment_cheque_data->delete();
        } elseif ($lims_payment_data->paying_method == 'Paypal') {
            $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $request['id'])->first();
            if ($lims_payment_paypal_data) {
                $provider = new ExpressCheckout;
                $response = $provider->refundTransaction($lims_payment_paypal_data->transaction_id);
                $lims_payment_paypal_data->delete();
            }
        } elseif ($lims_payment_data->paying_method == 'Deposit') {
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
            $lims_customer_data->expense -= $lims_payment_data->amount;
            $lims_customer_data->save();
        } elseif ($lims_payment_data->paying_method == 'Points') {
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
            $lims_customer_data->points += $lims_payment_data->used_points;
            $lims_customer_data->save();
        }
        $lims_payment_data->delete();
        return redirect('sales')->with('not_permitted', 'Payment deleted successfully');
    }

    public function todaySale()
    {
        $data['total_sale_amount'] = Sale::whereDate('created_at', date("Y-m-d"))->sum('grand_total');
        $data['total_payment'] = Payment::whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['cash_payment'] = Payment::where([
            ['paying_method', 'Cash']
        ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['credit_card_payment'] = Payment::where([
            ['paying_method', 'Credit Card']
        ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['gift_card_payment'] = Payment::where([
            ['paying_method', 'Gift Card']
        ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['deposit_payment'] = Payment::where([
            ['paying_method', 'Deposit']
        ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['cheque_payment'] = Payment::where([
            ['paying_method', 'Cheque']
        ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['paypal_payment'] = Payment::where([
            ['paying_method', 'Paypal']
        ])->whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['total_sale_return'] = Returns::whereDate('created_at', date("Y-m-d"))->sum('grand_total');
        $data['total_expense'] = Expense::whereDate('created_at', date("Y-m-d"))->sum('amount');
        $data['total_cash'] = $data['total_payment'] - ($data['total_sale_return'] + $data['total_expense']);
        return $data;
    }

    public function todayProfit($warehouse_id)
    {
        if ($warehouse_id == 0)
            $product_sale_data = Product_Sale::select(DB::raw('product_id, product_batch_id, sum(qty) as sold_qty, sum(total) as sold_amount'))->whereDate('created_at', date("Y-m-d"))->groupBy('product_id', 'product_batch_id')->get();
        else
            $product_sale_data = Sale::join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                ->select(DB::raw('product_sales.product_id, product_sales.product_batch_id, sum(product_sales.qty) as sold_qty, sum(product_sales.total) as sold_amount'))
                ->where('sales.warehouse_id', $warehouse_id)->whereDate('sales.created_at', date("Y-m-d"))
                ->groupBy('product_sales.product_id', 'product_sales.product_batch_id')->get();

        $product_revenue = 0;
        $product_cost = 0;
        $profit = 0;
        foreach ($product_sale_data as $key => $product_sale) {
            if ($warehouse_id == 0) {
                if ($product_sale->product_batch_id)
                    $product_purchase_data = ProductPurchase::where([
                        ['product_id', $product_sale->product_id],
                        ['product_batch_id', $product_sale->product_batch_id]
                    ])->get();
                else
                    $product_purchase_data = ProductPurchase::where('product_id', $product_sale->product_id)->get();
            } else {
                if ($product_sale->product_batch_id) {
                    $product_purchase_data = Purchase::join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                        ->where([
                            ['product_purchases.product_id', $product_sale->product_id],
                            ['product_purchases.product_batch_id', $product_sale->product_batch_id],
                            ['purchases.warehouse_id', $warehouse_id]
                        ])->select('product_purchases.*')->get();
                } else
                    $product_purchase_data = Purchase::join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                        ->where([
                            ['product_purchases.product_id', $product_sale->product_id],
                            ['purchases.warehouse_id', $warehouse_id]
                        ])->select('product_purchases.*')->get();
            }

            $purchased_qty = 0;
            $purchased_amount = 0;
            $sold_qty = $product_sale->sold_qty;
            $product_revenue += $product_sale->sold_amount;
            foreach ($product_purchase_data as $key => $product_purchase) {
                $purchased_qty += $product_purchase->qty;
                $purchased_amount += $product_purchase->total;
                if ($purchased_qty >= $sold_qty) {
                    $qty_diff = $purchased_qty - $sold_qty;
                    $unit_cost = $product_purchase->total / $product_purchase->qty;
                    $purchased_amount -= ($qty_diff * $unit_cost);
                    break;
                }
            }

            $product_cost += $purchased_amount;
            $profit += $product_sale->sold_amount - $purchased_amount;
        }

        $data['product_revenue'] = $product_revenue;
        $data['product_cost'] = $product_cost;
        if ($warehouse_id == 0)
            $data['expense_amount'] = Expense::whereDate('created_at', date("Y-m-d"))->sum('amount');
        else
            $data['expense_amount'] = Expense::where('warehouse_id', $warehouse_id)->whereDate('created_at', date("Y-m-d"))->sum('amount');

        $data['profit'] = $profit - $data['expense_amount'];
        return $data;
    }

    public function deleteBySelection(Request $request)
    {
        $sale_id = $request['saleIdArray'];
        foreach ($sale_id as $id) {
            $lims_sale_data = Sale::find($id);
            $return_ids = Returns::where('sale_id', $id)->pluck('id')->toArray();
            if (count($return_ids)) {
                ProductReturn::whereIn('return_id', $return_ids)->delete();
                Returns::whereIn('id', $return_ids)->delete();
            }
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            $lims_delivery_data = Delivery::where('sale_id', $id)->first();
            if ($lims_sale_data->sale_status == 3)
                $message = 'Draft deleted successfully';
            else
                $message = 'Sale deleted successfully';
            foreach ($lims_product_sale_data as $product_sale) {
                $lims_product_data = Product::find($product_sale->product_id);
                //adjust product quantity
                if (($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo')) {
                    if (!in_array('manufacturing', explode(',', config('addons')))) {
                        $product_list = explode(",", $lims_product_data->product_list);
                        if ($lims_product_data->variant_list)
                            $variant_list = explode(",", $lims_product_data->variant_list);
                        else
                            $variant_list = [];
                        $qty_list = explode(",", $lims_product_data->qty_list);

                        foreach ($product_list as $index => $child_id) {
                            $child_data = Product::find($child_id);
                            if (count($variant_list) && $variant_list[$index]) {
                                $child_product_variant_data = ProductVariant::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]]
                                ])->first();

                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]],
                                    ['warehouse_id', $lims_sale_data->warehouse_id],
                                ])->first();

                                $child_product_variant_data->qty += $product_sale->qty * $qty_list[$index];
                                $child_product_variant_data->save();
                            } else {
                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['warehouse_id', $lims_sale_data->warehouse_id],
                                ])->first();
                            }

                            $child_data->qty += $product_sale->qty * $qty_list[$index];
                            $child_warehouse_data->qty += $product_sale->qty * $qty_list[$index];

                            $child_data->save();
                            $child_warehouse_data->save();
                        }
                    }
                } elseif (($lims_sale_data->sale_status == 1) && ($product_sale->sale_unit_id != 0)) {
                    $lims_sale_unit_data = Unit::find($product_sale->sale_unit_id);
                    if ($lims_sale_unit_data->operator == '*')
                        $product_sale->qty = $product_sale->qty * $lims_sale_unit_data->operation_value;
                    else
                        $product_sale->qty = $product_sale->qty / $lims_sale_unit_data->operation_value;
                    if ($product_sale->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_sale->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($lims_product_data->id, $product_sale->variant_id, $lims_sale_data->warehouse_id)->first();
                        $lims_product_variant_data->qty += $product_sale->qty;
                        $lims_product_variant_data->save();
                    } elseif ($product_sale->product_batch_id) {
                        $lims_product_batch_data = ProductBatch::find($product_sale->product_batch_id);
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_batch_id', $product_sale->product_batch_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id]
                        ])->first();

                        $lims_product_batch_data->qty -= $product_sale->qty;
                        $lims_product_batch_data->save();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $lims_sale_data->warehouse_id)->first();
                    }

                    $lims_product_data->qty += $product_sale->qty;
                    $lims_product_warehouse_data->qty += $product_sale->qty;
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();

                    //restore imei numbers
                    if ($product_sale->imei_number) {
                        if ($lims_product_warehouse_data->imei_number)
                            $lims_product_warehouse_data->imei_number .= ',' . $product_sale->imei_number;
                        else
                            $lims_product_warehouse_data->imei_number = $product_sale->imei_number;
                        $lims_product_warehouse_data->save();
                    }
                }
                $product_sale->delete();
            }
            $lims_payment_data = Payment::where('sale_id', $id)->get();
            foreach ($lims_payment_data as $payment) {
                if ($payment->paying_method == 'Gift Card') {
                    $lims_payment_with_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                    $lims_gift_card_data = GiftCard::find($lims_payment_with_gift_card_data->gift_card_id);
                    $lims_gift_card_data->expense -= $payment->amount;
                    $lims_gift_card_data->save();
                    $lims_payment_with_gift_card_data->delete();
                } elseif ($payment->paying_method == 'Cheque') {
                    $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                    $lims_payment_cheque_data->delete();
                } elseif ($payment->paying_method == 'Credit Card') {
                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment->id)->first();
                    $lims_payment_with_credit_card_data->delete();
                } elseif ($payment->paying_method == 'Paypal') {
                    $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $payment->id)->first();
                    if ($lims_payment_paypal_data)
                        $lims_payment_paypal_data->delete();
                } elseif ($payment->paying_method == 'Deposit') {
                    $lims_customer_data = Customer::find($lims_sale_data->customer_id);
                    $lims_customer_data->expense -= $payment->amount;
                    $lims_customer_data->save();
                }
                $payment->delete();
            }
            if ($lims_delivery_data)
                $lims_delivery_data->delete();
            if ($lims_sale_data->coupon_id) {
                $lims_coupon_data = Coupon::find($lims_sale_data->coupon_id);
                $lims_coupon_data->used -= 1;
                $lims_coupon_data->save();
            }
            $lims_sale_data->delete();
            $this->fileDelete(public_path('documents/sale/'), $lims_sale_data->document);

        }
        return 'Sale deleted successfully!';
    }

    public function destroy($id)
    {
        $url = url()->previous();
        $lims_sale_data = Sale::find($id);
        $return_ids = Returns::where('sale_id', $id)->pluck('id')->toArray();
        if (count($return_ids)) {
            ProductReturn::whereIn('return_id', $return_ids)->delete();
            Returns::whereIn('id', $return_ids)->delete();
        }
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $lims_delivery_data = Delivery::where('sale_id', $id)->first();
        if ($lims_sale_data->sale_status == 3)
            $message = 'Draft deleted successfully';
        else
            $message = 'Sale deleted successfully';

        foreach ($lims_product_sale_data as $product_sale) {
            $lims_product_data = Product::find($product_sale->product_id);
            //adjust product quantity
            if (($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo')) {
                if (!in_array('manufacturing', explode(',', config('addons')))) {
                    $product_list = explode(",", $lims_product_data->product_list);
                    $variant_list = explode(",", $lims_product_data->variant_list);
                    $qty_list = explode(",", $lims_product_data->qty_list);
                    if ($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    foreach ($product_list as $index => $child_id) {
                        $child_data = Product::find($child_id);
                        if (count($variant_list) && $variant_list[$index]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]]
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$index]],
                                ['warehouse_id', $lims_sale_data->warehouse_id],
                            ])->first();

                            $child_product_variant_data->qty += $product_sale->qty * $qty_list[$index];
                            $child_product_variant_data->save();
                        } else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $lims_sale_data->warehouse_id],
                            ])->first();
                        }

                        $child_data->qty += $product_sale->qty * $qty_list[$index];
                        $child_warehouse_data->qty += $product_sale->qty * $qty_list[$index];

                        $child_data->save();
                        $child_warehouse_data->save();
                    }
                }
            }

            if (($lims_sale_data->sale_status == 1) && ($product_sale->sale_unit_id != 0)) {
                $lims_sale_unit_data = Unit::find($product_sale->sale_unit_id);
                if ($lims_sale_unit_data->operator == '*')
                    $product_sale->qty = $product_sale->qty * $lims_sale_unit_data->operation_value;
                else
                    $product_sale->qty = $product_sale->qty / $lims_sale_unit_data->operation_value;
                if ($product_sale->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_sale->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($lims_product_data->id, $product_sale->variant_id, $lims_sale_data->warehouse_id)->first();
                    $lims_product_variant_data->qty += $product_sale->qty;
                    $lims_product_variant_data->save();
                } elseif ($product_sale->product_batch_id) {
                    $lims_product_batch_data = ProductBatch::find($product_sale->product_batch_id);
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_sale->product_batch_id],
                        ['warehouse_id', $lims_sale_data->warehouse_id]
                    ])->first();

                    $lims_product_batch_data->qty -= $product_sale->qty;
                    $lims_product_batch_data->save();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $lims_sale_data->warehouse_id)->first();
                }

                $lims_product_data->qty += $product_sale->qty;
                $lims_product_warehouse_data->qty += $product_sale->qty;
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
                //restore imei numbers
                if ($product_sale->imei_number) {
                    if ($lims_product_warehouse_data->imei_number)
                        $lims_product_warehouse_data->imei_number .= ',' . $product_sale->imei_number;
                    else
                        $lims_product_warehouse_data->imei_number = $product_sale->imei_number;
                    $lims_product_warehouse_data->save();
                }
            }

            $product_sale->delete();
        }

        $lims_payment_data = Payment::where('sale_id', $id)->get();
        foreach ($lims_payment_data as $payment) {
            if ($payment->paying_method == 'Gift Card') {
                $lims_payment_with_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                $lims_gift_card_data = GiftCard::find($lims_payment_with_gift_card_data->gift_card_id);
                $lims_gift_card_data->expense -= $payment->amount;
                $lims_gift_card_data->save();
                $lims_payment_with_gift_card_data->delete();
            } elseif ($payment->paying_method == 'Cheque') {
                $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                if ($lims_payment_cheque_data)
                    $lims_payment_cheque_data->delete();
            } elseif ($payment->paying_method == 'Credit Card') {
                $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment->id)->first();
                if ($lims_payment_with_credit_card_data)
                    $lims_payment_with_credit_card_data->delete();
            } elseif ($payment->paying_method == 'Paypal') {
                $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $payment->id)->first();
                if ($lims_payment_paypal_data)
                    $lims_payment_paypal_data->delete();
            } elseif ($payment->paying_method == 'Deposit') {
                $lims_customer_data = Customer::find($lims_sale_data->customer_id);
                $lims_customer_data->expense -= $payment->amount;
                $lims_customer_data->save();
            }
            $payment->delete();
        }
        if ($lims_delivery_data)
            $lims_delivery_data->delete();
        if ($lims_sale_data->coupon_id) {
            $lims_coupon_data = Coupon::find($lims_sale_data->coupon_id);
            $lims_coupon_data->used -= 1;
            $lims_coupon_data->save();
        }
        $lims_sale_data->delete();
        $this->fileDelete(public_path('documents/sale/'), $lims_sale_data->document);

        return Redirect::to($url)->with('not_permitted', $message);
    }

    public function registerIPN()
    {
        $pg = DB::table('external_services')->where('name', 'Pesapal')->where('type', 'payment')->first();
        $lines = explode(';', $pg->details);
        $keys = explode(',', $lines[0]);
        $vals = explode(',', $lines[1]);

        $results = array_combine($keys, $vals);

        $APP_ENVIROMENT = $results['Mode'];

        $token = $this->accessToken();

        if ($APP_ENVIROMENT == 'sandbox') {
            $ipnRegistrationUrl = "https://cybqa.pesapal.com/pesapalv3/api/URLSetup/RegisterIPN";
        } elseif ($APP_ENVIROMENT == 'live') {
            $ipnRegistrationUrl = "https://pay.pesapal.com/v3/api/URLSetup/RegisterIPN";
        } else {
            echo "Invalid APP_ENVIROMENT";
            exit;
        }
        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        );
        $data = array(
            "url" => "https://12eb-41-81-142-80.ngrok-free.app/pesapal/pin.php",
            "ipn_notification_type" => "POST"
        );
        $ch = curl_init($ipnRegistrationUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response);
        return $data;
        // $ipn_id = $data->ipn_id;
        // $ipn_url = $data->url;
    }

    public function pesapalIPN()
    {
        return "PESAPAL IPN";
    }

    public function accessToken()
    {
        $pg = DB::table('external_services')->where('name', 'Pesapal')->where('type', 'payment')->first();
        $lines = explode(';', $pg->details);
        $keys = explode(',', $lines[0]);
        $vals = explode(',', $lines[1]);

        $results = array_combine($keys, $vals);

        $APP_ENVIROMENT = $results['Mode'];
        // return $APP_ENVIROMENT;
        if ($APP_ENVIROMENT == 'sandbox') {
            $apiUrl = "https://cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken"; // Sandbox URL
            $consumerKey = $results['Consumer Key']; //env('PESAPAL_CONSUMER_KEY');
            $consumerSecret = $results['Consumer Secret']; //env('PESAPAL_CONSUMER_SECRET');
        } elseif ($APP_ENVIROMENT == 'live') {
            $apiUrl = "https://pay.pesapal.com/v3/api/Auth/RequestToken"; // Live URL
            $consumerKey = "";
            $consumerSecret = "";
        } else {
            echo "Invalid APP_ENVIROMENT";
            exit;
        }
        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];
        $data = [
            "consumer_key" => $consumerKey,
            "consumer_secret" => $consumerSecret
        ];
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response);

        $token = $data->token;

        return $token;
    }
    public function submitOrderRequest($data, $amount)
    {
        $pg = DB::table('external_services')->where('name', 'Pesapal')->where('type', 'payment')->first();
        $lines = explode(';', $pg->details);
        $keys = explode(',', $lines[0]);
        $vals = explode(',', $lines[1]);

        $results = array_combine($keys, $vals);

        $lims_general_setting_data = GeneralSetting::latest()->first();
        $company = $lims_general_setting_data->company_name;

        $APP_ENVIROMENT = $results['Mode'];
        ;
        $token = $this->accessToken();
        $ipnData = $this->registerIPN();

        $merchantreference = rand(1, 1000000000000000000);
        $phone = $data->phone_number; //0768168060
        $amount = $amount;
        $callbackurl = "salepro.test/ipn";
        $branch = $company;
        $first_name = $data->name;
        //$middle_name = "Coders";
        $last_name = $data->name;
        $email_address = $data->email ? $data->email : "hello@lion-coders.com";
        if ($APP_ENVIROMENT == 'sandbox') {
            $submitOrderUrl = "https://cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest";
        } elseif ($APP_ENVIROMENT == 'live') {
            $submitOrderUrl = "https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest";
        } else {
            echo "Invalid APP_ENVIROMENT";
            exit;
        }
        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        );

        // Request payload
        $data = array(
            "id" => "$merchantreference",
            "currency" => "KES",
            "amount" => $amount,
            "description" => "Payment description goes here",
            "callback_url" => "$ipnData->url",
            "notification_id" => "$ipnData->ipn_id",
            "branch" => "$branch",
            "billing_address" => array(
                "email_address" => "$email_address",
                "phone_number" => "$phone",
                "country_code" => "KE",
                "first_name" => "$first_name",
                //"middle_name" => "$middle_name",
                "last_name" => "$last_name",
                "line_1" => "Pesapal Limited",
                "line_2" => "",
                "city" => "",
                "state" => "",
                "postal_code" => "",
                "zip_code" => ""
            )
        );
        $ch = curl_init($submitOrderUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response);
        $redirectUrl = $data->redirect_url;
        return $redirectUrl;
        // echo "<script>window.location.href='$redirectUrl'</script>";
    }
}
