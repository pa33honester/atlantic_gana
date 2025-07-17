@extends('backend.layout.main') @section('content')
@push('css')
<style>
    @media print {
        .hidden-print {
            display: none !important;
        }
    }
</style>
@endpush
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
@if(session()->has('error'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('error') }}</div>
@endif

<section id="pos-layout" class="forms hidden-print">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('Add Order')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'sales.store', 'method' => 'post', 'files' => true, 'class' => 'payment-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Date')}}</label>
                                            <input type="text" name="created_at" class="form-control date" value="{{ old('created_at', date('d-m-Y')) }}" placeholder="Choose date"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3 hidden">
                                        <div class="form-group">
                                            <label>
                                                {{trans('file.Reference No')}}
                                            </label>
                                            <input type="text" name="reference_no" class="form-control" />
                                        </div>
                                        @if($errors->has('reference_no'))
                                       <span>
                                           <strong>{{ $errors->first('reference_no') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.customer')}} *</label>
                                            <div class="input-group pos">
                                                <?php
                                                  $deposit = [];
                                                  $points = [];
                                                  $customer_active = DB::table('permissions')
                                                                    ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                                                                    ->where([
                                                                        ['permissions.name', 'customers-add'],
                                                                        ['role_id', \Auth::user()->role_id] ])
                                                                    ->first();
                                                ?>
                                                @if(auth()->user()->role_id == 1)
                                                    <select required name="customer_id" id="customer_id" class="selectpicker form-control" data-live-search="true" title="Select customer..." style="width: 100px;">
                                                    @foreach($lims_customer_list as $customer)
                                                        @php
                                                        $deposit[$customer->id] = $customer->deposit - $customer->expense;

                                                        $points[$customer->id] = $customer->points;
                                                        @endphp
                                                        <option value="{{$customer->id}}">{{$customer->name . ' (' . $customer->phone_number . ')'}}</option>
                                                    @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addCustomer"><i class="dripicons-plus"></i></button>
                                                @else
                                                    <select required name="customer_id" id="customer_id" class="d-none">
                                                    @foreach($lims_customer_list as $customer)
                                                        @php
                                                        $deposit[$customer->id] = $customer->deposit - $customer->expense;
                                                        $points[$customer->id] = $customer->points;
                                                        @endphp
                                                        <option value="{{$customer->id}}">{{$customer->name . ' (' . $customer->phone_number . ')'}}</option>
                                                    @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-default btn-block btn-sm" data-toggle="modal" data-target="#addCustomer"><i class="dripicons-plus"></i></button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @if(isset(auth()->user()->warehouse_id))
                                        <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{auth()->user()->warehouse_id}}" />
                                    @else
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Warehouse')}} *</label>
                                            <select required name="warehouse_id" id="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label>{{trans('file.Select Product')}}</label>
                                        <div class="search-box input-group">
                                            <button type="button" class="btn btn-secondary btn-lg"><i class="fa fa-barcode"></i></button>
                                            <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="Please type product code and select..." class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-5">
                                    <div class="col-md-12">
                                        <h5>{{trans('file.Order Table')}} *</h5>
                                        <div class="table-responsive mt-3">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{trans('file.name')}}</th>
                                                        <th>{{trans('file.Code')}}</th>
                                                        <th width="7%">{{trans('file.Quantity')}}</th>
                                                        <th width="10%">{{trans('file.Batch No')}}</th>
                                                        <th>{{trans('file.Expired Date')}}</th>
                                                        <th>{{trans('file.Price')}}</th>
                                                        <th>{{trans('file.Discount')}}</th>
                                                        <th>{{trans('file.Tax')}}</th>
                                                        <th>{{trans('file.Subtotal')}}</th>
                                                        <th><i class="dripicons-trash"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                                <tfoot class="tfoot active">
                                                    <th colspan="2">{{trans('file.Total')}}</th>
                                                    <th id="total-qty">0</th>
                                                    <th></th>
                                                    <th></th>
                                                    <th></th>
                                                    <th id="total-discount">{{number_format(0, $general_setting->decimal, '.', '')}}</th>
                                                    <th id="total-tax">{{number_format(0, $general_setting->decimal, '.', '')}}</th>
                                                    <th id="total">{{number_format(0, $general_setting->decimal, '.', '')}}</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_qty" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_discount" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_tax" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_price" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="item" />
                                            <input type="hidden" name="order_tax" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="grand_total" />
                                            <input type="hidden" name="used_points" />
                                            <input type="hidden" name="pos" value="0" />
                                            <input type="hidden" name="coupon_active" value="0" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Order Tax')}}</label>
                                            <select class="form-control" name="order_tax_rate">
                                                <option value="0">No Tax</option>
                                                @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Order Discount Type')}}</label>
                                            <select id="order-discount-type" name="order_discount_type" class="form-control">
                                              <option value="Flat">{{trans('file.Flat')}}</option>
                                              <option value="Percentage">{{trans('file.Percentage')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>{{trans('file.Value')}}</label>
                                            <input type="text" name="order_discount_value" class="form-control numkey" id="order-discount-val">
                                            <input type="hidden" name="order_discount" class="form-control" id="order-discount">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>
                                                {{trans('file.Shipping Cost')}}
                                            </label>
                                            <input type="number" name="shipping_cost" class="form-control" step="any"/>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Attach Document')}}</label> <i class="dripicons-question" data-toggle="tooltip" title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                            <input type="file" name="document" class="form-control" />
                                            @if($errors->has('extension'))
                                                <span>
                                                   <strong>{{ $errors->first('extension') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    @foreach($custom_fields as $field)
                                        @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                            <div class="{{'col-md-'.$field->grid_value}}">
                                                <div class="form-group">
                                                    <label>{{$field->name}}</label>
                                                    @if($field->type == 'text')
                                                        <input type="text" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                                    @elseif($field->type == 'number')
                                                        <input type="number" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                                    @elseif($field->type == 'textarea')
                                                        <textarea rows="5" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif></textarea>
                                                    @elseif($field->type == 'checkbox')
                                                        <br>
                                                        <?php $option_values = explode(",", $field->option_value); ?>
                                                        @foreach($option_values as $value)
                                                            <label>
                                                                <input type="checkbox" name="{{str_replace(' ', '_', strtolower($field->name))}}[]" value="{{$value}}" @if($value == $field->default_value){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                            </label>
                                                            &nbsp;
                                                        @endforeach
                                                    @elseif($field->type == 'radio_button')
                                                        <br>
                                                        <?php $option_values = explode(",", $field->option_value); ?>
                                                        @foreach($option_values as $value)
                                                            <label class="radio-inline">
                                                                <input type="radio" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$value}}" @if($value == $field->default_value){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                            </label>
                                                            &nbsp;
                                                        @endforeach
                                                    @elseif($field->type == 'select')
                                                        <?php $option_values = explode(",", $field->option_value); ?>
                                                        <select class="form-control" name="{{str_replace(' ', '_', strtolower($field->name))}}" @if($field->is_required){{'required'}}@endif>
                                                            @foreach($option_values as $value)
                                                                <option value="{{$value}}" @if($value == $field->default_value){{'selected'}}@endif>{{$value}}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($field->type == 'multi_select')
                                                        <?php $option_values = explode(",", $field->option_value); ?>
                                                        <select class="form-control" name="{{str_replace(' ', '_', strtolower($field->name))}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                            @foreach($option_values as $value)
                                                                <option value="{{$value}}" @if($value == $field->default_value){{'selected'}}@endif>{{$value}}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($field->type == 'date_picker')
                                                        <input type="text" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control date" @if($field->is_required){{'required'}}@endif>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{trans('file.Sale Note')}}</label>
                                            <textarea rows="5" class="form-control" name="sale_note"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{trans('file.Staff Note')}}</label>
                                            <textarea rows="5" class="form-control" name="staff_note"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button id="submit-button" type="button" class="btn btn-primary">{{trans('file.submit')}}</button>
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <table class="table table-bordered table-condensed totals">
            <td><strong>{{trans('file.Items')}}</strong>
                <span class="pull-right" id="item">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
            <td><strong>{{trans('file.Total')}}</strong>
                <span class="pull-right" id="subtotal">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
            <td><strong>{{trans('file.Order Tax')}}</strong>
                <span class="pull-right" id="order_tax">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
            <td><strong>{{trans('file.Order Discount')}}</strong>
                <span class="pull-right" id="order_discount">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
            <td><strong>{{trans('file.Shipping Cost')}}</strong>
                <span class="pull-right" id="shipping_cost">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
            <td><strong>{{trans('file.grand total')}}</strong>
                <span class="pull-right" id="grand_total">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>
        </table>
    </div>

    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modal_header" class="modal-title"></h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row modal-element">
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Quantity')}}</label>
                                <input type="number" step="any" name="edit_qty" class="form-control numkey">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Unit Discount')}}</label>
                                <input type="number" name="edit_discount" class="form-control numkey">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Unit Price')}}</label>
                                <input type="number" name="edit_unit_price" class="form-control numkey" step="any">
                            </div>
                            <div class="col-md-4 form-group hidden">
                                <label>{{trans('file.Tax Rate')}}</label>
                                <select name="edit_tax_rate" class="form-control selectpicker">
                                    <option value="0">{{ trans('No Tax') }}</option>
                                    <option value="10">{{ trans('@10') }}</option>
                                    <option value="15">{{ trans('@15') }}</option>
                                    <option value="20">{{ trans('20%') }}</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" name="update_btn" class="btn btn-primary">{{trans('file.update')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- add customer modal -->
    <div id="addCustomer" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
          <div class="modal-content">
            {!! Form::open(['route' => 'customer.store', 'method' => 'post', 'files' => true, 'id' => 'customer-form']) !!}
            <div class="modal-header">
              <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Add Customer')}}</h5>
              <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                <div class="form-group">
                    <label>{{trans('file.Customer Group')}} *</strong> </label>
                    <select required class="form-control selectpicker" name="customer_group_id">
                        @foreach($lims_customer_group_all as $customer_group)
                            <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>{{trans('file.name')}} *</strong> </label>
                    <input type="text" name="customer_name" required class="form-control">
                </div>
                <div class="form-group">
                    <label>{{trans('file.Email')}}</label>
                    <input type="text" name="email" placeholder="example@example.com" class="form-control">
                </div>
                <div class="form-group">
                    <label>{{trans('file.Phone Number')}} *</label>
                    <input type="text" name="phone_number" required class="form-control">
                </div>
                <div class="form-group">
                    <label>{{trans('file.Address')}}</label>
                    <input type="text" name="address" class="form-control">
                </div>
                <div class="form-group">
                    <label>{{trans('file.City')}}</label>
                    <input type="text" name="city" class="form-control">
                </div>
                <div class="form-group">
                    <input type="hidden" name="pos" value="1">
                    <input type="hidden" name="supplier_id" value="{{ auth()->user()->supplier_id }}">
                    <button type="button" class="btn btn-primary customer-submit-btn">{{trans('file.submit')}}</button>
                </div>
            </div>
            {{ Form::close() }}
          </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">

$("ul#sale").siblings('a').attr('aria-expanded','true');
$("ul#sale").addClass("show");
$("ul#sale #sale-create-menu").addClass("active");

@if(config('database.connections.saleprosaas_landlord'))
    numberOfInvoice = <?php echo json_encode($numberOfInvoice)?>;
    $.ajax({
        type: 'GET',
        async: false,
        url: '{{route("package.fetchData", $general_setting->package_id)}}',
        success: function(data) {
            if(data['number_of_invoice'] > 0 && data['number_of_invoice'] <= numberOfInvoice) {
                localStorage.setItem("message", "You don't have permission to create another invoice as you already exceed the limit! Subscribe to another package if you wants more!");
                location.href = "{{route('sales.index')}}";
            }
        }
    });
@endif

@if($lims_pos_setting_data)
    var public_key = <?php echo json_encode($lims_pos_setting_data->stripe_public_key) ?>;
@endif
var currency = <?php echo json_encode($currency) ?>;
var currencyChange = false;
var without_stock = <?php echo json_encode($general_setting->without_stock) ?>;

$('.customer-submit-btn').on("click", function() {
    $.ajax({
        type:'POST',
        url:'{{route('customer.store')}}',
        data: $("#customer-form").serialize(),
        success:function(response) {
            key = response['id'];
            value = response['name']+' ['+response['phone_number']+']';
            $('select[name="customer_id"]').append('<option value="'+ key +'">'+ value +'</option>');
            $('select[name="customer_id"]').val(key);
            $('.selectpicker').selectpicker('refresh');
            $("#addCustomer").modal('hide');
            setCustomerGroupRate(key);
        }
    });
});

function setCustomerGroupRate(id) {
    $.get('getcustomergroup/' + id, function(data) {
        customer_group_rate = (data / 100);
    });
}

// array data depend on warehouse
var user_role = <?= json_encode($role); ?>;
var lims_product_array = [];
var product_code = [];
var product_name = [];
var product_qty = [];
var product_type = [];
var product_id = [];
var product_list = [];
var variant_list = [];
var qty_list = [];

// array data with selection
var product_price = [];
var wholesale_price = [];
var cost = [];
var product_discount = [];
var tax_rate = [];
var tax_name = [];
var tax_method = [];
var unit_name = [];
var unit_operator = [];
var unit_operation_value = [];
var is_imei = [];
var is_variant = [];
var gift_card_amount = [];
var gift_card_expense = [];
// temporary array
var temp_unit_name = [];
var temp_unit_operator = [];
var temp_unit_operation_value = [];

var deposit = <?php echo json_encode($deposit) ?>;
var points = <?php echo json_encode($points) ?>;
@if($lims_reward_point_setting_data)
var reward_point_setting = <?php echo json_encode($lims_reward_point_setting_data) ?>;
@endif

var rowindex;
var customer_group_rate;
var row_product_price;
var pos;
var role_id = <?php echo json_encode(Auth::user()->role_id)?>;

$('.selectpicker').selectpicker({
    style: 'btn-link',
});

$('[data-toggle="tooltip"]').tooltip();

$('select[name="customer_id"]').on('change', function() {
    setCustomerGroupRate($(this).val());
});

function getProduct(warehouse_id){
    $.get('getproduct/' + warehouse_id, function(data) {
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
        expired_date = data[10];
        product_batch_id = data[9];
        is_embeded = data[11];
        imei_number = data[12];

        $.each(product_code, function(index) {
            lims_product_array.push(product_code[index]+'|'+product_name[index]+'|'+imei_number[index]+'|'+is_embeded[index]);
        });

        //updating in stock
        var rownumber = $('table.order-list tbody tr:last').index();
        for(rowindex  = 0; rowindex <= rownumber; rowindex++) {
            var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code').val();
            pos = product_code.indexOf(row_product_code);
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.in-stock').text(product_qty[pos]);
        }
    });
}

var warehouse_id = $("#warehouse_id").val();
if(warehouse_id.length){
    console.log(`get-product-function called - warehouse_id = ${warehouse_id}`);
    getProduct(warehouse_id);
}

$('select[name="warehouse_id"]').on('change', function() {
    var warehouse_id = $(this).val();
    getProduct(warehouse_id);
});

$('#lims_productcodeSearch').on('input', function(){
    var customer_id = $('#customer_id').val();
    var warehouse_id = $('#warehouse_id').val();
    temp_data = $('#lims_productcodeSearch').val();
    if(!customer_id){
        $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
        alert('Please select Customer!');
    }
    else if(!warehouse_id){
        $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
        alert('Please select Warehouse!');
    }
});

var lims_productcodeSearch = $('#lims_productcodeSearch');

lims_productcodeSearch.autocomplete({
    source: function(request, response) {
        var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
        response($.grep(lims_product_array, function(item) {
            return matcher.test(item);
        }));
    },
    response: function(event, ui) {
        if (ui.content.length == 1) {
            var data = ui.content[0].value;
            $(this).autocomplete( "close" );
            $(".ui-helper-hidden-accessible").css('display', 'none');
            productSearch(data);
        }
        else if(ui.content.length == 0 && $('#lims_productcodeSearch').val().length == 13) {
            $(".ui-helper-hidden-accessible").css('display', 'none');
          productSearch($('#lims_productcodeSearch').val()+'|'+1);
        }
    },
    select: function(event, ui) {
        var data = ui.item.value;
        $(".ui-helper-hidden-accessible").css('display', 'none');
        productSearch(data);
    }
});

//Delete product
$("table.order-list tbody").on("click", ".ibtnDel", function(event) {
    rowindex = $(this).closest('tr').index();
    product_price.splice(rowindex, 1);
    wholesale_price.splice(rowindex, 1);
    product_discount.splice(rowindex, 1);
    tax_rate.splice(rowindex, 1);
    tax_name.splice(rowindex, 1);
    tax_method.splice(rowindex, 1);
    unit_name.splice(rowindex, 1);
    unit_operator.splice(rowindex, 1);
    unit_operation_value.splice(rowindex, 1);
    is_imei.splice(rowindex, 1);
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

    if(edit_qty < 0) {
        $('input[name="edit_qty"]').val(1);
        edit_qty = 1;
        alert("Quantity can't be less than 0");
    }

    tax_rate[rowindex] = parseFloat($('select[name="edit_tax_rate"]').val());
    tax_name[rowindex] = $('select[name="edit_tax_rate"] option:selected').text();
    product_price[rowindex] = $('input[name="edit_unit_price"]').val();
    product_discount[rowindex] = $('input[name="edit_discount"]').val();
    checkDiscount(edit_qty, false);
});

function productSearch(data) {
    // Parse product info
    var product_info = data.split("|");
    var code = product_info[0];
    var imeiOrSerial = product_info[2];
    var pre_qty = 0;
    var foundRow = -1;
    var allowAdd = true;

    // Check for duplicate product (and IMEI/serial if present)
    $(".product-code").each(function(i) {
        if ($(this).val() == code) {
            foundRow = i;
            pre_qty = $('table.order-list tbody tr').eq(i).find('.qty').val() || 0;
        }
    });

    if (!allowAdd) return;

    // Prepare AJAX data
    var customerId = $('#customer_id').val();
    var qtyToAdd = parseFloat(pre_qty) + 1;
    var ajaxData = data + '?' + customerId + '?' + qtyToAdd;

    $.ajax({
        type: 'GET',
        url: 'lims_product_search',
        data: { data: ajaxData },
        success: function(response) {
            var isNewRow = (pre_qty == 0 || foundRow == -1);
            $("input[name='product_code_name']").val('');
            var pos = product_code.indexOf(response[1]);
            if (isNewRow) {
                // Build new row HTML
                var temp_unit_name = (response[6] || '').split(',');
                var product_name = response[0] || '';
                if (product_name.length > 25) product_name = product_name.substring(0, 25) + "..";
                var cols = `
                    <td>${product_name}</td>
                    <td>${response[1]}</td>
                    <td><input type="text" class="form-control qty" readonly name="qty[]" value="${response[15]}" required/></td>
                    ${response[12] ? `
                        <td><input type="text" class="form-control batch-no" value="${batch_no[pos]}" required/>
                            <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="${product_batch_id[pos]}"/>
                        </td>
                        <td class="expired-date">${expired_date[pos]}</td>
                    ` : `
                        <td><input type="text" class="form-control batch-no" disabled/>
                            <input type="hidden" class="product-batch-id" name="product_batch_id[]"/>
                        </td>
                        <td class="expired-date">N/A</td>
                    `}
                    <td class="net_unit_price">${response[2]}</td>
                    <td class="discount">{{number_format(0, $general_setting->decimal, '.', '')}}</td>
                    <td class="tax"></td>
                    <td class="sub-total">${response[2]}</td>
                    <td>
                        <button type="button" class="edit-product btn btn-info" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i></button>
                        <button type="button" class="ibtnDel btn btn-md btn-danger"><i class="dripicons-trash"></i></button>
                    </td>
                    <input type="hidden" class="product-code" name="product_code[]" value="${response[1]}"/>
                    <input type="hidden" class="product-id" name="product_id[]" value="${response[9]}"/>
                    <input type="hidden" class="supplier-id" name="supplier_id[]" value="${response[19]}"/>
                    <input type="hidden" class="price" name="price[]" value="${response[2]}"/>
                    <input type="hidden" class="price" name="sale_unit[]" value="${temp_unit_name[0]}"/>
                    <input type="hidden" class="net_unit_price" name="net_unit_price[]" value="${response[2]}"/>
                    <input type="hidden" class="discount-value" name="discount[]" value="0"/>
                    <input type="hidden" class="tax-rate" name="tax_rate[]" value="${response[3]}"/>
                    <input type="hidden" class="tax-value" name="tax[]"/>
                    <input type="hidden" class="subtotal-value" name="subtotal[]" value="${response[2]}"/>
                    <input type="hidden" class="imei-number" name="imei_number[]" value="${response[18] || ''}"/>
                `;
                var newRow = $("<tr>").append(cols);
                $("table.order-list tbody").prepend(newRow);
                var rowindex = newRow.index();

                // Update JS arrays
                var price = (!response[11] && product_warehouse_price[pos])
                    ? parseFloat(product_warehouse_price[pos] * currency['exchange_rate']) + parseFloat(product_warehouse_price[pos] * currency['exchange_rate'] * customer_group_rate)
                    : parseFloat(response[2] * currency['exchange_rate']) + parseFloat(response[2] * currency['exchange_rate'] * customer_group_rate);
                product_price.splice(rowindex, 0, response[2]);
                wholesale_price.splice(rowindex, 0, response[16]
                    ? parseFloat(response[16] * currency['exchange_rate']) + parseFloat(response[16] * currency['exchange_rate'] * customer_group_rate)
                    : '{{number_format(0, $general_setting->decimal, '.', '')}}');
                cost.splice(rowindex, 0, parseFloat(response[17] * currency['exchange_rate']));
                product_discount.splice(rowindex, 0, '{{number_format(0, $general_setting->decimal, '.', '')}}');
                tax_rate.splice(rowindex, 0, parseFloat(response[3]));
                tax_name.splice(rowindex, 0, response[4]);
                tax_method.splice(rowindex, 0, response[5]);
                unit_name.splice(rowindex, 0, response[6]);
                unit_operator.splice(rowindex, 0, response[7]);
                unit_operation_value.splice(rowindex, 0, response[8]);
                is_imei.splice(rowindex, 0, response[13]);
                is_variant.splice(rowindex, 0, response[14]);
                checkQuantity(response[15], true);
            } else {
                // Update existing row (qty, price, IMEI/serial)
                var qty = response[15];
                $('table.order-list tbody tr').eq(foundRow).find('.qty').val(qty);
                var price = (!response[11] && product_warehouse_price[pos])
                    ? parseFloat(product_warehouse_price[pos] * currency['exchange_rate']) + parseFloat(product_warehouse_price[pos] * currency['exchange_rate'] * customer_group_rate)
                    : parseFloat(response[2] * currency['exchange_rate']) + parseFloat(response[2] * currency['exchange_rate'] * customer_group_rate);
                product_price[foundRow] = price;
                checkQuantity(String(qty), true);

                // Add IMEI/serial if present
                if (response[18]) {
                    var imeiNumbers = $('table.order-list tbody tr').eq(foundRow).find('.imei-number').val() || '';
                    imeiNumbers = imeiNumbers ? imeiNumbers + ',' + response[18] : response[18];
                    $('table.order-list tbody tr').eq(foundRow).find('.imei-number').val(imeiNumbers);
                }
            }
        }
    });
}

function edit()
{
    $("#product-cost").text(cost[rowindex]);
    var row_product_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1)').text();
    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
    $('#modal_header').text(row_product_name + '(' + row_product_code + ')');

    var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
    $('input[name="edit_qty"]').val(qty);
    $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed({{$general_setting->decimal}}));

    row_product_price = Number(product_price[rowindex]);
    $('input[name="edit_unit_price"]').val(row_product_price.toFixed({{$general_setting->decimal}}));
    $('.selectpicker').selectpicker('refresh');
}

