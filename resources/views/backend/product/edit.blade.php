@extends('backend.layout.main')

@if(in_array('ecommerce',explode(',',$general_setting->modules)))
@push('css')
<style>
.search_result {border:1px solid #e4e6fc;border-radius:5px;overflow-y: scroll;}
.search_result > div, .selected_items > div {border-top:1px solid #e4e6fc;cursor:pointer;display:flex;align-items:center;padding: 10px;position: relative;}
.search_result > div > img, .selected_items > div > img {margin-right: 10px;max-width: 40px;}
.search_result > div h4, .selected_items > div h4 {font-size: 0.9rem;}
.search_result > div i {color:#54b948;position:absolute;right:5px;top:30%}
.search_result div:first-child {border-top:none}
.selected_items .remove_item {position: absolute;right: 20px;top:20px};
.delVarOption{display: flex;flex-direction: column;align-items: center;}
</style>
@endpush
@endif

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.Update Product')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        <form id="product-form">
                            <input type="hidden" name="id" value="{{$lims_product_data->id}}" />
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Type')}} * </label>
                                        <div class="input-group">
                                            <select name="type" required class="form-control selectpicker" id="type">
                                                <option value="standard">Standard</option>
                                                <option value="combo">Combo</option>
                                                <option value="digital">Digital</option>
                                                <option value="service">Service</option>
                                            </select>
                                            <input type="hidden" name="type_hidden" value="{{$lims_product_data->type}}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Name')}} * </label>
                                        <input type="text" name="name" value="{{$lims_product_data->name}}" required class="form-control">
                                        <span class="validation-msg" id="name-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Supplier')}}</label>
                                            <select id="supplier_id" name="supplier_id" class="selectpicker form-control" data-live-search="true" title="This is field is required">
                                                @foreach($lims_supplier_list as $supplier)
                                                <option value="{{$supplier->id}}" data-supname="{{$supplier->name}}" <?php if($lims_product_data->supplier_id == $supplier->id){ ?>selected<?php } ?>>{{$supplier->name .' ('. $supplier->company_name .')'}}</option>
                                                <?php if($lims_product_data->supplier_id == $supplier->id){ $supplier_name = $supplier->name; } ?>
                                                @endforeach
                                            </select>                                            
                                            <input type="hidden" name="supplier_name" value="{{$supplier_name}}">
                                            <span class="validation-msg"></span>
                                        </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Code')}} * </label>
                                        <div class="input-group">
                                            <input type="text" name="code" id="code" value="{{$lims_product_data->code}}" class="form-control" required>
                                            <div class="input-group-append">
                                                <button id="genbutton" type="button" class="btn btn-sm btn-default" title="{{trans('file.Generate')}}"><i class="fa fa-refresh"></i></button>
                                            </div>
                                        </div>
                                        <span class="validation-msg" id="code-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{trans('file.Barcode Symbology')}} * </label>
                                        <div class="input-group">
                                            <input type="hidden" name="barcode_symbology_hidden" value="{{$lims_product_data->barcode_symbology}}">
                                            <select name="barcode_symbology" required class="form-control selectpicker">
                                                <option value="UPCE">UPC-E</option>
                                                <option value="C128">Code 128</option>
                                                <option value="C39">Code 39</option>
                                                <option value="UPCA">UPC-A</option>
                                                <option value="EAN8">EAN-8</option>
                                                <option value="EAN13">EAN-13</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="digital" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Attach File')}} </label>
                                        <div class="input-group">
                                            <input id="file" type="file" name="file" class="form-control">
                                        </div>
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div id="combo" class="col-md-9 mb-1">
                                    <label>{{trans('file.add_product')}}</label>
                                    <div class="search-box input-group mb-3">
                                        <button class="btn btn-secondary"><i class="fa fa-barcode"></i></button>
                                        <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="Please type product code and select..." class="form-control" />
                                    </div>
                                    <label>{{trans('file.Combo Products')}}</label>
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-hover order-list">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.product')}}</th>
                                                    <th>{{trans('file.Quantity')}}</th>
                                                    <th>{{trans('file.Unit Cost')}}</th>
                                                    <th>{{trans('file.Unit Price')}}</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if($lims_product_data->type == 'combo')
                                                <?php
                                                    $product_list = explode(",", $lims_product_data->product_list);
                                                    $qty_list = explode(",", $lims_product_data->qty_list);
                                                    $variant_list = explode(",", $lims_product_data->variant_list);
                                                    $price_list = explode(",", $lims_product_data->price_list);
                                                ?>
                                                @foreach($product_list as $key=>$id)
                                                <tr>
                                                    <?php
                                                        $product = App\Models\Product::find($id);
                                                        if($lims_product_data->variant_list && $variant_list[$key]) {
                                                            $product_variant_data = App\Models\ProductVariant::select('item_code')->FindExactProduct($id, $variant_list[$key])->first();
                                                            $product->code = $product_variant_data->item_code;
                                                        }
                                                        else
                                                            $variant_list[$key] = "";
                                                    ?>
                                                    <td>{{$product->name}} [{{$product->code}}]</td>
                                                    <td><input type="number" class="form-control qty" name="product_qty[]" value="{{$qty_list[$key]}}" step="any"></td>
                                                    <td><input type="number" class="form-control unit_cost" name="" value="{{$product->cost}}" step="any"/></td>
                                                    <td><input type="number" class="form-control unit_price" name="unit_price[]" value="{{$price_list[$key]}}" step="any"/></td>
                                                    <td><button type="button" class="ibtnDel btn btn-danger btn-sm">X</button></td>
                                                    <input type="hidden" class="product-id" name="product_id[]" value="{{$id}}"/>
                                                    <input type="hidden" class="variant-id" name="variant_id[]" value="{{$variant_list[$key]}}"/>
                                                </tr>
                                                @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6 hidden">
                                    <div class="form-group">
                                        <label>{{trans('file.Brand')}} </label>
                                        <div class="input-group">
                                            <input type="hidden" name="brand" value="{{ $lims_product_data->brand_id}}">
                                          <select name="brand_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Brand...">
                                            @foreach($lims_brand_list as $brand)
                                                <option value="{{$brand->id}}" <?php if($brand->id == 1){ ?>selected<?php } ?>>{{$brand->title}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                    </div>
                                </div>
                                <div class="col-md-6 hidden">
                                    <div class="form-group">
                                        <input type="hidden" name="category" value="{{$lims_product_data->category_id}}">
                                        <label>{{trans('file.category')}} * </label>
                                        <div class="input-group">
                                          <select name="category_id" required class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Category...">
                                            @foreach($lims_category_list as $category)
                                                <option value="{{$category->id}}" <?php if($category->id == 1){ ?>selected<?php } ?>>{{$category->name}}</option>
                                            @endforeach
                                          </select>
                                      </div>
                                    </div>
                                </div>
                                <div id="unit" class="col-md-12 hidden">
                                    <div class="row ">
                                        <div class="col-md-4">
                                                <label>{{trans('file.Product Unit')}} * </label>
                                                <div class="input-group">
                                                  <select required class="form-control selectpicker" data-live-search="true" data-live-search-style="begins" title="Select unit..." name="unit_id">
                                                    @foreach($lims_unit_list as $unit)
                                                        @if($unit->base_unit==null)
                                                            <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                                                        @endif
                                                    @endforeach
                                                  </select>
                                                  <input type="hidden" name="unit" value="{{ $lims_product_data->unit_id}}">
                                              </div>
                                        </div>
                                        <div class="col-md-4">
                                                <label>{{trans('file.Sale Unit')}} </label>
                                                <div class="input-group">
                                                  <select class="form-control selectpicker" name="sale_unit_id" id="sale-unit">
                                                  </select>
                                                  <input type="hidden" name="sale_unit" value="{{ $lims_product_data->sale_unit_id}}">
                                              </div>
                                        </div>
                                        <div class="col-md-4 mt-2">
                                                <div class="form-group">
                                                    <label>{{trans('file.Purchase Unit')}} </label>
                                                    <div class="input-group">
                                                      <select class="form-control selectpicker" name="purchase_unit_id">
                                                      </select>
                                                      <input type="hidden" name="purchase_unit" value="{{ $lims_product_data->purchase_unit_id}}">
                                                  </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('Width')}} * (cm)</strong> </label>
                                        <input type="number" required name="width" value="{{$lims_product_data->width}}" class="form-control" step="any">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('Length')}} * (cm)</strong> </label>
                                        <input type="number" required name="length" value="{{$lims_product_data->length}}" class="form-control" step="any">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('Height')}} * (cm)</strong> </label>
                                        <input type="number" required name="height" value="{{$lims_product_data->height}}" class="form-control" step="any">
                                    </div>
                                </div>
                                <div id="cost" class="col-md-4 hidden">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Cost')}} * </label>
                                        <input type="number" name="cost" value="{{$lims_product_data->cost}}" required class="form-control" step="any">
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Price')}} * </label>
                                        <input type="number" name="price" value="{{$lims_product_data->price}}" required class="form-control" step="any">
                                        <span class="validation-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="qty" value="{{ $lims_product_data->qty }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4 hidden">
                                    <div class="form-group">
                                        <label>{{trans('file.Wholesale Price')}} </label>
                                        <input type="number" name="wholesale_price" class="form-control" value="{{$lims_product_data->wholesale_price}}" step="any">
                                    </div>
                                </div>
                                <div class="col-md-4 hidden">
                                    <div class="form-group">
                                        <label>{{trans('file.Daily Sale Objective')}} </label>
                                        <input type="number" name="daily_sale_objective" class="form-control" step="any" value="{{$lims_product_data->daily_sale_objective}}">
                                    </div>
                                </div>
                                <div id="alert-qty" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{trans('file.Alert Quantity')}} </label>
                                        <input type="number" name="alert_quantity" value="{{$lims_product_data->alert_quantity}}" class="form-control" step="any">
                                    </div>
                                </div>
                                <div class="col-md-4 hidden">
                                    <div class="form-group">
                                        <input type="hidden" name="tax" value="{{$lims_product_data->tax_id}}">
                                        <label>{{trans('file.product')}} {{trans('file.Tax')}} </label>
                                        <select name="tax_id" class="form-control selectpicker">
                                            <option value="">No Tax</option>
                                            @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->id}}">{{$tax->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="hidden" name="tax_method_id" value="{{$lims_product_data->tax_method}}">
                                        <label>{{trans('file.Tax Method')}} </label>
                                        <select name="tax_method" class="form-control selectpicker">
                                            <option value="1">{{trans('file.Exclusive')}}</option>
                                            <option value="2">{{trans('file.Inclusive')}}</option>
                                        </select>
                                    </div>
                                </div>
                                @foreach($custom_fields as $field)
                                    <?php $field_name = str_replace(' ', '_', strtolower($field->name)); ?>
                                    @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                        <div class="{{'col-md-'.$field->grid_value}}">
                                            <div class="form-group">
                                                <label>{{$field->name}}</label>
                                                @if($field->type == 'text')
                                                    <input type="text" name="{{$field_name}}" value="{{$lims_product_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                                @elseif($field->type == 'number')
                                                    <input type="number" name="{{$field_name}}" value="{{$lims_product_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                                @elseif($field->type == 'textarea')
                                                    <textarea rows="5" name="{{$field_name}}" value="{{$lims_product_data->$field_name}}" class="form-control" @if($field->is_required){{'required'}}@endif></textarea>
                                                @elseif($field->type == 'checkbox')
                                                    <br>
                                                    <?php
                                                    $option_values = explode(",", $field->option_value);
                                                    $field_values =  explode(",", $lims_product_data->$field_name);
                                                    ?>
                                                    @foreach($option_values as $value)
                                                        <label>
                                                            <input type="checkbox" name="{{$field_name}}[]" value="{{$value}}" @if(in_array($value, $field_values)) checked @endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                        </label>
                                                        &nbsp;
                                                    @endforeach
                                                @elseif($field->type == 'radio_button')
                                                    <br>
                                                    <?php
                                                    $option_values = explode(",", $field->option_value);
                                                    ?>
                                                    @foreach($option_values as $value)
                                                        <label class="radio-inline">
                                                            <input type="radio" name="{{$field_name}}" value="{{$value}}" @if($value == $lims_product_data->$field_name){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                        </label>
                                                        &nbsp;
                                                    @endforeach
                                                @elseif($field->type == 'select')
                                                    <?php $option_values = explode(",", $field->option_value); ?>
                                                    <select class="form-control" name="{{$field_name}}" @if($field->is_required){{'required'}}@endif>
                                                        @foreach($option_values as $value)
                                                            <option value="{{$value}}" @if($value == $lims_product_data->$field_name){{'selected'}}@endif>{{$value}}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($field->type == 'multi_select')
                                                    <?php
                                                    $option_values = explode(",", $field->option_value);
                                                    $field_values =  explode(",", $lims_product_data->$field_name);
                                                    ?>
                                                    <select class="form-control" name="{{$field_name}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                        @foreach($option_values as $value)
                                                            <option value="{{$value}}" @if(in_array($value, $field_values)) selected @endif>{{$value}}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($field->type == 'date_picker')
                                                    <input type="text" name="{{$field_name}}" value="{{$lims_product_data->$field_name}}" class="form-control date" @if($field->is_required){{'required'}}@endif>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                                <div class="col-md-4">
                                    <div class="form-group mt-3">
                                        <input type="checkbox" checked name="is_initial_stock" value="1">&nbsp;
                                        <label>{{trans('file.Initial Stock')}}</label>
                                        <p class="italic">{{trans('file.This feature will not work for product with variants and batches')}}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mt-3">
                                        @if($lims_product_data->featured)
                                            <input type="checkbox" name="featured" value="1" checked>
                                        @else
                                            <input type="checkbox" name="featured" value="1">
                                        @endif
                                        <label>{{trans('file.Featured')}}</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mt-3">
                                        @if($lims_product_data->is_embeded)
                                            <input type="checkbox" name="is_embeded" value="1" checked>
                                        @else
                                            <input type="checkbox" name="is_embeded" value="1">
                                        @endif
                                        <label>{{trans('file.Embedded Barcode')}} <i class="dripicons-question" data-toggle="tooltip" title="{{trans('file.Check this if this product will be used in weight scale machine.')}}"></i></label>
                                    </div>
                                </div>
                                <div class="col-md-12" id="initial-stock-section">
                                    <div class="table-responsive ml-2">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.Warehouse')}}</th>
                                                    <th>{{trans('file.qty')}}</th>
                                                </tr>
                                                @foreach($lims_warehouse_list as $warehouse)
                                                    @if (sizeof($lims_product_warehouse_list) > 0)
                                                        <?php  foreach($lims_product_warehouse_list as $wp){
                                                            if(($warehouse->id === $wp->warehouse_id)){
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <input type="hidden" name="stock_warehouse_id[]" value="{{$warehouse->id}}">
                                                                {{$warehouse->name}}
                                                            </td>
                                                            <td><input type="number" name="stock[]" min="0" value="{{$wp->qty}}" class="form-control"></td>
                                                        </tr>
                                                        <?php }
                                                        } ?>
                                                    @else
                                                        <tr>
                                                            <td>
                                                                <input type="hidden" name="stock_warehouse_id[]" value="{{$warehouse->id}}">
                                                                {{$warehouse->name}}
                                                            </td>
                                                            <td><input type="number" name="stock[]" min="0" value="0" class="form-control"></td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Image')}} </label> <i class="dripicons-question" data-toggle="tooltip" title="{{trans('file.You can upload multiple image. Only .jpeg, .jpg, .png, .gif file can be uploaded. First image will be base image.')}}"></i>
                                        <div id="imageUpload" class="dropzone"></div>
                                        <span class="validation-msg" id="image-error"></span>
                                    </div>
                                </div>
                                @if($lims_product_data->image)
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th><button type="button" class="btn btn-sm"><i class="fa fa-list"></i></button></th>
                                                    <th>Image</th>
                                                    <th>Remove</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $images = explode(",", $lims_product_data->image)?>
                                                @foreach($images as $key => $image)
                                                <tr>
                                                    <td><button type="button" class="btn btn-sm"><i class="fa fa-list"></i></button></td>
                                                    <td>
                                                        <img src="{{url('images/product', $image)}}" height="60" width="60">
                                                        <input type="hidden" name="prev_img[]" value="{{$image}}">
                                                    </td>
                                                    <td><button type="button" class="btn btn-sm btn-danger remove-img">X</button></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Details')}}</label>
                                        <textarea name="product_details" class="form-control" rows="5">{{str_replace('@', '"', $lims_product_data->product_details)}}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-2" id="diffPrice-option">
                                    @if($lims_product_data->is_diffPrice)
                                        <h5><input name="is_diffPrice" type="checkbox" id="is-diffPrice" value="1" checked>&nbsp; {{trans('file.This product has different price for different warehouse')}}</h5>
                                    @else
                                        <h5><input name="is_diffPrice" type="checkbox" id="is-diffPrice" value="1">&nbsp; {{trans('file.This product has different price for different warehouse')}}</h5>
                                    @endif
                                </div>
                                <div class="col-md-6" id="diffPrice-section">
                                    <div class="table-responsive ml-2">
                                        <table id="diffPrice-table" class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.Warehouse')}}</th>
                                                    <th>{{trans('file.Price')}}</th>
                                                </tr>
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="warehouse_id[]" value="{{$warehouse->id}}">
                                                        {{$warehouse->name}}
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $product_warehouse = \App\Models\Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $warehouse->id)->first();
                                                        ?>
                                                        @if($product_warehouse)
                                                            <input type="number" name="diff_price[]" class="form-control" value="{{$product_warehouse->price}}">
                                                        @else
                                                            <input type="number" name="diff_price[]" class="form-control">
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3" id="batch-option">
                                    @if($lims_product_data->is_batch)
                                    <h5><input name="is_batch" type="checkbox" id="is-batch" value="1" checked>&nbsp; {{trans('file.This product has batch and expired date')}}</h5>
                                    @else
                                    <h5><input name="is_batch" type="checkbox" id="is-batch" value="1">&nbsp; {{trans('file.This product has batch and expired date')}}</h5>
                                    @endif
                                </div>
                                <div class="col-md-12 mt-3" id="imei-option">
                                    @if($lims_product_data->is_imei)
                                    <h5><input name="is_imei" type="checkbox" id="is-imei" value="1" checked>&nbsp; {{trans('file.This product has IMEI or Serial numbers')}}</h5>
                                    @else
                                    <h5><input name="is_imei" type="checkbox" id="is-imei" value="1">&nbsp; {{trans('file.This product has IMEI or Serial numbers')}}</h5>
                                    @endif
                                </div>
                                @if($lims_product_data->is_variant)
                                <div class="col-md-12 mt-3" id="variant-option">
                                    <h5 class="d-none"><input name="is_variant" type="checkbox" id="is-variant" value="1" checked>&nbsp; {{trans('file.This product has variant')}}</h5>
                                </div>
                                @endif
                                <div class="col-md-12" id="variant-section">
                                    @if($lims_product_data->variant_option)
                                    <div id="variant-input-section">
                                        @foreach($lims_product_data->variant_option as $key => $variant_option)
                                        <?php
                                            $noOfVariantValue += count(explode(",", $lims_product_data->variant_value[$key]));
                                        ?>
                                        <div class="row">
                                            <div class="col-sm-4 form-group mt-2">
                                                <label>{{trans('file.Option')}} *</label>
                                                <input type="text" name="variant_option[]" class="form-control variant-field" value="{{$lims_product_data->variant_option[$key]}}">
                                            </div>
                                            <div class="col-sm-7 form-group mt-2">
                                                <label>{{trans('file.Value')}} *</label>
                                                <input type="text" name="variant_value[]" class="type-variant form-control variant-field" value="{{$lims_product_data->variant_value[$key]}}">
                                            </div>
                                            <div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;">
                                                <button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="dripicons-cross"></i></button>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <div id="variant-input-section">
                                        <div class="row">
                                            <div class="col-md-4 form-group mt-2">
                                                <label>{{trans('file.Option')}} *</label>
                                                <input type="text" name="variant_option[]" class="form-control variant-field" placeholder="Size, Color etc...">
                                            </div>
                                            <div class="col-md-7 form-group mt-2">
                                                <label>{{trans('file.Value')}} *</label>
                                                <input type="text" name="variant_value[]" class="type-variant form-control variant-field">
                                            </div>
                                            <div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;">
                                                <button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="dripicons-cross"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-md-12 form-group">
                                        <button type="button" class="btn btn-info add-more-variant"><i class="dripicons-plus"></i> {{trans('file.Add More Variant')}}</button>
                                    </div>
                                    <div class="table-responsive ml-2">
                                        <table id="variant-table" class="table table-hover variant-list">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.name')}}</th>
                                                    <th>{{trans('file.Item Code')}}</th>
                                                    <th>{{trans('file.Additional Cost')}}</th>
                                                    <th>{{trans('file.Additional Price')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($lims_product_variant_data as $key=> $variant)
                                                <tr>
                                                    <td>{{$variant->name}}
                                                        <input type="hidden" class="form-control variant-name" name="variant_name[]" value="{{$variant->name}}" />
                                                    </td>
                                                    <td><input type="text" class="form-control" name="item_code[]" value="{{$variant->pivot['item_code']}}" /></td>
                                                    <td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="{{$variant->pivot['additional_cost']}}" step="any" /></td>
                                                    <td><input type="number" class="form-control additional-price" name="additional_price[]" value="{{$variant->pivot['additional_price']}}" step="any" /></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <input type="hidden" name="promotion_hidden" value="{{$lims_product_data->promotion}}">
                                    <h5><input name="promotion" type="checkbox" id="promotion" value="1">&nbsp; {{trans('file.Add Promotional Price')}}</h5>
                                </div>

                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-4" id="promotion_price">   <label>{{trans('file.Promotional Price')}}</label>
                                            <input type="number" name="promotion_price" value="{{$lims_product_data->promotion_price}}" class="form-control" step="any" />
                                        </div>
                                        <div id="start_date" class="col-md-4">
                                            <div class="form-group">
                                                <label>{{trans('file.Promotion Starts')}}</label>
                                                <input type="text" name="starting_date" value="{{$lims_product_data->starting_date}}" id="starting_date" class="form-control" />
                                            </div>
                                        </div>
                                        <div id="last_date" class="col-md-4">
                                            <div class="form-group">
                                                <label>{{trans('file.Promotion Ends')}}</label>
                                                <input type="text" name="last_date" value="{{$lims_product_data->last_date}}" id="ending_date" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (\Schema::hasColumn('products', 'woocommerce_product_id'))
                                <div class="col-md-12 mt-3">
                                    <h5><input name="is_sync_disable" {{$lims_product_data->is_sync_disable==1 ? 'checked':''}} type="checkbox" id="is_sync_disable" value="1">&nbsp; {{trans('file.Disable Woocommerce Sync')}}</h5>
                                </div>
                                @endif
                                @if(in_array('ecommerce',explode(',',$general_setting->modules)))
                                <div class="col-md-12 mt-3">
                                    <h5><input name="is_online" type="checkbox" id="is_online" value="1" {{$lims_product_data->is_online==1 ? 'checked':''}}>&nbsp; {{trans('file.Sell Online')}}</h5>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <h5><input name="in_stock" type="checkbox" id="in_stock" value="1" {{$lims_product_data->in_stock==1 ? 'checked':''}}>&nbsp; {{trans('file.In Stock')}}</h5>
                                </div>
                                <div class="col-md-12 mt-3 track_inventory">
                                    <h5><input name="track_inventory" type="checkbox" id="track_inventory" value="1" {{$lims_product_data->track_inventory==1 ? 'checked':''}}>&nbsp; {{trans('file.Track Inventory')}}</h5>
                                </div>
                                @endif
                                @if(in_array('ecommerce',explode(',',$general_setting->modules)))
                                <div class="col-12 mt-3">
                                    <div class="form-group">
                                        <label>{{trans('file.Product Tags')}} </label>
                                        <input type="text" name="tags" class="form-control" value="{{$lims_product_data->tags}}">
                                        <span class="validation-msg" id="tags-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <br/>
                                    <h6>For SEO</h6>
                                    <br>
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>{{ __('Meta Title') }}</label>
                                    <input type="text" name="meta_title" class="form-control" value="{{$lims_product_data->meta_title}}">
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>{{ __('Meta Description') }}</label>
                                    <input type="text" name="meta_description" class="form-control" value="{{$lims_product_data->meta_description}}">
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>{{trans('file.Related Products')}}</label>
                                    <input type="text" id="search_products" class="form-control">
                                    <div class="search_result"></div>
                                    <h4 class="mt-5 mb-3">{{trans('file.Selected Items')}}</h4>
                                    @if(isset($related_products))
                                        <div class="selected_items">
                                            @foreach($related_products as $product)
                                            @php
                                                $image = explode(',', $product->image);
                                            @endphp
                                            <div data-id="{{$product->id}}"><img src="{{asset('images/product/small/')}}/{{$image[0]}}"><h4>{{$product->name}}</h4><span class="remove_item"><i class="dripicons-cross"></i></span></div>
                                            @endforeach
                                        </div>
                                        <textarea class="selected_ids hidden no-tiny" name="products">{{$related_products}},</textarea>
                                    @endif
                                </div>
                                @endif
                                <div class="col-md-12 mt-3">
                                    <div class="form-group">
                                        <button class="btn btn-primary" type="submit" id="submit-btn">{{trans('file.submit')}}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <h3>
        {{ json_encode($lims_product_warehouse_list) }}
    </h3>
</section>

@endsection

@push('scripts')
<script type="text/javascript">

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    if($("#in_stock").prop('checked') == false){
        $('.track_inventory').css('display','block');
    }else{
        $('.track_inventory').css('display','none');
        $("#track_inventory").prop('checked') == false
    }

    $("#in_stock").on('click', function(){
        if($("#in_stock").prop('checked') == false){
            $('.track_inventory').css('display','block');
        }else{
            $('.track_inventory').css('display','none');
            $("#track_inventory").prop('checked') == false
        }
    });

    @if(in_array('ecommerce',explode(',',$general_setting->modules)))
    $('#search_products').on('input', function() {
        var item = $(this).val();
        $('.search_result').html('<div class="d-block text-center"><div class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>');

        if(item.length >= 3){
            $.ajax({
                type: "get",
                url: "{{url('search')}}/" + item,
                success: function(data) {
                    $('.search_result').html('').css('height','200px');
                    $.each(data,function(key, value){
                        var image = value.image.split(',');
                        $('.search_result').append('<div data-id="'+value.id+'"><img src="{{asset("images/product/small/")}}/'+image[0]+'"><h4>'+value.name+'</h4><i class="dripicons-checkmark d-none"></i></div>')
                    })
                }
            })
        } else if (item.length < 3) {
            $('.search_result').html('');
        }
    });

    $(document).on('click','.search_result div',function(){
        $(this).find('i').removeClass('d-none');
        var selected_item = '<div data-id="'+$(this).data('id')+'">'+$(this).html()+'<span class="remove_item"><i class="dripicons-cross"></i></span></div>';
        if ($('.selected_ids').html().indexOf($(this).data('id')) === -1){
            $('.selected_items').prepend(selected_item);
            $('.selected_ids').append($(this).data('id')+',');
            $('.selected_items .dripicons-checkmark').addClass('d-none');
        }
    });

    $(document).on('click','.remove_item',function(){
        var item = $(this).parent().remove();
        var remove_id = $(this).parent().data('id');
        var selected_ids = $('.selected_ids').html().replace(remove_id+',','');
        $('.selected_ids').html(selected_ids);

    });
    @endif

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    var product_id = <?php echo json_encode($lims_product_data->id) ?>;
    var is_batch = <?php echo json_encode($lims_product_data->is_batch) ?>;
    var is_variant = <?php echo json_encode($lims_product_data->is_variant) ?>;
    var redirectUrl = <?php echo json_encode(url('products')); ?>;
    var variantPlaceholder = <?php echo json_encode(trans('file.Enter variant value seperated by comma')); ?>;
    var variantIds = [];
    var combinations = [];
    var oldCombinations = [];
    var step;
    var count = 1;
    var customizedVariantCode = 1;
    var noOfVariantValue = <?php echo json_encode($noOfVariantValue); ?>;

    $('[data-toggle="tooltip"]').tooltip();

    $(".remove-img").on("click", function () {
        $(this).closest("tr").remove();
    });

    $("#digital").hide();
    $("#combo").hide();
    $("select[name='type']").val($("input[name='type_hidden']").val());
    variantShowHide();
    diffPriceShowHide();
    if(is_batch)
        $("#variant-option").hide();
    if(is_variant) {
        var customizedVariantCode = 0;
        $("#batch-option").hide();
    }

    if($("input[name='type_hidden']").val() == "digital"){
        $("input[name='cost']").prop('required',false);
        $("select[name='unit_id']").prop('required',false);
        hide();
        $("#digital").show();
    }
    else if($("input[name='type_hidden']").val() == "service"){
        $("input[name='cost']").prop('required',false);
        $("select[name='unit_id']").prop('required',false);
        hide();
        $("#variant-section, #variant-option").hide();
    }
    else if($("input[name='type_hidden']").val() == "combo"){
        //$("input[name='cost']").prop('required', false);
        //$("input[name='price']").prop('disabled', true);
        //$("select[name='unit_id']").prop('required', false);
        hide();
        $("#cost").show();
        $("#unit").show();
        $("#combo").show();
    }

    var promotion = $("input[name='promotion_hidden']").val();
    if(promotion){
        $("input[name='promotion']").prop('checked', true);
        $("#promotion_price").show(300);
        $("#start_date").show(300);
        $("#last_date").show(300);
    }
    else {
        $("#promotion_price").hide(300);
        $("#start_date").hide(300);
        $("#last_date").hide(300);
    }

    $('#genbutton').on("click", function(){
      $.get('../gencode', function(data){
        $("input[name='code']").val(data);
      });
    });

    $('.selectpicker').selectpicker({
      style: 'btn-link',
    });

    $('.type-variant').on('input', function() {
        alert('dadffff');
    });

    $('.add-more-variant').on("click", function() {
        var htmlText = '<div class="row"><div class="col-md-4 form-group mt-2"><label>Option *</label><input type="text" name="variant_option[]" class="form-control variant-field" placeholder="Size, Color etc..."></div><div class="col-md-7 form-group mt-2"><label>Value *</label><input type="text" name="variant_value[]" class="type-variant form-control variant-field"></div><div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;"><button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="dripicons-cross"></i></button></div></div>';
        $("#variant-input-section").append(htmlText);
        $('.type-variant').tagsInput();
    });

    $(document).on("click", '.delVarOption', function() {
        $(this).parent().parent().remove();
        $('.type-variant').tagsInput();
    });

    $(document).ready(function() {
      $('#supplier_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var dataValue = selectedOption.data('supname');
        $('input[name="supplier_name"]').val(dataValue);
      });
    });

    //start variant related js
    $(function() {
        $('.type-variant').tagsInput();
    });

    (function($) {
        var delimiter = [];
        var inputSettings = [];
        var callbacks = [];

        $.fn.addTag = function(value, options) {
            if(count == noOfVariantValue)
                customizedVariantCode = 1;
            options = jQuery.extend({
                focus: false,
                callback: true
            }, options);

            this.each(function() {
                var id = $(this).attr('id');
                var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
                if (tagslist[0] === '') tagslist = [];

                value = jQuery.trim(value);

                if ((inputSettings[id].unique && $(this).tagExist(value)) || !_validateTag(value, inputSettings[id], tagslist, delimiter[id])) {
                    $('#' + id + '_tag').addClass('error');
                    return false;
                }

                $('<span>', {class: 'tag'}).append(
                    $('<span>', {class: 'tag-text'}).text(value),
                    $('<button>', {class: 'tag-remove'}).click(function() {
                        return $('#' + id).removeTag(encodeURI(value));
                    })
                ).insertBefore('#' + id + '_addTag');

                tagslist.push(value);

                $('#' + id + '_tag').val('');
                if (options.focus) {
                    $('#' + id + '_tag').focus();
                } else {
                    $('#' + id + '_tag').blur();
                }

                $.fn.tagsInput.updateTagsField(this, tagslist);

                if (options.callback && callbacks[id] && callbacks[id]['onAddTag']) {
                    var f = callbacks[id]['onAddTag'];
                    f.call(this, this, value);
                }

                if (callbacks[id] && callbacks[id]['onChange']) {
                    var i = tagslist.length;
                    var f = callbacks[id]['onChange'];
                    f.call(this, this, value);
                }

                $(".type-variant").each(function(index) {
                    variantIds.splice(index, 1, $(this).attr('id'));
                });
                count++;
                if(customizedVariantCode) {
                    first_variant_values = $('#'+variantIds[0]).val().split(_getDelimiter(delimiter[variantIds[0] ]));
                    combinations = first_variant_values;
                    step = 1;
                    while(step < variantIds.length) {
                        var newCombinations = [];
                        for (var i = 0; i < combinations.length; i++) {
                            new_variant_values = $('#'+variantIds[step]).val().split(_getDelimiter(delimiter[variantIds[step] ]));
                            for (var j = 0; j < new_variant_values.length; j++) {
                                newCombinations.push(combinations[i]+'/'+new_variant_values[j]);
                            }
                        }
                        combinations = newCombinations;
                        step++;
                    }
                    var rownumber = $('table.variant-list tbody tr:last').index();
                    if(rownumber > -1) {
                        oldCombinations = [];
                        oldAdditionalCost = [];
                        oldAdditionalPrice = [];
                        oldProductVariantId = [];
                        $(".variant-name").each(function(i) {
                            oldCombinations.push($(this).val());
                            oldProductVariantId.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.product-variant-id').val());
                            oldAdditionalCost.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-cost').val());
                            oldAdditionalPrice.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-price').val());
                        });
                    }

                    $("table.variant-list tbody").remove();
                    var newBody = $("<tbody>");
                    for(i = 0; i < combinations.length; i++) {
                        var variant_name = combinations[i];
                        var item_code = variant_name+'-'+$("#code").val();
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>'+variant_name+'<input type="hidden" class="variant-name" name="variant_name[]" value="' + variant_name + '" /></td>';
                        cols += '<td><input type="text" class="form-control item-code" name="item_code[]" value="'+item_code+'" /></td>';
                        //checking if this variant already exist in the variant table
                        oldIndex = oldCombinations.indexOf(combinations[i]);
                        if(oldIndex >= 0) {
                            cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="'+oldAdditionalCost[oldIndex]+'" step="any" /></td>';
                            cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="'+oldAdditionalPrice[oldIndex]+'" step="any" /></td>';
                        }
                        else {
                            cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="" step="any" /></td>';
                            cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="" step="any" /></td>';
                        }
                        newRow.append(cols);
                        newBody.append(newRow);
                    }
                    $("table.variant-list").append(newBody);
                }
            });

            return false;
        };

        $.fn.removeTag = function(value) {
            value = decodeURI(value);

            this.each(function() {
                var id = $(this).attr('id');

                var old = $(this).val().split(_getDelimiter(delimiter[id]));

                $('#' + id + '_tagsinput .tag').remove();

                var str = '';
                for (i = 0; i < old.length; ++i) {
                    if (old[i] != value) {
                        str = str + _getDelimiter(delimiter[id]) + old[i];
                    }
                }

                $.fn.tagsInput.importTags(this, str);

                if (callbacks[id] && callbacks[id]['onRemoveTag']) {
                    var f = callbacks[id]['onRemoveTag'];
                    f.call(this, this, value);
                }
            });

            return false;
        };

        $.fn.tagExist = function(val) {
            var id = $(this).attr('id');
            var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
            return (jQuery.inArray(val, tagslist) >= 0);
        };

        $.fn.importTags = function(str) {
            var id = $(this).attr('id');
            $('#' + id + '_tagsinput .tag').remove();
            $.fn.tagsInput.importTags(this, str);
        };

        $.fn.tagsInput = function(options) {
            var settings = jQuery.extend({
                interactive: true,
                placeholder: variantPlaceholder,
                minChars: 0,
                maxChars: null,
                limit: null,
                validationPattern: null,
                width: 'auto',
                height: 'auto',
                autocomplete: null,
                hide: true,
                delimiter: ',',
                unique: true,
                removeWithBackspace: true
            }, options);

            var uniqueIdCounter = 0;

            this.each(function() {
                if (typeof $(this).data('tagsinput-init') !== 'undefined') return;

                $(this).data('tagsinput-init', true);

                if (settings.hide) $(this).hide();

                var id = $(this).attr('id');
                if (!id || _getDelimiter(delimiter[$(this).attr('id')])) {
                    id = $(this).attr('id', 'tags' + new Date().getTime() + (++uniqueIdCounter)).attr('id');
                }

                var data = jQuery.extend({
                    pid: id,
                    real_input: '#' + id,
                    holder: '#' + id + '_tagsinput',
                    input_wrapper: '#' + id + '_addTag',
                    fake_input: '#' + id + '_tag'
                }, settings);

                delimiter[id] = data.delimiter;
                inputSettings[id] = {
                    minChars: settings.minChars,
                    maxChars: settings.maxChars,
                    limit: settings.limit,
                    validationPattern: settings.validationPattern,
                    unique: settings.unique
                };

                if (settings.onAddTag || settings.onRemoveTag || settings.onChange) {
                    callbacks[id] = [];
                    callbacks[id]['onAddTag'] = settings.onAddTag;
                    callbacks[id]['onRemoveTag'] = settings.onRemoveTag;
                    callbacks[id]['onChange'] = settings.onChange;
                }

                var markup = $('<div>', {id: id + '_tagsinput', class: 'tagsinput'}).append(
                    $('<div>', {id: id + '_addTag'}).append(
                        settings.interactive ? $('<input>', {id: id + '_tag', class: 'tag-input', value: '', placeholder: settings.placeholder}) : null
                    )
                );

                $(markup).insertAfter(this);

                $(data.holder).css('width', settings.width);
                $(data.holder).css('min-height', settings.height);
                $(data.holder).css('height', settings.height);

                if ($(data.real_input).val() !== '') {
                    $.fn.tagsInput.importTags($(data.real_input), $(data.real_input).val());
                }

                // Stop here if interactive option is not chosen
                if (!settings.interactive) return;

                $(data.fake_input).val('');
                $(data.fake_input).data('pasted', false);

                $(data.fake_input).on('focus', data, function(event) {
                    $(data.holder).addClass('focus');

                    if ($(this).val() === '') {
                        $(this).removeClass('error');
                    }
                });

                $(data.fake_input).on('blur', data, function(event) {
                    $(data.holder).removeClass('focus');
                });

                if (settings.autocomplete !== null && jQuery.ui.autocomplete !== undefined) {
                    $(data.fake_input).autocomplete(settings.autocomplete);
                    $(data.fake_input).on('autocompleteselect', data, function(event, ui) {
                        $(event.data.real_input).addTag(ui.item.value, {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    });

                    $(data.fake_input).on('keypress', data, function(event) {
                        if (_checkDelimiter(event)) {
                            $(this).autocomplete("close");
                        }
                    });
                } else {
                    $(data.fake_input).on('blur', data, function(event) {
                        $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    });
                }

                // If a user types a delimiter create a new tag
                $(data.fake_input).on('keypress', data, function(event) {
                    if (_checkDelimiter(event)) {
                        event.preventDefault();

                        $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    }
                });

                $(data.fake_input).on('paste', function () {
                    $(this).data('pasted', true);
                });

                // If a user pastes the text check if it shouldn't be splitted into tags
                $(data.fake_input).on('input', data, function(event) {
                    if (!$(this).data('pasted')) return;

                    $(this).data('pasted', false);

                    var value = $(event.data.fake_input).val();

                    value = value.replace(/\n/g, '');
                    value = value.replace(/\s/g, '');

                    var tags = _splitIntoTags(event.data.delimiter, value);

                    if (tags.length > 1) {
                        for (var i = 0; i < tags.length; ++i) {
                            $(event.data.real_input).addTag(tags[i], {
                                focus: true,
                                unique: settings.unique
                            });
                        }

                        return false;
                    }
                });

                // Deletes last tag on backspace
                data.removeWithBackspace && $(data.fake_input).on('keydown', function(event) {
                    if (event.keyCode == 8 && $(this).val() === '') {
                         event.preventDefault();
                         var lastTag = $(this).closest('.tagsinput').find('.tag:last > span').text();
                         var id = $(this).attr('id').replace(/_tag$/, '');
                         $('#' + id).removeTag(encodeURI(lastTag));
                         $(this).trigger('focus');
                    }
                });

                // Removes the error class when user changes the value of the fake input
                $(data.fake_input).keydown(function(event) {
                    // enter, alt, shift, esc, ctrl and arrows keys are ignored
                    if (jQuery.inArray(event.keyCode, [13, 37, 38, 39, 40, 27, 16, 17, 18, 225]) === -1) {
                        $(this).removeClass('error');
                    }
                });
            });

            return this;
        };

        $.fn.tagsInput.updateTagsField = function(obj, tagslist) {
            var id = $(obj).attr('id');
            $(obj).val(tagslist.join(_getDelimiter(delimiter[id])));
        };

        $.fn.tagsInput.importTags = function(obj, val) {
            $(obj).val('');

            var id = $(obj).attr('id');
            var tags = _splitIntoTags(delimiter[id], val);

            for (i = 0; i < tags.length; ++i) {
                $(obj).addTag(tags[i], {
                    focus: false,
                    callback: false
                });
            }

            if (callbacks[id] && callbacks[id]['onChange']) {
                var f = callbacks[id]['onChange'];
                f.call(obj, obj, tags);
            }
        };

        var _getDelimiter = function(delimiter) {
            if (typeof delimiter === 'undefined') {
                return delimiter;
            } else if (typeof delimiter === 'string') {
                return delimiter;
            } else {
                return delimiter[0];
            }
        };

        var _validateTag = function(value, inputSettings, tagslist, delimiter) {
            var result = true;

            if (value === '') result = false;
            if (value.length < inputSettings.minChars) result = false;
            if (inputSettings.maxChars !== null && value.length > inputSettings.maxChars) result = false;
            if (inputSettings.limit !== null && tagslist.length >= inputSettings.limit) result = false;
            if (inputSettings.validationPattern !== null && !inputSettings.validationPattern.test(value)) result = false;

            if (typeof delimiter === 'string') {
                if (value.indexOf(delimiter) > -1) result = false;
            } else {
                $.each(delimiter, function(index, _delimiter) {
                    if (value.indexOf(_delimiter) > -1) result = false;
                    return false;
                });
            }

            return result;
        };

        var _checkDelimiter = function(event) {
            var found = false;

            if (event.which === 13) {
                return true;
            }

            if (typeof event.data.delimiter === 'string') {
                if (event.which === event.data.delimiter.charCodeAt(0)) {
                    found = true;
                }
            } else {
                $.each(event.data.delimiter, function(index, delimiter) {
                    if (event.which === delimiter.charCodeAt(0)) {
                        found = true;
                    }
                });
            }

            return found;
         };

         var _splitIntoTags = function(delimiter, value) {
             if (value === '') return [];

             if (typeof delimiter === 'string') {
                 return value.split(delimiter);
             } else {
                 var tmpDelimiter = '∞';
                 var text = value;

                 $.each(delimiter, function(index, _delimiter) {
                     text = text.split(_delimiter).join(tmpDelimiter);
                 });

                 return text.split(tmpDelimiter);
             }

             return [];
         };
    })(jQuery);
    //end of variant related js

    tinymce.init({
      selector: 'textarea:not(.no-tiny)',
      height: 130,
      plugins: [
        'advlist autolink lists link image charmap print preview anchor textcolor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table contextmenu paste code wordcount'
      ],
      toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
      branding:false
    });

    var barcode_symbology = $("input[name='barcode_symbology_hidden']").val();
    $('select[name=barcode_symbology]').val(barcode_symbology);

    var brand = $("input[name='brand']").val();
    $('select[name=brand_id]').val(brand);

    var cat = $("input[name='category']").val();
    $('select[name=category_id]').val(cat);

    if($("input[name='unit']").val()) {
        $('select[name=unit_id]').val($("input[name='unit']").val());
        populate_unit($("input[name='unit']").val());
    }

    var tax = $("input[name='tax']").val();
    if(tax)
        $('select[name=tax_id]').val(tax);

    var tax_method = $("input[name='tax_method_id']").val();
    $('select[name=tax_method]').val(tax_method);
    $('.selectpicker').selectpicker('refresh');

    $('select[name="type"]').on('change', function() {
        if($(this).val() == 'combo'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            hide();
            $("#cost").show(300);
            $("#unit").show(300);
            $("#digital").hide();
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
            $("#combo").show();
            $("input[name='price']").prop('disabled',true);
        }
        else if($(this).val() == 'digital'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',true);
            hide();
            $("#combo").hide();
            $("#digital").show();
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
            $("input[name='price']").prop('disabled',false);
        }
        else if($(this).val() == 'service') {
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',true);
            hide();
            $("#combo").hide(300);
            $("#digital").hide(300);
            $("input[name='price']").prop('disabled',false);
            $("#is-variant").prop("checked", false);
            $("#variant-section, #variant-option").hide(300);
        }
        else if($(this).val() == 'standard'){
            $("input[name='cost']").prop('required',true);
            $("select[name='unit_id']").prop('required',true);
            $("input[name='file']").prop('required',false);
            $("#cost").show();
            $("#unit").show();
            $("#alert-qty").show();
            $("#variant-option").show(300);
            $("#diffPrice-option").show(300);
            $("#digital").hide();
            $("#combo").hide();
            $("input[name='price']").prop('disabled',false);
        }
    });

    $('select[name="unit_id"]').on('change', function() {
        unitID = $(this).val();
        if(unitID) {
            populate_unit_second(unitID);
        }else{
            $('select[name="sale_unit_id"]').empty();
            $('select[name="purchase_unit_id"]').empty();
        }
    });

    <?php $productArray = []; ?>
    var lims_product_code = [
        @foreach($lims_product_list_without_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->code) . '|' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name));
        ?>
        @endforeach
        @foreach($lims_product_list_with_variant as $product)
            <?php
                $productArray[] = htmlspecialchars($product->item_code) . '|' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name));
            ?>
        @endforeach
            <?php
                echo  '"'.implode('","', $productArray).'"';
            ?> ];

    var lims_productcodeSearch = $('#lims_productcodeSearch');

    lims_productcodeSearch.autocomplete({
        source: function(request, response) {
            var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
            response($.grep(lims_product_code, function(item) {
                return matcher.test(item);
            }));
        },
        select: function(event, ui) {
            var data = ui.item.value;
            $.ajax({
                type: 'GET',
                url: '../lims_product_search',
                data: {
                    data: data
                },
                success: function(data) {
                    //console.log(data);
                    var flag = 1;
                    $(".product-id").each(function() {
                        if ($(this).val() == data[8]) {
                            alert('Duplicate input is not allowed!')
                            flag = 0;
                        }
                    });
                    $("input[name='product_code_name']").val('');
                    if(flag){
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + data[0] +' [' + data[1] + ']</td>';
                        cols += '<td><input type="number" class="form-control qty" name="product_qty[]" value="1" step="any"/></td>';
                        cols += '<td><input type="number" class="form-control unit_cost" value="' + data[10] + '"/></td>';
                        cols += '<td><input type="number" class="form-control unit_price" name="unit_price[]" value="' + data[2] + '" step="any"/></td>';
                        cols += '<td><button type="button" class="ibtnDel btn btn-sm btn-danger">X</button></td>';
                        cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[8] + '"/>';
                        cols += '<input type="hidden" class="" name="variant_id[]" value="' + data[9] + '"/>';

                        newRow.append(cols);
                        $("table.order-list tbody").append(newRow);
                        calculate_price();
                    }
                }
            });
        }
    });

    //Change quantity or unit price
    $("#myTable").on('input', '.qty , .unit_cost, .unit_price', function() {
        calculate_price();
    });

    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        $(this).closest("tr").remove();
        calculate_price();
    });

    function calculate_price() {
        var price = 0;
        var cost = 0;
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            quantity =  $(this).val();
            unit_price = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .unit_price').val();
            price += quantity * unit_price;
            unit_cost = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .unit_cost').val();
            cost += quantity * parseFloat(unit_cost);
        });
        $('input[name="price"]').val(price);
        $('input[name="cost"]').val(cost);
    }

    function hide() {
        $("#cost").hide();
        $("#unit").hide();
        $("#alert-qty").hide();
    }

    function populate_unit(unitID){
        $.ajax({
            url: '../saleunit/'+unitID,
            type: "GET",
            dataType: "json",

            success:function(data) {
                  $('select[name="sale_unit_id"]').empty();
                  $('select[name="purchase_unit_id"]').empty();
                  $.each(data, function(key, value) {
                      $('select[name="sale_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                      $('select[name="purchase_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                  });
                  $('.selectpicker').selectpicker('refresh');
                  var sale_unit = $("input[name='sale_unit']").val();
                  var purchase_unit = $("input[name='purchase_unit']").val();
                $('#sale-unit').val(sale_unit);
                $('select[name=purchase_unit_id]').val(purchase_unit);
                $('.selectpicker').selectpicker('refresh');
            },
        });
    }

    function populate_unit_second(unitID){
        $.ajax({
            url: '../saleunit/'+unitID,
            type: "GET",
            dataType: "json",
            success:function(data) {
                  $('select[name="sale_unit_id"]').empty();
                  $('select[name="purchase_unit_id"]').empty();
                  $.each(data, function(key, value) {
                      $('select[name="sale_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                      $('select[name="purchase_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                  });
                  $('.selectpicker').selectpicker('refresh');
            },
        });
    };

    $("input[name='is_batch']").on("change", function () {
        if ($(this).is(':checked')) {
            $("#variant-option").hide(300);
        }
        else
            $("#variant-option").show(300);
    });

    $("input[name='is_variant']").on("change", function () {
        variantShowHide();
    });

    $("input[name='is_diffPrice']").on("change", function () {
        diffPriceShowHide();
    });

    function variantShowHide() {
         if ($("#is-variant").is(':checked')) {
            $("#variant-section").show(300);
            $("#batch-option").hide(300);
            $(".variant-field").prop("required", true);
        }
        else {
            $("#variant-section").hide(300);
            $("#batch-option").show(300);
            $(".variant-field").prop("required", false);
        }
    };

    function diffPriceShowHide() {
         if ($("#is-diffPrice").is(':checked')) {
            $("#diffPrice-section").show(300);
        }
        else {
            $("#diffPrice-section").hide(300);
        }
    };

    $( "#promotion" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("#promotion_price").show();
            $("#start_date").show();
            $("#last_date").show();
        }
        else {
            $("#promotion_price").hide();
            $("#start_date").hide();
            $("#last_date").hide();
        }
    });

    var starting_date = $('#starting_date');
    starting_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    var ending_date = $('#ending_date');
    ending_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    //dropzone portion
    Dropzone.autoDiscover = false;

    jQuery.validator.setDefaults({
        errorPlacement: function (error, element) {
            if(error.html() == 'Select Category...')
                error.html('This field is required.');
            $(element).closest('div.form-group').find('.validation-msg').html(error.html());
        },
        highlight: function (element) {
            $(element).closest('div.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('div.form-group').removeClass('has-error').addClass('has-success');
            $(element).closest('div.form-group').find('.validation-msg').html('');
        }
    });

    function validate() {
        var product_code = $("input[name='code']").val();
        var barcode_symbology = $('select[name="barcode_symbology"]').val();
        var exp = /^\d+$/;

        if(!(product_code.match(exp)) && (barcode_symbology == 'UPCA' || barcode_symbology == 'UPCE' || barcode_symbology == 'EAN8' || barcode_symbology == 'EAN13') ) {
            alert('Product code must be numeric.');
            return false;
        }
        else if(product_code.match(exp)) {
            if(barcode_symbology == 'UPCA' && product_code.length > 11){
                alert('Product code length must be less than 12');
                return false;
            }
            else if(barcode_symbology == 'EAN8' && product_code.length > 7){
                alert('Product code length must be less than 8');
                return false;
            }
            /*else if(barcode_symbology == 'EAN13' && product_code.length > 12){
                alert('Product code length must be less than 13');
                return false;
            }*/
        }

        if( $("#type").val() == 'combo' ) {
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to table!")
                return false;
            }
        }
        $("input[name='price']").prop('disabled',false);
        return true;
    }

    $(".dropzone").sortable({
        items:'.dz-preview',
        cursor: 'grab',
        opacity: 0.5,
        containment: '.dropzone',
        distance: 20,
        tolerance: 'pointer',
        stop: function () {
          var queue = myDropzone.getAcceptedFiles();
          newQueue = [];
          $('#imageUpload .dz-preview .dz-filename [data-dz-name]').each(function (count, el) {
                var name = el.innerHTML;
                queue.forEach(function(file) {
                    if (file.name === name) {
                        newQueue.push(file);
                    }
                });
          });
          myDropzone.files = newQueue;
        }
    });

    myDropzone = new Dropzone('div#imageUpload', {
        addRemoveLinks: true,
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 100,
        maxFilesize: 12,
        paramName: 'image',
        clickable: true,
        method: 'POST',
        url:'../update',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        renameFile: function(file) {
            var dt = new Date();
            var time = dt.getTime();
            return time + file.name;
        },
        acceptedFiles: ".jpeg,.jpg,.png,.gif",
        init: function () {
            var myDropzone = this;
            $('#submit-btn').on("click", function (e) {
                e.preventDefault();
                if ( $("#product-form").valid() && validate() ) {
                    tinyMCE.triggerSave();
                    $(this).attr('disabled','true').html('<span class="spinner-border text-light" role="status"></span> {{trans("file.Saving")}}...');
                    if(myDropzone.getAcceptedFiles().length) {
                        myDropzone.processQueue();
                    }
                    else {
                        var formData = new FormData();
                        //$("#product-form").serialize();
                        var data = $("#product-form").serializeArray();
                        $.each(data, function (key, el) {
                            formData.append(el.name, el.value);
                        });
                        var file = $('#file')[0].files;
                        if(file.length > 0)
                            formData.append('file',file[0]);
                        $.ajax({
                            type:'POST',
                            url:'../update',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success:function(response) {
                                //console.log(response);
                                location.href = redirectUrl;
                            },
                            error:function(response) {
                                //console.log(response);
                              if(response.responseJSON.errors.name) {
                                  $("#name-error").text(response.responseJSON.errors.name);
                              }
                              else if(response.responseJSON.errors.code) {
                                  $("#code-error").text(response.responseJSON.errors.code);
                              }
                            },
                        });
                    }
                }
            });

            this.on('sending', function (file, xhr, formData) {
                // Append all form inputs to the formData Dropzone will POST
                var data = $("#product-form").serializeArray();
                $.each(data, function (key, el) {
                    formData.append(el.name, el.value);
                });
                var file = $('#file')[0].files;
                if(file.length > 0)
                    formData.append('file',file[0]);
            });
        },
        error: function (file, response) {
            console.log(response);
            /*if(response.errors.name) {
              $("#name-error").text(response.errors.name);
              this.removeAllFiles(true);
            }
            else if(response.errors.code) {
              $("#code-error").text(response.errors.code);
              this.removeAllFiles(true);
            }
            else {
              try {
                  var res = JSON.parse(response);
                  if (typeof res.message !== 'undefined' && !$modal.hasClass('in')) {
                      $("#success-icon").attr("class", "fas fa-thumbs-down");
                      $("#success-text").html(res.message);
                      $modal.modal("show");
                  } else {
                      if ($.type(response) === "string")
                          var message = response; //dropzone sends it's own error messages in string
                      else
                          var message = response.message;
                      file.previewElement.classList.add("dz-error");
                      _ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
                      _results = [];
                      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                          node = _ref[_i];
                          _results.push(node.textContent = message);
                      }
                      return _results;
                  }
              } catch (error) {
                  console.log(error);
              }
            }*/
        },
        successmultiple: function (file, response) {
            location.href = redirectUrl;
            //console.log('sss: '+ response);
        },
        completemultiple: function (file, response) {
            console.log(file, response, "completemultiple");
        },
        reset: function () {
            console.log("resetFiles");
            this.removeAllFiles(true);
        }
    });

</script>
@endpush
