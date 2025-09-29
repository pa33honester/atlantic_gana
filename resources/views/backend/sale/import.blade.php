@extends('backend.layout.main') @section('content')
    @if (session()->has('message'))
        <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert"
                aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
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
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ trans('Import Order') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ trans('file.The field labels marked with * are required input fields') }}.</small>
                            </p>
                            {!! Form::open(['route' => 'sale.import', 'method' => 'post', 'files' => true, 'id' => 'import-sale-form']) !!}
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ trans('file.Warehouse') }} *</label>
                                                <select required name="warehouse_id" class="selectpicker form-control"
                                                    data-live-search="true" data-live-search-style="begins"
                                                    title="Select warehouse...">
                                                    @foreach ($lims_warehouse_list as $warehouse)
                                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>{{ trans('file.Upload Excel File') }} *</label>
                                                <input type="file" name="file" class="form-control" required />
                                                <p>{{ trans('file.The correct column order is') }} (product_code, quantity,
                                                    sale_unit_code, price, discount_per_unit, tax_name)
                                                    {{ trans('file.and you must follow this') }}.
                                                    {{ trans('file.For Digital product sale_unit will be n/a') }}.
                                                    {{ trans('file.All columns are required') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_qty" value="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_discount" value="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_tax" value="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_price" value="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="item" value="0" />
                                                <input type="hidden" name="order_tax" value="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="grand_total" value="0" />
                                                <input type="hidden" name="paid_amount"
                                                    value="{{ number_format(0, $general_setting->decimal, '.', '') }}" />
                                                <input type="hidden" name="payment_status" value="2" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ trans('file.Order Tax') }}</label>
                                                <select class="form-control" name="order_tax_rate">
                                                    <option value="0">No Tax</option>
                                                    @foreach ($lims_tax_list as $tax)
                                                        <option value="{{ $tax->rate }}">{{ $tax->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>
                                                    <strong>{{ trans('file.Order Discount') }}</strong>
                                                </label>
                                                <input type="number" name="order_discount" class="form-control"
                                                    step="any" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>
                                                    <strong>{{ trans('file.Shipping Cost') }}</strong>
                                                </label>
                                                <input type="number" name="shipping_cost" class="form-control"
                                                    step="any" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>{{ trans('file.Sale Note') }}</label>
                                                <textarea rows="5" class="form-control" name="sale_note"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>{{ trans('file.Staff Note') }}</label>
                                                <textarea rows="5" class="form-control" name="staff_note"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" value="{{ trans('file.submit') }}" class="btn btn-primary"
                                            id="submit-button">
                                    </div>
                                </div>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="text/javascript">
        $("ul#sale").siblings('a').attr('aria-expanded', 'true');
        $("ul#sale").addClass("show");
        $("ul#sale #sale-import-menu").addClass("active");

        @if (config('database.connections.saleprosaas_landlord'))
            numberOfInvoice = <?php echo json_encode($numberOfInvoice); ?>;
            $.ajax({
                type: 'GET',
                async: false,
                url: '{{ route('package.fetchData', $general_setting->package_id) }}',
                success: function(data) {
                    if (data['number_of_invoice'] > 0 && data['number_of_invoice'] <= numberOfInvoice) {
                        localStorage.setItem("message",
                            "You don't have permission to create another invoice as you already exceed the limit! Subscribe to another package if you wants more!"
                        );
                        location.href = "{{ route('sales.index') }}";
                    }
                }
            });
        @endif

        $('.selectpicker').selectpicker({
            style: 'btn-link',
        });

        $('[data-toggle="tooltip"]').tooltip();

        $(document).ready(function() {
            $('#submit-button').on('click', function(e) {
                e.preventDefault();

                let warehouse_id = $('select[name=warehouse_id]').val();
                if (!warehouse_id) {
                    alert('Select Warehouse');
                    return;
                }

                const form = document.getElementById('import-sale-form');

                if (!form) {
                    alert('Form not found!!');
                    return;
                }

                console.log(form);

                const formData = new FormData(form);

                fetch("{{ route('sale.import') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token') // Laravel CSRF token
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Success:', data);
                        console.log('data.code = ', data.code);
                        if (data['code'] == 200) {
                            toastr.success(data.msg);
                            const salesIndexUrl = "{{ route('sales.index') }}";
                            console.log({
                                salesIndexUrl
                            });
                            location.href = "/sales";
                        } else {
                            toastr.error(data.msg);
                        }
                    })
            });
        });
    </script>
@endpush
