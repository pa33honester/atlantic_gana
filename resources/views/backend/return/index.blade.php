@extends('backend.layout.main') @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{trans('Report Order List')}}</h3>
            </div>
            {!! Form::open(['route' => 'return-sale.index', 'method' => 'get']) !!}

            <div class="row m-2 align-items-end">
                <div class="col-lg-2 col-md-3 mb-2">
                    <label for="date-range" class="font-weight-bold">{{ trans('file.Date') }}:</label>
                    <div class="input-group">
                        <input id="date-range" type="text" class="daterangepicker-field form-control" value="{{ $starting_date }} To {{ $ending_date }}" required />
                        <input type="hidden" name="starting_date" value="{{ $starting_date }}" />
                        <input type="hidden" name="ending_date" value="{{ $ending_date }}" />
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 mb-2">
                    <label for="supplier-id" class="font-weight-bold">{{ trans('file.Supplier') }}:</label>
                    <select id="supplier-id" class="form-control selectpicker" name="supplier_id" data-live-search="true">
                        @if(sizeof($lims_supplier_list) > 1)
                        <option value="0">{{ trans('All') }}</option>
                        @endif
                        @foreach($lims_supplier_list as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->phone_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 mb-2">
                    <div class="form-group">
                        <label><strong>{{trans('Product')}}</strong></label>
                        <select id="product_code" class="form-control" name="product_code" data-live-search="true">
                            <option value="0">All</option>
                            @foreach($lims_product_codes as $row)
                                <option value="{{$row['code']}}">{{$row['code']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-lg-offset-4 col-md-4 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="filter-btn" type="submit">{{ trans('file.submit') }}</button>
                </div>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
    <div class="table-responsive">
        <table id="return-table" class="table return-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('Order Number')}}</th>
                    <th>{{trans('Reporting Reason')}}</th>      
                    <th>{{trans('Reporting Time')}}</th>      
                    <th>{{trans('Expected Call On')}}</th>      
                    <th>{{trans('file.Product Name')}}</th>
                    <th>{{trans('Product Number')}}</th>     
                    <th>{{trans('file.Supplier')}}</th>
                    <th>{{trans('Order Time')}}</th>  
                    <th>{{trans('Product Quantity')}}</th>                         
                    <th>{{trans('Total Product Price')}}</th>
                    <th>{{trans('Customer Information')}}</th>    
                    <th>{{trans('Customer Address')}}</th>      
                    <th>{{trans('Report Times')}}</th>      
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>

            <tfoot class="tfoot active">
                <th></th>
                <th>{{trans('file.Total')}}</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tfoot>
        </table>
    </div>

    <div id="return-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
          <div class="modal-content">
            <div class="container mt-3 pb-2 border-bottom">
            <div class="row">
                <div class="col-md-6 d-print-none">
                    <button id="print-btn" type="button" class="btn btn-default btn-sm"><i class="dripicons-print"></i> {{trans('file.Print')}}</button>
                    {{ Form::open(['route' => 'return-sale.sendmail', 'method' => 'post', 'class' => 'sendmail-form'] ) }}
                        <input type="hidden" name="return_id">
                        <button class="btn btn-default btn-sm d-print-none"><i class="dripicons-mail"></i> {{trans('file.Email')}}</button>
                    {{ Form::close() }}
                </div>
                <div class="col-md-6 d-print-none">
                    <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="col-md-12">
                    <h3 id="exampleModalLabel" class="modal-title text-center container-fluid">{{$general_setting->site_title}}</h3>
                </div>
                <div class="col-md-12 text-center">
                    <i style="font-size: 15px;">{{trans('file.Return Details')}}</i>
                </div>
            </div>
        </div>
                <div id="return-content" class="modal-body">
                </div>
                <br>
                <table class="table table-bordered product-return-list">
                    <thead>
                        <th>#</th>
                        <th>{{trans('file.product')}}</th>
                        <th>{{trans('file.Batch No')}}</th>
                        <th>{{trans('file.Qty')}}</th>
                        <th>{{trans('file.Unit Price')}}</th>
                        <th>{{trans('file.Tax')}}</th>
                        <th>{{trans('file.Discount')}}</th>
                        <th>{{trans('file.Subtotal')}}</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <div id="return-footer" class="modal-body"></div>
          </div>
        </div>
    </div>
</section>

<div id="update-status" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('Edit Status')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'sale.update-status', 'method' => 'post', 'files' => true, 'class' => 'update_status']) !!}
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>{{trans('Order Number')}}</label>
                        <p class="order_number"></p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{trans('Order Time')}}</label>
                        <p class="order_time"></p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{trans('Product Amount')}}</label>
                        <p class="product_amount"></p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{trans('Customer Name')}}</label>
                        <p class="customer_name"></p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{trans('Phone Number')}}</label>
                        <p class="customer_phone"></p>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{trans('Customer Address')}}</label>
                        <p class="customer_address"></p>
                    </div>
                    <div class="col-md-12">
                        <table id="confirm-product-list" class="table table-responsive">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Product Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-12 form-group">
                         <label>{{trans('Location')}} *</label>
                        <select  name="location" class="form-control selectpicker">
                            <option value="0">{{ trans('Not Selected') }}</option>
                            <option value="1">{{trans('Inside Accra')}}</option>
                            <option value="2">{{trans('Outside Accra')}}</option>
                            <option value="3">{{trans('Kumasi')}}</option>
                        </select>
                    </div>
                    <div class="col-md-12 form-group">
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-success active">
                                <input type="radio" name="res_type" id="confirm" value="confirm" onchange="order_reason(this.id);reset_validation('input', 'res_type');" checked> Confirm Order
                            </label>
                            <label class="btn btn-danger">
                                <input type="radio" name="res_type" id="cancel" value="cancel" onchange="order_reason(this.id);reset_validation('input', 'res_type');"> Cancel Order
                            </label>
                            <label class="btn btn-warning">
                                <input type="radio" name="res_type" id="report" value="report" onchange="order_reason(this.id);reset_validation('input', 'res_type');"> Report Order
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12 form-group" id="res_reason_1">
                        <label>{{trans('Cancel Reason')}} *</label>
                        <select name="res_reason_1" required class="form-control selectpicker" onchange="reset_validation('select', 'res_reason_1');">
                            <option value="No-Answer">{{trans('No Answer')}}</option>
                            <option value="Rejected">{{trans('Rejected')}}</option>
                            <option value="Out-of-Stock">{{trans('Out of Stock')}}</option>
                        </select>
                    </div>
                    <div class="col-md-12 form-group" id="res_reason_2">
                        <label>{{trans('Reporting Reason')}} *</label>
                        <div class="d-flex flex-row gap-3" style="gap: 2rem;">
                            <div class="form-check form-check-inline" style="display: flex; align-items: center; margin-right: 2rem;">
                                <input class="form-check-input" type="radio" name="res_reason_2" id="reason_no_answer" value="No-Answer" required onchange="reset_validation('select', 'res_reason_2');$('#call_on_date_picker').addClass('d-none');">
                                <label class="form-check-label ml-2" for="reason_no_answer" style="margin-left: 0.5rem;">{{trans('No Answer')}}</label>
                            </div>
                            <div class="form-check form-check-inline" style="display: flex; align-items: center; margin-right: 2rem;">
                                <input class="form-check-input" type="radio" name="res_reason_2" id="reason_switched_off" value="Switch-Off" onchange="reset_validation('select', 'res_reason_2');$('#call_on_date_picker').addClass('d-none');">
                                <label class="form-check-label ml-2" for="reason_switched_off" style="margin-left: 0.5rem;">{{trans('Switched Off')}}</label>
                            </div>
                            <div class="form-check form-check-inline" style="display: flex; align-items: center;">
                                <input class="form-check-input" type="radio" name="res_reason_2" id="reason_call_on" value="Call-On" onchange="reset_validation('select', 'res_reason_2');$('#call_on_date_picker').removeClass('d-none');">
                                <label class="form-check-label ml-2" for="reason_call_on" style="margin-left: 0.5rem;">{{trans('Call On')}}</label>
                            </div>
                             <div class="form-check form-check-inline" style="display: flex; align-items: center;">
                                <input class="form-check-input" type="radio" name="res_reason_2" id="reason_out_of_stock" value="Out-of-Stock" onchange="reset_validation('select', 'res_reason_2');$('#call_on_date_picker').addClass('d-none');">
                                <label class="form-check-label ml-2" for="reason_out_of_stock" style="margin-left: 0.5rem;">{{trans('Out-of-Stock')}}</label>
                            </div>
                        </div>
                        <div id="call_on_date_picker" class="mt-2 d-none">
                            <label for="call_on_date">{{ trans('Select Call On Date') }}</label>
                            <input type="date" name="call_on_date" id="call_on_date" class="form-control" />
                        </div>
                    </div>
                    
                    <div class="col-md-12 form-group">
                        <label>{{trans('Remark')}}</label>
                        <textarea rows="3" name="res_info" class="form-control" onclick="reset_validation('textarea', 'res_info');"></textarea>
                    </div>
                </div>
                <div id="product-image-modal" class="modal">
                    <span class="close">&times;</span>
                    <img class="modal-content" id="modal-confirm-product-image">
                </div>
                <input type="hidden" name="reference_no">
                <input type="hidden" name="sale_id">
                <button type="button" class="btn btn-primary update_status_btn">{{trans('file.submit')}}</button>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

