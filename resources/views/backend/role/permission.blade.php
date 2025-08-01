@extends('backend.layout.main')
@section('content')
@if(session()->has('not_permitted'))
<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert"
        aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.Group Permission')}}</h4>
                    </div>
                    {!! Form::open(['route' => 'role.setPermission', 'method' => 'post']) !!}
                    <div class="card-body">
                        <input type="hidden" name="role_id" value="{{$lims_role_data->id}}" />
                        <div class="table-responsive">
                            <table class="table table-bordered permission-table">
                                <thead>
                                    <tr>
                                        <th colspan="5" class="text-center">{{$lims_role_data->name}}
                                            {{trans('file.Group Permission')}}</th>
                                    </tr>
                                    <tr>
                                        <th rowspan="2" class="text-center">Module Name</th>
                                        <th colspan="4" class="text-center">
                                            <div class="checkbox">
                                                <input type="checkbox" id="select_all">
                                                <label for="select_all">{{trans('file.Permissions')}}</label>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th class="text-center">{{trans('file.View')}}</th>
                                        <th class="text-center">{{trans('file.add')}}</th>
                                        <th class="text-center">{{trans('file.edit')}}</th>
                                        <th class="text-center">{{trans('file.delete')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{trans('file.product')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("products-index", $all_permission))
                                                    <input type="checkbox" value="1" id="products-index"
                                                        name="products-index" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="products-index"
                                                        name="products-index" />
                                                    @endif
                                                    <label for="products-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("products-add", $all_permission))
                                                    <input type="checkbox" value="1" id="products-add"
                                                        name="products-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="products-add"
                                                        name="products-add">
                                                    @endif
                                                    <label for="products-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("products-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="products-edit"
                                                        name="products-edit" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="products-edit"
                                                        name="products-edit" />
                                                    @endif
                                                    <label for="products-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("products-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="products-delete"
                                                        name="products-delete" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="products-delete"
                                                        name="products-delete" />
                                                    @endif
                                                    <label for="products-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('Inbound')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("inbound-index", $all_permission))
                                                    <input type="checkbox" value="1" id="inbound-index"
                                                        name="inbound-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="inbound-index"
                                                        name="inbound-index">
                                                    @endif
                                                    <label for="inbound-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("inbound-add", $all_permission))
                                                    <input type="checkbox" value="1" id="inbound-add"
                                                        name="inbound-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="inbound-add"
                                                        name="inbound-add">
                                                    @endif
                                                    <label for="inbound-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("inbound-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="inbound-edit"
                                                        name="inbound-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="inbound-edit"
                                                        name="inbound-edit">
                                                    @endif
                                                    <label for="inbound-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("inbound-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="inbound-delete"
                                                        name="inbound-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="inbound-delete"
                                                        name="inbound-delete">
                                                    @endif
                                                    <label for="inbound-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('Outbound')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("outbound-index", $all_permission))
                                                    <input type="checkbox" value="1" id="outbound-index"
                                                        name="outbound-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="outbound-index"
                                                        name="outbound-index">
                                                    @endif
                                                    <label for="outbound-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("outbound-add", $all_permission))
                                                    <input type="checkbox" value="1" id="outbound-add"
                                                        name="outbound-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="outbound-add"
                                                        name="outbound-add">
                                                    @endif
                                                    <label for="outbound-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("outbound-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="outbound-edit"
                                                        name="outbound-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="outbound-edit"
                                                        name="outbound-edit">
                                                    @endif
                                                    <label for="outbound-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("outbound-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="outbound-delete"
                                                        name="outbound-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="outbound-delete"
                                                        name="outbound-delete">
                                                    @endif
                                                    <label for="outbound-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('file.Purchase')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchases-index", $all_permission))
                                                    <input type="checkbox" value="1" id="purchases-index"
                                                        name="purchases-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchases-index"
                                                        name="purchases-index">
                                                    @endif
                                                    <label for="purchases-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchases-add", $all_permission))
                                                    <input type="checkbox" value="1" id="purchases-add"
                                                        name="purchases-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchases-add"
                                                        name="purchases-add">
                                                    @endif
                                                    <label for="purchases-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchases-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="purchases-edit"
                                                        name="purchases-edit" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="purchases-edit"
                                                        name="purchases-edit">
                                                    @endif
                                                    <label for="purchases-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchases-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="purchases-delete"
                                                        name="purchases-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchases-delete"
                                                        name="purchases-delete">
                                                    @endif
                                                    <label for="purchases-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>{{trans('file.Purchase Payment')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchase-payment-index", $all_permission))
                                                    <input type="checkbox" value="1" id="purchase-payment-index"
                                                        name="purchase-payment-index" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="purchase-payment-index"
                                                        name="purchase-payment-index">
                                                    @endif
                                                    <label for="purchase-payment-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchase-payment-add", $all_permission))
                                                    <input type="checkbox" value="1" id="purchase-payment-add"
                                                        name="purchase-payment-add" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="purchase-payment-add"
                                                        name="purchase-payment-add">
                                                    @endif
                                                    <label for="purchase-payment-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchase-payment-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="purchase-payment-edit"
                                                        name="purchase-payment-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchase-payment-edit"
                                                        name="purchase-payment-edit">
                                                    @endif
                                                    <label for="purchase-payment-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchase-payment-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="purchase-payment-delete"
                                                        name="purchase-payment-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchase-payment-delete"
                                                        name="purchase-payment-delete">
                                                    @endif
                                                    <label for="purchase-payment-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>{{trans('file.Sale')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("sales-index", $all_permission))
                                                    <input type="checkbox" value="1" id="sales-index" name="sales-index"
                                                        checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="sales-index"
                                                        name="sales-index">
                                                    @endif
                                                    <label for="sales-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("sales-add", $all_permission))
                                                    <input type="checkbox" value="1" id="sales-add" name="sales-add"
                                                        checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="sales-add" name="sales-add">
                                                    @endif
                                                    <label for="sales-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("sales-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="sales-edit" name="sales-edit"
                                                        checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="sales-edit" name="sales-edit">
                                                    @endif
                                                    <label for="sales-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("sales-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="sales-delete"
                                                        name="sales-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="sales-delete"
                                                        name="sales-delete">
                                                    @endif
                                                    <label for="sales-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>{{trans('file.Sale Payment')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("sale-payment-index", $all_permission))
                                                    <input type="checkbox" value="1" id="sale-payment-index"
                                                        name="sale-payment-index" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="sale-payment-index"
                                                        name="sale-payment-index">
                                                    @endif
                                                    <label for="sale-payment-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("sale-payment-add", $all_permission))
                                                    <input type="checkbox" value="1" id="sale-payment-add"
                                                        name="sale-payment-add" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="sale-payment-add"
                                                        name="sale-payment-add">
                                                    @endif
                                                    <label for="sale-payment-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("sale-payment-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="sale-payment-edit"
                                                        name="sale-payment-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="sale-payment-edit"
                                                        name="sale-payment-edit">
                                                    @endif
                                                    <label for="sale-payment-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("sale-payment-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="sale-payment-delete"
                                                        name="sale-payment-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="sale-payment-delete"
                                                        name="sale-payment-delete">
                                                    @endif
                                                    <label for="sale-payment-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="expense-row">
                                        <td>{{trans('file.Expense')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("expenses-index", $all_permission))
                                                    <input type="checkbox" value="1" id="expenses-index"
                                                        name="expenses-index" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="expenses-index"
                                                        name="expenses-index">
                                                    @endif
                                                    <label for="expenses-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("expenses-add", $all_permission))
                                                    <input type="checkbox" value="1" id="expenses-add"
                                                        name="expenses-add" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="expenses-add"
                                                        name="expenses-add">
                                                    @endif
                                                    <label for="expenses-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("expenses-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="expenses-edit"
                                                        name="expenses-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="expenses-edit"
                                                        name="expenses-edit">
                                                    @endif
                                                    <label for="expenses-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("expenses-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="expenses-delete"
                                                        name="expenses-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="expenses-delete"
                                                        name="expenses-delete">
                                                    @endif
                                                    <label for="expenses-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="income-row">
                                        <td>{{trans('file.Income')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("incomes-index", $all_permission))
                                                    <input type="checkbox" value="1" id="incomes-index"
                                                        name="incomes-index" checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="incomes-index"
                                                        name="incomes-index">
                                                    @endif
                                                    <label for="incomes-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("incomes-add", $all_permission))
                                                    <input type="checkbox" value="1" id="incomes-add" name="incomes-add"
                                                        checked />
                                                    @else
                                                    <input type="checkbox" value="1" id="incomes-add"
                                                        name="incomes-add">
                                                    @endif
                                                    <label for="incomes-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("incomes-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="incomes-edit"
                                                        name="incomes-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="incomes-edit"
                                                        name="incomes-edit">
                                                    @endif
                                                    <label for="incomes-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("incomes-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="incomes-delete"
                                                        name="incomes-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="incomes-delete"
                                                        name="incomes-delete">
                                                    @endif
                                                    <label for="incomes-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="quotation-row">
                                        <td>{{trans('file.Quotation')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("quotes-index", $all_permission))
                                                    <input type="checkbox" value="1" id="quotes-index"
                                                        name="quotes-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="quotes-index"
                                                        name="quotes-index">
                                                    @endif
                                                    <label for="quotes-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("quotes-add", $all_permission))
                                                    <input type="checkbox" value="1" id="quotes-add" name="quotes-add"
                                                        checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="quotes-add" name="quotes-add">
                                                    @endif
                                                    <label for="quotes-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("quotes-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="quotes-edit" name="quotes-edit"
                                                        checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="quotes-edit"
                                                        name="quotes-edit">
                                                    @endif
                                                    <label for="quotes-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("quotes-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="quotes-delete"
                                                        name="quotes-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="quotes-delete"
                                                        name="quotes-delete">
                                                    @endif
                                                    <label for="quotes-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="transfer-row">
                                        <td>{{trans('file.Transfer')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("transfers-index", $all_permission))
                                                    <input type="checkbox" value="1" id="transfers-index"
                                                        name="transfers-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="transfers-index"
                                                        name="transfers-index">
                                                    @endif
                                                    <label for="transfers-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("transfers-add", $all_permission))
                                                    <input type="checkbox" value="1" id="transfers-add"
                                                        name="transfers-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="transfers-add"
                                                        name="transfers-add">
                                                    @endif
                                                    <label for="transfers-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("transfers-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="transfers-edit"
                                                        name="transfers-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="transfers-edit"
                                                        name="transfers-edit">
                                                    @endif
                                                    <label for="transfers-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("transfers-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="transfers-delete"
                                                        name="transfers-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="transfers-delete"
                                                        name="transfers-delete">
                                                    @endif
                                                    <label for="transfers-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="purchase-return-row">
                                        <td>{{trans('file.Purchase Return')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchase-return-index", $all_permission))
                                                    <input type="checkbox" value="1" id="purchase-return-index"
                                                        name="purchase-return-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchase-return-index"
                                                        name="purchase-return-index">
                                                    @endif
                                                    <label for="purchase-return-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchase-return-add", $all_permission))
                                                    <input type="checkbox" value="1" id="purchase-return-add"
                                                        name="purchase-return-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchase-return-add"
                                                        name="purchase-return-add">
                                                    @endif
                                                    <label for="purchase-return-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchase-return-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="purchase-return-edit"
                                                        name="purchase-return-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchase-return-edit"
                                                        name="purchase-return-edit">
                                                    @endif
                                                    <label for="purchase-return-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("purchase-return-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="purchase-return-delete"
                                                        name="purchase-return-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="purchase-return-delete"
                                                        name="purchase-return-delete">
                                                    @endif
                                                    <label for="purchase-return-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="employee-row">
                                        <td>{{trans('file.Employee')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("employees-index", $all_permission))
                                                    <input type="checkbox" value="1" id="employees-index"
                                                        name="employees-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="employees-index"
                                                        name="employees-index">
                                                    @endif
                                                    <label for="employees-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("employees-add", $all_permission))
                                                    <input type="checkbox" value="1" id="employees-add"
                                                        name="employees-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="employees-add"
                                                        name="employees-add">
                                                    @endif
                                                    <label for="employees-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("employees-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="employees-edit"
                                                        name="employees-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="employees-edit"
                                                        name="employees-edit">
                                                    @endif
                                                    <label for="employees-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("employees-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="employees-delete"
                                                        name="employees-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="employees-delete"
                                                        name="employees-delete">
                                                    @endif
                                                    <label for="employees-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('file.User')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("users-index", $all_permission))
                                                    <input type="checkbox" value="1" id="users-index" name="users-index"
                                                        checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="users-index"
                                                        name="users-index">
                                                    @endif
                                                    <label for="users-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("users-add", $all_permission))
                                                    <input type="checkbox" value="1" id="users-add" name="users-add"
                                                        checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="users-add" name="users-add">
                                                    @endif
                                                    <label for="users-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("users-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="users-edit" name="users-edit"
                                                        checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="users-edit" name="users-edit">
                                                    @endif
                                                    <label for="users-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("users-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="users-delete"
                                                        name="users-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="users-delete"
                                                        name="users-delete">
                                                    @endif
                                                    <label for="users-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('file.customer')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("customers-index", $all_permission))
                                                    <input type="checkbox" value="1" id="customers-index"
                                                        name="customers-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="customers-index"
                                                        name="customers-index">
                                                    @endif
                                                    <label for="customers-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("customers-add", $all_permission))
                                                    <input type="checkbox" value="1" id="customers-add"
                                                        name="customers-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="customers-add"
                                                        name="customers-add">
                                                    @endif
                                                    <label for="customers-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("customers-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="customers-edit"
                                                        name="customers-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="customers-edit"
                                                        name="customers-edit">
                                                    @endif
                                                    <label for="customers-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("customers-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="customers-delete"
                                                        name="customers-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="customers-delete"
                                                        name="customers-delete">
                                                    @endif
                                                    <label for="customers-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('file.Biller')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("billers-index", $all_permission))
                                                    <input type="checkbox" value="1" id="billers-index"
                                                        name="billers-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="billers-index"
                                                        name="billers-index">
                                                    @endif
                                                    <label for="billers-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("billers-add", $all_permission))
                                                    <input type="checkbox" value="1" id="billers-add" name="billers-add"
                                                        checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="billers-add"
                                                        name="billers-add">
                                                    @endif
                                                    <label for="billers-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue checked" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("billers-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="billers-edit"
                                                        name="billers-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="billers-edit"
                                                        name="billers-edit">
                                                    @endif
                                                    <label for="billers-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("billers-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="billers-delete"
                                                        name="billers-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="billers-delete"
                                                        name="billers-delete">
                                                    @endif
                                                    <label for="billers-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('file.Supplier')}}</td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("suppliers-index", $all_permission))
                                                    <input type="checkbox" value="1" id="suppliers-index"
                                                        name="suppliers-index" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="suppliers-index"
                                                        name="suppliers-index">
                                                    @endif
                                                    <label for="suppliers-index"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("suppliers-add", $all_permission))
                                                    <input type="checkbox" value="1" id="suppliers-add"
                                                        name="suppliers-add" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="suppliers-add"
                                                        name="suppliers-add">
                                                    @endif
                                                    <label for="suppliers-add"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("suppliers-edit", $all_permission))
                                                    <input type="checkbox" value="1" id="suppliers-edit"
                                                        name="suppliers-edit" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="suppliers-edit"
                                                        name="suppliers-edit">
                                                    @endif
                                                    <label for="suppliers-edit"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="icheckbox_square-blue" aria-checked="false"
                                                aria-disabled="false">
                                                <div class="checkbox">
                                                    @if(in_array("suppliers-delete", $all_permission))
                                                    <input type="checkbox" value="1" id="suppliers-delete"
                                                        name="suppliers-delete" checked>
                                                    @else
                                                    <input type="checkbox" value="1" id="suppliers-delete"
                                                        name="suppliers-delete">
                                                    @endif
                                                    <label for="suppliers-delete"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('file.dashboard')}}</td>
                                        <td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("revenue_profit_summary", $all_permission))
                                                        <input type="checkbox" value="1" id="revenue_profit_summary"
                                                            name="revenue_profit_summary" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="revenue_profit_summary"
                                                            name="revenue_profit_summary">
                                                        @endif
                                                        <label for="revenue_profit_summary"
                                                            class="padding05">{{trans('file.Revenue and Profit Summary')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("cash_flow", $all_permission))
                                                        <input type="checkbox" value="1" id="cash_flow" name="cash_flow"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="cash_flow"
                                                            name="cash_flow">
                                                        @endif
                                                        <label for="cash_flow"
                                                            class="padding05">{{trans('file.Cash Flow')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("monthly_summary", $all_permission))
                                                        <input type="checkbox" value="1" id="monthly_summary"
                                                            name="monthly_summary" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="monthly_summary"
                                                            name="monthly_summary">
                                                        @endif
                                                        <label for="monthly_summary"
                                                            class="padding05">{{trans('file.Monthly Summary')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("yearly_report", $all_permission))
                                                        <input type="checkbox" value="1" id="yearly_report"
                                                            name="yearly_report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="yearly_report"
                                                            name="yearly_report">
                                                        @endif
                                                        <label for="yearly_report"
                                                            class="padding05">{{trans('file.yearly report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="accounting-row">
                                        <td>{{trans('file.Accounting')}}</td>
                                        <td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("account-index", $all_permission))
                                                        <input type="checkbox" value="1" id="account-index"
                                                            name="account-index" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="account-index"
                                                            name="account-index">
                                                        @endif
                                                        <label for="account-index"
                                                            class="padding05">{{trans('file.Account')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("money-transfer", $all_permission))
                                                        <input type="checkbox" value="1" id="money-transfer"
                                                            name="money-transfer" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="money-transfer"
                                                            name="money-transfer">
                                                        @endif
                                                        <label for="money-transfer"
                                                            class="padding05">{{trans('file.Money Transfer')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("balance-sheet", $all_permission))
                                                        <input type="checkbox" value="1" id="balance-sheet"
                                                            name="balance-sheet" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="balance-sheet"
                                                            name="balance-sheet">
                                                        @endif
                                                        <label for="balance-sheet"
                                                            class="padding05">{{trans('file.Balance Sheet')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("account-statement", $all_permission))
                                                        <input type="checkbox" value="1"
                                                            id="account-statement-permission" name="account-statement"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1"
                                                            id="account-statement-permission" name="account-statement">
                                                        @endif
                                                        <label for="account-statement-permission"
                                                            class="padding05">{{trans('file.Account Statement')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="hrm-row">
                                        <td>HRM</td>
                                        <td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("department", $all_permission))
                                                        <input type="checkbox" value="1" id="department"
                                                            name="department" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="department"
                                                            name="department">
                                                        @endif
                                                        <label for="department"
                                                            class="padding05">{{trans('file.Department')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("attendance", $all_permission))
                                                        <input type="checkbox" value="1" id="attendance"
                                                            name="attendance" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="attendance"
                                                            name="attendance">
                                                        @endif
                                                        <label for="attendance"
                                                            class="padding05">{{trans('file.Attendance')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("payroll", $all_permission))
                                                        <input type="checkbox" value="1" id="payroll" name="payroll"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="payroll" name="payroll">
                                                        @endif
                                                        <label for="payroll" class="padding05">{{trans('file.Payroll')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("holiday", $all_permission))
                                                        <input type="checkbox" value="1" id="holiday" name="holiday"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="holiday" name="holiday">
                                                        @endif
                                                        <label for="holiday"
                                                            class="padding05">{{trans('file.Holiday Approve')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="report-row">
                                        <td>{{trans('file.Reports')}}</td>
                                        <td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("profit-loss", $all_permission))
                                                        <input type="checkbox" value="1" id="profit-loss"
                                                            name="profit-loss" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="profit-loss"
                                                            name="profit-loss">
                                                        @endif
                                                        <label for="profit-loss"
                                                            class="padding05">{{trans('file.Summary Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("best-seller", $all_permission))
                                                        <input type="checkbox" value="1" id="best-seller"
                                                            name="best-seller" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="best-seller"
                                                            name="best-seller">
                                                        @endif
                                                        <label for="best-seller"
                                                            class="padding05">{{trans('file.Best Seller')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("daily-sale", $all_permission))
                                                        <input type="checkbox" value="1" id="daily-sale"
                                                            name="daily-sale" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="daily-sale"
                                                            name="daily-sale">
                                                        @endif
                                                        <label for="daily-sale"
                                                            class="padding05">{{trans('file.Daily Sale')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("monthly-sale", $all_permission))
                                                        <input type="checkbox" value="1" id="monthly-sale"
                                                            name="monthly-sale" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="monthly-sale"
                                                            name="monthly-sale">
                                                        @endif
                                                        <label for="monthly-sale"
                                                            class="padding05">{{trans('file.Monthly Sale')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("daily-purchase", $all_permission))
                                                        <input type="checkbox" value="1" id="daily-purchase"
                                                            name="daily-purchase" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="daily-purchase"
                                                            name="daily-purchase">
                                                        @endif
                                                        <label for="daily-purchase"
                                                            class="padding05">{{trans('file.Daily Purchase')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("monthly-purchase", $all_permission))
                                                        <input type="checkbox" value="1" id="monthly-purchase"
                                                            name="monthly-purchase" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="monthly-purchase"
                                                            name="monthly-purchase">
                                                        @endif
                                                        <label for="monthly-purchase"
                                                            class="padding05">{{trans('file.Monthly Purchase')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("product-report", $all_permission))
                                                        <input type="checkbox" value="1" id="product-report"
                                                            name="product-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="product-report"
                                                            name="product-report">
                                                        @endif
                                                        <label for="product-report"
                                                            class="padding05">{{trans('file.Product Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("payment-report", $all_permission))
                                                        <input type="checkbox" value="1" id="payment-report"
                                                            name="payment-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="payment-report"
                                                            name="payment-report">
                                                        @endif
                                                        <label for="payment-report"
                                                            class="padding05">{{trans('file.Payment Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("purchase-report", $all_permission))
                                                        <input type="checkbox" value="1" id="purchase-report"
                                                            name="purchase-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="purchase-report"
                                                            name="purchase-report">
                                                        @endif
                                                        <label for="purchase-report" class="padding05">
                                                            {{trans('file.Purchase Report')}} &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("sale-report-chart", $all_permission))
                                                        <input type="checkbox" value="1" id="sale-report-chart"
                                                            name="sale-report-chart" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="sale-report-chart"
                                                            name="sale-report-chart">
                                                        @endif
                                                        <label for="sale-report-chart"
                                                            class="padding05">{{trans('file.Sale Report Chart')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("warehouse-report", $all_permission))
                                                        <input type="checkbox" value="1" id="warehouse-report"
                                                            name="warehouse-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="warehouse-report"
                                                            name="warehouse-report">
                                                        @endif
                                                        <label for="warehouse-report"
                                                            class="padding05">{{trans('file.Warehouse Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("warehouse-stock-report", $all_permission))
                                                        <input type="checkbox" value="1" id="warehouse-stock-report"
                                                            name="warehouse-stock-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="warehouse-stock-report"
                                                            name="warehouse-stock-report">
                                                        @endif
                                                        <label for="warehouse-stock-report"
                                                            class="padding05">{{trans('file.Warehouse Stock Chart')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("product-expiry-report", $all_permission))
                                                        <input type="checkbox" value="1" id="product-expiry-report"
                                                            name="product-expiry-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="product-expiry-report"
                                                            name="product-expiry-report">
                                                        @endif
                                                        <label for="product-expiry-report"
                                                            class="padding05">{{trans('file.Product Expiry Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("dso-report", $all_permission))
                                                        <input type="checkbox" value="1" id="dso-report"
                                                            name="dso-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="dso-report"
                                                            name="dso-report">
                                                        @endif
                                                        <label for="dso-report"
                                                            class="padding05">{{trans('file.Daily Sale Objective Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("product-qty-alert", $all_permission))
                                                        <input type="checkbox" value="1" id="product-qty-alert"
                                                            name="product-qty-alert" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="product-qty-alert"
                                                            name="product-qty-alert">
                                                        @endif
                                                        <label for="product-qty-alert"
                                                            class="padding05">{{trans('file.Product Quantity Alert')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("user-report", $all_permission))
                                                        <input type="checkbox" value="1" id="user-report"
                                                            name="user-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="user-report"
                                                            name="user-report">
                                                        @endif
                                                        <label for="user-report"
                                                            class="padding05">{{trans('file.User Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("biller-report", $all_permission))
                                                        <input type="checkbox" value="1" id="biller-report"
                                                            name="biller-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="biller-report"
                                                            name="biller-report">
                                                        @endif
                                                        <label for="biller-report"
                                                            class="padding05">{{trans('file.Biller Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("customer-report", $all_permission))
                                                        <input type="checkbox" value="1" id="customer-report"
                                                            name="customer-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="customer-report"
                                                            name="customer-report">
                                                        @endif
                                                        <label for="customer-report"
                                                            class="padding05">{{trans('file.Customer Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("supplier-report", $all_permission))
                                                        <input type="checkbox" value="1" id="supplier-report"
                                                            name="supplier-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="supplier-report"
                                                            name="supplier-report">
                                                        @endif
                                                        <label for="supplier-report"
                                                            class="padding05">{{trans('file.Supplier Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("supplier-due-report", $all_permission))
                                                        <input type="checkbox" value="1" id="supplier-due-report"
                                                            name="supplier-due-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="supplier-due-report"
                                                            name="supplier-due-report">
                                                        @endif
                                                        <label for="supplier-due-report"
                                                            class="padding05">{{trans('file.Supplier Due Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("due-report", $all_permission))
                                                        <input type="checkbox" value="1" id="due-report"
                                                            name="due-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="due-report"
                                                            name="due-report">
                                                        @endif
                                                        <label for="due-report"
                                                            class="padding05">{{trans('file.Customer Due Report')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('file.settings')}}</td>
                                        <td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("custom_field", $all_permission))
                                                        <input type="checkbox" value="1" id="custom_field"
                                                            name="custom_field" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="custom_field"
                                                            name="custom_field">
                                                        @endif
                                                        <label for="custom_field"
                                                            class="padding05">{{trans('file.Custom Field')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("all_notification", $all_permission))
                                                        <input type="checkbox" value="1" id="all_notification"
                                                            name="all_notification" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="all_notification"
                                                            name="all_notification">
                                                        @endif
                                                        <label for="all_notification"
                                                            class="padding05">{{trans('file.All Notification')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("send_notification", $all_permission))
                                                        <input type="checkbox" value="1" id="send_notification"
                                                            name="send_notification" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="send_notification"
                                                            name="send_notification">
                                                        @endif
                                                        <label for="send_notification"
                                                            class="padding05">{{trans('file.Send Notification')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("discount_plan", $all_permission))
                                                        <input type="checkbox" value="1" id="discount_plan"
                                                            name="discount_plan" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="discount_plan"
                                                            name="discount_plan">
                                                        @endif
                                                        <label for="discount_plan"
                                                            class="padding05">{{trans('file.Discount Plan')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("discount", $all_permission))
                                                        <input type="checkbox" value="1" id="discount" name="discount"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="discount" name="discount">
                                                        @endif
                                                        <label for="discount"
                                                            class="padding05">{{trans('file.Discount')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("warehouse", $all_permission))
                                                        <input type="checkbox" value="1" id="warehouse" name="warehouse"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="warehouse"
                                                            name="warehouse">
                                                        @endif
                                                        <label for="warehouse"
                                                            class="padding05">{{trans('file.Warehouse')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("customer_group", $all_permission))
                                                        <input type="checkbox" value="1" id="customer_group"
                                                            name="customer_group" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="customer_group"
                                                            name="customer_group">
                                                        @endif
                                                        <label for="customer_group"
                                                            class="padding05">{{trans('file.Customer Group')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("brand", $all_permission))
                                                        <input type="checkbox" value="1" id="brand" name="brand"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="brand" name="brand">
                                                        @endif
                                                        <label for="brand" class="padding05">{{trans('file.Brand')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("unit", $all_permission))
                                                        <input type="checkbox" value="1" id="unit" name="unit" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="unit" name="unit">
                                                        @endif
                                                        <label for="unit" class="padding05">{{trans('file.Unit')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("currency", $all_permission))
                                                        <input type="checkbox" value="1" id="currency" name="currency"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="currency" name="currency">
                                                        @endif
                                                        <label for="currency"
                                                            class="padding05">{{trans('file.Currency')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("tax", $all_permission))
                                                        <input type="checkbox" value="1" id="tax" name="tax" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="tax" name="tax">
                                                        @endif
                                                        <label for="tax" class="padding05">{{trans('file.Tax')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("backup_database", $all_permission))
                                                        <input type="checkbox" value="1" id="backup_database"
                                                            name="backup_database" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="backup_database"
                                                            name="backup_database">
                                                        @endif
                                                        <label for="backup_database"
                                                            class="padding05">{{trans('file.Backup Database')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("general_setting", $all_permission))
                                                        <input type="checkbox" value="1" id="general_setting"
                                                            name="general_setting" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="general_setting"
                                                            name="general_setting">
                                                        @endif
                                                        <label for="general_setting"
                                                            class="padding05">{{trans('file.General Setting')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("mail_setting", $all_permission))
                                                        <input type="checkbox" value="1" id="mail_setting"
                                                            name="mail_setting" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="mail_setting"
                                                            name="mail_setting">
                                                        @endif
                                                        <label for="mail_setting"
                                                            class="padding05">{{trans('file.Mail Setting')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("sms_setting", $all_permission))
                                                        <input type="checkbox" value="1" id="sms_setting"
                                                            name="sms_setting" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="sms_setting"
                                                            name="sms_setting">
                                                        @endif
                                                        <label for="sms_setting"
                                                            class="padding05">{{trans('file.SMS Setting')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("create_sms", $all_permission))
                                                        <input type="checkbox" value="1" id="create_sms"
                                                            name="create_sms" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="create_sms"
                                                            name="create_sms">
                                                        @endif
                                                        <label for="create_sms"
                                                            class="padding05">{{trans('file.Create SMS')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("pos_setting", $all_permission))
                                                        <input type="checkbox" value="1" id="pos_setting"
                                                            name="pos_setting" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="pos_setting"
                                                            name="pos_setting">
                                                        @endif
                                                        <label for="pos_setting"
                                                            class="padding05">{{trans('file.POS Setting')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span class="hrm-setting-section">
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("hrm_setting", $all_permission))
                                                        <input type="checkbox" value="1" id="hrm_setting"
                                                            name="hrm_setting" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="hrm_setting"
                                                            name="hrm_setting">
                                                        @endif
                                                        <label for="hrm_setting"
                                                            class="padding05">{{trans('file.HRM Setting')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("reward_point_setting", $all_permission))
                                                        <input type="checkbox" value="1" id="reward_point_setting"
                                                            name="reward_point_setting" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="reward_point_setting"
                                                            name="reward_point_setting">
                                                        @endif
                                                        <label for="reward_point_setting"
                                                            class="padding05">{{trans('file.Reward Point Setting')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{trans('file.Miscellaneous')}}</td>
                                        <td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("category", $all_permission))
                                                        <input type="checkbox" value="1" id="category" name="category"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="category" name="category">
                                                        @endif
                                                        <label for="category"
                                                            class="padding05">{{trans('file.category')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span class="packing-slip-challan-section">
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("packing_slip_challan", $all_permission))
                                                        <input type="checkbox" value="1" id="packing_slip_challan"
                                                            name="packing_slip_challan" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="packing_slip_challan"
                                                            name="packing_slip_challan">
                                                        @endif
                                                        <label for="packing_slip_challan"
                                                            class="padding05">{{trans('file.Packing Slip & Challan')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span class="delivery-section">
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("delivery", $all_permission))
                                                        <input type="checkbox" value="1" id="delivery" name="delivery"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="delivery" name="delivery">
                                                        @endif
                                                        <label for="delivery"
                                                            class="padding05">{{trans('file.Delivery')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span class="stock-count-section">
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("stock_count", $all_permission))
                                                        <input type="checkbox" value="1" id="stock_count"
                                                            name="stock_count" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="stock_count"
                                                            name="stock_count">
                                                        @endif
                                                        <label for="stock_count"
                                                            class="padding05">{{trans('file.Stock Count')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("gift_card", $all_permission))
                                                        <input type="checkbox" value="1" id="gift_card" name="gift_card"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="gift_card"
                                                            name="gift_card">
                                                        @endif
                                                        <label for="gift_card"
                                                            class="padding05">{{trans('file.Gift Card')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("coupon", $all_permission))
                                                        <input type="checkbox" value="1" id="coupon" name="coupon"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="coupon" name="coupon">
                                                        @endif
                                                        <label for="coupon" class="padding05">{{trans('file.Coupon')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("product_history", $all_permission))
                                                        <input type="checkbox" value="1" id="product_history"
                                                            name="product_history" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="product_history"
                                                            name="product_history">
                                                        @endif
                                                        <label for="product_history"
                                                            class="padding05">{{trans('file.Product History')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("print_barcode", $all_permission))
                                                        <input type="checkbox" value="1" id="print_barcode"
                                                            name="print_barcode" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="print_barcode"
                                                            name="print_barcode">
                                                        @endif
                                                        <label for="print_barcode"
                                                            class="padding05">{{trans('file.print_barcode')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("empty_database", $all_permission))
                                                        <input type="checkbox" value="1" id="empty_database"
                                                            name="empty_database" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="empty_database"
                                                            name="empty_database">
                                                        @endif
                                                        <label for="empty_database"
                                                            class="padding05">{{trans('file.Empty Database')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("today_sale", $all_permission))
                                                        <input type="checkbox" value="1" id="today_sale"
                                                            name="today_sale" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="today_sale"
                                                            name="today_sale">
                                                        @endif
                                                        <label for="today_sale"
                                                            class="padding05">{{trans('file.Today Sale')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("today_profit", $all_permission))
                                                        <input type="checkbox" value="1" id="today_profit"
                                                            name="today_profit" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="today_profit"
                                                            name="today_profit">
                                                        @endif
                                                        <label for="today_profit"
                                                            class="padding05">{{trans('file.Today Profit')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td rowspan="2">{{trans('file.Order')}}</td>
                                        <td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("unpaid", $all_permission))
                                                        <input type="checkbox" value="1" id="unpaid" name="unpaid"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="unpaid" name="unpaid">
                                                        @endif
                                                        <label for="unpaid" class="padding05">Unpaid
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("confirmed", $all_permission))
                                                        <input type="checkbox" value="1" id="confirmed" name="confirmed"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="confirmed"
                                                            name="confirmed">
                                                        @endif
                                                        <label for="confirmed" class="padding05">Confirmed
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("receiving", $all_permission))
                                                        <input type="checkbox" value="1" id="receiving" name="receiving"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="receiving"
                                                            name="receiving">
                                                        @endif
                                                        <label for="receiving" class="padding05">Receiving
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("shipped", $all_permission))
                                                        <input type="checkbox" value="1" id="shipped" name="shipped"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="shipped" name="shipped">
                                                        @endif
                                                        <label for="shipped" class="padding05">Shipped
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("signed", $all_permission))
                                                        <input type="checkbox" value="1" id="signed" name="signed"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="signed" name="signed">
                                                        @endif
                                                        <label for="signed" class="padding05">Signed
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("refunded", $all_permission))
                                                        <input type="checkbox" value="1" id="refunded" name="refunded"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="refunded" name="refunded">
                                                        @endif
                                                        <label for="refunded" class="padding05">Refunded
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("cancelled", $all_permission))
                                                        <input type="checkbox" value="1" id="cancelled" name="cancelled"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="cancelled"
                                                            name="cancelled">
                                                        @endif
                                                        <label for="cancelled" class="padding05">Cancelled
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("returned", $all_permission))
                                                        <input type="checkbox" value="1" id="returned" name="returned"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="returned" name="returned">
                                                        @endif
                                                        <label for="returned" class="padding05">Returned
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
									<tr>
										<td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("sales-import", $all_permission))
                                                        <input type="checkbox" value="1" id="sales-import" name="sales-import"
                                                            checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="sales-import" name="sales-import">
                                                        @endif
                                                        <label for="sales-import" class="padding05">Import Excel
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
											<span>
												<div aria-checked="false" aria-disabled="false">
													<div class="checkbox">
														@if(in_array("unpaid-confirm", $all_permission))
														<input type="checkbox" value="1" id="unpaid-confirm"
															name="unpaid-confirm" checked>
														@else
														<input type="checkbox" value="1" id="unpaid-confirm"
															name="unpaid-confirm">
														@endif
														<label for="unpaid-confirm" class="padding05">Unpaid Confirm
															&nbsp;&nbsp;</label>
													</div>
												</div>
											</span>
											<span>
												<div aria-checked="false" aria-disabled="false">
													<div class="checkbox">
														@if(in_array("unpaid-edit", $all_permission))
														<input type="checkbox" value="1" id="unpaid-edit"
															name="unpaid-edit" checked>
														@else
														<input type="checkbox" value="1" id="unpaid-edit"
															name="unpaid-edit">
														@endif
														<label for="unpaid-edit" class="padding05">Unpaid Edit
															&nbsp;&nbsp;</label>
													</div>
												</div>
											</span>
											<span>
												<div aria-checked="false" aria-disabled="false">
													<div class="checkbox">
														@if(in_array("unpaid-cancel", $all_permission))
														<input type="checkbox" value="1" id="unpaid-cancel"
															name="unpaid-cancel" checked>
														@else
														<input type="checkbox" value="1" id="unpaid-cancel"
															name="unpaid-cancel">
														@endif
														<label for="unpaid-cancel" class="padding05">Unpaid Cancel
															&nbsp;&nbsp;</label>
													</div>
												</div>
											</span>
											<span>
												<div aria-checked="false" aria-disabled="false">
													<div class="checkbox">
														@if(in_array("return-receiving", $all_permission))
														<input type="checkbox" value="1" id="return-receiving"
															name="return-receiving" checked>
														@else
														<input type="checkbox" value="1" id="return-receiving"
															name="return-receiving">
														@endif
														<label for="return-receiving" class="padding05">Returned Receiving
															&nbsp;&nbsp;</label>
													</div>
												</div>
											</span>
                                            <span>
												<div aria-checked="false" aria-disabled="false">
													<div class="checkbox">
														@if(in_array("shipped-sign", $all_permission))
														<input type="checkbox" value="1" id="shipped-sign"
															name="shipped-sign" checked>
														@else
														<input type="checkbox" value="1" id="shipped-sign"
															name="shipped-sign">
														@endif
														<label for="shipped-sign" class="padding05">Sign Shipped
															&nbsp;&nbsp;</label>
													</div>
												</div>
											</span>
                                            <span>
												<div aria-checked="false" aria-disabled="false">
													<div class="checkbox">
														@if(in_array("shipped-return", $all_permission))
														<input type="checkbox" value="1" id="shipped-return"
															name="shipped-return" checked>
														@else
														<input type="checkbox" value="1" id="shipped-return"
															name="shipped-return">
														@endif
														<label for="shipped-return" class="padding05">Return Shipped
															&nbsp;&nbsp;</label>
													</div>
												</div>
											</span>
										</td>
									</tr>
                                    <tr class="sale-report-row">
                                        <td>{{trans('Order Reporting')}}</td>
                                        <td class="report-permissions" colspan="5">
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("sale-report", $all_permission))
                                                        <input type="checkbox" value="1" id="sale-report"
                                                            name="sale-report" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="sale-report"
                                                            name="sale-report">
                                                        @endif
                                                        <label for="sale-report"
                                                            class="padding05">{{trans('Sale Report View')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("sale-report-edit", $all_permission))
                                                        <input type="checkbox" value="1" id="sale-report-edit"
                                                            name="sale-report-edit" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="sale-report-edit"
                                                            name="sale-report-edit">
                                                        @endif
                                                        <label for="sale-report-edit"
                                                            class="padding05">{{trans('Sale Report Edit')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                            <span>
                                                <div aria-checked="false" aria-disabled="false">
                                                    <div class="checkbox">
                                                        @if(in_array("sale-report-delete", $all_permission))
                                                        <input type="checkbox" value="1" id="sale-report-delete"
                                                            name="sale-report-delete" checked>
                                                        @else
                                                        <input type="checkbox" value="1" id="sale-report-delete"
                                                            name="sale-report-delete">
                                                        @endif
                                                        <label for="sale-report-delete"
                                                            class="padding05">{{trans('Sale Report Delete')}}
                                                            &nbsp;&nbsp;</label>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="form-group">
                            <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
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
$("ul#setting").siblings('a').attr('aria-expanded', 'true');
$("ul#setting").addClass("show");
$("ul#setting #role-menu").addClass("active");

@if(config('database.connections.saleprosaas_landlord'))
$.ajax({
    type: 'GET',
    async: false,
    url: '{{route("package.fetchData", $general_setting->package_id)}}',
    success: function(data) {
        features = data['features'];
        if (!features.includes("sale_return"))
            $("tr.sale-return-row").addClass('d-none');
        if (!features.includes("purchase_return"))
            $("tr.purchase-return-row").addClass('d-none');
        if (!features.includes("expense"))
            $("tr.expense-row").addClass('d-none');
        if (!features.includes("quotation"))
            $("tr.quotation-row").addClass('d-none');
        if (!features.includes("transfer"))
            $("tr.transfer-row").addClass('d-none');
        if (!features.includes("delivery"))
            $("span.delivery-section").addClass('d-none');
        if (!features.includes("stock_count_and_adjustment")) {
            $("span.stock-count-section").addClass('d-none');
            $("span.adjustment-section").addClass('d-none');
        }
        if (!features.includes("report"))
            $("tr.report-row").addClass('d-none');
        if (!features.includes("accounting"))
            $("tr.accounting-row").addClass('d-none');
        if (!features.includes("hrm")) {
            $("tr.employee-row").addClass('d-none');
            $("tr.hrm-row").addClass('d-none');
            $("span.hrm-setting-section").addClass('d-none');
        }
    }
});
@endif

$("#select_all").on("change", function() {
    if ($(this).is(':checked')) {
        $("tbody input[type='checkbox']").prop('checked', true);
    } else {
        $("tbody input[type='checkbox']").prop('checked', false);
    }
});
</script>
@endpush