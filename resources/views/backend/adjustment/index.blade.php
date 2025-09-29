@extends('backend.layout.main')
@section('content')
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close"
                data-dismiss="alert" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
    @endif
    @if (session()->has('not_permitted'))
        <div class="alert alert-danger alert-dismissible text-center">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}
        </div>
    @endif

    <section>
        <div class="container-fluid">
            @if (in_array('inbound-add', $all_permission) && $product_adjust_action == 'inbound')
                <a href="#" class="btn btn-info btn-add-adjust"><i class="dripicons-plus"></i>
                    {{ __('Add Inbound') }}</a>&nbsp;
            @endif
            @if (in_array('outbound-add', $all_permission) && $product_adjust_action == 'outbound')
                <a href="#" class="btn btn-info btn-add-adjust"><i class="dripicons-plus"></i>
                    {{ __('Add Outbound') }}</a>&nbsp;
            @endif
            <div class="card mt-3">
                <div class="card-body">
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><strong>{{ trans('file.Date') }}</strong></label>
                                <input type="text" class="daterangepicker-field form-control"
                                    value="{{ $start_date }} To {{ $end_date }}" />
                                <input type="hidden" name="start_date" value="{{ $start_date }}" />
                                <input type="hidden" name="end_date" value="{{ $end_date }}" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><strong>{{ trans('file.Supplier') }}</strong></label>
                                <select id="supplier-id" class="form-control" name="supplier_id">
                                    <option value="0">All</option>
                                    @foreach ($lims_supplier_list as $supplier)
                                        <option value="{{ $supplier->id }}">
                                            {{ $supplier->name }}({{ $supplier->phone_number }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Search</strong></label><br>
                                <button class="btn btn-primary" id="filter-btn"
                                    type="button">{{ trans('file.submit') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table id="adjustment-table" class="table purchase-list" style="width:100%;">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ trans('Image') }}</th>
                        <th>{{ trans('Product Name') }}</th>
                        <th>{{ trans('Product Code') }}</th>
                        <th>{{ trans('file.Warehouse') }}</th>
                        <th>{{ trans('Quantity') }}</th>
                        <th>{{ trans('Supplier') }}</th>
                        <th>{{ trans('Note') }}</th>
                        <th>{{ trans('file.Date') }}</th>
                        <th class="not-exported">{{ trans('Action') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div id="modal-add-adjust" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="editSaleLabel" class="modal-title">
                            @if ($product_adjust_action == 'inbound')
                                Add Inbound
                            @else
                                Add Outbound
                            @endif
                        </h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <iframe src="" style="border:none;width:100%; height: 850px;"
                            id="adjustment-iframe"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success btn-md" id="submit-inbound-form"> Confirm </button>
                        <button class="btn btn-dard btn-md" data-dismiss="modal" class="close"> Close </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-update-adjust" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="editSaleLabel" class="modal-title">
                            @if ($product_adjust_action == 'inbound')
                                Edit Inbound
                            @else
                                Edit Outbound
                            @endif
                        </h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <div class="product-modal-container">
                            <div class="product-image"></div>
                            <div class="product-details">
                                <h2 class="product-title"></h2>
                                <p class="product-code"><strong>Code:</strong> </p>
                                <p class="product-warehouse"><strong>Warehouse:</strong> </p>
                                <p class="product-supplier"><strong>Supplier:</strong> Electronics</p>
                                <p class="product-update"><strong>Updated Date:</strong> </p>

                                <div class="qty-section">
                                    <label for="qty"><strong>Quantity:</strong></label>
                                    <input type="number" id="adjust_qty" name="qty" value="1" min="1">
                                    <input type="hidden" id="adjust_id" value="" />
                                </div>
                                <div class="reason-section mt-3">
                                    <label for="adjust_reason"><strong>Reason:</strong></label>
                                    <textarea id="adjust_reason" name="reason" rows="3" class="form-control" placeholder="Add reason..."></textarea>
                                </div>
                            </div>
                        </div>
                        <style>
                            .product-modal-container {
                                display: flex;
                                gap: 20px;
                                padding: 20px;
                                align-items: flex-start;
                                flex-wrap: wrap;
                            }

                            .product-image img {
                                width: 150px;
                                height: auto;
                                border-radius: 8px;
                                border: 1px solid #ccc;
                            }

                            .product-details {
                                flex: 1;
                                margin-left: 30px;
                                min-width: 200px;
                            }

                            .product-title {
                                font-size: 1.6rem;
                                color: #333;
                                margin-top: 0;
                                margin-bottom: 10px;
                            }

                            .product-details p {
                                margin: 5px 0;
                                font-size: 14px;
                            }

                            .qty-section {
                                margin-top: 15px;
                                display: flex;
                                align-items: center;
                                gap: 10px;
                            }

                            .qty-section input[type="number"] {
                                width: 120px;
                                padding: 4px 8px;
                                border-radius: 4px;
                                border: 1px solid #ccc;
                            }
                        </style>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success btn-md" id="submit-edit-form"> Save </button>
                        <button class="btn btn-dard btn-md" data-dismiss="modal" class="close"> Discard </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script type="text/javascript">
        $("ul#product").siblings('a').attr('aria-expanded', 'true');
        $("ul#product").addClass("show");

        $("ul#product #{{ $product_adjust_action }}-list-menu").addClass("active");

        var adjustment_id = [];
        var user_verified = <?php echo json_encode(env('USER_VERIFIED')); ?>;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function edit(el) {
            var data = JSON.parse(el.getAttribute('data-adjustment'));

            $('.product-image').html(data['product_img']);
            $('.product-title').text(data['product_name']);
            $('.product-code strong').after(data['product_code']);
            $('.product-warehouse strong').after(data['warehouse_name']);
            $('.product-supplier strong').after(data['supplier_name']);
            $('.product-update strong').after(data['updated_at']);
            $('#adjust_qty').val(data['adjust_qty'])
            $('#adjust_id').val(data['id']);
            // populate reason if present
            $('#adjust_reason').val(data['reason'] || '');
            $('#modal-update-adjust').modal('show');
        }

        function remove(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `${id}`,
                        type: "DELETE",
                        success: function(response) {
                            if (response.code == 200) {
                                toastr.success(response.msg);
                                $('#adjustment-table').DataTable().ajax.reload();
                            }
                        }
                    });
                }
            });
        }

        $(document).ready(function() {

            $(".daterangepicker-field").daterangepicker({
                callback: function(startDate, endDate, period) {
                    var starting_date = startDate.format('YYYY-MM-DD');
                    var ending_date = endDate.format('YYYY-MM-DD');
                    var title = starting_date + ' To ' + ending_date;
                    $(this).val(title);
                    $('input[name=start_date]').val(starting_date);
                    $('input[name=end_date]').val(ending_date);
                }
            });

            let table = $('#adjustment-table').DataTable({
                "processing": true,
                "serverSide": true,
                ajax: {
                    url: "get_table",
                    type: 'POST',
                    data: function(p) {
                        p.supplier_id = $('#supplier-id').val();
                        p.adjustment_action = "{{ $product_adjust_action == 'inbound' ? '+' : '-' }}";
                        p.start_date = $("input[name=start_date]").val();
                        p.end_date = $("input[name=end_date]").val();
                    },
                    dataType: "json"
                },
                columns: [{
                        data: 'key'
                    },
                    {
                        data: 'product_img'
                    },
                    {
                        data: 'product_name'
                    },
                    {
                        data: 'product_code'
                    },
                    {
                        data: 'warehouse_name'
                    },
                    {
                        data: 'adjust_qty'
                    },
                    {
                        data: 'supplier_name'
                    },
                    {
                        data: 'reason'
                    },
                    {
                        data: 'updated_at'
                    },
                    {
                        data: 'actions'
                    },
                ],
                "createdRow": function(row, data, dataIndex) {
                    $(row).attr('data-adjustment', data['json']);
                },
                "order": [],
                'language': {
                    'lengthMenu': '_MENU_ {{ trans('file.records per page') }}',
                    "info": '<small>{{ trans('file.Showing') }} _START_ - _END_ (_TOTAL_)</small>',
                    "search": '{{ trans('file.Search') }}',
                    'paginate': {
                        'previous': '<i class="dripicons-chevron-left"></i>',
                        'next': '<i class="dripicons-chevron-right"></i>'
                    }
                },
                'columnDefs': [{
                        "orderable": false,
                        'targets': [0, 1, 2, 3, 4, 6, 8]
                    },
                    {
                        'checkboxes': {
                            'selectRow': true
                        },
                        'targets': 0
                    }
                ],
                'select': {
                    style: 'multi',
                    selector: 'td:first-child'
                },
                'lengthMenu': [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                dom: '<"row"lfB>rtip',
                buttons: [{
                        extend: 'pdf',
                        text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                        footer: true
                    },
                    {
                        extend: 'excel',
                        text: '<i title="export to excel" class="dripicons-document-new"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                        footer: true
                    },
                    {
                        extend: 'csv',
                        text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                        footer: true
                    },
                    {
                        extend: 'print',
                        text: '<i title="print" class="fa fa-print"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                        footer: true
                    },
                    {
                        extend: 'colvis',
                        text: '<i title="column visibility" class="fa fa-eye"></i>',
                        columns: ':gt(0)'
                    },
                ],
            });

            $('#filter-btn').on('click', function() {
                table.ajax.reload();
            });

            $('.btn-add-adjust').on('click', function() {
                $('#adjustment-iframe').attr('src',
                    "{{ url('adjustment/create/' . $product_adjust_action) }}");
                $('#modal-add-adjust').modal('show');
            });

            document.getElementById('submit-inbound-form').addEventListener('click', function() {
                const iframe = document.getElementById('adjustment-iframe');
                const iframeDoc = iframe.contentWindow.document;

                // Get the form inside iframe
                const form = iframeDoc.getElementById('adjustment-form');

                if (!form) {
                    alert('Form not found in iframe!');
                    return;
                }

                // Collect form data
                const formData = new FormData(form);

                // Optional: convert FormData to plain object (for JSON-style payloads)
                // let payload = {};
                // formData.forEach((value, key) => payload[key] = value);

                // Send AJAX POST
                fetch('{{ route('adjustment.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token') // Laravel CSRF token
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // ✅ Example: close modal, reload datatable, show alert
                        toastr.success(data.msg);
                        $('#modal-add-adjust').modal('hide');
                        table.ajax.reload(); // if using DataTables
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to submit inbound form.');
                    });
            });

            $('#submit-edit-form').on('click', function() {
                $.ajax({
                    url: '{{ route('adjustment.update') }}',
                    type: "POST",
                    data: {
                        "id": $('#adjust_id').val(),
                        "qty": $('#adjust_qty').val(),
                        "reason": $('#adjust_reason').val()
                    },
                    success: function(res) {
                        if (res.code == 200) {
                            toastr.success(res.msg);
                            $('#modal-update-adjust').modal('hide');
                            table.ajax.reload();
                        } else {
                            toastr.error(res.msg);
                        }
                    }
                });
            });

        });
    </script>
@endpush
