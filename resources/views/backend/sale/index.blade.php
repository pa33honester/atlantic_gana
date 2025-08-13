@extends('backend.layout.main')
@section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" />

<section>
    <div class="container-fluid"><?php //echo $sale_status; ?>
        @if(in_array("sales-add", $all_permission))
            <a href="{{route('sales.create')}}" class="btn btn-info add-sale-btn"><i class="dripicons-plus"></i> {{trans('Add Order')}}</a>&nbsp;
        @endif
        <div class="card mt-3">
            <div class="card-body">
                {!! Form::open(['route' => 'sales.index', 'method' => 'get']) !!}
                <div class="row mt-2">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><strong>{{trans('file.Date')}}</strong></label>
                            <input type="text" class="daterangepicker-field form-control" value="{{$starting_date}} ~ {{$ending_date}}" required />
                            <input type="hidden" name="starting_date" value="{{$starting_date}}" />
                            <input type="hidden" name="ending_date" value="{{$ending_date}}" />
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label><strong>{{trans('Location')}}</strong></label>
                            <select id="location" class="form-control" name="location">
                                <option value="0">{{trans('file.All')}}</option>
                                <option value="1">{{trans('Inside Accra')}}</option>
                                <option value="2">{{trans('Outside Accra')}}</option>
                                <option value="3">{{trans('Kumasi')}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label><strong>{{trans('file.Supplier')}}</strong></label>
                            <select id="supplier-id" class="form-control" name="supplier_id" data-live-search="true">
                                <option value="0">All</option>
                                @foreach($lims_supplier_list as $supplier)
                                    <option value="{{$supplier->id}}">{{$supplier->name}} ({{$supplier->phone_number}})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
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
                    <div class="col-md-6">
                        <div class="form-group">
                        <label><strong>{{trans('file.Order Status')}}</strong></label><br>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-info btn-sale btn-0">
                                    <input type="radio" name="sale_status"  value="0" onchange="make_active(this.value)"> All
                                </label>
                                <label class="btn btn-info btn-sale btn-6">
                                    <input type="radio" name="sale_status" value="6" onchange="make_active(this.value)"> Unpaid
                                </label>
                                <label class="btn btn-info btn-sale btn-7">
                                    <input type="radio" name="sale_status" value="7" onchange="make_active(this.value)"> Confirmed
                                </label>
                                <label class="btn btn-info btn-sale btn-12">
                                    <input type="radio" name="sale_status" value="12" onchange="make_active(this.value)"> Receiving
                                </label>
                                <label class="btn btn-info btn-sale btn-8">
                                    <input type="radio" name="sale_status" value="8" onchange="make_active(this.value)"> Shipped
                                </label>
                                <label class="btn btn-info btn-sale btn-9">
                                    <input type="radio" name="sale_status" value="9" onchange="make_active(this.value)"> Signed
                                </label>
                                <label class="btn btn-info btn-sale btn-14">
                                    <input type="radio" name="sale_status" value="14" onchange="make_active(this.value)"> Returned Receiving
                                </label>
                                <label class="btn btn-info btn-sale btn-4">
                                    <input type="radio" name="sale_status" value="4" onchange="make_active(this.value)"> Returned
                                </label>
                                <label class="btn btn-info btn-sale btn-11">
                                    <input type="radio" name="sale_status" value="11" onchange="make_active(this.value)"> Cancelled
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                        <label><strong>Search</strong></label><br>
                            <button class="btn btn-primary" id="filter-btn" type="submit">{{trans('file.submit')}}</button>
                        </div>
                    </div>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <style>
           .datatable-controls-wrapper {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 1rem;
            }

            .left-section,
            .middle-section,
            .right-section {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .middle-section {
                flex-grow: 1;
                justify-content: center;
                gap: 150px;
            }

            /* Force buttons to line up horizontally */
            .dt-buttons {
                display: flex !important;
                gap: 6px;
                flex-wrap: nowrap;
                justify-content: flex-end;
                align-items: center;
            }

            /* Optional: make sure each button doesn't expand or stack */
            .dt-button {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                padding: 4px 8px;
                white-space: nowrap;
            }

            /* Optional: if the right section is misbehaving */
            .right-section {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                flex-wrap: nowrap;
            }
        </style>
        <table id="sale-table" class="table sale-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('Order Number')}}</th>
                    <th>{{trans('file.Product Name')}}</th>
                    <th>{{trans('Product Number')}}</th>      
                    <th>{{trans('Supplier')}}</th>
                    <th>{{trans('Order Time')}}</th>                         
                    <th>{{trans('file.Order Status')}}</th>   
                    <th>{{trans('Product Quantity')}}</th>
                    <th>{{trans('Total Product Price')}}</th>
                    <th>{{trans('Delivery Fee')}}</th>                    
                    <th>{{trans('Customer Information')}}</th>    
                    <th>{{trans('Customer Address')}}</th>                
                    <th>{{trans('Update Time')}}</th>   
                    <th>{{trans('Location')}}</th>
                    @if($sale_status == 11)
                    <th>{{trans('Cancel Reason')}}</th>
                    @endif
                    <th class="not-exported" style="width:200px !important;">{{trans('file.Action')}}</th>
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
                <th style="width:100px !important;">
                    <button id="print-waybill-btn" class="btn btn-danger" style="display:none;">Print Waybill</button>
                </th>
            </tfoot>
        </table>
    </div>
</section>

<div id="sale-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="container mt-3 pb-2 border-bottom">
                <div class="row">
                    <div class="col-md-6 d-print-none">
                        <button type="button" class="btn btn-default btn-sm btn-print" data-print-target="#sale-details"><i class="dripicons-print"></i> {{trans('file.Print')}}</button>

                        {{ Form::open(['route' => 'sale.sendmail', 'method' => 'post', 'class' => 'sendmail-form'] ) }}
                            <input type="hidden" name="sale_id">
                            <button id='email-btn' class="btn btn-default btn-sm d-print-none"><i class="dripicons-mail"></i> {{trans('file.Email')}}</button>
                        {{ Form::close() }}
                    </div>
                    <div class="col-md-6 d-print-none">
                        <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="col-md-12 text-center">
                        <div class="d-print-none">
                            <img src="{{url('logo', $general_setting->site_logo)}}" width="90px;">
                        </div>
                        <h3 id="exampleModalLabel" class="modal-title container-fluid">{{$general_setting->site_title}}</h3>
                        <div class="d-print-none">
                            <i style="font-size: 15px;">{{trans('file.Sale Details')}}</i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-rounded btn-md btn-success d-print-none" data-dismiss="modal" aria-label="Close" onclick=""> OK </button>
            </div>
        </div>
    </div>
</div>

<div id="confirm-print" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="container mt-3 pb-2 border-bottom">
                <div class="row">
                    <div class="col-md-6 d-print-none">
                        <button type="button" class="btn btn-default btn-sm btn-print" data-print-target="#confirm-print"><i class="dripicons-print"></i> {{trans('file.Print')}}</button>
                    </div>
                    <div class="col-md-6 d-print-none">
                        <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="col-md-12 text-center">
                        <div class="d-print-none">
                            <img src="{{url('logo', $general_setting->site_logo)}}" width="90px;">
                        </div>
                        <h3 id="exampleModalLabel" class="modal-title container-fluid">{{$general_setting->site_title}}</h3>
                        <div class="d-print-none">
                            <i style="font-size: 15px;">{{trans('file.Sale Details')}}</i>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="order_type">
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button id="btn-delivery" type="submit" class="btn btn-rounded btn-md btn-success d-print-none"> Deliver </button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal (Stacked) -->
<div id="imageModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Image</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" class="img-fluid" src="">
            </div>
        </div>
    </div>
</div>

<style id="img-pop-up-style">
    #imageModal {
        z-index: 1060 !important; /* Higher than the original modal */
    }

    /* Adjust backdrop to appear between modals */
    #imageModal + .modal-backdrop {
        z-index: 1055 !important;
    }

    /* Original modal backdrop (optional, if needed) */
    #update-status + .modal-backdrop {
        z-index: 1040 !important;
    }