<div id="cancel-order" tabindex="-1" role="dialog" aria-labelledby="orderCancel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="orderCancel" class="modal-title">{{trans('Update Status')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'sale.update-status', 'method' => 'post', 'files' => true, 'class' => 'cancel_order']) !!}
                <div class="row">
                    <div class="col-md-12 form-group" id="res_reason">
                        <label>{{trans('Reporting Reason')}} *</label>
                        <select name="res_reason_1" required class="form-control selectpicker" onchange="reset_validation('select', 'res_reason_1');">
                            <option value="No-Answer">{{trans('No Answer')}}</option>
                            <option value="Rejected">{{trans('Rejected')}}</option>
                            <option value="Out-of-Stock">{{trans('Out of Stock')}}</option>
                        </select>
                    </div>                    
                    <div class="col-md-12 form-group">
                        <label>{{trans('Remark')}}</label>
                        <textarea rows="3" name="res_info" class="form-control" onclick="reset_validation('textarea', 'res_info');"></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12"><p class="ajax-status"></p></div>
                    <div class="col-md-6 form-group text-left">
                        <input type="hidden" name="reference_no">
                        <input type="hidden" name="sale_id">
                        <input type="hidden" name="order_type">
                        <h5 id="orderCancelLabel">Are you sure to cancel delivery ?</h5>
                    </div>
                    <div class="col-md-6 form-group text-left">
                        <button type="button" class="btn btn-info order_cancel_btn">{{trans('file.submit')}}</button>
                    </div>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

