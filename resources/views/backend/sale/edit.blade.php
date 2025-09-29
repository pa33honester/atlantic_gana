@extends('backend.layout.iframe')
@push('iframe-css')
@endpush
@push('iframe-scripts')
@endpush
@section('content')
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close"
                data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}
        </div>
    @endif
    @if (session()->has('not_permitted'))
        <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert"
                aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}
        </div>
    @endif
    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ trans('file.The field labels marked with * are required input fields') }}.</small>
                            </p>
                            {!! Form::open([
                                'route' => ['sales.update', $lims_sale_data->id],
                                'method' => 'put',
                                'files' => true,
                                'id' => 'payment-form',
                            ]) !!}
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>{{ trans('file.Date') }}</label>
                                                <input type="text" name="created_at" class="form-control date"
                                                    value="{{ date($general_setting->date_format, strtotime($lims_sale_data->created_at->toDateString())) }}" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>{{ trans('Order Number') }}</label>
                                                <p><strong>{{ $lims_sale_data->reference_no }}</strong></p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>{{ trans('file.customer') }} *</label>
                                                <input type="hidden" name="customer_id_hidden"
                                                    value="{{ $lims_customer_data->id }}" />
                                                <br>
                                                <button type="button" class="btn btn-md btn-primary" data-toggle="modal"
                                                    data-target="#update-customer-modal" id="btn-customer-info">
                                                    {{ $lims_customer_data->name }}
                                                    ({{ $lims_customer_data->phone_number }})
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3 d-none">
                                            <div class="form-group">
                                                <label>{{ trans('file.Warehouse') }} *</label>
                                                <input type="hidden" name="warehouse_id_hidden"
                                                    value="{{ $lims_sale_data->warehouse_id }}" />
                                                <select required id="warehouse_id" name="warehouse_id"
                                                    class="selectpicker form-control" data-live-search="true"
                                                    data-live-search-style="begins" title="Select warehouse...">
                                                    @foreach ($lims_warehouse_list as $warehouse)
                                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <label>{{ trans('file.Select Product') }}</label>
                                            <div class="search-box input-group">
                                                <button type="button" class="btn btn-secondary btn-lg"><i
                                                        class="fa fa-barcode"></i></button>
                                                <input type="text" name="product_code_name" id="lims_productcodeSearch"
                                                    placeholder="Please type product code and select..."
                                                    class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-5">
                                        <div class="col-md-12">
                                            <h5>{{ trans('file.Order Table') }} *</h5>
                                            <div class="table-responsive mt-3">
                                                <table id="myTable" class="table table-hover order-list">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ trans('file.name') }}</th>
                                                            <th>{{ trans('file.Code') }}</th>
                                                            <th width="7%">{{ trans('file.Quantity') }}</th>
                                                            <th>{{ trans('file.Net Unit Price') }}</th>
                                                            <th>{{ trans('file.Discount') }}</th>
                                                            <th>{{ trans('file.Tax') }}</th>
                                                            <th>{{ trans('file.Subtotal') }}</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $temp_unit_name = [];
                                                        $temp_unit_operator = [];
                                                        $temp_unit_operation_value = [];
                                                        
                                                        // Batch preload products and taxes to avoid N+1 queries
                                                        // Prefer controller-provided collections (products, taxes, coupon_data)
                                                        $products = isset($products) ? collect($products) : collect();
                                                        $taxes = isset($taxes) ? collect($taxes) : collect();
                                                        if (!isset($coupon_data) && isset($lims_sale_data->coupon_id) && $lims_sale_data->coupon_id) {
                                                            // fallback to model lookup if controller didn't provide it
                                                            $coupon_data = \App\Models\Coupon::find($lims_sale_data->coupon_id);
                                                        }
                                                        
                                                        ?>
                                                        @foreach ($lims_product_sale_data as $product_sale)
                                                            <tr>
                                                                <?php
                                                                $product_data = isset($products[$product_sale->product_id]) ? $products[$product_sale->product_id] : null;
                                                                
                                                                if ($product_data && $product_data->tax_method == 1) {
                                                                    $product_price = $product_sale->net_unit_price + $product_sale->discount / $product_sale->qty;
                                                                } elseif ($product_data && $product_data->tax_method == 2) {
                                                                    $product_price = $product_sale->total / $product_sale->qty + $product_sale->discount / $product_sale->qty;
                                                                } else {
                                                                    $product_price = $product_sale->net_unit_price;
                                                                }
                                                                
                                                                $tax = isset($taxes[$product_sale->tax_rate]) ? $taxes[$product_sale->tax_rate] : null;
                                                                $unit_name = [];
                                                                $unit_operator = [];
                                                                $unit_operation_value = [];
                                                                
                                                                $unit_name[] = 'n/a' . ',';
                                                                $unit_operator[] = 'n/a' . ',';
                                                                $unit_operation_value[] = 'n/a' . ',';
                                                                
                                                                $temp_unit_name = $unit_name = implode(',', $unit_name) . ',';
                                                                
                                                                $temp_unit_operator = $unit_operator = implode(',', $unit_operator) . ',';
                                                                
                                                                $temp_unit_operation_value = $unit_operation_value = implode(',', $unit_operation_value) . ',';
                                                                
                                                                $product_name = $product_data ? $product_data->name : 'Unknown Product';
                                                                
                                                                if (strlen($product_name) > 25) {
                                                                    $product_name = substr($product_name, 0, 25) . '..';
                                                                }
                                                                
                                                                ?>
                                                                <td>{{ $product_name }} <input type="hidden"
                                                                        class="product-type"
                                                                        value="{{ $product_data->type }}" /></td>
                                                                <td>{{ $product_data->code }}</td>
                                                                <td><input type="number" class="form-control qty" readonly
                                                                        name="qty[]" value="{{ $product_sale->qty }}"
                                                                        step="any" required /></td>
                                                                <td class="net_unit_price">
                                                                    {{ number_format((float) $product_sale->net_unit_price, $general_setting->decimal, '.', '') }}
                                                                </td>
                                                                <td class="discount">
                                                                    {{ number_format((float) $product_sale->discount, $general_setting->decimal, '.', '') }}
                                                                </td>
                                                                <td class="tax">
                                                                    {{ number_format((float) $product_sale->tax, $general_setting->decimal, '.', '') }}
                                                                </td>
                                                                <td class="sub-total">
                                                                    {{ number_format((float) $product_sale->total, $general_setting->decimal, '.', '') }}
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="edit-product btn btn-info"
                                                                        data-toggle="modal" data-target="#editModal"> <i
                                                                            class="dripicons-document-edit"></i></button>
                                                                    <button type="button"
                                                                        class="ibtnDel btn btn-md btn-danger"><i
                                                                            class="dripicons-trash"></i></button>
                                                                </td>
                                                                <input type="hidden" class="product-code"
                                                                    name="product_code[]"
                                                                    value="{{ $product_data->code }}" />
                                                                <input type="hidden" class="product-id" name="product_id[]"
                                                                    value="{{ $product_data->id }}" />
                                                                <input type="hidden" class="product-price"
                                                                    name="product_price[]" value="{{ $product_price }}" />
                                                                <input type="hidden" class="sale-unit"
                                                                    name="sale_unit[]" value="{{ $unit_name }}" />
                                                                <input type="hidden" class="sale-unit-operator"
                                                                    value="{{ $unit_operator }}" />
                                                                <input type="hidden" class="sale-unit-operation-value"
                                                                    value="{{ $unit_operation_value }}" />
                                                                <input type="hidden" class="net_unit_price"
                                                                    name="net_unit_price[]"
                                                                    value="{{ $product_sale->net_unit_price }}" />
                                                                <input type="hidden" class="discount-value"
                                                                    name="discount[]"
                                                                    value="{{ $product_sale->discount }}" />
                                                                <input type="hidden" class="tax-rate" name="tax_rate[]"
                                                                    value="{{ $product_sale->tax_rate }}" />
                                                                @if ($tax)
                                                                    <input type="hidden" class="tax-name"
                                                                        value="{{ $tax->name }}" />
                                                                @else
                                                                    <input type="hidden" class="tax-name"
                                                                        value="No Tax" />
                                                                @endif
                                                                <input type="hidden" class="tax-method"
                                                                    value="{{ $product_data->tax_method }}" />
                                                                <input type="hidden" class="tax-value" name="tax[]"
                                                                    value="{{ $product_sale->tax }}" />
                                                                <input type="hidden" class="subtotal-value"
                                                                    name="subtotal[]"
                                                                    value="{{ $product_sale->total }}" />
                                                                <input type="hidden" class="imei-number"
                                                                    name="imei_number[]"
                                                                    value="{{ $product_sale->imei_number }}" />
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="tfoot active">
                                                        <th colspan="2">{{ trans('file.Total') }}</th>
                                                        <th id="total-qty">{{ $lims_sale_data->total_qty }}</th>
                                                        <th></th>
                                                        <th id="total-discount">
                                                            {{ number_format((float) $lims_sale_data->total_discount, $general_setting->decimal, '.', '') }}
                                                        </th>
                                                        <th id="total-tax">
                                                            {{ number_format((float) $lims_sale_data->total_tax, $general_setting->decimal, '.', '') }}
                                                        </th>
                                                        <th id="total">
                                                            {{ number_format((float) $lims_sale_data->total_price, $general_setting->decimal, '.', '') }}
                                                        </th>
                                                        <th><i class="dripicons-trash"></i></th>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_qty"
                                                    value="{{ $lims_sale_data->total_qty }}" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_discount"
                                                    value="{{ $lims_sale_data->total_discount }}" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_tax"
                                                    value="{{ $lims_sale_data->total_tax }}" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_price"
                                                    value="{{ $lims_sale_data->total_price }}" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="item"
                                                    value="{{ $lims_sale_data->item }}" />
                                                <input type="hidden" name="order_tax"
                                                    value="{{ $lims_sale_data->order_tax }}" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            @if ($lims_sale_data->coupon_id)
                                                @php
                                                    $coupon_data = DB::table('coupons')->find(
                                                        $lims_sale_data->coupon_id,
                                                    );
                                                @endphp
                                                <input type="hidden" name="coupon_active" value="1" />
                                                <input type="hidden" name="coupon_type"
                                                    value="{{ $coupon_data->type }}" />
                                                <input type="hidden" name="coupon_amount"
                                                    value="{{ $coupon_data->amount }}" />
                                                <input type="hidden" name="coupon_minimum_amount"
                                                    value="{{ $coupon_data->minimum_amount }}" />
                                                <input type="hidden" name="coupon_discount"
                                                    value="{{ $lims_sale_data->coupon_discount }}">
                                            @else
                                                <input type="hidden" name="coupon_active" value="0" />
                                            @endif
                                            <div class="form-group">
                                                <input type="hidden" name="grand_total"
                                                    value="{{ $lims_sale_data->grand_total }}" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="order_tax_rate_hidden"
                                                    value="{{ $lims_sale_data->order_tax_rate }}">
                                                <label>{{ trans('file.Order Tax') }}</label>
                                                <select class="form-control" name="order_tax_rate">
                                                    <option value="0">No Tax</option>
                                                    <option value="10">10%</option>
                                                    <option value="15">15%</option>
                                                    <option value="20">20%</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>{{ trans('file.Order Discount Type') }}</label>
                                                <select class="form-control" name="order_discount_type">
                                                    @if ($lims_sale_data->order_discount_type == 'Percentage')
                                                        <option value="Percentage">Percentage</option>
                                                        <option value="Flat">Flat</option>
                                                    @else
                                                        <option value="Flat">Flat</option>
                                                        <option value="Percentage">Percentage</option>
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>
                                                    {{ trans('file.Order Discount Value') }}
                                                </label>
                                                <input type="number" name="order_discount_value" class="form-control"
                                                    value="@if ($lims_sale_data->order_discount_value) {{ $lims_sale_data->order_discount_value }}@else{{ $lims_sale_data->order_discount }} @endif"
                                                    step="any" />
                                                <input type="hidden" name="order_discount"
                                                    value="{{ $lims_sale_data->order_discount }}" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>
                                                    {{ trans('file.Shipping Cost') }}
                                                </label>
                                                <input type="number" name="shipping_cost" class="form-control"
                                                    value="{{ $lims_sale_data->shipping_cost }}" step="any" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ trans('file.Attach Document') }}</label> <i
                                                    class="dripicons-question" data-toggle="tooltip"
                                                    title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                                <input type="file" name="document" class="form-control" />
                                                @if ($errors->has('extension'))
                                                    <span>
                                                        <strong>{{ $errors->first('extension') }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($lims_sale_data->coupon_id)
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>
                                                        <strong>{{ trans('file.Coupon Discount') }}</strong>
                                                    </label>
                                                    <p class="mt-2 pl-2"><strong
                                                            id="coupon-text">{{ number_format((float) $lims_sale_data->coupon_discount, $general_setting->decimal, '.', '') }}</strong>
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>{{ trans('file.Sale Note') }}</label>
                                                <textarea rows="5" class="form-control" name="sale_note">{{ $lims_sale_data->sale_note }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>{{ trans('file.Staff Note') }}</label>
                                                <textarea rows="5" class="form-control" name="staff_note">{{ $lims_sale_data->staff_note }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <input type="hidden" name="payment_status"
                                                    value="{{ $lims_sale_data->payment_status }}" />
                                                <input type="hidden" name="paid_amount"
                                                    value="{{ $lims_sale_data->paid_amount }}" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class = "row mt-3">
                                        <table class="table table-bordered table-condensed totals">
                                            <td><strong>{{ trans('file.Items') }}</strong>
                                                <span class="pull-right"
                                                    id="item">{{ number_format(0, $general_setting->decimal, '.', '') }}</span>
                                            </td>
                                            <td><strong>{{ trans('file.Total') }}</strong>
                                                <span class="pull-right"
                                                    id="subtotal">{{ number_format(0, $general_setting->decimal, '.', '') }}</span>
                                            </td>
                                            <td><strong>{{ trans('file.Order Tax') }}</strong>
                                                <span class="pull-right"
                                                    id="order_tax">{{ number_format(0, $general_setting->decimal, '.', '') }}</span>
                                            </td>
                                            <td><strong>{{ trans('file.Order Discount') }}</strong>
                                                <span class="pull-right"
                                                    id="order_discount">{{ number_format(0, $general_setting->decimal, '.', '') }}</span>
                                            </td>
                                            <td><strong>{{ trans('file.Shipping Cost') }}</strong>
                                                <span class="pull-right"
                                                    id="shipping_cost">{{ number_format(0, $general_setting->decimal, '.', '') }}</span>
                                            </td>
                                            <td><strong>{{ trans('file.grand total') }}</strong>
                                                <span class="pull-right"
                                                    id="grand_total">{{ number_format(0, $general_setting->decimal, '.', '') }}</span>
                                            </td>
                                        </table>
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" value="{{ trans('file.submit') }}"
                                            class="btn btn-primary pull-right" id="submit-button" formnovalidate>
                                    </div>
                                </div>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
            class="modal fade text-left">
            <div role="document" class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="modal_header" class="modal-title">Edit Quantity</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="row modal-element">
                                <div class="col-md-4 form-group">
                                    <label>{{ trans('file.Quantity') }}</label>
                                    <input type="number" step="any" name="edit_qty" class="form-control">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ trans('file.Unit Discount') }}</label>
                                    <input type="number" name="edit_discount" class="form-control">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ trans('file.Unit Price') }}</label>
                                    <input type="number" name="edit_unit_price" class="form-control" step="any">
                                </div>
                                <?php
                                $tax_name_all[] = 'No Tax';
                                $tax_rate_all[] = 0;
                                foreach ($lims_tax_list as $tax) {
                                    $tax_name_all[] = $tax->name;
                                    $tax_rate_all[] = $tax->rate;
                                }
                                ?>
                                <div class="col-md-4 form-group hidden">
                                    <label>{{ trans('file.Tax Rate') }}</label>
                                    <select name="edit_tax_rate" class="form-control selectpicker">
                                        <option value="0">{{ trans('No Tax') }}</option>
                                        <option value="10">{{ trans('@10') }}</option>
                                        <option value="15">{{ trans('@15') }}</option>
                                        <option value="20">{{ trans('20%') }}</option>
                                    </select>
                                </div>
                                <div id="edit_unit" class="col-md-4 form-group hidden">
                                    <label>{{ trans('file.Product Unit') }}</label>
                                    <select name="edit_unit" class="form-control selectpicker">
                                    </select>
                                </div>
                            </div>
                            <button type="button" name="update_btn"
                                class="btn btn-primary">{{ trans('file.update') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="update-customer-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ trans('Update Customer') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ trans('file.The field labels marked with * are required input fields') }}.</small>
                        </p>
                        {!! Form::open([
                            'route' => ['customer.update', $lims_customer_data->id],
                            'method' => 'put',
                            'files' => true,
                            'class' => 'update-customer-form',
                        ]) !!}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ trans('file.name') }} *</strong> </label>
                                    <input type="text" name="customer_name" value="{{ $lims_customer_data->name }}"
                                        required class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ trans('file.Company Name') }} </label>
                                    <input type="text" name="company_name"
                                        value="{{ $lims_customer_data->company_name }}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ trans('file.Email') }}</label>
                                    <input type="email" name="email" value="{{ $lims_customer_data->email }}"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ trans('file.Phone Number') }} *</label>
                                    <input type="text" name="phone_number" required
                                        value="{{ $lims_customer_data->phone_number }}" class="form-control">
                                    @if ($errors->has('phone_number'))
                                        <span>
                                            <strong>{{ $errors->first('phone_number') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ trans('file.Address') }}</label>
                                    <input type="text" name="address" value="{{ $lims_customer_data->address }}"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ trans('file.City') }}</label>
                                    <input type="text" name="city" value="{{ $lims_customer_data->city }}"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mt-3">
                                    <input type="hidden" name="redirect" value="false" />
                                    <button type="button" class="btn btn-primary" id="btn-update-customer">
                                        {{ trans('Update') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(function() {
            // array data depend on warehouse
            var lims_product_array = [];
            var product_code = [];
            var product_name = [];
            var product_qty = [];
            var product_type = [];
            var product_id = [];
            var product_list = [];
            var qty_list = [];

            // array data with selection
            var product_price = [];
            var product_discount = [];
            var tax_rate = [];
            var tax_name = [];
            var tax_method = [];
            var unit_name = [];
            var unit_operator = [];
            var unit_operation_value = [];

            // temporary array
            var temp_unit_name = [];
            var temp_unit_operator = [];
            var temp_unit_operation_value = [];

            var exist_type = [];
            var exist_code = [];
            var exist_qty = [];
            var rowindex;
            var customer_group_rate;
            var row_product_price;
            var currencyExchangeRate = <?php echo json_encode($currency_exchange_rate); ?>;
            var role_id = <?php echo json_encode(Auth::user()->role_id); ?>;
            var without_stock = <?php echo json_encode($general_setting->without_stock); ?>;
            var lims_customer_data = <?php echo json_encode($lims_customer_data); ?>;

            var rownumber = $('table.order-list tbody tr:last').index();

            for (rowindex = 0; rowindex <= rownumber; rowindex++) {
                product_price.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                    '.product-price').val()));
                exist_code.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                        'td:nth-child(2)')
                    .text());
                exist_type.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                    '.product-type').val());
                var total_discount = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')')
                    .find(
                        '.discount').text());
                var quantity = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                    '.qty').val());
                exist_qty.push(quantity);
                product_discount.push((total_discount / quantity).toFixed({{ $general_setting->decimal }}));
                tax_rate.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                        '.tax-rate')
                    .val()));
                tax_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-name')
                    .val());
                tax_method.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-method')
                    .val());
                temp_unit_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit')
                    .val()
                    .split(',');
                unit_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit')
                    .val());
                unit_operator.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                        '.sale-unit-operator')
                    .val());
                unit_operation_value.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                    '.sale-unit-operation-value').val());
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(
                    temp_unit_name[0]);
            }

            // Initialize selectpicker only if the plugin is loaded (iframe layout may omit bootstrap-select)
            if ($.fn.selectpicker) {
                $('.selectpicker').selectpicker({
                    style: 'btn-link',
                });
            } else {
                // Fallback: ensure selects are visible and usable
                $('.selectpicker').each(function() {
                    $(this).css('display', 'inline-block');
                });
            }

            $('[data-toggle="tooltip"]').tooltip();

            $('#btn-update-customer').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $form = $('.update-customer-form');

                if (!$form.length) {
                    alert('Customer update form not found in DOM');
                    return;
                }

                // Determine action URL from the form if available, otherwise fall back to route
                var action = $form.attr('action') ||
                    "{{ route('customer.update', $lims_customer_data->id) }}";

                // If no action attribute is present on the form, fall back to native submit
                // (some iframe / blade builds may render relative or missing actions)
                if (!$form.attr('action')) {
                    console.warn('update-customer: form has no action attribute, performing native submit');
                    // ensure method override exists
                    if (!$form.find('input[name="_method"]').length) {
                        $form.append('<input type="hidden" name="_method" value="PUT">');
                    }
                    // native submit — allow the browser to handle CSRF and redirects
                    $form.trigger('submit');
                    return;
                }

                // Serialize form data (includes _token and _method inputs generated by Form::open)
                var payload = $form.serializeArray();

                // Ensure method override is present (some servers may not accept PUT verb in AJAX)
                var methodPresent = payload.some(function(item) {
                    return item.name === '_method';
                });
                if (!methodPresent) {
                    payload.push({
                        name: '_method',
                        value: 'PUT'
                    });
                }

                // Disable button to prevent double submit
                $btn.prop('disabled', true);

                // Send AJAX; be tolerant to non-JSON responses (redirects/HTML)
                $.ajax({
                    url: action,
                    type: 'POST', // use POST and rely on _method for Laravel
                    data: $.param(payload),
                    // Do not force JSON parsing here: some controllers redirect or return HTML.
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res, textStatus, jqXHR) {
                        var parsed = null;
                        // Try to parse JSON if possible
                        try {
                            if (typeof res === 'string' && res.trim().length) {
                                parsed = JSON.parse(res);
                            } else {
                                parsed = res;
                            }
                        } catch (e) {
                            // Not JSON — server may have redirected or returned HTML. Fall back to form values.
                            parsed = null;
                        }

                        if (parsed && parsed.code == 200) {
                            var data = parsed.data || {};
                            var name = data.name || ($form.find('input[name="customer_name"]')
                                .val());
                            var phone = data.phone_number || ($form.find(
                                'input[name="phone_number"]').val());
                            $('#btn-customer-info').html(name + ' (' + phone + ')');
                            $('#update-customer-modal').modal('hide');
                            if (typeof toastr !== 'undefined') toastr.success(parsed.msg ||
                                'Customer updated');
                            else alert(parsed.msg || 'Customer updated');
                        } else {
                            // If server didn't provide JSON but request succeeded (status 200), assume update worked
                            if (jqXHR && jqXHR.status >= 200 && jqXHR.status < 400 && !parsed) {
                                var name = $form.find('input[name="customer_name"]').val();
                                var phone = $form.find('input[name="phone_number"]').val();
                                $('#btn-customer-info').html(name + ' (' + phone + ')');
                                $('#update-customer-modal').modal('hide');
                                if (typeof toastr !== 'undefined') toastr.success(
                                    'Customer updated');
                            } else {
                                var message = (parsed && parsed.msg) ? parsed.msg :
                                    'Failed to update customer';
                                if (typeof toastr !== 'undefined') toastr.error(message);
                                else alert(message);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        // If the AJAX request was blocked (status 0) or cross-origin, fall back
                        console.warn('update-customer ajax error', status, error, xhr && xhr
                            .status);
                        if (!xhr || xhr.status === 0) {
                            console.warn(
                                'update-customer: falling back to native submit due to ajax failure'
                            );
                            // ensure method override exists
                            if (!$form.find('input[name="_method"]').length) {
                                $form.append(
                                    '<input type="hidden" name="_method" value="PUT">');
                            }
                            $form.trigger('submit');
                            return;
                        }
                        var msg = 'Request failed';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr
                            .responseJSON.message;
                        else if (xhr && xhr.responseText) {
                            try {
                                var json = JSON.parse(xhr.responseText);
                                msg = json.message || json.msg || xhr.statusText;
                            } catch (e) {
                                msg = xhr.status + ' ' + xhr.statusText;
                            }
                        }
                        if (typeof toastr !== 'undefined') toastr.error(msg);
                        else alert(msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            //assigning value
            $('select[name="warehouse_id"]').val($('input[name="warehouse_id_hidden"]').val());
            $('select[name="sale_status"]').val($('input[name="sale_status_hidden"]').val());
            $('select[name="order_tax_rate"]').val($('input[name="order_tax_rate_hidden"]').val());
            if ($.fn.selectpicker) {
                $('.selectpicker').selectpicker('refresh');
            }

            $('#item').text($('input[name="item"]').val() + '(' + $('input[name="total_qty"]').val() + ')');
            $('#subtotal').text(parseFloat($('input[name="total_price"]').val()).toFixed(
                {{ $general_setting->decimal }}));
            $('#order_tax').text(parseFloat($('input[name="order_tax"]').val()).toFixed(
                {{ $general_setting->decimal }}));
            if (!$('input[name="order_discount"]').val())
                $('input[name="order_discount"]').val(
                    '{{ number_format(0, $general_setting->decimal, '.', '') }}');
            $('#order_discount').text(parseFloat($('input[name="order_discount"]').val()).toFixed(
                {{ $general_setting->decimal }}));
            if (!$('input[name="shipping_cost"]').val())
                $('input[name="shipping_cost"]').val(
                    '{{ number_format(0, $general_setting->decimal, '.', '') }}');
            $('#shipping_cost').text(parseFloat($('input[name="shipping_cost"]').val()).toFixed(
                {{ $general_setting->decimal }}));
            $('#grand_total').text(parseFloat($('input[name="grand_total"]').val()).toFixed(
                {{ $general_setting->decimal }}));

            var id = $('select[name="warehouse_id"]').val();
            $.get('../getproduct/' + id, function(data) {
                lims_product_array = [];
                product_code = data[0];
                product_name = data[1];
                product_qty = data[2];
                product_type = data[3];
                product_id = data[4];
                product_list = data[5];
                qty_list = data[6];
                product_warehouse_price = data[7];
                batch_no = data[8];
                product_batch_id = data[9];
                expired_date = data[10];
                is_embeded = data[11];
                imei_number = data[12];

                $.each(product_code, function(index) {
                    if (exist_code.includes(product_code[index])) {
                        pos = exist_code.indexOf(product_code[index]);
                        product_qty[index] = product_qty[index] + exist_qty[pos];
                        exist_code.splice(pos, 1);
                        exist_qty.splice(pos, 1);
                    }
                    lims_product_array.push(product_code[index] + '|' + product_name[index] + '|' +
                        imei_number[
                            index] + '|' + is_embeded[index]);

                });
                $.each(exist_code, function(index) {
                    product_type.push(exist_type[index]);
                    product_code.push(exist_code[index]);
                    product_qty.push(exist_qty[index]);
                });
                // If the user has already typed into the product search input, refresh autocomplete suggestions
                try {
                    var currentVal = $('#lims_productcodeSearch').val();
                    if (currentVal && currentVal.length > 0 && $.ui && $.ui.autocomplete) {
                        $('#lims_productcodeSearch').autocomplete('search', currentVal);
                    }
                } catch (e) {
                    // ignore if autocomplete not initialized yet
                }
            });

            var lims_productcodeSearch = $('#lims_productcodeSearch');
            // Use jQuery UI autocomplete if available; otherwise skip autocomplete to avoid errors.
            if ($.ui && $.ui.autocomplete) {
                lims_productcodeSearch.autocomplete({
                    source: function(request, response) {
                        var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term),
                            "i");
                        response($.grep(lims_product_array, function(item) {
                            return matcher.test(item);
                        }));
                    },
                    response: function(event, ui) {
                        if (ui.content.length == 1) {
                            var data = ui.content[0].value;
                            $(this).autocomplete("close");
                            productSearch(data);
                        } else if (ui.content.length == 0 && $('#lims_productcodeSearch').val()
                            .length == 13) {
                            productSearch($('#lims_productcodeSearch').val() + '|' + 1);
                        }
                    },
                    select: function(event, ui) {
                        var data = ui.item.value;
                        productSearch(data);
                    }
                });
            } else {
                // fallback: do nothing, allow raw text input
            }

            //Delete product
            $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
                rowindex = $(this).closest('tr').index();
                product_price.splice(rowindex, 1);
                product_discount.splice(rowindex, 1);
                tax_rate.splice(rowindex, 1);
                tax_name.splice(rowindex, 1);
                tax_method.splice(rowindex, 1);
                unit_name.splice(rowindex, 1);
                unit_operator.splice(rowindex, 1);
                unit_operation_value.splice(rowindex, 1);

                $(this).closest("tr").remove();
                calculateTotal();
            });

            //Edit product
            $("table.order-list").on("click", ".edit-product", function() {
                rowindex = $(this).closest('tr').index();
                edit();
            });

            //Update product
            $('button[name="update_btn"]').on("click", function() {

                var edit_discount = $('input[name="edit_discount"]').val();
                var edit_qty = $('input[name="edit_qty"]').val();
                var edit_unit_price = $('input[name="edit_unit_price"]').val();

                if (parseFloat(edit_discount) > parseFloat(edit_unit_price)) {
                    alert('Invalid Discount Input!');
                    return;
                }

                if (edit_qty < 1) {
                    $('input[name="edit_qty"]').val(1);
                    edit_qty = 1;
                    alert("Quantity can't be less than 1");
                }

                tax_rate[rowindex] = parseFloat($('select[name="edit_tax_rate"]').val());
                tax_name[rowindex] = $('select[name="edit_tax_rate"] option:selected').text();
                product_price[rowindex] = $('input[name="edit_unit_price"]').val();
                product_discount[rowindex] = $('input[name="edit_discount"]').val();
                checkQuantity(edit_qty, false);
            });

            function productSearch(data) {
                var product_info = data.split("|");
                var code = product_info[0];
                var pre_qty = 0;
                var flag = true;
                $(".product-code").each(function(i) {
                    if ($(this).val() == code) {
                        rowindex = i;
                        pre_qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty')
                            .val();
                    }
                });

                if (flag) {
                    data += '?' + $('#customer_id').val() + '?' + (parseFloat(pre_qty) + 1);
                    $.ajax({
                        type: 'GET',
                        url: '../lims_product_search',
                        data: {
                            data: data
                        },
                        success: function(data) {
                            var flag = 1;
                            if (pre_qty > 0) {
                                var qty = data[15];
                                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty')
                                    .val(qty);
                                pos = product_code.indexOf(data[1]);
                                if (!data[11] && product_warehouse_price[pos]) {
                                    product_price[rowindex] = parseFloat(product_warehouse_price[pos] *
                                        currencyExchangeRate) + parseFloat(product_warehouse_price[
                                            pos] *
                                        currencyExchangeRate * customer_group_rate);
                                } else {
                                    product_price[rowindex] = parseFloat(data[2] *
                                            currencyExchangeRate) +
                                        parseFloat(data[2] * currencyExchangeRate *
                                            customer_group_rate);
                                }
                                flag = 0;
                                checkQuantity(String(qty), true);
                                flag = 0;
                            }
                            $("input[name='product_code_name']").val('');
                            if (flag) {
                                var newRow = $("<tr>");
                                var cols = '';
                                pos = product_code.indexOf(data[1]);
                                temp_unit_name = (data[6]).split(',');
                                product_name = data[0];
                                if (product_name.length > 25) {
                                    product_name = product_name.substring(0, 25);
                                    product_name = product_name + "..";
                                }
                                cols += '<td>' + product_name + '</td>';
                                cols += '<td>' + data[1] + '</td>';
                                cols +=
                                    '<td><input type="number" class="form-control qty" readonly name="qty[]" value="' +
                                    data[15] + '" step="any" required/></td>';

                                cols +=
                                    `<td class="net_unit_price">${data[2].toFixed({{ $general_setting->decimal }})}</td>`;
                                cols +=
                                    '<td class="discount">{{ number_format(0, $general_setting->decimal, '.', '') }}</td>';
                                cols += '<td class="tax">0.00</td>';
                                cols += '<td class="sub-total"></td>';
                                cols +=
                                    '<td><button type="button" class="edit-product btn btn-info" data-toggle="modal" data-target="#editModal"> <i class="dripicons-document-edit"></i></button> <button type="button" class="ibtnDel btn btn-md btn-danger"><i class="dripicons-trash"></i></button></td>';
                                cols +=
                                    '<input type="hidden" class="product-code" name="product_code[]" value="' +
                                    data[1] + '"/>';
                                cols +=
                                    '<input type="hidden" class="product-id" name="product_id[]" value="' +
                                    data[9] + '"/>';
                                cols +=
                                    '<input type="hidden" class="sale-unit" name="sale_unit[]" value="' +
                                    temp_unit_name[0] + '"/>';
                                cols +=
                                    `<input type="hidden" class="net_unit_price" name="net_unit_price[]" value="${data[2]}"/>`;
                                cols +=
                                    '<input type="hidden" class="discount-value" name="discount[]" />';
                                cols +=
                                    '<input type="hidden" class="tax-rate" name="tax_rate[]" value="' +
                                    data[
                                        3] + '"/>';
                                cols += '<input type="hidden" class="tax-value" name="tax[]" />';
                                cols +=
                                    '<input type="hidden" class="subtotal-value" name="subtotal[]" />';

                                newRow.append(cols);
                                $("table.order-list tbody").prepend(newRow);
                                rowindex = newRow.index();

                                product_price.splice(rowindex, 0, parseFloat(data[2]));

                                product_discount.splice(rowindex, 0,
                                    '{{ number_format(0, $general_setting->decimal, '.', '') }}');
                                tax_rate.splice(rowindex, 0, parseFloat(data[3]));
                                tax_name.splice(rowindex, 0, data[4]);
                                tax_method.splice(rowindex, 0, data[5]);
                                unit_name.splice(rowindex, 0, data[6]);
                                unit_operator.splice(rowindex, 0, data[7]);
                                unit_operation_value.splice(rowindex, 0, data[8]);
                                checkQuantity(data[15], true);
                            }
                        }
                    });
                }
            }

            function edit() {
                var row_product_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                        'td:nth-child(1)')
                    .text();
                var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                        'td:nth-child(2)')
                    .text();
                $('#modal_header').text(row_product_name + '(' + row_product_code + ')');

                var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
                $('input[name="edit_qty"]').val(qty);
                $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed(
                    {{ $general_setting->decimal }}));

                var tax_name_all = <?php echo json_encode($tax_name_all); ?>;
                pos = tax_name_all.indexOf(tax_name[rowindex]);
                $('select[name="edit_tax_rate"]').val(0);

                row_product_price = product_price[rowindex];

                $('input[name="edit_unit_price"]').val(row_product_price.toFixed(
                    {{ $general_setting->decimal }}));
            }

            function checkQuantity(sale_qty, flag) {
                var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                        'td:nth-child(2)')
                    .text();
                pos = product_code.indexOf(row_product_code);

                if (!flag) {
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(
                        sale_qty); // change quantity
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .net_unit_price').val(
                        product_price[
                            rowindex]); // change net_unit_price
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(4)').text(
                        product_price[
                            rowindex]); // change net_unit_price
                    $('#editModal').modal('hide');
                }

                calculateRowProductData(sale_qty);
            }

            function calculateRowProductData(quantity) {
                row_product_price = parseFloat(product_price[rowindex]);
                row_product_discount = parseFloat(product_discount[rowindex]);

                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text((
                    product_discount[
                        rowindex] * quantity).toFixed({{ $general_setting->decimal }}));
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount-value').val((
                    product_discount[
                        rowindex] * quantity).toFixed({{ $general_setting->decimal }}));
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val(tax_rate[
                        rowindex]
                    .toFixed({{ $general_setting->decimal }}));

                var net_unit_price = row_product_price; // - product_discount[rowindex]; @dorian
                var tax = 0;
                var sub_total = (row_product_price - row_product_discount) * quantity;

                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed(
                    {{ $general_setting->decimal }}));
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total
                    .toFixed(
                        {{ $general_setting->decimal }}));
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(
                    sub_total.toFixed(
                        {{ $general_setting->decimal }}));

                calculateTotal();
            }

            function calculateTotal() {
                //Sum of quantity
                var total_qty = 0;
                $(".qty").each(function() {

                    if ($(this).val() == '') {
                        total_qty += 0;
                    } else {
                        total_qty += parseFloat($(this).val());
                    }
                });
                $("#total-qty").text(total_qty);
                $('input[name="total_qty"]').val(total_qty);

                //Sum of discount
                var total_discount = 0;
                $(".discount").each(function() {
                    total_discount += parseFloat($(this).text());
                });
                $("#total-discount").text(total_discount.toFixed({{ $general_setting->decimal }}));
                $('input[name="total_discount"]').val(total_discount.toFixed({{ $general_setting->decimal }}));

                //Sum of tax
                var total_tax = 0;
                $(".tax").each(function() {
                    total_tax += parseFloat($(this).text());
                });
                $("#total-tax").text(total_tax.toFixed({{ $general_setting->decimal }}));
                $('input[name="total_tax"]').val(total_tax.toFixed({{ $general_setting->decimal }}));

                //Sum of subtotal
                var total = 0;
                $(".sub-total").each(function() {
                    total += parseFloat($(this).text());
                });
                $("#total").text(total.toFixed({{ $general_setting->decimal }}));
                $('input[name="total_price"]').val(total.toFixed({{ $general_setting->decimal }}));

                calculateGrandTotal();
            }

            function calculateGrandTotal() {

                var item = $('table.order-list tbody tr:last').index();
                var total_qty = parseFloat($('#total-qty').text());
                var subtotal = parseFloat($('#total').text());
                var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
                var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());
                var order_discount_type = $('select[name="order_discount_type"]').val();
                var order_discount_value = parseFloat($('input[name="order_discount_value"]').val());

                if (!order_discount_value)
                    order_discount_value = {{ number_format(0, $general_setting->decimal, '.', '') }};

                if (order_discount_type == 'Flat')
                    var order_discount = parseFloat(order_discount_value);
                else
                    var order_discount = parseFloat(subtotal * (order_discount_value / 100));

                $('input[name="order_discount"]').val(order_discount);

                if (!shipping_cost)
                    shipping_cost = {{ number_format(0, $general_setting->decimal, '.', '') }};

                item = ++item + '(' + total_qty + ')';
                order_tax = (subtotal - order_discount) * (order_tax / 100);
                var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;
                $('input[name="grand_total"]').val(grand_total.toFixed({{ $general_setting->decimal }}));
                if ($('input[name="coupon_active"]').val()) {
                    couponDiscount();
                    var coupon_discount = parseFloat($('input[name="coupon_discount"]').val());
                    if (!coupon_discount)
                        coupon_discount = {{ number_format(0, $general_setting->decimal, '.', '') }};
                    grand_total -= coupon_discount;
                }

                $('#item').text(item);
                $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
                $('#subtotal').text(subtotal.toFixed({{ $general_setting->decimal }}));
                $('#order_tax').text(order_tax.toFixed({{ $general_setting->decimal }}));
                $('input[name="order_tax"]').val(order_tax.toFixed({{ $general_setting->decimal }}));
                $('#order_discount').text(order_discount.toFixed({{ $general_setting->decimal }}));
                $('#shipping_cost').text(shipping_cost.toFixed({{ $general_setting->decimal }}));
                $('#grand_total').text(grand_total.toFixed({{ $general_setting->decimal }}));
                $('input[name="grand_total"]').val(grand_total.toFixed({{ $general_setting->decimal }}));
            }

            function couponDiscount() {
                var rownumber = $('table.order-list tbody tr:last').index();
                if (rownumber < 0) {
                    alert("Please insert product to order table!")
                } else {
                    if ($('input[name="coupon_type"]').val() == 'fixed') {
                        if ($('input[name="grand_total"]').val() >= $('input[name="coupon_minimum_amount"]')
                            .val()) {
                            $('input[name="grand_total"]').val($('input[name="grand_total"]').val() - $(
                                'input[name="coupon_amount"]').val());
                        } else
                            alert('Grand Total is not sufficient for discount! Required ' + currency['code'] + ' ' +
                                $(
                                    'input[name="coupon_minimum_amount"]').val());
                    } else {
                        var grand_total = $('input[name="grand_total"]').val();
                        var coupon_discount = grand_total * (parseFloat($('input[name="coupon_amount"]').val()) /
                            100);
                        grand_total = grand_total - coupon_discount;
                        $('input[name="grand_total"]').val(grand_total);
                        $('input[name="coupon_discount"]').val(coupon_discount);
                        $('#coupon-text').text(parseFloat(coupon_discount).toFixed(
                            {{ $general_setting->decimal }}));
                    }
                }
            }

            $('select[name="order_discount_type"]').on("change", function() {
                calculateGrandTotal();
            });

            $('input[name="order_discount_value"]').on("input", function() {
                calculateGrandTotal();
            });

            $('input[name="shipping_cost"]').on("input", function() {
                calculateGrandTotal();
            });

            $('select[name="order_tax_rate"]').on("change", function() {
                calculateGrandTotal();
            });

            $('#payment-form').on('submit', function(e) {
                var rownumber = $('table.order-list tbody tr:last').index();
                $("table.order-list tbody .qty").each(function(index) {
                    if ($(this).val() == '') {
                        alert('One of products has no quantity!');
                        e.preventDefault();
                    }
                });
                if (rownumber < 0) {
                    alert("Please insert product to order table!")
                    e.preventDefault();
                } else if (parseFloat($('input[name="total_qty"]').val()) <= 0) {
                    alert('Product quantity is 0');
                    e.preventDefault();
                } else {
                    parent.$('#edit-sale').modal('hide');
                    parent.$('#sale-details').modal('hide');
                    parent.location.reload();
                    $("#submit-button").prop('disabled', true);
                    $(".batch-no").prop('disabled', false);
                }
            });
        });
    </script>
@endpush