function checkDiscount(qty, flag) {
    var customer_id = $('#customer_id').val();
    var warehouse_id = $('#warehouse_id').val();
    var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();

    checkQuantity(String(qty), flag);
}

function checkQuantity(sale_qty, flag) {
    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
    pos = product_code.indexOf(row_product_code);
    
    
    if(!flag){
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(sale_qty); // change quantity
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .net_unit_price').val(product_price[rowindex]); // change net_unit_price
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(6)').text(product_price[rowindex]); // change net_unit_price
        $('#editModal').modal('hide');
    }
    calculateRowProductData(sale_qty);
}

function calculateRowProductData(quantity) {
    
    row_product_price = parseFloat(product_price[rowindex]);
    row_discount = parseFloat(product_discount[rowindex]);

    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text((product_discount[rowindex] * quantity).toFixed({{$general_setting->decimal}}));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount-value').val((product_discount[rowindex] * quantity).toFixed({{$general_setting->decimal}}));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val(tax_rate[rowindex].toFixed({{$general_setting->decimal}}));

    var net_unit_price = row_product_price; // - product_discount[rowindex]; @dorian
    var tax = 0;
    var sub_total = (row_product_price - row_discount) * quantity;

    // console.log('product_price = ' + product_price);
    // console.log('rowindex = ' + rowindex);
    // console.log('row_product_price = ' + row_product_price);
    // console.log('net_unit_price = ' + net_unit_price);
    // console.log('quantity = ' + quantity);
    // console.log('sub-total = ' + sub_total);

    // $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').text(net_unit_price.toFixed({{$general_setting->decimal}})); 
    // $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(net_unit_price.toFixed({{$general_setting->decimal}})); 
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax').text(tax.toFixed({{$general_setting->decimal}}));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed({{$general_setting->decimal}}));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total.toFixed({{$general_setting->decimal}}));
    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(sub_total.toFixed({{$general_setting->decimal}}));


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
    $("#total-discount").text(total_discount.toFixed({{$general_setting->decimal}}));
    $('input[name="total_discount"]').val(total_discount.toFixed({{$general_setting->decimal}}));

    //Sum of tax
    var total_tax = 0;
    $(".tax").each(function() {
        total_tax += parseFloat($(this).text());
    });
    $("#total-tax").text(total_tax.toFixed({{$general_setting->decimal}}));
    $('input[name="total_tax"]').val(total_tax.toFixed({{$general_setting->decimal}}));

    //Sum of subtotal
    var total = 0;
    $(".sub-total").each(function() {
        total += parseFloat($(this).text());
    });
    $("#total").text(total.toFixed({{$general_setting->decimal}}));
    $('input[name="total_price"]').val(total.toFixed({{$general_setting->decimal}}));

    calculateGrandTotal();
}

