@extends('backend.layout.main') @section('content')
@if(session()->has('message'))
    <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('Add Tracking Order')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'sale.tracking', 'method' => 'post', 'files' => true, 'id' => 'tracking-sale-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{trans('Upload Excel File')}} *</label>
                                            <input type="file" name="file" class="form-control" required />
                                            <p>{{trans('file.The correct column order is')}} (Order Number, Tracking Code) {{trans('file.and you must follow this')}}.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary" id="submit-button">
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

    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #sale-tracking-menu").addClass("active");

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

$('.selectpicker').selectpicker({
    style: 'btn-link',
});

$('[data-toggle="tooltip"]').tooltip();

$(document).ready(function(){
    $('#submit-button').on('click', function(e){
        e.preventDefault();

        const form = document.getElementById('tracking-sale-form');

        if(!form){
            alert('Form not found!!');
            return;
        }

        console.log(form);

        const formData = new FormData(form);

        fetch("{{ route('sale.tracking') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': formData.get('_token')  // Laravel CSRF token
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Success:', data);
            if(data.code == 200){
                toastr.success(data.msg);
                setTimeout(function(){
                    location.href = "{{ route('sales.index') }}";
                }, 3000);
            }
            else {
                toastr.error(data.msg);
            }
        })
    });
});

</script>
@endpush
