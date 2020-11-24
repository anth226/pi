@extends('layouts.app')

@section('style')
    <link href="{{ asset('css/jquery.datetimepicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
    <style>
        .select2-selection.select2-selection--single {
            display: block;
            width: 100%;
            height: calc(1.6em + .75rem + 2px);
            padding: .45rem .75rem;
            font-size: .9rem;
            font-weight: 400;
            line-height: 1.6;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }
        .select2-selection.select2-selection--single:focus,
        .select2-selection.select2-selection--single:hover {
            color: #495057;
            background-color: #fff;
            border-color: #a1cbef;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(52,144,220,.25);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 90%;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: .9rem;
            font-weight: 400;
            line-height: 1.6;
            color: #495057;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Create New Invoice</h2>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-primary mb-4 mt-2" href="{{ route('customers.index') }}"> Back</a>
                    </div>
                </div>
            </div>


            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif



            {!! Form::open(array('route' => 'customers-invoices.store','method'=>'POST')) !!}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>First Name *:</strong>
                        {!! Form::text('first_name', null, array('placeholder' => 'First Name','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Last Name *:</strong>
                        {!! Form::text('last_name', null, array('placeholder' => 'Last Name','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Address 1 *:</strong>
                        {!! Form::text('address_1', null, array('placeholder' => 'Address 1','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Address 2:</strong>
                        {!! Form::text('address_2', null, array('placeholder' => 'Address 2','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <strong>City *:</strong>
                        {!! Form::text('city', null, array('placeholder' => 'City','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <strong>State *:</strong>
                        {!! Form::select('state', $states,[], array('class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <strong>Zip *:</strong>
                        {!! Form::text('zip', null, array('placeholder' => 'Zip','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Email *:</strong>
                        {!! Form::text('email', null, array('placeholder' => 'Email','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Phone Number *:</strong>
                        {!! Form::text('phone_number', null, array('placeholder' => 'Phone Number','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Salesperson *:</strong>
                        {!! Form::select('salespeople_id', $salespeople,[], array('class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Sales Price *:</strong>
                        {!! Form::text('sales_price', null, array('class' => 'form-control','pattern="^\$\d{1,3}(,\d{3})*(\.\d+)?$"', 'data-type="currency"', 'placeholder="Sales Price"', 'required="required"')) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Access Date *:</strong>
                        {!! Form::text('access_date', null, array('id="access_date"', 'placeholder' => 'Access Date','class' => 'form-control datetimepicker-input', 'data-toggle="datetimepicker"', 'data-target="#access_date"', 'value="'.date("m-d-Y").'"')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>CC *:</strong>
                        {!! Form::number('cc', null, array('placeholder' => 'CC','class' => 'form-control', 'maxlength="4"', 'minlength="4"', 'required="required"')) !!}
                    </div>
                </div>
            </div>
            <div class="row d-none">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Product *:</strong>
                        {!! Form::select('product_id', $products,[], array('class' => 'form-control')) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Quantity *:</strong>
                        {!! Form::number('qty', null, array('placeholder' => 'Quantity','class' => 'form-control', 'min="1"', 'max="10000"', 'step="1"', 'value="1"')) !!}
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                    <button type="submit" class="btn btn-primary">Generate Invoice</button>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ url('/js/jquery.datetimepicker.full.min.js') }}"></script>
    <script src="{{ url('/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $("select").select2({
                width: '100%',
                placeholder: 'Please select'
            });
            $("input[data-type='currency']").on({
                keyup: function() {
                    formatCurrency($(this));
                },
                blur: function() {
                    formatCurrency($(this), "blur");
                }
            });

            $('#access_date').datetimepicker({
                timepicker:false,
                format:'m-d-Y'
            });
            $.datetimepicker.setLocale('en');


            function formatNumber(n) {
                // format number 1000000 to 1,234,567
                return n.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",")
            }


            function formatCurrency(input, blur) {
                // appends $ to value, validates decimal side
                // and puts cursor back in right position.

                // get input value
                var input_val = input.val();

                // don't validate empty input
                if (input_val === "") { return; }

                // original length
                var original_len = input_val.length;

                // initial caret position
                var caret_pos = input.prop("selectionStart");

                // check for decimal
                if (input_val.indexOf(".") >= 0) {

                    // get position of first decimal
                    // this prevents multiple decimals from
                    // being entered
                    var decimal_pos = input_val.indexOf(".");

                    // split number by decimal point
                    var left_side = input_val.substring(0, decimal_pos);
                    var right_side = input_val.substring(decimal_pos);

                    // add commas to left side of number
                    left_side = formatNumber(left_side);

                    // validate right side
                    right_side = formatNumber(right_side);

                    // On blur make sure 2 numbers after decimal
                    if (blur === "blur") {
                        right_side += "00";
                    }

                    // Limit decimal to only 2 digits
                    right_side = right_side.substring(0, 2);

                    // join number by .
                    input_val = "$" + left_side + "." + right_side;

                } else {
                    // no decimal entered
                    // add commas to number
                    // remove all non-digits
                    input_val = formatNumber(input_val);
                    input_val = "$" + input_val;

                    // final formatting
                    if (blur === "blur") {
                        input_val += ".00";
                    }
                }

                // send updated string to input
                input.val(input_val);

                // put caret back in the right position
                var updated_len = input_val.length;
                caret_pos = updated_len - original_len + caret_pos;
                input[0].setSelectionRange(caret_pos, caret_pos);
            }

        });
    </script>
@endsection