@extends('backend.layout.main')
@section('content')

    @if(session()->has('create_message'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert"
                aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('create_message') }}</div>
    @endif
    @if(session()->has('edit_message'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert"
                aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('edit_message') }}</div>
    @endif
    @if(session()->has('import_message'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert"
                aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('import_message') }}</div>
    @endif
    @if(session()->has('not_permitted'))
        <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert"
                aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
    @endif
    @if(session()->has('message'))
        <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert"
                aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
    @endif

    <section>
        <div class="container-fluid">
            @if(in_array("products-add", $all_permission))
                <a href="{{route('products.create')}}" class="btn btn-info add-product-btn"><i class="dripicons-plus"></i>
                    {{__('file.add_product')}}</a>
                <!-- <a href="#" data-toggle="modal" data-target="#importProduct" class="btn btn-primary add-product-btn"><i class="dripicons-copy"></i> {{__('file.import_product')}}</a> -->

            @endif
            @if(in_array("products-edit", $all_permission) && in_array('ecommerce', explode(',', $general_setting->modules)))
                <a href="{{route('product.allProductInStock')}}" class="btn btn-dark add-product-btn"><i
                        class="dripicons-stack"></i> {{__('file.All Product In Stock')}}</a>
                <a href="{{route('product.showAllProductOnline')}}" class="btn btn-dark add-product-btn"><i
                        class="dripicons-wifi"></i> {{__('file.Show All Product Online')}}</a>
            @endif
            <div class="card mt-3 hidden">
                <!-- <h3 class="text-center mt-3">{{trans('file.Filter Products')}}</h3> -->
                <div class="card-body">
                    {!! Form::open(['route' => 'products.index', 'method' => 'get']) !!}
                    <div class="row">
                        <div class="col-md-3 offset-3 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                            <div class="form-group">
                                <!-- <label><strong>{{trans('file.Warehouse')}}</strong></label> -->
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0">{{trans('file.All Warehouse')}}</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <button class="btn btn-primary" id="filter-btn"
                                    type="submit">{{trans('file.submit')}}</button>
                            </div>
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table id="product-data-table" class="table" style="width: 100%">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{trans('file.Image')}}</th>
                        <th>{{trans('file.name')}}</th>
                        <th>{{trans('file.Code')}}</th>
                        <th>{{trans('Delivering Qty')}}</th>
                        <th>{{trans('file.Sold Qty')}}</th>
                        <th>{{trans('file.Stock Quantity')}}</th>
                        <th>{{trans('Volume')}}</th>
                        <th>{{trans('file.Price')}}</th>
                        <th>{{trans('file.Supplier')}}</th>
                        
                        <!-- <th>{{trans('file.Brand')}}</th>
                        <th>{{trans('file.category')}}</th> -->
                        @foreach($custom_fields as $fieldName)
                            <th>{{$fieldName}}</th>
                        @endforeach
                        <th class="not-exported">{{trans('file.action')}}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </section>

    <div id="importProduct" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                {!! Form::open(['route' => 'product.import', 'method' => 'post', 'files' => true]) !!}
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">Import Product</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i
                                class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <p class="italic">
                        <small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                    <p>{{trans('file.The correct column order is')}} (image, name*, code*, type*, unit_code*, cost*, price*,
                        product_details, variant_name, item_code, additional_price)
                        {{trans('file.and you must follow this')}}.</p>
                    <p>{{trans('file.To display Image it must be stored in')}} images/product {{trans('file.directory')}}.
                        {{trans('file.Image name must be same as product name')}}</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Upload CSV File')}} *</label>
                                {{Form::file('file', array('class' => 'form-control', 'required'))}}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label> {{trans('file.Sample File')}}</label>
                                <a href="sample_file/sample_products.csv" class="btn btn-info btn-block btn-md"><i
                                        class="dripicons-download"></i> {{trans('file.Download')}}</a>
                            </div>
                        </div>
                    </div>
                    {{Form::submit('Submit', ['class' => 'btn btn-primary'])}}
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    <div id="product-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{trans('Product Details')}}</h5>
                    <button id="print-btn" type="button" class="btn btn-default btn-sm ml-3"><i class="dripicons-print"></i>
                        {{trans('file.Print')}}</button>
                    <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span
                            aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5" id="slider-content"></div>
                        <div class="col-md-5 offset-1" id="product-content"></div>
                        @if($role_id <= 2)
                            <div class="col-md-12 mt-2" id="product-warehouse-section">
                                <h5>{{trans('file.Warehouse Quantity')}}</h5>
                                <table class="table table-bordered table-hover product-warehouse-list">
                                    <thead>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        <div class="col-md-7 mt-2" id="product-variant-section">
                            <h5>{{trans('file.Product Variant Information')}}</h5>
                            <table class="table table-bordered table-hover product-variant-list">
                                <thead>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        @if($role_id <= 2)
                            <div class="col-md-5 mt-2" id="product-variant-warehouse-section">
                                <h5>{{trans('file.Warehouse quantity of product variants')}}</h5>
                                <table class="table table-bordered table-hover product-variant-warehouse-list">
                                    <thead>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <h5 id="combo-header"></h5>
                    <table class="table table-bordered table-hover item-list">
                        <thead>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>

        $("ul#product").siblings('a').attr('aria-expanded', 'true');
        $("ul#product").addClass("show");
        $("ul#product #product-list-menu").addClass("active");

        @if(config('database.connections.saleprosaas_landlord'))
            if (localStorage.getItem("message")) {
                alert(localStorage.getItem("message"));
                localStorage.removeItem("message");
            }

            numberOfProduct = <?php    echo json_encode($numberOfProduct)?>;
            $.ajax({
                type: 'GET',
                async: false,
                url: '{{route("package.fetchData", $general_setting->package_id)}}',
                success: function (data) {
                    if (data['number_of_product'] > 0 && data['number_of_product'] <= numberOfProduct) {
                        $("a.add-product-btn").addClass('d-none');
                    }
                }
            });
        @endif

        function confirmDelete() {
            if (confirm("Are you sure want to delete?")) {
                return true;
            }
            return false;
        }

        var columns = [
            { "data": "key" },
            { "data": "image" }, 
            { "data": "name" }, 
            { "data": "code" }, 
            { "data" : "delivery_qty"}, // added 6.20
            { "data" : "sold_qty"}, // added 6.20
            { "data": "qty" }, 
            { "data": "volume" }, 
            { "data": "price" }, 
            { "data": "supplier_name" },
        ];
        var field_name = <?php echo json_encode($field_name) ?>;
        for (i = 0; i < field_name.length; i++) {
            columns.push({ "data": field_name[i] });
        }
        columns.push({ "data": "options" });

        var warehouse = [];
        var variant = [];
        var qty = [];
        var htmltext;
        var slidertext;
        var product_id = [];
        var all_permission = <?php echo json_encode($all_permission) ?>;
        var role_id = <?php echo json_encode($role_id) ?>;
        var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;
        var logoUrl = <?php echo json_encode(url('logo', $general_setting->site_logo)) ?>;
        var warehouse_id = <?php echo json_encode($warehouse_id); ?>;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#warehouse_id").val(warehouse_id);

        $("#select_all").on("change", function () {
            if ($(this).is(':checked')) {
                $("tbody input[type='checkbox']").prop('checked', true);
            }
            else {
                $("tbody input[type='checkbox']").prop('checked', false);
            }
        });

        $(document).on("click", "tr.product-link td:not(:first-child, :last-child)", function () {
            productDetails($(this).parent().data('product'), $(this).parent().data('imagedata'));
        });

        $(document).on("click", ".view", function () {
            var product = $(this).parent().parent().parent().parent().parent().data('product');
            var imagedata = $(this).parent().parent().parent().parent().parent().data('imagedata');
            productDetails(product, imagedata);
        });

        $("#print-btn").on("click", function () {
            var divToPrint = document.getElementById('product-details');
            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
            newWin.document.write('<link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css"><style type="text/css">@media print {.modal-dialog { max-width: 1000px;} }</style><body onload="window.print()">' + divToPrint.innerHTML + '</body>');
            newWin.document.close();
            setTimeout(function () { newWin.close(); }, 10);
        });

        function productDetails(product, imagedata) {
            var slidertext = '';
            var htmltext = 
            `
                <p class="m-t-1"></p>
                <p><strong>{{trans("Product Name")}}: </strong> ${product['name']}</p>
                <p><strong>{{trans("Product Code")}}: </strong>  ${product['code']} </p>
                <p><strong>{{trans("Delivery Qty")}}: </strong>  ${product['delivery_qty']} </p>
                <p><strong>{{trans("Sold Qty")}}: </strong>  ${product['sold_qty']} </p>
                <p><strong>{{trans("Stock Quantity")}}: </strong>  ${product['stock']} </p>
                <p><strong>{{trans("Volume")}}: </strong>  ${product['volume']} </p>
                <p><strong>{{trans("Price")}}: </strong>  ${product['price']} </p>
                <p><strong>{{trans("Supplier")}}: </strong>  ${product['supplier']} </p>
            `;
            // alert(product[20]);
            if (product['stock']) {
                var product_image = product['image'].split(",");
                if (product_image.length > 1) {
                    slidertext = '<div id="product-img-slider" class="carousel slide" data-ride="carousel"><div class="carousel-inner">';
                    for (var i = 0; i < product_image.length; i++) {
                        if (!i)
                            slidertext += '<div class="carousel-item active"><img src="images/product/' + product_image[i] + '" height="400" width="100%"></div>';
                        else
                            slidertext += '<div class="carousel-item"><img src="images/product/' + product_image[i] + '" height="400" width="100%"></div>';
                    }
                    slidertext += '</div><a class="carousel-control-prev" href="#product-img-slider" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a><a class="carousel-control-next" href="#product-img-slider" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a></div>';
                }
                else {
                    slidertext = '<img src="images/product/' + product['image'] + '" height="300" width="100%">';
                }
            }
            else {
                slidertext = '<img src="images/product/zummXD2dvAtI.png" height="300" width="100%">';
            }
            
            $("#combo-header").text('');
            $("table.item-list thead").remove();
            $("table.item-list tbody").remove();
            $("table.product-warehouse-list thead").remove();
            $("table.product-warehouse-list tbody").remove();
            $(".product-variant-list thead").remove();
            $(".product-variant-list tbody").remove();
            $(".product-variant-warehouse-list thead").remove();
            $(".product-variant-warehouse-list tbody").remove();
            $("#product-warehouse-section").addClass('d-none');
            $("#product-variant-section").addClass('d-none');
            $("#product-variant-warehouse-section").addClass('d-none');

            $('#product-content').html(htmltext);
            $('#slider-content').html(slidertext);
            $('#product-details').modal('show');
            $('#product-img-slider').carousel(0);
        }

        $(document).ready(function () {
            var table = $('#product-data-table').DataTable({
                responsive: true,
                fixedHeader: {
                    header: true,
                    footer: true
                },
                "processing": true,
                "serverSide": true,
                "ajax": {
                    url: "products/product-data",
                    data: {
                        all_permission: all_permission,
                        warehouse_id: warehouse_id
                    },
                    dataType: "json",
                    type: "post"
                },
                "createdRow": function (row, data, dataIndex) {
                    $(row).addClass('product-link');
                    $(row).attr('data-product', data['product']);
                    // $(row).attr('data-imagedata', data['imagedata']);
                },
                "columns": columns,
                'language': {
                    'searchPlaceholder': "{{trans('file.Type Product Name or Code...')}}",
                    'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
                    "info": '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                    "search": '{{trans("file.Search")}}',
                    'paginate': {
                        'previous': '<i class="dripicons-chevron-left"></i>',
                        'next': '<i class="dripicons-chevron-right"></i>'
                    }
                },
                order: [['2', 'asc']],
                'columnDefs': [
                    {
                        "orderable": false,
                        'targets': [0, 1, 4, 5, 10]
                    },
                    {
                        'render': function (data, type, row, meta) {
                            if (type === 'display') {
                                data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                            }

                            return data;
                        },
                        'checkboxes': {
                            'selectRow': false,
                            'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                        },
                        'targets': [0]
                    }
                ],
                'select': { style: 'multi', selector: 'td:first-child' },
                'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
                dom: '<"row"lfB>rtip',
                buttons: [
                    {
                        extend: 'pdf',
                        text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                        exportOptions: {
                            columns: ':visible:not(.not-exported)',
                            rows: ':visible',
                            stripHtml: false
                        },
                        customize: function (doc) {
                            for (var i = 1; i < doc.content[1].table.body.length; i++) {
                                if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                                    var imagehtml = doc.content[1].table.body[i][0].text;
                                    var regex = /<img.*?src=['"](.*?)['"]/;
                                    var src = regex.exec(imagehtml)[1];
                                    var tempImage = new Image();
                                    tempImage.src = src;
                                    var canvas = document.createElement("canvas");
                                    canvas.width = tempImage.width;
                                    canvas.height = tempImage.height;
                                    var ctx = canvas.getContext("2d");
                                    ctx.drawImage(tempImage, 0, 0);
                                    var imagedata = canvas.toDataURL("image/png");
                                    delete doc.content[1].table.body[i][0].text;
                                    doc.content[1].table.body[i][0].image = imagedata;
                                    doc.content[1].table.body[i][0].fit = [30, 30];
                                }
                            }
                        },
                    },
                    {
                        extend: 'excel',
                        text: '<i title="export to excel" class="dripicons-document-new"></i>',
                        exportOptions: {
                            columns: ':visible:not(.not-exported)',
                            rows: ':visible',
                            format: {
                                body: function (data, row, column, node) {
                                    if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                        var regex = /<img.*?src=['"](.*?)['"]/;
                                        data = regex.exec(data)[1];
                                    }
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'csv',
                        text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                        exportOptions: {
                            columns: ':visible:not(.not-exported)',
                            rows: ':visible',
                            format: {
                                body: function (data, row, column, node) {
                                    if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                        var regex = /<img.*?src=['"](.*?)['"]/;
                                        data = regex.exec(data)[1];
                                    }
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'print',
                        title: '',
                        text: '<i title="print" class="fa fa-print"></i>',
                        exportOptions: {
                            columns: ':visible:not(.not-exported)',
                            rows: ':visible',
                            stripHtml: false
                        },
                        repeatingHead: {
                            logo: logoUrl,
                            logoPosition: 'left',
                            logoStyle: '',
                            title: '<h3>Product List</h3>'
                        }
                    }, 
                    {
                        extend: 'colvis',
                        text: '<i title="column visibility" class="fa fa-eye"></i>',
                        columns: ':gt(0)'
                    },
                ],
            });            
        });

        if (all_permission.indexOf("products-delete") == -1)
            $('.buttons-delete').addClass('d-none');

        $('select').selectpicker();

    </script>
@endpush