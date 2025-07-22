<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product_Warehouse;
use App\Models\Product;
use App\Models\Adjustment;
use App\Models\ProductAdjustment;
use DB;
use App\Models\StockCount;
use App\Models\ProductVariant;
use App\Models\ProductPurchase;
use App\Models\Supplier;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdjustmentController extends Controller
{
    public function index($product_adjust_action)
    {
        if (!in_array($product_adjust_action, ['inbound', 'outbound'])) {
            abort(404);
        }
        $user = Auth::user();
        $role = Role::find($user->role_id);
        if( $role->hasPermissionTo($product_adjust_action.'-index') ) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach($permissions as $permission){
                $all_permission []= $permission->name;
            }

            $supplier_id = $user->supplier_id ?? 0;
            $start_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d'))))));
            $end_date = date("Y-m-d");

            if($user->supplier_id) {
                $lims_supplier_list = Supplier::where("id", $user->supplier_id)->select("id", "name", "phone_number")->get();
            }
            else {
                $lims_supplier_list = Supplier::where('is_active', true)->get();
            }

            $lims_adjustment_all = Adjustment::orderBy('id', 'desc')->get();
            return view(
                'backend.adjustment.index',
                compact(
                    'lims_adjustment_all', 
                    'lims_supplier_list',
                                'all_permission',
                                'supplier_id',
                                'start_date',
                                'end_date',
                                'product_adjust_action'
                    )
            );
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function get_table(Request $request)
    {
        $columns = [
            1 => 'updated_at',
            5 => 'qty'
        ];

        $orderColumnIndex = $request->input('order.0.column', 1);
        $orderColumn = $columns[$orderColumnIndex] ?? 'updated_at';
        $orderDir = $request->input('order.0.dir', 'desc');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $search = $request->input('search.value');

        $filter = [
            'supplier_id'       => $request->input('supplier_id', 0),
            'adjustment_action' => $request->input('adjustment_action'),
        ];

        $query = ProductAdjustment::with(['product.supplier', 'warehouse'])
            ->whereHas('product', function ($q) {
                $q->where('is_active', true)->whereNull('is_variant');
            });

        // Filters
        if ($filter['adjustment_action']) {
            $query->where('action', $filter['adjustment_action']);
        }

        if ($filter['supplier_id']) {
            $query->whereHas('product', function ($q) use ($filter) {
                $q->where('supplier_id', $filter['supplier_id']);
            });
        }

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
                })->orWhereHas('product.supplier', function ($s) use ($search) {
                    $s->where('name', 'like', "%{$search}%");
                });
            });
        }

        $totalData = ProductAdjustment::whereHas('product', function ($q) {
            $q->where('is_active', true)->whereNull('is_variant');
        })->count();

        $totalFiltered = $query->count();

        $rows = $query->orderBy($orderColumn, $orderDir)
            ->offset($start)
            ->limit($limit == -1 ? $totalFiltered : $limit)
            ->get();

        $role = Role::find(Auth::user()->role_id);

        $data = $rows->map(function ($row) use($role) {
            $product = $row->product;
            $supplier = $product->supplier;
            $warehouse = $row->warehouse;
            
           

            $product_image = explode(",", $product->image);
            $product_image = htmlspecialchars($product_image[0]);
            if ($product_image && $product_image != 'zummXD2dvAtI.png') {
                if (file_exists("public/images/product/small/" . $product_image))
                    $product_image = '<img src="' . url('images/product/small', $product_image) . '" height="80" width="80">';
                else
                    $product_image = '<img src="' . url('images/product', $product_image) . '" height="80" width="80">';
            } else
                $product_image = '<img src="images/zummXD2dvAtI.png" height="80" width="80">';


            $data = [
                'id'             => $row->id,
                'key'            => $row->id,
                'product_img'    => $product_image,
                'product_name'   => $product->name ?? '',
                'product_code'   => $product->code ?? '',
                'adjust_qty'     => $row->qty,
                'warehouse_name' => $warehouse->name ?? '',
                'supplier_name'  => $supplier->name ?? '',
                'updated_at'     => \Carbon\Carbon::parse($row->updated_at)->format('Y-m-d H:i'),
            ];

            $actions = 
            '<div class="btn-group">
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . trans("file.action") . '
                    <span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';

            if($role->hasPermissionTo('inbound-edit')){
                $actions .= 
                    "<li>
                        <a href='#' class='btn btn-link text-info' data-adjustment='".json_encode($data)."' onclick='edit(this)'><i class='fa fa-edit'></i> Edit </a>
                    </li>";
            }
            if($role->hasPermissionTo('inbound-delete')) {
                $actions .= 
                    '<li>
                        <a href="#" class="btn btn-link text-info" onclick="remove(' . $row->id . ')"><i class="fa fa-trash"></i> Delete</a>
                    </li>';
            }
            $data['actions'] = $actions;
            return $data;
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data
        ]);
    }

    public function getProduct($id)
    {
        $lims_product_warehouse_data = DB::table('products')
                                    ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                                    ->whereNull('products.is_variant')
                                    ->where([
                                        ['products.is_active', true],
                                        ['product_warehouse.warehouse_id', $id]
                                    ])
                                    ->select('product_warehouse.qty', 'products.code', 'products.name', 'product_warehouse.product_id', 'products.cost')
                                    ->get();
        $lims_product_withVariant_warehouse_data = DB::table('products')
                                    ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                                    ->whereNotNull('products.is_variant')
                                    ->where([
                                        ['products.is_active', true],
                                        ['product_warehouse.warehouse_id', $id]
                                    ])
                                    ->select('products.name', 'product_warehouse.qty', 'product_warehouse.product_id', 'product_warehouse.variant_id', 'products.cost')
                                    ->get();
        $product_code = [];
        $product_name = [];
        $product_qty = [];
        $product_cost = [];
        $product_data = [];
        foreach ($lims_product_warehouse_data as $product_warehouse)
        {
            $product_qty[] = $product_warehouse->qty;
            $product_code[] =  $product_warehouse->code;
            $product_name[] = $product_warehouse->name;
            $query = array(
                    'SUM(qty) AS total_qty',
                    'SUM(total) AS total_cost'
                );
            $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.product_id', '=', 'purchases.id')
                                    ->where([
                                        ['product_id', $product_warehouse->product_id],
                                        ['warehouse_id', $id]
                                    ])->selectRaw(implode(',', $query))->get();
            if(count($product_purchase_data) && $product_purchase_data[0]->total_qty > 0)
                $product_cost[] = $product_purchase_data[0]->total_cost / $product_purchase_data[0]->total_qty;
            else
                $product_cost[] = $product_warehouse->cost;
        }

        foreach ($lims_product_withVariant_warehouse_data as $product_warehouse)
        {
            $product_variant = ProductVariant::select('item_code')->FindExactProduct($product_warehouse->product_id, $product_warehouse->variant_id)->first();
            if($product_variant) {
                $product_qty[] = $product_warehouse->qty;
                $product_code[] =  $product_variant->item_code;
                $product_name[] = $product_warehouse->name;
                $query = array(
                    'SUM(qty) AS total_qty',
                    'SUM(total) AS total_cost'
                );
                $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.product_id', '=', 'purchases.id')
                                        ->where([
                                            ['product_id', $product_warehouse->product_id],
                                            ['variant_id', $product_warehouse->variant_id],
                                            ['warehouse_id', $id]
                                        ])->selectRaw(implode(',', $query))->get();
                if(count($product_purchase_data) && $product_purchase_data[0]->total_qty > 0)
                    $product_cost[] = $product_purchase_data[0]->total_cost / $product_purchase_data[0]->total_qty;
                else
                    $product_cost[] = $product_warehouse->cost;
                }
        }

        $product_data[] = $product_code;
        $product_data[] = $product_name;
        $product_data[] = $product_qty;
        $product_data[] = $product_cost;
        return $product_data;
    }

    public function limsProductSearch(Request $request)
    {
        $product_code = explode("(", $request['data']);
        $product_info = explode("|", $request['data']);
        $product_code[0] = rtrim($product_code[0], " ");
        $lims_product_data = Product::where([
            ['code', $product_code[0]],
            ['is_active', true]
        ])->first();
        if(!$lims_product_data) {
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.id', 'products.name', 'products.is_variant', 'product_variants.id as product_variant_id', 'product_variants.item_code')
                ->where([
                    ['product_variants.item_code', $product_code[0]],
                    ['products.is_active', true]
                ])->first();
        }

        $product[] = $lims_product_data->name;
        $product_variant_id = null;
        if($lims_product_data->is_variant) {
            $product[] = $lims_product_data->item_code;
            $product_variant_id = $lims_product_data->product_variant_id;
        }
        else
            $product[] = $lims_product_data->code;

        $product[] = $lims_product_data->id;
        $product[] = $product_variant_id;
        $product[] = $product_info[1];
        return $product;
    }

    public function create($action)
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('backend.adjustment.create', compact('lims_warehouse_list', 'action'));
    }

    public function store(Request $request)
    {
        $data = $request->except('document');
        
        $data['reference_no'] = 'adr-' . date("Ymd") . '-'. date("his");
        $document = $request->document;
        if ($document) {
            $documentName = $document->getClientOriginalName();
            $document->move(public_path('documents/adjustment'), $documentName);
            $data['document'] = $documentName;
        }

        $product_id = $data['product_id'];
        $qty = $data['qty'];
        $action = $data['action'];
        $warehouse_id = $data['warehouse_id'];

        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);

            $lims_product_warehouse_data = Product_Warehouse::where([
                ['product_id', $pro_id],
                ['warehouse_id', $warehouse_id ],
                ])->first();

            if($action[$key] == '-') {
                $lims_product_data->qty -= $qty[$key];
                $lims_product_warehouse_data->qty -= $qty[$key];
            }
            elseif($action[$key] == '+') {
                $lims_product_data->qty += $qty[$key];
                $lims_product_warehouse_data->qty += $qty[$key];
            }
            
            $lims_product_data->save();
            $lims_product_warehouse_data->save();

            ProductAdjustment::create([
                'product_id'        => $pro_id,
                'warehouse_id'      => $warehouse_id,
                'variant_id'        => null,
                'adjustment_id'     => 0,
                'qty'               => $qty[$key],
                'action'            => $action[$key]
            ]);
        }
        return response()->json([
            'code'      => 200,
            'msg'       => $request->input('adjustment_action') . ' sucessfully created!!!'
        ]);
    }

    public function update(Request $request)
    {
        $id = $request->input('id');
        $qty = $request->input('qty');


        DB::beginTransaction();
        try{
            $row = ProductAdjustment::find($id);
            $products = Product_Warehouse::where('warehouse_id', $row->warehouse_id)
                                        ->where('product_id', $row->product_id)
                                        ->get();
            
            foreach($products as $key => $product){
                if($row->action == '-'){
                    $product->qty += $row->qty;
                    $product->qty -= $qty;
                }
                else if($row->action == '+'){
                    $product->qty -= $row->qty;
                    $product->qty += $qty;
                }
                $product->save();
            }

            $row->qty = $qty;
            $row->save();

            DB::commit();
        }
        catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                "code"  => 400,
                "msg"   => $e
            ]);
        }
        return response()->json([
            'code'      => 200,
            'msg'       => 'Update Success!!!'
        ]);
    }

    public function deleteBySelection(Request $request)
    {
        $adjustment_id = $request['adjustmentIdArray'];
        foreach ($adjustment_id as $id) {
            $lims_adjustment_data = Adjustment::find($id);
            $this->fileDelete(public_path('documents/adjustment/'), $lims_adjustment_data->document);

            $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
            foreach ($lims_product_adjustment_data as $key => $product_adjustment_data) {
                $lims_product_data = Product::find($product_adjustment_data->product_id);
                if($product_adjustment_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_adjustment_data->product_id, $product_adjustment_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['variant_id', $product_adjustment_data->variant_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();
                    if($product_adjustment_data->action == '-'){
                        $lims_product_variant_data->qty += $product_adjustment_data->qty;
                    }
                    elseif($product_adjustment_data->action == '+'){
                        $lims_product_variant_data->qty -= $product_adjustment_data->qty;
                    }
                    $lims_product_variant_data->save();
                }
                else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();
                }
                if($product_adjustment_data->action == '-'){
                    $lims_product_data->qty += $product_adjustment_data->qty;
                    $lims_product_warehouse_data->qty += $product_adjustment_data->qty;
                }
                elseif($product_adjustment_data->action == '+'){
                    $lims_product_data->qty -= $product_adjustment_data->qty;
                    $lims_product_warehouse_data->qty -= $product_adjustment_data->qty;
                }
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
                $product_adjustment_data->delete();
            }
            $lims_adjustment_data->delete();
        }
        return 'Data deleted successfully';
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try{
            ProductAdjustment::find( $id)->delete();
            DB::commit();
        }
        catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'code'      => 400,
                'msg'       => 'Oops! Server snaps...'
            ]);
        }

        return response()->json([
            'code'      => 200,
            'msg'       => 'Deleted Successfully'
        ]);
    }
}