</style>

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

<div id="update-status-filters" tabindex="-1" role="dialog" aria-labelledby="updateStatusFilters" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="updateStatusFilters" class="modal-title">{{trans('Update Status')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'sale.update-status-filters', 'method' => 'post', 'files' => true, 'class' => 'update_status_filters']) !!}
                <div class="row">
                    <div class="col-md-6 form-group text-left">
                        <input type="hidden" name="reference_no">
                        <input type="hidden" name="sale_id">
                        <input type="hidden" name="order_type">
                        <h5 id="updateStatusFiltersLabel">Are you sure to start delivery ?</h5>
                    </div>
                    <div class="col-md-6 form-group text-left">
                        <button type="button" class="btn btn-info update_status_filters_btn">{{trans('file.submit')}}</button>
                    </div>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

<div id="update-shipping-fee" tabindex="-1" role="dialog" aria-labelledby="updateShippingFee" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="updateShippingFee" class="modal-title">Update Shipping Fee</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'sale.update-status-filters', 'method' => 'post', 'files' => true, 'class' => 'update_shipping_fee']) !!}
                <div class="row">
                    <div class="col-md-12"><p class="ajax-status">Shipping Cost</p></div>
                    <div class="col-md-6 form-group text-left">
                        <input type="hidden" name="sale_id">
                        <input type="hidden" name="order_type">
                        <select name="shipping_cost" class="form-control">
                            @foreach($lims_shipping_cost_list as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group text-left">
                        <button type="button" class="btn btn-info update_shipping_fee_btn">{{trans('file.submit')}}</button>
                    </div>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

<div id="return-ship" tabindex="-1" role="dialog" aria-labelledby="updateReturnShip" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="updateReturnShip" class="modal-title">{{trans('Update Status')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'sale.update-status-filters', 'method' => 'post', 'files' => true, 'class' => 'return_ship']) !!}
                <div class="row">
                    <div class="col-md-12"><p class="ajax-status">Return Shipping Cost</p></div>
                    <div class="col-md-8 form-group text-left">
                        <input type="hidden" name="reference_no">
                        <input type="hidden" name="sale_id">
                        <input type="hidden" name="order_type">
                        <select name="return_shipping_cost" class="form-control">
                            @foreach($lims_return_shipping_cost_list as $opt)
                            <option value="{{ $opt }}"> {{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group text-left">
                        <button type="button" class="btn btn-info return_ship_btn">{{trans('file.submit')}}</button>
                    </div>
                </div>
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
                        <label>{{trans('Reason for Cancellation')}} *</label>
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

<div id="reset-order" tabindex="-1" role="dialog" aria-labelledby="orderReset" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="orderReset" class="modal-title">{{trans('Update Status')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'sale.update-status-filters', 'method' => 'post', 'files' => true, 'class' => 'reset_order']) !!}
                <div class="row">
                    <div class="col-md-6 form-group text-left">
                        <input type="hidden" name="reference_no">
                        <input type="hidden" name="sale_id">
                        <input type="hidden" name="order_type">
                        <h5 id="orderResetLabel">Are you sure to reset this order ?</h5>
                    </div>
                    <div class="col-md-6 form-group text-left">
                        <button type="button" class="btn btn-info order_reset_btn">{{trans('file.submit')}}</button>
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

    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #sale-list-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        if(localStorage.getItem("message")) {
            alert(localStorage.getItem("message"));
            localStorage.removeItem("message");
        }

        numberOfInvoice = <?php echo json_encode($numberOfInvoice)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", $general_setting->package_id)}}',
            success: function(data) {
                if(data['number_of_invoice'] > 0 && data['number_of_product'] <= numberOfInvoice) {
                    $("a.add-sale-btn").addClass('d-none');
                }
            }
        });
    @endif

    var columns = [
        {"data": "key"}, 
        {"data": "reference_no"}, 
        {"data": "product_name"}, 
        {"data": "product_code"},
        {"data" : "supplier_name"}, 
        {"data": "date"}, 
        {"data": "sale_status"},
        {"data": "total_qty"}, 
        {"data": "total_price"},
        {"data": "delivery_fee"},
        {"data": "customer_info"},
        {"data": "customer_address"},
        {"data": "updated_date"}, 
        {"data" : "location"},
        @if($sale_status == 11)
        {"data" : "res_reason"},
        @endif
    ];
    
    var field_name = <?php echo json_encode($field_name) ?>;
    for(i = 0; i < field_name.length; i++) {
        columns.push({"data": field_name[i]});
    }
    columns.push({"data": "options"});

    @if($lims_pos_setting_data)
        var public_key = <?php echo json_encode($lims_pos_setting_data->stripe_public_key) ?>;
    @endif
    var all_permission = <?php echo json_encode($all_permission) ?>;
    var sale_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;
    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date = <?php echo json_encode($ending_date); ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;
    var sale_status = <?php echo json_encode($sale_status); ?>;
    var _location = <?php echo json_encode($location); ?>;
    var supplier_id = <?php echo json_encode($supplier_id); ?>;
    var product_code = <?php echo json_encode($product_code); ?>;
    var current_date = <?php echo json_encode(date("Y-m-d")) ?>;
    var payment_date = [];
    var payment_reference = [];
    var paid_amount = [];
    var paying_method = [];
    var payment_id = [];
    var payment_note = [];
    var account = [];
    var deposit;
    var without_stock = <?php echo json_encode($general_setting->without_stock) ?>;
    var can_scanner = @json($can_scanner);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#warehouse_id").val(warehouse_id);
    $("#sale-status").val(sale_status);
    $("#location").val(_location);
    $("#supplier-id").val(supplier_id);
    $("#product_code").val(product_code);

    $(".daterangepicker-field").daterangepicker({
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 1 Year': [moment().subtract(1, 'years'), moment()]
        },
        alwaysShowCalendars: true,
        callback: function(startDate, endDate, period) {
            var starting_date = startDate.format('YYYY-MM-DD');
            var ending_date = endDate.format('YYYY-MM-DD');
            var title = starting_date + ' ~ ' + ending_date;
            $(this).val(title);
            $('input[name="starting_date"]').val(starting_date);
            $('input[name="ending_date"]').val(ending_date);
        }
    });

    $('.selectpicker').selectpicker('refresh');

    function make_active(sale_status){
        $(".btn-sale").removeClass("btn-dark").addClass("btn-info");
        $(".btn-"+sale_status).addClass("btn-dark");
    }

    $(".btn-"+<?=$sale_status?>).addClass("btn-dark");

    function editx(id){
        //alert(id);
        $("#editSale").attr("src","/sales/"+id+"/edit");
        $('#edit-sale').modal('show');
    }

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

    $(".update_status_btn").on("click", function(){
        
        var loc = $('#update-status select[name=location]').val();
        
        var sale_id = $('input[name="sale_id"]').val();
        var reference_no = $('input[name="reference_no"]').val();
        
        
        var res_type = $('input[name="res_type"]:checked').val();
        check_validation("input", "res_type", res_type);
        
        if(res_type == "confirm"){
            if(loc == 0){ // unselected case
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
            var res_reason_2 = $('select[name="res_reason_2"]').val();
            check_validation("select", "res_reason_2", res_reason_2);
            var res_info = $('textarea[name="res_info"]').val();
            check_validation("textarea", "res_info", res_info);
        }

        $.ajax({
            url: '../../sales/updatestatus',
            type: "POST",
            data: $(".update_status").serializeArray(),
            success:function(data) {
                if(data['code'] == 200){
                    $('#update-status').modal('hide');
                    location.reload();
                }
                else {
                    alert(data['msg']);
                }
            }
        });
    });   

    function update_status_filters(id){
        $.get('delivery/create/'+id, function(data) {
            $('.dr_num').text(data[0]);
                
                $('input[name="reference_no"]').val(data[0]);
                $('input[name="sale_id"]').val(id);
                $('input[name="order_type"]').val("delivery");

                $("#updateStatusFilters").text("Delivery Status");
                $("#updateStatusFiltersLabel").text("Are you sure to start delivery ?");
            });
            $('#update-status-filters').modal('show');
    }

     function update_status_filters_shipped(id){
        $.get('delivery/create/'+id, function(data) {
            $('.dr_num').text(data[0]);
                
                $('input[name="reference_no"]').val(data[0]);
                $('input[name="sale_id"]').val(id);
                $('input[name="order_type"]').val("shipped");

                $("#updateStatusFilters").text("Delivery Status");
                $("#updateStatusFiltersLabel").text("Are you sure to finish delivery ?");
            });
            $('#update-status-filters').modal('show');
    }

    $(".update_status_filters_btn").on("click", function(){

        var sale_id = $('input[name="sale_id"]').val();
        var reference_no = $('input[name="reference_no"]').val();
        var reference_no = $('input[name="order_type"]').val();

        $.ajax({
            url: '../../sales/updatestatusfilters',
            type: "POST",
            data: $(".update_status_filters").serializeArray(),
            success:function(data) {
                //alert(data);
                $('#update-status-filters').modal('hide');
                location.reload();
            }
        });
    });

    $("#btn-delivery").on("click", function(){
        var sale_ids = $('input[name="sale_id[]"]').map(function() {
            return this.value;
        }).get();
        var order_type = $('input[name="order_type"]').val();

        $.ajax({
            url: '../../sales/updatestatusfilters',
            type: "POST",
            data: {
                "sale_id" : sale_ids,
                "order_type" : order_type,
                "_token" : $('meta[name="csrf-token"]').attr('content')
            },
            success:function(data) {
                $('#confirm-print').modal('hide');
                location.reload();
            }
        });
    });

    function update_shipping_fee(id, shipping_cost){
        // $.get('delivery/create/'+id, function(data) {
        //     // console.log(data);
        //     $('.dr_num').text(data[0]);
        //     $('.sr_num').text(data[1]);
        //     $('.customer_name').text(data[5]);
        //     $('.customer_address').text(data[6]);
            
        //     $('input[name="reference_no"]').val(data[0]);
        
        //     $("#updateShippingLabel").text("Please check shipping fee before return delivery ?");
        // });
        $('input[name="sale_id"]').val(id);
        $('input[name="order_type"]').val("shipping");
        $('#update-shipping-fee').modal('show');
    }

    $(".update_shipping_fee_btn").on("click", function(){
        $.ajax({
            url: '../../sales/updatestatusfilters',
            type: "POST",
            data: $(".update_shipping_fee").serializeArray(),
            success:function(data) {
                //alert(data);
                $('#update-shipping-fee').modal('hide');
                location.reload();
            }
        });
    });

    function return_ship(id){
        $.get('delivery/create/'+id, function(data) {           
            $('input[name="reference_no"]').val(data[0]);
            $('input[name="sale_id"]').val(id);
            $('input[name="order_type"]').val("return_ship");
            //$(".ajax-status").html(data);
            $("#updateReturnShip").text("Return Shipping");
            $('#return-ship').modal('show');
        });
    }

    function return_receiving_sign(sale_id){
        $('input[name="sale_id"]').val(sale_id);
        $('input[name="order_type"]').val("return_receiving");
        //$(".ajax-status").html(data);
        $("#updateReturnShip").text("Return Receving");
        $('#return-ship').modal('show');
    }

    $(".return_ship_btn").on("click", function(){
        var sale_id = $('input[name="sale_id"]').val();
        var reference_no = $('input[name="reference_no"]').val();
        var return_shipping_cost = $('input[name="return_shipping_cost"]').val();

        $.ajax({
            url: '../../sales/updatestatusfilters',
            type: "POST",
            data: $(".return_ship").serializeArray(),
            success:function(data) {
                // console.log(data);
                //alert(data);
                $('#return-ship').modal('hide');
                location.reload();
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

    function reset_order(id){
        $.get('delivery/create/'+id, function(data) {
                $('.dr_num').text(data[0]);
                $('.sr_num').text(data[1]);
                $('.customer_name').text(data[5]);
                $('.customer_address').text(data[6]);
                
                $('input[name="reference_no"]').val(data[0]);
                $('input[name="sale_id"]').val(id);
                $('input[name="order_type"]').val("reset_order");
                //$(".ajax-status").html(data);
                $("#orderReset").text("Reset Order");
                $("#orderCancelLabel").text("Are you sure to reset this order ?");
            });
            $('#reset-order').modal('show');
    }

    $(".order_reset_btn").on("click", function(){

        var sale_id = $('input[name="sale_id"]').val();
        var reference_no = $('input[name="reference_no"]').val();

        $.ajax({
            url: '../../sales/updatestatusfilters',
            type: "POST",
            data: $(".reset_order").serializeArray(),
            success:function(data) {
                //alert(data);
                $('#reset-order').modal('hide');
                location.reload();
            }
        });
    });

    $(document).on("click", "tr.sale-link td:not(:first-child, :last-child)", function() {
        var sale = $(this).parent().data('sale');
        saleDetails(sale);
    });

    $(document).on("click", ".view", function(){
        var sale = $(this).parent().parent().parent().parent().parent().data('sale');
        saleDetails(sale);
    });

    $(document).on('submit', '.packing-slip-form', function(e) {
        $(".packing-slip-submit-btn").prop("disabled", true);
    });

    function printDocument(selector) {
        var divContents = document.querySelector(selector).innerHTML;
        var a = window.open('');
        a.document.write('<html>');
        a.document.write('<body>');
        a.document.write(`
            <style>
            body {
                line-height: 1.15;
                -webkit-text-size-adjust: 100%;
                margin: 0;
                font-size: 16px;
            }
            .d-print-none { display: none !important; }
            .text-left { text-align: left !important; }
            .text-center { text-align: center !important; }
            .text-right { text-align: right !important; }
            .row { width: 100%; margin-right: -15px; margin-left: -15px; }
            .col-md-12 { width: 100%; display: block; padding: 5px 15px; }
            .col-md-6 { width: 50%; float: left; padding: 5px 15px; }
            table { width: 100%; margin-top: 30px; }
            th { text-align: left; }
            td { padding: 10px; }
            table, th, td { border: 1px solid black; border-collapse: collapse; }
            @media print {
                .modal-dialog { max-width: 1000px; }
                .modal-header, .modal-title, .modal-header .row {
                    display: flex !important;
                    flex-direction: row !important;
                    align-items: center !important;
                    justify-content: center !important;
                    text-align: center !important;
                    width: 100% !important;
                }
                .modal-header .col-md-4,
                .modal-header .col-md-6,
                .modal-header .col-md-12 {
                    float: none !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    text-align: center !important;
                    width: 100% !important;
                    padding: 0 !important;
                }
                .modal-title {
                    width: 100% !important;
                    text-align: center !important;
                    margin: 0 auto !important;
                    font-size: 25px !important;
                    font-weight: bold !important;
                }
            }
            </style>
        `);
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        a.print();
        setTimeout(function(){a.close();},200);
    }

    function createBillHtml(sale){
        return (`
            <div class="sale-details" style="line-height: 1.6; font-size: 20px; font-weight:500; margin: 20px auto; width: 80%">
                <p> <strong> Order Number : </strong> ${sale['reference_no']} </p>
                <p> <strong> Name: </strong>${sale['customer_name']} </p>
                <p> <strong> Number: </strong> ${sale['customer_phone_number']} </p> 
                <p class="m-b-2"> <strong> Address: </strong> ${sale['customer_address']} </p>
                <p> <strong> Date: </strong>${sale['date']} </p>
                <p> <strong> Product : </strong>${sale['product_name']} </p>
                <p> <strong> SKU: </strong> ${sale['product_code']}&nbsp;<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Qty : </strong> ${sale['total_qty']}</p>
                <p> <strong> Amount : </strong> ${sale['total_price']} </p>
                <p style="margin-bottom: 1rem"> <strong> Location : </strong> ${sale['location']} </p>
                <div class="barcode-wrapper">
                    <svg id="barcode-${sale['reference_no']}" class="barcode" style="margin:20px auto;display:block;width:220px;height:60px;"></svg>
                </div>
                <input type="hidden" name="sale_id[]" value="${sale['sale_id']}">
            </div>
        `);
    }

    function saleDetails(sale){
        console.log(sale);

        var htmltext = createBillHtml(sale);

        $('#sale-details .modal-body').html(htmltext);

        // After generating the barcode
        JsBarcode(`#barcode-${sale['reference_no']}`, sale['reference_no'], {
            height: 60,
            displayValue: true,
            class: "d-print-none"
        });

        $('.barcode-wrapper').css({
            'margin' : '20px auto',
            'text-align' : 'center',
            'padding' : '10px',
            'background' : '#f8f9fa',
            'border-radius': '8px',
            'box-shadow' : '0 2px 8px rgba(0,0,0,0.05)',
            'display' : 'inline-block'
        });

        $('#sale-details').modal('show');
    }

    function print_waybill(saleList){
    
        var htmltext = '';
        for(let i = 0; i < saleList.length; i ++){
            var sale = saleList[i];
            if(i > 0) {
                htmltext += '<hr>';
                htmltext += `
                <div class="row">
                    <div class="col-md-12 text-center mb-2">
                        <div class="d-print-none">
                            <img src="{{url('logo', $general_setting->site_logo)}}" width="90px;">
                        </div>
                        <h3 class="modal-title container-fluid">{{$general_setting->site_title}}</h3>
                    </div>
                </div>
                `;
            }
            htmltext += createBillHtml(sale);
        }

        $('#confirm-print .modal-body').html(htmltext);
        // salelist is a javascript array
        saleList.forEach(function(e){
            JsBarcode(`#barcode-${e['reference_no']}`, e['reference_no'], {
                format: "CODE128",
                lineColor: "#000",
                width: 2,
                height: 60,
                displayValue: true
            });
        });

        $('.barcode-wrapper').css({
                'margin' : '20px auto',
                'text-align' : 'center',
                'padding' : '10px',
                'background' : '#f8f9fa',
                'border-radius': '8px',
                'box-shadow' : '0 2px 8px rgba(0,0,0,0.05)',
                'display' : 'inline-block'
        });

        $('.barcode').css({
            'margin': '0 auto',
            'display': 'block',
            'width': '290px',
            'height': '100px'
        });
        $('#confirm-print input[name=order_type]').val('delivery');
        $('#confirm-print').modal('show');
    }

    $(document).on("click", ".btn-print", function() {
        var selector = $(this).data('print-target');
        printDocument(selector);
    });

    $(document).on("click", "table.sale-list tbody .add-delivery", function(event) {
        var id = $(this).data('id').toString();
        $.get('delivery/create/'+id, function(data) {
            $('#dr').text(data[0]);
            $('#sr').text(data[1]);

            $('select[name="status"]').val(data[2]);
            $('.selectpicker').selectpicker('refresh');
            $('input[name="delivered_by"]').val(data[3]);
            $('input[name="recieved_by"]').val(data[4]);
            $('#customer').text(data[5]);
            $('textarea[name="address"]').val(data[6]);
            $('textarea[name="note"]').val(data[7]);
            $('select[name="courier_id"]').val(data[8]);
            $('.selectpicker').selectpicker('refresh');
            $('input[name="reference_no"]').val(data[0]);
            $('input[name="sale_id"]').val(id);
            $('#add-delivery').modal('show');
        });
    });
   
    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 7 ).footer() ).html(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
            $( dt_selector.column( 8 ).footer() ).html(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
            $( dt_selector.column( 9 ).footer() ).html(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum().toFixed());
        }
        else {
            $( dt_selector.column( 7 ).footer() ).html(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
            $( dt_selector.column( 8 ).footer() ).html(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
            $( dt_selector.column( 9 ).footer() ).html(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum().toFixed({{$general_setting->decimal}}));
        }
    }

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    $(document).ready(function() {
        let data_table = $('#sale-table').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":{
                url:"sales/sale-data",
                data:{
                    all_permission: all_permission,
                    starting_date: starting_date,
                    ending_date: ending_date,
                    warehouse_id: warehouse_id,
                    sale_status: sale_status,
                    location : _location,
                    supplier_id: supplier_id,
                    product_code: product_code
                },
                dataType: "json",
                type:"post"
            },
            "createdRow": function( row, data, dataIndex ) {
                $(row).addClass('sale-link');
                $(row).attr('data-sale', data['sale']);
            },
            "columns": columns,
            "language": {
                'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
                "info": '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
                }
            },
            "dom": '<"datatable-controls-wrapper"<"left-section"l><"middle-section"><"right-section"B>>rtip',
            @if($sale_status == 4 || $sale_status == 9)
                "order":[['12', 'desc']],
            @else
                "order":[['5', 'desc']],
            @endif
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, 4, 6, 9, 10, 11, 13, 14]
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
            'lengthMenu': [[10, 25, 50, 100], [10, 25, 50, 100]],
            "rowId": 'ObjectID',
            "buttons": [
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
                    text: '<i title="delete" class="dripicons-cross"></i>',
                    className: 'buttons-delete',
                    action: function ( e, dt, node, config ) {
                        if(user_verified == '1') {
                            sale_id.length = 0;
                            $(':checkbox:checked').each(function(i){
                                if(i){
                                    var sale = $(this).closest('tr').data('sale');
                                    if(sale)
                                        sale_id[i-1] = sale['sale_id'];
                                }
                            });
                            if(sale_id.length && confirm("Are you sure want to delete?")) {
                                $.ajax({
                                    type:'POST',
                                    url:'sales/deletebyselection',
                                    data:{
                                        saleIdArray: sale_id
                                    },
                                    success:function(data){
                                        alert(data);
                                        //dt.rows({ page: 'current', selected: true }).deselect();
                                        dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                    }
                                });
                            }
                            else if(!sale_id.length)
                                alert('Nothing is selected!');
                        }
                        else
                            alert('This feature is disable for demo!');
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="fa fa-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
            "drawCallback": function () {
                var api = this.api();
                datatable_sum(api, false);
                if(all_permission.indexOf("sales-delete") == -1){
                    $('.buttons-delete').addClass('d-none');
                }
            }
        });

        // Inject custom search into the middle section
        @if($can_scanner)
            $('.middle-section').html(`
                <input type="text" id="regular-search" class="form-control form-control-sm"
                    placeholder="Search..." style="max-width: 200px; margin-right: 10px;">
                <input type="text" id="scanner-search" class="form-control form-control-sm"
                    placeholder="Scan..." style="max-width: 200px;">
            `);
            // Always focus #scanner-search after it is created
            function focusScannerInput(delay = 3000) {
                setTimeout(function(){
                    $('#scanner-search').focus();
                }, delay);
            }

            focusScannerInput();

            // Also focus scanner input when user clicks anywhere except an input/textarea/select
            $(document).on('keydown', function(e) {
                if (!$(e.target).is('input, textarea, select')) {
                    focusScannerInput();
                }
            });

            $(document).on('click', function(e) {
                if (!$(e.target).is('input, textarea, select')) {
                    focusScannerInput(10000);
                }
            });

            // Scanner input (search on Enter, auto-clear input)
            $(document).on('keydown', '#scanner-search' , function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const searchValue = this.value;

                    $.ajax({
                        url: '/sales/sale-scan', // Your backend search endpoint
                        method: 'POST',
                        data: {
                            search: searchValue,
                            sale_status : sale_status,
                        },
                        success: function(response) {
                            // Handle the response from the backend
                            if(response.code == 200){
                                // ✅ Play audio on success
                                const audio = new Audio('/sounds/success-scan.mp3'); // Adjust path as needed
                                audio.play().catch(err => {
                                    console.error('Audio play failed:', err);
                                });
                                data_table.search('').draw();
                            }
                            else {
                                const errorAudio = new Audio('/sounds/fail-scan.mp3');
                                errorAudio.play().catch(err => {
                                    console.error('Error audio failed:', err);
                                });
                            }
                        },
                        error: function(error) {
                            console.error('Scan Search Error : ', error);
                        }
                    });

                    this.value = '';
                    setTimeout(focusScannerInput, 100); // Refocus after processing
                }
            });
        @else
            $('.middle-section').html(`
                <input type="text" id="regular-search" class="form-control form-control-sm"
                    placeholder="Search..." style="max-width: 200px; margin-right: 10px;">
            `);
        @endif

        // Manual (live) search input
        $(document).on('input', '#regular-search' , function () {
            const value = this.value;
            data_table.search(value).draw();
            setTimeout(function() {
                $('#regular-search').blur();
            }, 3000);
        });

        // Hide button initially
        $('#print-waybill-btn').hide();

        $('#print-waybill-btn').click(function(){
            var selectedCheckboxes = $('#sale-table input[type="checkbox"]:checked');
            if (selectedCheckboxes.length > 0) {
                var saleList = [];
                selectedCheckboxes.each(function() {
                    var sale = $(this).closest('tr').data('sale');
                    if (sale) {
                        saleList.push(sale); // Assuming the sale ID is at index 13
                    }
                });
                print_waybill(saleList);
            } else {
                alert('Please select at least one sale to print the waybill.');
            }
        });

        setTimeout(function(){
            $('.update-status.print-waybill').click(function(){
                var sale = $(this).closest('tr').data('sale');
                print_waybill([sale]);
            }),
            // Listen for checkbox changes in the DataTable
            $('#sale-table').on('change', 'input[type="checkbox"]', function() {
                // Check if any checkbox is checked
                if(sale_status != 7) return 0;
                if ($('#sale-table input[type="checkbox"]:checked').length > 0) {
                    $('#print-waybill-btn').show();
                } else {
                    $('#print-waybill-btn').hide();
                }
            });
        }, 2000);
    });
</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
