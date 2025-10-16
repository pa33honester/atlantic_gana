<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{

    public function supplierReport(Request $request)
    {
        $user = Auth::user();

        $supplier_id = $request->input('supplier_id');

        if ($request->input('start_date')) {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
        } else {
            $start_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d'))))));
            $end_date = date("Y-m-d");
        }

        if ($user->supplier_id) {
            $lims_supplier_list = Supplier::where("id", $user->supplier_id)->select('id', 'name', 'phone_number')->get();
        } else {
            $lims_supplier_list = Supplier::where('is_active', true)->get();
        }

        $query = Sale::where('sale_status', 9)->whereDate('updated_at', '>=', $start_date)->whereDate('updated_at', '<=', $end_date);
        $query2 = Sale::where('sale_status', 4)->whereDate('updated_at', '>=', $start_date)->whereDate('updated_at', '<=', $end_date);

        if ($user->supplier_id) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('products', function ($qp) use ($user) {
                    $qp->where('products.supplier_id', $user->supplier_id);
                })
                    ->orWhere('sales.user_id', $user->id);
            });
            $query2->where(function ($q) use ($user) {
                $q->whereHas('products', function ($qp) use ($user) {
                    $qp->where('products.supplier_id', $user->supplier_id);
                })
                    ->orWhere('sales.user_id', $user->id);
            });
        } else {
            if (intval($supplier_id) > 0) {
                $supplier_uid = User::where('supplier_id', $supplier_id)->first()->id;
                $query->whereHas('products', function ($q) use ($supplier_id) {
                    $q->where('products.supplier_id', $supplier_id);
                });
                $query2->whereHas('products', function ($q) use ($supplier_id) {
                    $q->where('products.supplier_id', $supplier_id);
                });

                if ($supplier_uid) {
                    $query->orWhere('sales.user_id', $supplier_uid);
                    $query2->orWhere('sales.user_id', $supplier_uid);
                }
            }
        }

        $signed_data = $query->selectRaw('SUM(sales.total_price) as total_price,
                                SUM(sales.shipping_cost) as shipping_cost')
            ->first();


        $returned_data = $query2->selectRaw('SUM(shipping_cost) as shipping_cost, 
                                                SUM(return_shipping_cost) as return_shipping_cost')
            ->first();

        $signed_total = $signed_data->total_price - $signed_data->shipping_cost;
        $returned_total = $returned_data->shipping_cost + $returned_data->return_shipping_cost;

        return view(
            'backend.report.supplier_report',
            compact(
                'start_date',
                'end_date',
                'supplier_id',
                'lims_supplier_list',
                'signed_total',
                'returned_total'
            )
        );
    }

    public function supplierPurchaseData(Request $request)
    {

        $user = Auth::user();

        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
            9 => 'updated_at',
        );

        $filters = [
            'supplier_id'    => $request->input('supplier_id') ?? 0,
            'sale_status'    => 9, // signed data
            'start_date'     => $request->input('start_date'),
            'end_date'       => $request->input('end_date'),
        ];

        $query = Sale::with([
            'customer',
            'warehouse',
            'user',
            'products'
        ])
            ->whereDate('updated_at', '>=', $filters['start_date'])
            ->whereDate('updated_at', '<=', $filters['end_date'])
            ->where('sale_status', $filters['sale_status']);

        if ($user->supplier_id) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('products', function ($qp) use ($user) {
                    $qp->where('products.supplier_id', $user->supplier_id);
                })
                    ->orWhere('sales.user_id', $user->id);
            });
        } else {
            if (intval($filters['supplier_id']) > 0) {
                $supplier_uid = User::where('supplier_id', $filters['supplier_id'])->first()->id;
                $query->whereHas('products', function ($q) use ($filters) {
                    $q->where('products.supplier_id', $filters['supplier_id']);
                });

                if ($supplier_uid) {
                    $query->orWhere('sales.user_id', $supplier_uid);
                }
            }
        }

        $totalData = (clone $query)->count();
        $totalFiltered = $totalData;

        // Pagination and ordering
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $limit = ($limit == -1) ? $totalFiltered : $limit; // Fetch all if limit = -1
        $orderColumn = $columns[$request->input('order.0.column')];
        $orderDir = $request->input('order.0.dir', 'desc');

        // Handle search
        $searchValue = $request->input('search.value');

        if ($searchValue) {
            // Apply search filters
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_no', 'LIKE', "%{$searchValue}%")
                    ->orWhereHas('customer', function ($q2) use ($searchValue) {
                        $q2->where('name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('phone_number', 'LIKE', "%{$searchValue}%");
                    });
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

        foreach ($sales as $key => $purchase) {
            $nestedData = [
                'id'                    => $purchase->id,
                'key'                   => $key,
                'date'                  => date(config('date_format') . ' h:i:s', strtotime($purchase->updated_at)),
                'reference_no'          => $purchase->reference_no,
                'warehouse'             => $purchase->warehouse->name,
                'shipping_cost'         => $purchase->shipping_cost,
                'return_shipping_cost'  => $purchase->return_shipping_cost,
                'product'               => $purchase->products->pluck('name')->toArray(),
                'qty'                   => $purchase->products->pluck('pivot.qty')->toArray(),
                'paid'                  => number_format($purchase->total_price, cache()->get('general_setting')->decimal),
                'balance'               => number_format($purchase->grand_total -  $purchase->shipping_cost, cache()->get('general_setting')->decimal),
                'grand_total'           => number_format($purchase->grand_total, cache()->get('general_setting')->decimal)
            ];
            $data[] = $nestedData;
        }

        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "supplier"        => $filters['supplier_id'],
        );
        echo json_encode($json_data);
    }

    public function supplierReturnData(Request $request)
    {
        $user = Auth::user();

        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
            9 => 'updated_at',
        );

        $filters = [
            'supplier_id'    => $request->input('supplier_id') ?? 0,
            'sale_status'    => 4, // return data
            'start_date'     => $request->input('start_date'),
            'end_date'       => $request->input('end_date'),
        ];

        $query = Sale::with([
            'customer',
            'warehouse',
            'user',
            'products'
        ])
            ->whereDate('updated_at', '>=', $filters['start_date'])
            ->whereDate('updated_at', '<=', $filters['end_date'])
            ->where('sale_status', $filters['sale_status']);

        if ($user->supplier_id) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('products', function ($qp) use ($user) {
                    $qp->where('products.supplier_id', $user->supplier_id);
                })
                    ->orWhere('sales.user_id', $user->id);
            });
        } else {
            if (intval($filters['supplier_id']) > 0) {
                $supplier_uid = User::where('supplier_id', $filters['supplier_id'])->first()->id;
                $query->whereHas('products', function ($q) use ($filters) {
                    $q->where('products.supplier_id', $filters['supplier_id']);
                });

                if ($supplier_uid) {
                    $query->orWhere('sales.user_id', $supplier_uid);
                }
            }
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        // Pagination and ordering
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $limit = ($limit == -1) ? $totalFiltered : $limit; // Fetch all if limit = -1
        $orderColumn = $columns[$request->input('order.0.column')];
        $orderDir = $request->input('order.0.dir', 'desc');

        // Handle search
        $searchValue = $request->input('search.value');

        if ($searchValue) {
            // Apply search filters
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_no', 'LIKE', "%{$searchValue}%")
                    ->orWhereHas('customer', function ($q2) use ($searchValue) {
                        $q2->where('name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('phone_number', 'LIKE', "%{$searchValue}%");
                    });
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

        foreach ($sales as $key => $return) {
            $nestedData = [
                "id"            => $return->id,
                "key"           => $key,
                "date"          => date(config('date_format'), strtotime($return->created_at)),
                "return_time"   => date(config('date_format') . ' h:i:s', strtotime($return->updated_at)),
                "reference_no"  => $return->reference_no,
                "warehouse"     => $return->warehouse->name,
                "shipping_cost" => $return->shipping_cost,
                "return_shipping_cost"  => $return->return_shipping_cost,
                "product"       => $return->products->pluck('name')->toArray(),
                "qty"           => $return->products->pluck('pivot.qty')->toArray(),
                "grand_total"   => $return->shipping_cost + $return->return_shipping_cost,
            ];
            $data[] = $nestedData;
        }

        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);
    }
}