function calculateGrandTotal() {

    var item = $('table.order-list tbody tr:last').index();
    var total_qty = parseFloat($('#total-qty').text());
    var subtotal = parseFloat($('#total').text());
    var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
    if(!currencyChange)
        var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());
    else
        var shipping_cost = parseFloat($('input[name="shipping_cost"]').val()*currency['exchange_rate']);
    var order_discount_type = $('select[name="order_discount_type"]').val();
    var order_discount_value = parseFloat($('input[name="order_discount_value"]').val());
    if (!order_discount_value)
        order_discount_value = {{number_format(0, $general_setting->decimal, '.', '')}};

    if(order_discount_type == 'Flat') {
        if(!currencyChange)
            var order_discount = parseFloat(order_discount_value);
        else
            var order_discount = parseFloat(order_discount_value*currency['exchange_rate']);
    }
    else
        var order_discount = parseFloat(subtotal * (order_discount_value / 100));

    if (!shipping_cost)
        shipping_cost = {{number_format(0, $general_setting->decimal, '.', '')}};

    item = ++item + '(' + total_qty + ')';
    order_tax = (subtotal - order_discount) * (order_tax / 100);
    var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;

    $('input[name="order_discount"]').val(order_discount);
    $('input[name="shipping_cost"]').val(shipping_cost);
    $('#item').text(item);
    $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
    $('#subtotal').text(subtotal.toFixed({{$general_setting->decimal}}));
    $('#order_tax').text(order_tax.toFixed({{$general_setting->decimal}}));
    $('input[name="order_tax"]').val(order_tax.toFixed({{$general_setting->decimal}}));
    $('#order_discount').text(order_discount.toFixed({{$general_setting->decimal}}));
    $('#shipping_cost').text(shipping_cost.toFixed({{$general_setting->decimal}}));
    $('#grand_total').text(grand_total.toFixed({{$general_setting->decimal}}));
    $('input[name="grand_total"]').val(grand_total.toFixed({{$general_setting->decimal}}));
    currencyChange = false;
}

