<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;

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
use App\Models\User;
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

use App\Models\ProductVariant;
use App\Models\Returns;
use App\Models\ProductReturn;
use App\Models\ProductBatch;
use App\Models\RewardPointSetting;
use App\Models\CustomField;
use App\Models\GeneralSetting;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use App\Models\Currency;
use App\ViewModels\ISmsModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    use \App\Traits\TenantInfo;
    use \App\Traits\MailInfo;

    private $_smsModel;

    public function __construct(ISmsModel $smsModel)
    {
        $this->_smsModel = $smsModel;
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
        $resReason = $data['res_reason_2'] ?? "";
        $cancelReason = $data['res_reason_1'] ?? "";
        $resInfo = $data['res_info'];

        // Prepare sale details for update

        $sale_data = Sale::with([
            'products'
        ])->find($saleId);

        $sale_data->res_type = $resType;

        // Handle each response type
        switch ($resType) {
            case 'confirm':
                if ($location)
                    $sale_data->location = $location;

                $sale_data->sale_status = 7;

                // deduct qty from warehouse
                foreach ($sale_data->products as $prod) {
                    $product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $prod->id],
                        ['warehouse_id', $sale_data->warehouse_id]
                    ])
                        ->select('id', 'qty')
                        ->first();

                    $stock = $product_warehouse_data->qty;
                    $qty = $prod->pivot->qty - $prod->pivot->return_qty;

                    if ($stock <= 0 || $stock < $qty) {
                        return response()->json([
                            "code"  => 400,
                            "msg"   => "Cannot confirm order, product stock insufficient"
                        ]);
                    }
                }

                foreach ($sale_data->products as $prod) {
                    $product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $prod->id],
                        ['warehouse_id', $sale_data->warehouse_id]
                    ])
                        ->select('id', 'qty')
                        ->first();

                    $stock = $product_warehouse_data->qty;
                    $qty = $prod->pivot->qty - $prod->pivot->return_qty;
                    $product_warehouse_data->qty -= $qty;
                    $product_warehouse_data->save();
                }
                break;

            case 'cancel':
                $sale_data->update([
                    'res_reason'    => $cancelReason,
                    'sale_status'   => 11,
                ]);
                break;

            case 'report':
                if ($sale_data->sale_status == 13) {
                    // If a return already exists, do nothing further, but update call-on-date & report-times
                    if (isset($data['call_on_date'])) {
                        $sale_data->call_on = $data['call_on_date'];
                    }
                    if ($resReason) {
                        $sale_data->sale_note = $resReason;
                    }
                    $sale_data->report_times = ($sale_data->report_times ?? 0) + 1;
                } else {
                    $sale_data->update([
                        'res_type'  => $resType,
                        'sale_status' => 13,
                        'sale_note' => $resReason,
                        'staff_note' => $resInfo,
                        "report_times" => 1,
                        "call_on"   => $data['call_on_date'] ?? null,
                    ]);
                }

            default:
                break;
        }

        // Update the sale record
        $sale_data->save();

        return response()->json([
            "code"  => 200,
            "msg"   => "Update Status Success!"
        ]);
    }

    private function returnSale($sale_id)
    {
        DB::beginTransaction();
        try {
            $sale = Sale::find($sale_id);
            if (!$sale) {
                throw new Exception('Sale not found');
            }

            $productSales = Product_Sale::where('sale_id', $sale_id)->get();
            foreach ($productSales as $productSale) {
                $product = Product::find($productSale->product_id);
                if (!$product) {
                    throw new Exception('Product not found');
                }

                $productWarehouse = Product_Warehouse::where('product_id', $productSale->product_id)
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->first();
                if (!$productWarehouse) {
                    throw new Exception('Product warehouse record not found');
                }

                $product->qty += $productSale->qty;
                $productSale->return_qty += $productSale->qty;
                $productWarehouse->qty += $productSale->qty;

                $product->save();
                $productSale->save();
                $productWarehouse->save();
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            // Log the error or handle it as needed
            return false;
        }
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
                Sale::whereIn('id', $sale_id)->update([
                    'sale_status'       => 12
                ]);
                return $data;
            case "shipped":
                $sale_details["sale_status"] = 8;
                break;

            case "shipping":
                $sale_details["sale_status"] = 9;
                $sale_details["shipping_cost"] = $data["shipping_cost"];
                // Update shipping cost in purchase and sale tables
                break;

            case "return_ship":
                $sale_details["sale_status"] = 14; // return receiving
                $sale_details["return_shipping_cost"] = $data["return_shipping_cost"];
                break;

            case 'recover_ship':
                $sale_details["sale_status"] = 8; // shipped
                $sale_details['shipping_cost'] = 0;
                $sale_details['return_shipping_cost'] = 0;
                break;
            case 'add_tracking_code':
                DB::table('sales')->where('id', $sale_id)->update([
                    'tracking_code'     => $data['tracking_code']
                ]);
                return;

            case "return_receiving":
                $sale_details["sale_status"] = 4; // return receiving - return
                $sale_details["return_shipping_cost"] = $data["return_shipping_cost"];
                $this->returnSale($sale_id);
                break;

            case "cancel_order":
                $sale_details["sale_status"] = 11; // cancel
                $sale_details["res_type"] = "cancel_order";
                $sale_details["res_reason"] = $data["res_reason_1"];
                $sale_details["res_info"] = $data["res_info"];
                break;

            case "reset_order": // cancel - unpaid
                $sale_details["sale_status"] = 6;
                break;
        }

        // Update the sale record
        Sale::where('id', $sale_id)->update($sale_details);
    }

    // krishna singh - https://linktr.ee/iamsinghkrishna
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = Role::find($user->role_id);

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

            if ($request->input('location'))
                $location = $request->input('location');
            else
                $location = 0;


            if ($request->input('product_code'))
                $product_code = $request->input('product_code');
            else
                $product_code = 0;

            if ($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            } else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d'))))));
                $ending_date = date("Y-m-d");
            }

            if ($request->input('supplier_id')) {
                $supplier_id = $request->input('supplier_id');
            } else {
                $supplier_id = 0;
            }

            if ($user->supplier_id) {
                $supplier_id = $user->supplier_id;
                $lims_supplier_list = []; //Supplier::where("id", $user->supplier_id)->select("id", "name", "phone_number")->get();
            } else {
                $lims_supplier_list = Supplier::where('is_active', true)->get();
            }

            $lims_product_codes = Product_Sale::join('products', 'products.id', '=', 'product_sales.product_id')
                ->when($supplier_id, function ($query) use ($supplier_id) {
                    return $query->where('product_sales.supplier_id', $supplier_id);
                })
                ->select('products.code')
                ->distinct()
                ->get();

            $lims_pos_setting_data = PosSetting::latest()->first();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_account_list = Account::where('is_active', true)->get();
            $lims_general_setting_data = GeneralSetting::latest()->select('shipping_cost_list', 'return_shipping_cost_list')->first();
            $lims_shipping_cost_list = explode(',', $lims_general_setting_data->shipping_cost_list);
            $lims_return_shipping_cost_list = explode(',', $lims_general_setting_data->return_shipping_cost_list);

            $can_scanner = ($role->hasPermissionTo('return-receiving') && $sale_status == 14)
                || ($role->hasPermissionTo('receiving') && $sale_status == 12)
                || ($role->hasPermissionTo('shipped-return') && $sale_status == 8);
            return view(
                'backend.sale.index',
                compact(
                    'starting_date',
                    'ending_date',
                    'warehouse_id',
                    'lims_warehouse_list',
                    'sale_status',
                    'location',
                    'supplier_id',
                    'lims_supplier_list',
                    'product_code',
                    'lims_product_codes',
                    'can_scanner',
                    'lims_shipping_cost_list',
                    'lims_return_shipping_cost_list',
                    'lims_account_list',
                    'all_permission'
                )
            );
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
        $user = Auth::user();

        $columns = [
            1  => 'sales.reference_no',
            2  => 'products.name',
            3  => 'products.code',
            4  => 'suppliers.name',
            5  => 'sales.created_at',
            7  => 'sales.total_qty',
            8  => 'sales.total_price',
            12 => 'sales.updated_at',
            13 => 'sales.location'
        ];

        $orderColumn = $columns[$request->input('order.0.column')] ?? 'sales.created_at';
        $orderDir = $request->input('order.0.dir', 'desc');

        $filters = [
            'warehouse_id' => $request->input('warehouse_id'),
            'supplier_id'  => $request->input('supplier_id') ?? 0,
            'product_code' => $request->input('product_code') ?? 0,
            'sale_status'  => $request->input('sale_status'),
            'location'     => $request->input('location'),
            'start_date'   => $request->input('starting_date'),
            'end_date'     => $request->input('ending_date'),
        ];

        // Base query with join for sorting + eager loading
        $baseQuery = Sale::query();

        // We'll eager load after we determine the matching sale IDs

        if ($filters['sale_status'] == 4 || $filters['sale_status'] == 9) { // received or signed
            $baseQuery->whereDate('sales.updated_at', '>=', $filters['start_date'])
                ->whereDate('sales.updated_at', '<=', $filters['end_date']);
        } else { // others
            $baseQuery->whereDate('sales.created_at', '>=', $filters['start_date'])
                ->whereDate('sales.created_at', '<=', $filters['end_date']);
        }

        // Role-based access
        if ($user->role_id > 2) {
            if (config('staff_access') === 'own') {
                $baseQuery->where('sales.user_id', $user->id);
            } elseif (config('staff_access') === 'warehouse') {
                $baseQuery->where('sales.warehouse_id', $user->warehouse_id);
            }
        }

        // Apply basic filters
        foreach (['warehouse_id', 'sale_status', 'location'] as $field) {
            if (!empty($filters[$field])) {
                $baseQuery->where("sales.$field", $filters[$field]);
            }
        }

        $special_supplier_uids = User::where('is_special', 1)->pluck('supplier_id');

        if ($user->supplier_id) {
            // Group the supplier_product check with the user_id OR so the OR doesn't
            // bypass other filters (e.g., sale_status or date filters).
            $baseQuery->where(function ($q) use ($user) {
                $q->whereHas('products', function ($qp) use ($user) {
                    $qp->where('products.supplier_id', $user->supplier_id);
                })
                    ->orWhere('sales.user_id', $user->id);
            });
        } else {
            $supplier_uid = \App\Models\User::where('supplier_id', $filters['supplier_id'])->first()->id;
            if ($filters['supplier_id']) {
                $baseQuery->where(function ($q) use ($filters, $supplier_uid) {
                    $q->whereHas('products', function ($qp) use ($filters) {
                        $qp->where('products.supplier_id', $filters['supplier_id']);
                    })
                        ->orWhere('sales.user_id', $supplier_uid);
                });
            }
        }

        // Search (use whereHas for relations to avoid depending on explicit joins)
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->where('reference_no', 'LIKE', "%{$searchValue}%")
                    ->orWhereHas('customer', function ($qc) use ($searchValue) {
                        $qc->where('name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('phone_number', 'LIKE', "%{$searchValue}%");
                    })
                    ->orWhereHas('products', function ($qp) use ($searchValue) {
                        $qp->where('products.name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('products.code', 'LIKE', "%{$searchValue}%");
                    })
                    ->orWhereHas('products.supplier', function ($qs) use ($searchValue) {
                        $qs->where('name', 'LIKE', "%{$searchValue}%");
                    });
            });
        }

        // Counts (filtered and total)
        $totalData = Sale::count();
        $totalFiltered = (clone $baseQuery)->distinct('sales.id')->count('sales.id');

        // Pagination params
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $limit = ($limit == -1) ? $totalFiltered : $limit;

        // Step 1: fetch matching sale IDs (distinct) with ordering & limit/offset applied
        $idQuery = (clone $baseQuery)->select('sales.id')->distinct('sales.id')->orderBy($orderColumn, $orderDir);
        // If ordering uses product or supplier columns, we need to join those tables so ORDER BY works
        if (strpos($orderColumn, 'products.') !== false || strpos($orderColumn, 'suppliers.') !== false) {
            $idQuery = $idQuery->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                ->join('products', 'products.id', '=', 'product_sales.product_id')
                ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
                ->select('sales.id');
        }
        if ($limit != -1) {
            $idQuery = $idQuery->offset($start)->limit($limit);
        }
        $saleIds = $idQuery->pluck('id')->toArray();

        // Step 2: eager load the sales by IDs (preserves eager loading, avoids duplicates)
        if (empty($saleIds)) {
            $sales = collect();
        } else {
            $salesQuery = Sale::with(['customer', 'warehouse', 'user', 'products.supplier'])
                ->whereIn('id', $saleIds);

            // If ordering was requested on related columns (products/suppliers), the idQuery
            // already computed the correct order. Preserve that order using FIELD().
            if (strpos($orderColumn, 'products.') !== false || strpos($orderColumn, 'suppliers.') !== false) {
                // FIELD requires a comma-separated list of ids; preserve the idQuery ordering
                $idsList = implode(',', array_map('intval', $saleIds));
                // Use orderByRaw to apply the FIELD order
                $salesQuery = $salesQuery->orderByRaw("FIELD(sales.id, $idsList)");
            } else {
                $salesQuery = $salesQuery->orderBy($orderColumn, $orderDir);
            }

            $sales = $salesQuery->get();
        }

        $data = [];
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
            13 => ['danger',    'Rported'],
            14 => ['warning',   'Return Receiving']
        ];
        foreach ($sales as $key => $sale) {
            $nestedData = [
                'id'               => $sale->id,
                'key'              => $sale->id,
                'reference_no'     => $sale->reference_no,
                'product_name'     => "",
                'product_code'     => "",
                'supplier_name'    => "",
                'date'             => date(config('date_format') . ' h:i:s', strtotime($sale->created_at)),
                'sale_status'      => $sale->sale_status,
                'total_qty'        => $sale->total_qty,
                'total_price'      => number_format($sale->total_price, config('decimal')),
                'delivery_fee'     => ($filters['sale_status'] != 14 && $filters['sale_status'] != 4)
                    ? ($sale->shipping_cost ?? 0)
                    : ($sale->return_shipping_cost ?? 0),
                'customer_info'    => ($sale->customer->name ?? '') . '<br>' . ($sale->customer->phone_number ?? ''),
                'customer_address' => ($sale->customer->address ?? '') . '<br>' . ($sale->customer->city ?? ''),
                'updated_date'     => \Carbon\Carbon::parse($sale->updated_at)->format('Y-m-d H:i:s'),
                'location'         => $sale->location,
                'res_reason'       => $sale->res_reason,
            ];

            $second = 0;
            foreach ($sale->products as $product) {
                if ($second) {
                    $nestedData['product_name'] .= '<br>';
                    $nestedData['product_code'] .= ',';
                    $nestedData['supplier_name'] .= '<br>';
                }
                $nestedData['product_name'] .= $product->name . ' x' . $product->pivot->qty;
                $nestedData['product_code'] .= $product->code;

                if ($special_supplier_uids->contains($product->supplier_id)) {
                    $nestedData['supplier_name'] .= User::find($sale->user_id)?->name ?? '';
                } else {
                    $nestedData['supplier_name'] .= $product->supplier->name;
                }

                $second++;
            }

            switch ($sale->location) {
                case '1':
                    $nestedData['location'] = "Inside Accra";
                    break;
                case '2':
                    $nestedData['location'] = "Outside Accra";
                    break;
                case "3":
                    $nestedData["location"] = "Kumasi";
                    break;
                default:
                    $nestedData['location'] = "Undefined";
            }

            list($badgeClass, $statusText) = $statusMap[$sale->sale_status] ?? ['secondary', 'Undefined'];
            $nestedData['sale_status'] = '<div class="badge badge-' . $badgeClass . '">' . trans($statusText) . '</div>';

            // RBAC and options
            $role = Role::find($user->role_id);

            $nestedData['options'] = ' ';
            if ($sale->sale_status == 1) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-info" onclick="return_ship(' . $sale->id . ')">return</button>';
            } else if ($sale->sale_status == 4 && $role->hasPermissionTo('returned')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-primary" onclick="recover_ship(' . $sale->id . ')">Recover</button>';
            } else if ($sale->sale_status == 6 && $role->hasPermissionTo('unpaid')) {
                $nestedData['options'] =
                    '<div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . trans("file.action") . '
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                ';

                if ($role->hasPermissionTo('unpaid-edit')) {
                    $nestedData['options'] .=
                        '<li>
                            <a href="#" class="btn btn-link text-info" onclick="editx(' . $sale->id . ')">edit</a>
                        </li>';
                }
                if ($role->hasPermissionTo('unpaid-confirm')) {
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
                    foreach ($sale->products as $product) {
                        $temp = [
                            'id'            => $product->id,
                            'product_sale_id' => $product->pivot->id,
                            'product_name'  => $product->name,
                            'img'           => explode(',', $product->image),
                            'price'         => $product->pivot->net_unit_price,
                            'qty'           => $product->pivot->qty - $product->pivot->return_qty,
                            'amount'        => $product->pivot->net_unit_price * ($product->pivot->qty - $product->pivot->return_qty),
                        ];
                        $confirm_data['products'][] = $temp;
                        $confirm_data['product_amount'] += $temp['amount'];
                    }
                    $confirm_json = htmlspecialchars(json_encode($confirm_data), ENT_QUOTES, 'UTF-8');

                    $nestedData['options'] .=
                        '<li>
                            <a href="#" class="update-status btn btn-link text-success" data-confirm="' . $confirm_json . '" onclick="update_status(this)">confirm</a>
                        </li>';
                }
                if ($role->hasPermissionTo('unpaid-cancel')) {
                    $nestedData['options'] .=
                        '<li>
                            <a href="#" class="update-status btn btn-link text-danger" onclick="cancel_order(' . $sale->id . ')">cancel</a>
                        </li>';
                }

                $nestedData['options'] .= '</ul></div>';
            } else if ($sale->sale_status == 7 && $role->hasPermissionTo('confirmed')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-dark print-waybill"> Print Waybill </button>';
            } else if ($sale->sale_status == 8 && $role->hasPermissionTo('shipped')) {
                if ($role->hasPermissionTo('shipped-sign')) {
                    $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-info" onclick="update_shipping_fee(' . $sale->id . ', ' . $sale->shipping_cost . ')">Sign</button>';
                }
                if ($role->hasPermissionTo('shipped-return')) {
                    $nestedData['options'] .= ' <button type="button" class="update-status btn btn-link text-info" onclick="return_ship(' . $sale->id . ')">Return</button>';
                }
                if ($role->hasPermissionTo('add-tracking-code')) {
                    $nestedData['options'] .= ' <button type="button" class="update-status btn btn-link text-info" onclick="add_tracking_code(' . $sale->id . ')">Add tracking code</button>';
                }
            } else if ($sale->sale_status == 9 && $role->hasPermissionTo('signed')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-primary" onclick="recover_ship(' . $sale->id . ')">Recover</button>';
                $nestedData['options'] .= ' <button type="button" class="update-status btn btn-link text-info" onclick="update_shipping_fee(' . $sale->id . ', ' . $sale->shipping_cost . ')">Revise</button>';
            } else if ($sale->sale_status == 11 && $role->hasPermissionTo('cancelled')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-danger" onclick="reset_order(' . $sale->id . ')"><i class="fa fa-refresh"></i> Confirm</button>';
            } else if ($sale->sale_status == 12 && $role->hasPermissionTo('receiving')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-info" onclick="update_status_filters_shipped(' . $sale->id . ')">shipped</button>';
            } else if ($sale->sale_status == 14 && $role->hasPermissionTo('return-receiving')) {
                $nestedData['options'] = ' <button type="button" class="update-status btn btn-link text-info" onclick="return_receiving_sign(' . $sale->id . ')">sign</button>';
            }

            // sale-details
            $nestedData['sale'] = json_encode([
                'sale_id'               => $sale->id,
                'reference_no'          => $sale->reference_no,
                'sale_status'           => $sale->sale_status,
                'product_name'          => $nestedData['product_name'],
                'product_code'          => $nestedData['product_code'],
                'warehouse'             => $sale->warehouse->name,
                'customer_name'         => $sale->customer->name ?? 'Undefined',
                'customer_phone_number' => $sale->customer->phone_number ?? '',
                'customer_address'      => $sale->customer->address ?? '',
                'customer_city'         => $sale->customer->city ?? '',
                'date'                  => \Carbon\Carbon::parse($sale->updated_at)->format('Y-m-d H:i:s'),
                'total_qty'             => $sale->total_qty,
                'total_price'           => $nestedData['total_price'],
                'location'              => $nestedData['location'],
                'tracking_code'         => $sale->tracking_code,
            ]);

            $data[] = $nestedData;
        }

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
            "suppliers" => $filters['supplier_id'],
        ];

        return response()->json($json_data);
    }

    public function saleScan(Request $request)
    {
        $user = Auth::user();
        $role = Role::find($user->role_id);
        $searchValue = $request->search;

        $sale = Sale::where('reference_no', $searchValue)->select('id', 'sale_status')->first();
        $status = $request->sale_status;

        if ($searchValue) { //  E1839795360176649
            if (preg_match('/^E\d{16}$/', $searchValue) === 1) {
                if ($role->hasPermissionTo('receiving') || $role->hasPermissionTo('return-receiving') || $role->hasPermissionTo('shipped')) {
                    if ($status == 12 && $sale->sale_status == 12) {  // receiving -> shipped
                        $sale->fill([
                            'sale_status' => 8
                        ]);
                    } else if ($status == 8 && $sale->sale_status == 8) { // shipped-> return receiving
                        $lims_general_setting_data = GeneralSetting::latest()->select('shipping_cost_list', 'return_shipping_cost_list')->first();
                        $return_shipping_cost_list = explode(',', $lims_general_setting_data->return_shipping_cost_list);
                        $sale->fill([
                            'sale_status' => 14,
                            'return_shipping_cost'  => intval($return_shipping_cost_list[0]),
                        ]);
                    } else if ($status == 14 && $sale->sale_status == 14) { // return receiving -> return
                        // $delivery_fee = 
                        $lims_general_setting_data = GeneralSetting::latest()->select('shipping_cost_list', 'return_shipping_cost_list')->first();
                        $return_shipping_cost_list = explode(',', $lims_general_setting_data->return_shipping_cost_list);
                        $sale->fill([
                            'sale_status' => 4,
                            'return_shipping_cost'  => intval($return_shipping_cost_list[0]),
                        ]);
                        $this->returnSale($sale->id); // added 7.17 - removed 7.20
                    }

                    $sale->save();
                    return response()->json([
                        "code"  => 200,
                        "msg"   => "Scan Order Success!"
                    ]);
                }
            }
        }
        return response()->json([
            "code"  => 400,
            "msg"   => "No Scan!"
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $role = Role::find($user->role_id);
        Log::info("User Role Supplier ID : " . $user->supplier_id);
        if ($role->hasPermissionTo('sales-add')) {
            $lims_customer_list = Customer::where('is_active', true)
                ->when($user->supplier_id, function ($q) use ($user) {
                    return $q->where('supplier_id', '=', $user->supplier_id);
                })
                ->get();
            if ($user->role_id > 2) {
                $lims_warehouse_list = Warehouse::where([
                    ['is_active', true],
                    ['id', $user->warehouse_id]
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

            return view('backend.sale.create', compact('currency_list', 'lims_customer_list', 'lims_warehouse_list', 'lims_pos_setting_data', 'lims_tax_list', 'lims_reward_point_setting_data', 'options', 'numberOfInvoice'));
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
        foreach ($data['product_id'] as $i => $product_id) {
            if ($data['qty'][$i] <= 0)
                return [
                    "code"  => "400",
                    "msg"   => "Product Quantity Nagative Error!"
                ];

            $product_warehouse_data = Product_Warehouse::where([
                ['product_id', $product_id],
                ['warehouse_id', $data['warehouse_id']]
            ])->select('id', 'qty')->first();

            if ($product_warehouse_data->qty < $data['qty'][$i]) {
                return response()->json([
                    "code"  => "400",
                    "msg"   => "Product Not Enough to Order!"
                ]);
            }
        }

        $data['user_id'] = Auth::id();
        $data['payment_status'] = 1;

        if (isset($data['created_at']))
            $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at']))) . ' ' . date("H:i:s");
        else
            $data['created_at'] = date("Y-m-d H:i:s");

        if (!isset($data['reference_no']))
            $data['reference_no'] = 'E' . base_convert(uniqid(), 16, 10);

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
            if (empty(config('database.connections.saleprosaas_landlord'))) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            }
            $data['document'] = $documentName;
        }

        if (isset($data['table_id'])) {
            $latest_sale = Sale::whereNotNull('table_id')->whereDate('created_at', date('Y-m-d'))->where('warehouse_id', $data['warehouse_id'])->select('queue')->orderBy('id', 'desc')->first();
            if ($latest_sale)
                $data['queue'] = $latest_sale->queue + 1;
            else
                $data['queue'] = 1;
        }

        //inserting data to sales table
        $data['sale_status'] = 6; // unpaid
        $data['res_type'] = 'new'; // adding

        $lims_sale_data = Sale::create($data);

        $product_ids = $data['product_id'];
        $qty = $data['qty'];
        $net_unit_price = $data['net_unit_price'];
        $total = $data['subtotal'];

        foreach ($product_ids as $i => $id) {

            $lims_product_data = Product::where('id', $id)->first();

            // Products Sale Create
            $product_sale = [
                'supplier_id'      => $lims_product_data->supplier_id,
                'sale_id'          => $lims_sale_data->id,
                'product_id'       => $id,
                'qty'              => $qty[$i],
                'net_unit_price'   => $net_unit_price[$i],
                'discount'         => $lims_sale_data->order_discount,
                'tax_rate'         => $lims_sale_data->order_tax_rate,
                'sale_unit_id'     => 1,
                'variant_id'       => null,
                'product_batch_id' => null,
                'imei_number'      => null,
                'tax'              => 0,
                'total'            => $total[$i]
            ];
            Product_Sale::create($product_sale);
        }

        return redirect('sales')->with('message', 'Sale created successfully');
    }

    public function getProduct($id)
    {
        $supplier_id = Auth::user()->supplier_id;

        $special_supplier_ids = User::where('is_special', 1)->get()->map(function ($item) {
            return $item->supplier_id;
        });
        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->when($supplier_id, function ($q) use ($supplier_id, $special_supplier_ids) {
                // Group supplier filters so the OR does not escape the intended where scope.
                $q->where(function ($sq) use ($supplier_id, $special_supplier_ids) {
                    $sq->where('products.supplier_id', $supplier_id);
                    if (!empty($special_supplier_ids)) {
                        $sq->orWhereIn('products.supplier_id', $special_supplier_ids);
                    }
                });
                return $q;
            });

        if (config('without_stock') == 'no') {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id],
                // ['product_warehouse.qty', '>', 0]
            ]);
        } else {
            $query = $query->where([
                ['products.is_active', true],
                ['product_warehouse.warehouse_id', $id]
            ]);
        }

        //retrieve product with type of digital and service
        $lims_product_data = $query
            ->select('product_warehouse.*', 'products.name', 'products.code', 'products.price', 'products.type', 'products.product_list', 'products.qty_list', 'products.is_embeded')
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
            $product_price[] = $product->price;
        }
        $product_data = [$product_code, $product_name, $product_qty, $product_type, $product_id, $product_list, $qty_list, $product_price, $batch_no, $product_batch_id, $expired_date, $is_embeded, $imei_number];
        return $product_data;
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

    public function limsProductSearch(Request $request)
    {
        $todayDate = date('Y-m-d');
        $product_data = explode("|", $request['data']);

        $product_info = explode("?", $request['data']);
        $customer_id = $product_info[1];

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
                    $product[] = $lims_product_data->price - $discount->value; //@dorian
                } elseif ($discount->type == 'percentage') {
                    $product[] = $lims_product_data->price - ($lims_product_data->price * ($discount->value / 100)); //@dorian
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
            $product[] = 0;
            $product[] = $lims_tax_data->name;
        } else {
            $product[] = 0;
            $product[] = 'No Tax';
        }
        $product[] = $lims_product_data->tax_method ?? 1;
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
        $product[] = $lims_product_data->id; // 10
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

    public function saleTracking()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('sales-tracking')) {
            $numberOfInvoice = Sale::count();
            return view('backend.sale.tracking', compact('numberOfInvoice'));
        } else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function importSale(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $products = Excel::toArray(
            new class implements ToArray {
                public function array(array $array)
                {
                    return $array;
                }
            },
            $request->file('file')
        );

        $field_name = $products[0][0];

        $warehouse_id = $request->input('warehouse_id');
        $sale_note = $request->input('sale_note') ?? "N/A";
        $staff_note = $request->input('staff_note') ?? "N/A";
        $order_tax_rate = $request->input('order_tax_rate') ?? 0;
        $order_discount = $request->input('order_discount') ?? 0;
        $shipping_cost = $request->input('shipping_cost') ?? 0;

        $rows = sizeof($products[0]);
        for ($i = 1; $i < $rows; $i++) {
            $row = $products[0][$i];

            if ($row[0] == null) break;

            $product = Product::with('warehouse')->where('code', $row[1])->first();

            if (!$product) {
                return response()->json([
                    'code'      => 400,
                    'msg'       => $row[0] . ' does not exist in product',
                ]);
            }

            $warehouse = $product->warehouse->firstWhere('id', $warehouse_id);

            if (!$warehouse) {
                return response()->json([
                    'code'      => 400,
                    'msg'       => $row[0] . ' does not exist in warehouse'
                ]);
            }
        }

        $queue = 0;

        $result = [];
        DB::beginTransaction();
        try {
            for ($i = 1; $i < $rows; $i++) {
                $row = $products[0][$i];

                if ($row[0] == null || $row[0] == "") break;

                $price = floatval($row[2]);
                $qty = floatval($row[3]);
                $total = $price * $qty;

                $product = Product::with('warehouse')->where('code', $row[1])->first();

                $customer = Customer::create([
                    'customer_group_id' => 1,
                    'name'          => $row[4],
                    'phone_number'  => $row[5],
                    'address'       => $row[6],
                    'city'          => $row[7]
                ]);

                $data = [
                    'user_id'       => Auth::id(),
                    'payment_status' => 1,
                    'created_at'    => date("Y-m-d H:i:s"),
                    'reference_no'  => 'E' . base_convert(uniqid(), 16, 10),
                    'sale_status'   => 6,
                    'customer_id'   => $customer->id,
                    'warehouse_id'  => $warehouse_id,
                    'item'          => $qty,
                    'total_qty'     => $qty,
                    'total_discount' => $order_discount * $qty,
                    'total_tax'     => 0,
                    'total_price'   => $qty * $price,
                    'grand_total'   => $qty * ($price - $order_discount),
                    'order_discount' => $order_discount,
                    'sale_note'     => $sale_note,
                    'staff_note'    => $staff_note,
                    'order_tax_rate' => $order_tax_rate,
                    'res_type'      => 'import',
                    'tracking_code' => $row[8] ?? "",
                    'queue'         => ++$queue
                ];

                $lims_sale_data = Sale::create($data);
                $result[] = $lims_sale_data;

                Product_Sale::create([
                    'supplier_id'      => $product->supplier_id,
                    'sale_id'          => $lims_sale_data->id,
                    'product_id'       => $product->id,
                    'net_unit_price'   => $price,
                    'qty'              => $qty,
                    'discount'         => $order_discount ?? 0,
                    'tax_rate'         => $lims_sale_data->order_tax_rate,
                    'sale_unit_id'     => 1,
                    'variant_id'        => null,
                    'product_batch_id'  => null,
                    'imei_number'       => null,
                    'tax'               => 0,
                    'shipping_cost'     => $shipping_cost,
                    'total'             => $total
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'code'      => 500,
                'msg'       => $e
            ]);
        }

        return response()->json([
            'code'      => 200,
            'msg'       => 'Creating Order Successful',
            'data'  => $result,
            'rows'  => $products
        ]);
    }

    public function importTracking(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $products = Excel::toArray(
            new class implements ToArray {
                public function array(array $array)
                {
                    return $array;
                }
            },
            $request->file('file')
        );

        $field_name = $products[0][0];

        $rows = sizeof($products[0]);
        $result = [];

        DB::beginTransaction();
        try {
            for ($i = 1; $i < $rows; $i++) {
                $row = $products[0][$i];

                if ($row[0] == null || $row[0] == "") break;

                $reference_no = trim($row[0], " \t\n\r\0\x0B\xC2\xA0");
                $tracking_code = trim($row[1], " \t\n\r\0\x0B\xC2\xA0");

                $lims_sale_data = DB::table('sales')->where('reference_no', $reference_no)->first();

                if ($lims_sale_data && $lims_sale_data->sale_status == 8) {
                    DB::table('sales')
                        ->where('reference_no', $reference_no)
                        ->update(['tracking_code' => $tracking_code]);

                    $result[] = $lims_sale_data;
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'code'      => 500,
                'msg'       => $e
            ]);
        }

        return response()->json([
            'code'      => 200,
            'msg'       => 'Tracking Order Successfully',
            'data'      => $result
        ]);
    }

    public function edit($id)
    {

        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('unpaid-edit')) {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_sale_data = Sale::find($id);
            $lims_customer_data = Customer::find($lims_sale_data->customer_id);
            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();

            // Preload products and taxes to avoid DB queries in Blade (N+1)
            $productIds = $lims_product_sale_data->pluck('product_id')->unique()->filter()->values()->all();
            $products = collect();
            if (!empty($productIds)) {
                $products = DB::table('products')->whereIn('id', $productIds)->get()->keyBy('id');
            }

            $taxRates = $lims_product_sale_data->pluck('tax_rate')->unique()->filter()->values()->all();
            $taxes = collect();
            if (!empty($taxRates)) {
                $taxes = DB::table('taxes')->whereIn('rate', $taxRates)->get()->keyBy('rate');
            }

            // Preload coupon if exists
            $coupon_data = null;
            if ($lims_sale_data->coupon_id) {
                $coupon_data = Coupon::find($lims_sale_data->coupon_id);
            }
            if ($lims_sale_data->exchange_rate)
                $currency_exchange_rate = $lims_sale_data->exchange_rate;
            else
                $currency_exchange_rate = 1;
            $custom_fields = CustomField::where('belongs_to', 'sale')->get();
            return view('backend.sale.edit', compact('lims_customer_data', 'lims_warehouse_list', 'lims_biller_list', 'lims_tax_list', 'lims_sale_data', 'lims_product_sale_data', 'currency_exchange_rate', 'custom_fields', 'products', 'taxes', 'coupon_data'));
        } else
            return  response()->view('errors.403', [], 403);
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
        // unpaid status only
        $data = $request->except('document');

        $document = $request->document;
        $lims_sale_data = Sale::find($id);
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();

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

        $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at']))) . ' ' . date("H:i:s");
        $product_ids = $data['product_id'];
        $qty = $data['qty'];
        $net_unit_price = $data['net_unit_price'];
        $discount = $data['discount'];
        $total = $data['subtotal'];
        $old_product_ids = [];
        $product_sale = [];

        // delete products, no need to qty adjustment, because this is unpaid order
        foreach ($lims_product_sale_data as $product_sale) {
            $old_product_ids[] = $product_sale->product_id;
            if (!in_array($product_sale->product_id, $product_ids)) {
                $product_sale->delete();
            }
        }

        //dealing with new products
        foreach ($product_ids as $key => $pro_id) {

            $lims_product_data = Product::find($pro_id);

            $product_sale = [
                'sale_id'          => $id,
                'product_id'       => $pro_id,
                'supplier_id'      => $lims_product_data->supplier_id,
                'qty'              => $qty[$key],
                'net_unit_price'   => $net_unit_price[$key],
                'discount'         => $discount[$key],
                'tax'              => 0,
                'tax_rate'         => 0,
                'sale_unit_id'     => 1,
                'variant_id'       => null,
                'product_batch_id' => null,
                'imei_number'      => null,
                'total'            => $total[$key]
            ];

            if (in_array($pro_id, $old_product_ids)) {
                Product_Sale::where([
                    "sale_id"       => $id,
                    "product_id"    => $pro_id
                ])->update($product_sale);
            } else {
                Product_Sale::create($product_sale);
            }
        }

        $lims_sale_data->update([
            'item'                      => $data['item'],
            'total_qty'                 => $data['total_qty'],
            'total_discount'            => $data['total_discount'],
            'order_tax'                 => $data['order_tax'],
            'order_tax_rate'            => $data['order_tax'],
            'order_discount_type'       => $data['order_discount_type'],
            'order_discount'            => $data['order_discount'],
            'order_discount_value'      => $data['order_discount_value'],
            'shipping_cost'             => $data['shipping_cost'],
            'sale_note'                 => $data['sale_note'],
            'staff_note'                => $data['staff_note'],
            'total_price'               => $data['total_price'],
            'grand_total'               => $data['grand_total']
        ]);
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        return [$lims_product_sale_data, $data];
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

    public function deleteBySelection(Request $request)
    {
        $sale_id = $request['saleIdArray'];
        foreach ($sale_id as $id) {
            $lims_sale_data = Sale::find($id);

            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();

            foreach ($lims_product_sale_data as $product_sale) {
                $lims_product_data = Product::find($product_sale->product_id);
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $product_sale->product_id],
                    ['warehouse_id', $lims_sale_data->warehouse_id]
                ])->first();
                if ($lims_sale_data->sale_status == 7 || $lims_sale_data->sale_status == 8 || $lims_sale_data->sale_status == 9) {
                    //adjust product quantity
                    $lims_product_data->qty += $product_sale->qty;
                    $lims_product_warehouse_data->qty += $product_sale->qty;
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }

                $product_sale->delete();
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
}