<div id="edit-sale" tabindex="-1" role="dialog" aria-labelledby="editSale" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="editSaleLabel" class="modal-title">Update Order</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
            <iframe src="" style="border:none;width:100%;height:800px;" id="editSale">
            </iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#return").siblings('a').attr('aria-expanded','true');
    $("ul#return").addClass("show");
    $("ul#return #sale-return-menu").addClass("active");

    $(".daterangepicker-field").daterangepicker({
      callback: function(startDate, endDate, period){
        var starting_date = startDate.format('YYYY-MM-DD');
        var ending_date = endDate.format('YYYY-MM-DD');
        var title = starting_date + ' To ' + ending_date;
        $(this).val(title);
        $('input[name="starting_date"]').val(starting_date);
        $('input[name="ending_date"]').val(ending_date);
      }
    });

    var all_permission = <?php echo json_encode($all_permission) ?>;
    var return_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;
    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date = <?php echo json_encode($ending_date); ?>;
    var supplier_id = <?php echo json_encode($supplier_id); ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;
    var product_code = <?php echo json_encode($product_code); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    $(document).on("click", ".view", function() {
        var returns = $(this).parent().parent().parent().parent().parent().data('return');
        returnDetails(returns);
    });

    $("#print-btn").on("click", function(){
        var divContents = document.getElementById("return-details").innerHTML;
        var a = window.open('');
        a.document.write('<html>');
        a.document.write('<body>');
        a.document.write('<style>body{font-family: sans-serif;line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-center{text-align:center}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-aligh:left}td{padding:10px}table,th,td{border: 1px solid black; border-collapse: collapse;}</style><style>@media print {.modal-dialog { max-width: 1000px;} }</style>');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function(){a.close();},10);
        a.print();
    });

    $("#supplier-id").val(supplier_id);
    $("#product_code").val(product_code);
    $('.selectpicker').selectpicker('refresh');

    $('#return-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"return-sale/return-data",
            data:{
                all_permission: all_permission,
                starting_date: starting_date,
                ending_date: ending_date,
                warehouse_id: warehouse_id,
                supplier_id : supplier_id,
                product_code: product_code,
            },
            dataType: "json",
            type:"post"
        },
        "createdRow": function( row, data, dataIndex ) {
            //alert(data);
            $(row).addClass('return-link');
            $(row).attr('data-return', data['return']);
        },
        "columns": [
            {"data": "key"},            
            {"data": "sale_reference"},
            {"data": "return_note"},
            {"data": "date"},
            {"data": "call_on"},
            {"data": "product_name"}, 
            {"data": "product_code"},
            {"data": "supplier"},
            {"data": "order_date"},
            {"data": "item"}, 
            {"data": "grand_total"},
            {"data": "customer"},
            {"data": "customer_address"},
            {"data": "report_times"},
            {"data": "options"},
        ],
        'language': {

            'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
             "info":      '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{trans("file.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        order:[['1', 'desc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 2, 3, 4, 5, 6, 7,8, 11, 12, 14]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            },
        ],
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
    } );

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 10 ).footer() ).html(dt_selector.cells( rows, 10, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
            $( dt_selector.column( 9 ).footer() ).html(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
        }
        else {
            $( dt_selector.column( 10 ).footer() ).html(dt_selector.cells( rows, 10, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
            $( dt_selector.column( 9 ).footer() ).html(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
        }
    }

    function returnDetails(returns){
        $('input[name="return_id"]').val(returns[13]);
        var htmltext = '<strong>{{trans("file.Date")}}: </strong>'+returns[0]+'<br><strong>{{trans("file.reference")}}: </strong>'+returns[1]+'<br><strong>{{trans("file.Sale Reference")}}: </strong>'+returns[24]+'<br><strong>{{trans("file.Warehouse")}}: </strong>'+returns[2]+'<br><strong>{{trans("file.Currency")}}: </strong>'+returns[26];
        if(returns[27])
            htmltext += '<br><strong>{{trans("file.Exchange Rate")}}: </strong>'+returns[27]+'<br>';
        else
            htmltext += '<br><strong>{{trans("file.Exchange Rate")}}: </strong>N/A<br>';
        if(returns[25])
            htmltext += '<strong>{{trans("file.Attach Document")}}: </strong><a href="documents/sale_return/'+returns[25]+'">Download</a><br>';
        htmltext += '<br><div class="row"><div class="col-md-6"><strong>{{trans("file.From")}}:</strong><br>'+returns[3]+'<br>'+returns[4]+'<br>'+returns[5]+'<br>'+returns[6]+'<br>'+returns[7]+'<br>'+returns[8]+'</div><div class="col-md-6"><div class="float-right"><strong>{{trans("file.To")}}:</strong><br>'+returns[9]+'<br>'+returns[10]+'<br>'+returns[11]+'<br>'+returns[12]+'</div></div></div>';
        $.get('return-sale/product_return/' + returns[13], function(data){
            $(".product-return-list tbody").remove();
            var name_code = data[0];
            var qty = data[1];
            var unit_code = data[2];
            var tax = data[3];
            var tax_rate = data[4];
            var discount = data[5];
            var subtotal = data[6];
            var batch_no = data[7];
            var newBody = $("<tbody>");
            $.each(name_code, function(index){
                var newRow = $("<tr>");
                var cols = '';
                cols += '<td><strong>' + (index+1) + '</strong></td>';
                cols += '<td>' + name_code[index] + '</td>';
                cols += '<td>' + batch_no[index] + '</td>';
                cols += '<td>' + qty[index] + ' ' + unit_code[index] + '</td>';
                cols += '<td>' + (subtotal[index] / qty[index]) + '</td>';
                cols += '<td>' + tax[index] + '(' + tax_rate[index] + '%)' + '</td>';
                cols += '<td>' + discount[index] + '</td>';
                cols += '<td>' + subtotal[index] + '</td>';
                newRow.append(cols);
                newBody.append(newRow);
            });

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=5><strong>{{trans("file.Total")}}:</strong></td>';
            cols += '<td>' + returns[14] + '</td>';
            cols += '<td>' + returns[15] + '</td>';
            cols += '<td>' + returns[16] + '</td>';
            newRow.append(cols);
            newBody.append(newRow);

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=7><strong>{{trans("file.Order Tax")}}:</strong></td>';
            cols += '<td>' + returns[17] + '(' + returns[18] + '%)' + '</td>';
            newRow.append(cols);
            newBody.append(newRow);

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=7><strong>{{trans("file.grand total")}}:</strong></td>';
            cols += '<td>' + returns[19] + '</td>';
            newRow.append(cols);
            newBody.append(newRow);

            $("table.product-return-list").append(newBody);
        });
        var htmlfooter = '<p><strong>{{trans("file.Return Note")}}:</strong> '+returns[20]+'</p><p><strong>{{trans("file.Staff Note")}}:</strong> '+returns[21]+'</p><strong>{{trans("file.Created By")}}:</strong><br>'+returns[22]+'<br>'+returns[23];
        $('#return-content').html(htmltext);
        $('#return-footer').html(htmlfooter);
        $('#return-details').modal('show');
    }

    if(all_permission.indexOf("returns-delete") == -1)
        $('.buttons-delete').addClass('d-none');
</script>
<script>
    function order_reason(id){
        if(id == "all"){
            $("#res_reason_1").addClass("hidden");
            $("#res_reason_2").addClass("hidden");  
        } else if(id == "confirm"){
            $("#res_reason_1").addClass("hidden");
            $("#res_reason_2").addClass("hidden"); 
        } else if(id == "cancel"){
            $("#res_reason_1").removeClass("hidden");
            $("#res_reason_2").addClass("hidden");
        } else if(id == "report"){
            $("#res_reason_1").addClass("hidden");
            $("#res_reason_2").removeClass("hidden");
        }
    }
    order_reason("all");

    function check_validation(field_type, field_name, field_value){
        if(field_value == ""){
            if(field_type == "input"){
                $('input[name="'+field_name+'"]').css("border","1px solid red");
                return false;
            }            
            if(field_type == "select"){
                $('select[name="'+field_name+'"]').css("border","1px solid red");
                return false;
            }            
            if(field_type == "textarea"){
                $('textarea[name="'+field_name+'"]').css("border","1px solid red");
                return false;
            }            
        }
    }

    function reset_validation(field_type, field_name){
        if(field_type == "input"){
            $('input[name="'+field_name+'"]').css("border","1px solid #e4e6fc");
            return false;
        }            
        if(field_type == "select"){
            $('select[name="'+field_name+'"]').css("border","1px solid #e4e6fc");
            return false;
        }            
        if(field_type == "textarea"){
            $('textarea[name="'+field_name+'"]').css("border","1px solid #e4e6fc");
            return false;
        }
    }

    function update_status(el){
        var data = JSON.parse(el.getAttribute('data-confirm'));

        $('#update-status .order_number').text(data['order_number']);
        $('#update-status .order_time').text(data['order_time']);
        $('#update-status .product_amount').text(data['product_amount']);
        $('#update-status .customer_name').text(data['customer_name']);
        $('#update-status .customer_phone').text(data['customer_phone']);
        $('#update-status .customer_address').text(data['customer_address']);
        $('#update-status select[name=location]').val(data['location']); // @dorian
        $('#update-status input[name="reference_no"]').val(data['order_number']);
        $('#update-status input[name="sale_id"]').val(data['id']);
        var html_product_list = "";
        data['products'].forEach(function(e){
            html_product_list += `
                <tr>
                    <td>
                        <img src="images/product/${e.img[0]}" 
                            class="img-responsive product-thumbnail" 
                            width="50" 
                            height="50"
                            data-full-image="images/product/${e.img[0]}"
                            style="cursor: pointer;">
                    </td>
                    <td>${e.product_name}</td>
                    <td>${e.price}</td>
                    <td>${e.qty}</td>
                    <td>${e.amount}</td>
                </tr>
            `;
        });
        $('#confirm-product-list tbody').html(html_product_list);
        // Handle click on thumbnails to open stacked modal
        $(document).on('click', '.product-thumbnail', function() {
            const fullImageUrl = $(this).data('full-image');
            $('#modalImage').attr('src', fullImageUrl);
            $('#imageModal').modal('show');
        });
        $('#update-status .selectpicker').selectpicker('refresh');
        $('#update-status').modal('show');
    }

    function editx(id){
        //alert(id);
        $("#editSale").attr("src","/sales/"+id+"/edit");
        $('#edit-sale').modal('show');
    }
    
    $(".update_status_btn").on("click", function(){

        var loc = $('select[name=location]').val();
        
        var sale_id = $('input[name="sale_id"]').val();
        var reference_no = $('input[name="reference_no"]').val();
        
        var res_type = $('input[name="res_type"]:checked').val();
        check_validation("input", "res_type", res_type);
        
        if(res_type == "confirm"){
            if(loc == 0) {
                alert('Please select location!');
                return;
            }
            var res_info = $('textarea[name="res_info"]').val();
            check_validation("textarea", "res_info", res_info);
        } else if(res_type == "cancel"){
            var res_reason_1 = $('select[name="res_reason_1"]').val();
            check_validation("select", "res_reason_1", res_reason_1);
            var res_info = $('textarea[name="res_info"]').val();
            check_validation("textarea", "res_info", res_info); 
        } else if(res_type == "reject"){
            var res_reason_2 = $('input[name="res_reason_2"]:checked').val();
            check_validation("select", "res_reason_2", res_reason_2);
            var res_info = $('textarea[name="res_info"]').val();
            check_validation("textarea", "res_info", res_info);
        }

        $.ajax({
            url: '../../sales/updatestatus',
            type: "POST",
            data: $(".update_status").serializeArray(),
            success:function(data) {
                //alert(data);
                if(data.code == 200){
                    toastr.success(data.msg);
                    $('#update-status').modal('hide');
                    location.reload();
                }
                else {
                    toastr.error(data.msg);
                }
            }
        });
    }); 

    function cancel_order(id){
        $.get('delivery/create/'+id, function(data) {
                
                $('input[name="reference_no"]').val(data[0]);
                $('input[name="sale_id"]').val(id);
                $('input[name="order_type"]').val("cancel_order");
                //$(".ajax-status").html(data);
                $("#orderCancel").text("Cancel Order");
                $("#orderCancelLabel").text("Are you sure to cancel this order ?");

                var res_reason = $('select[name="res_reason_1"]').val();
                check_validation("select", "res_reason", res_reason_2);
                var res_info = $('textarea[name="res_info"]').val();
                check_validation("textarea", "res_info", res_info);
            });
            $('#cancel-order').modal('show');
    }
    $(".order_cancel_btn").on("click", function(){

        var sale_id = $('input[name="sale_id"]').val();
        var reference_no = $('input[name="reference_no"]').val();

        $.ajax({
            url: '../../sales/updatestatusfilters',
            type: "POST",
            data: $(".cancel_order").serializeArray(),
            success:function(data) {
                //alert(data);
                $('#cancel-order').modal('hide');
                location.reload();
            }
        });
    });
</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