function cancel(rownumber) {
    while(rownumber >= 0) {
        product_price.pop();
        wholesale_price.pop();
        product_discount.pop();
        tax_rate.pop();
        tax_name.pop();
        tax_method.pop();
        unit_name.pop();
        unit_operator.pop();
        unit_operation_value.pop();
        $('table.order-list tbody tr:last').remove();
        rownumber--;
    }
    $('input[name="shipping_cost"]').val('');
    $('input[name="order_discount_value"]').val('');
    $('select[name="order_tax_rate_select"]').val(0);
    calculateTotal();
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


$(window).keydown(function(e){
    if (e.which == 13) {
        var $targ = $(e.target);
        if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
            var focusNext = false;
            $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                if (this === e.target) {
                    focusNext = true;
                }
                else if (focusNext){
                    $(this).focus();
                    return false;
                }
            });
            return false;
        }
    }
});

$("#submit-button").on("click", function() {
    $('.payment-form').submit();
});

$(document).on('submit', '.payment-form', function(e) {
    var user_role = 1;
    var rownumber = $('table.order-list tbody tr:last').index();
    $("table.order-list tbody .qty").each(function(index) {
        if ($(this).val() == '') {
            alert('One of products has no quantity!');
            e.preventDefault();
        }
    });
    if ( rownumber < 0 ) {
        alert("Please insert product to order table!")
        e.preventDefault();
    }
    else if(parseFloat($('input[name="total_qty"]').val()) <= 0) {
        alert('Product quantity is 0');
        e.preventDefault();
    }
    else if( parseFloat($("#paying-amount").val()) < parseFloat($("#paid-amount").val()) ){
        alert('Paying amount cannot be bigger than recieved amount');
        e.preventDefault();
    }
    else if(!$('#biller_id').val() && user_role["name"] == 'Admin') {
        alert('Please select a biller');
        e.preventDefault();
    }
    else {
        e.preventDefault(); // Prevents the default form submission behavior
        $.ajax({
            url: $('.payment-form').attr('action'),
            type: $('.payment-form').attr('method'),
            data: $('.payment-form').serialize(),
            success: function(response) {
                
                // return 0;

                if(response.code == 400){
                    alert(response.msg);
                    $("#submit-button").prop('disabled', false);
                    return ;
                }

                if (response.payment_method === 'pesapal' && response.redirect_url) {
                    // Redirect to the URL returned for Pesapal payment method
                    location.href = response.redirect_url;
                } else if ($('select[name="sale_status"]').val() == 1 && response !== 'pesapal') {
                    let link = "{{url('sales/gen_invoice/')}}" + '/' + response;
                    $('#print-layout').load(link, function() {
                        setTimeout(function() {
                            window.print();
                        }, 50);
                    });
 
                    $("#submit-button").prop('disabled', false);
                    $('#add-payment').modal('hide');
                    cancel($('table.order-list tbody tr:last').index());

                    setTimeout(function() {
                        window.onafterprint = function(){
                            $('#print-layout').html('');
                        }
                    }, 100);
                }
                else if($('select[name="sale_status"]').val() != 1){
                    localStorage.clear();
                    location.href = "{{route('sales.index')}}";
                }
                else {
                    localStorage.clear();
                    location.href = response;
                }
            },
            error: function(xhr) {
                console.log('Form submission failed.');
            }
        });

    }
});

</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
