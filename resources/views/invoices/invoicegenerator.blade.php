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
        .red_border{
            border: 1px solid red;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row mb-4">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Invoice Generator</h2>
                    </div>
                </div>
            </div>

            <div class="error_box">

            </div>


            {!! Form::open(array('method'=>'POST', 'id' => 'invoiceCreate')) !!}
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
                        <strong>Address 1:</strong>
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
                        <strong>City:</strong>
                        {!! Form::text('city', null, array('placeholder' => 'City','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <strong>State:</strong>
                        {!! Form::select('state', $states,[], array('class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <strong>Zip:</strong>
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
                        <strong>Phone Number:</strong>
                        {!! Form::text('phone_number', null, array('placeholder' => 'Phone Number','class' => 'form-control phone-number')) !!}
                        <label>{!! Form::checkbox('is_formated', null, array('class' => 'form-control')) !!}<small class="small align-text-top"> Format as US Phone number</small></label>
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
                        <strong>CC:</strong>
                        {!! Form::number('cc', null, array('placeholder' => 'CC','class' => 'form-control', 'maxlength="4"', 'minlength="4"')) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Sales Price *:</strong>
                        {!! Form::text('sales_price', null, array('class' => 'form-control price_data','pattern="^\$\d{1,3}(,\d{3})*(\.\d+)?$"', 'data-type="currency"', 'placeholder="Sales Price"', 'required="required"', 'value=9995')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Paid:</strong>
                        {!! Form::text('paid', null, array('class' => 'form-control','pattern="^\$\d{1,3}(,\d{3})*(\.\d+)?$"', 'data-type="currency"', 'placeholder="Paid"', 'disabled' => 'disabled', 'value=4995')) !!}
                    </div>
                </div>
            </div>
            <div id="all_discounts" class="mb-1">
                <div class="one_discount p-1">
                    <div class="row bg-light border pt-2">
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>Discount Title:</strong>
                                {!! Form::text('discounttitle_1', null, array('placeholder' => 'Discount Title','class' => 'form-control', 'value=Discount')) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>Discount Amount:</strong>
                                {!! Form::text('discountamount_1', null, array('class' => 'form-control price_data','pattern="^\$\d{1,3}(,\d{3})*(\.\d+)?$"', 'data-type="currency"', 'placeholder="Discount Amount"', 'value=5000')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row b">
                <div class="col-md-2">
                    <div class="form-group">
                        <button class="btn btn-sm btn-primary">Add Discount</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                    <button id="invoiceGenerate" type="submit" class="btn btn-primary">Generate Invoice</button>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ url('/js/jquery.datetimepicker.full.min.js') }}"></script>
    <script src="{{ url('/js/select2.min.js') }}"></script>
    <script src="{{ url('/js/jquery-input-mask-phone-number.min.js') }}"></script>
    <script>
        $(document).ready(function() {

            $("select").select2({
                width: '100%',
                placeholder: 'Please select',
                allowClear: true
            });

            $.each($("input[data-type='currency']"), function(k, v){
                formatCurrency($(this));
            });

            calculatePaid();

            $('document').on('change', '.price_data', function(){
                calculatePaid();
            });

            function calculatePaid(){

            }

            $("input[data-type='currency']").on({
                keyup: function() {
                    formatCurrency($(this));
                },
                blur: function() {
                    formatCurrency($(this), "blur");
                    const paid_el = $('input[name="paid"]');
                    const sales_price_el = $('input[name="sales_price"]');
                    const own_el = $('input[name="own"]');
                    if($(this).attr('name') === 'sales_price' && !paid_el.val()){
                        paid_el.val($(this).val());
                    }
                    if(($(this).attr('name') === 'paid' || $(this).attr('name') === 'sales_price') && sales_price_el.val()){
                        if(paid_el.val() != '') {
                            const sales_price = currencyToNumber(sales_price_el.val()) * 1;
                            const paid = currencyToNumber(paid_el.val()) * 1;
                            const own = sales_price - paid;
                            if (own > 0) {
                                own_el.val(own.toFixed(2));
                            }
                            else {
                                own_el.val(0);
                            }
                            formatCurrency(own_el);
                        }
                        else{
                            paid_el.val(sales_price_el.val());
                            own_el.val(0);
                            formatCurrency(own_el);
                        }
                    }
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


            function currencyToNumber(currency){
                return currency.replace(/\$/g,'').replace(/,/g,'') * 1;
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


            $(document).on('submit', '#invoiceCreate', function (event) {
                makeAjaxCall();
            });

            function makeAjaxCall(){
                event.preventDefault();
                var submit_button = $('button#invoiceGenerate');
                var $form = $('#invoiceCreate');
                var submitData = $form.serialize();

                var button_title = beforeSubmit(submit_button);
                var  message = "Unknown Error";
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/customers-invoices',
                    type: "POST",
                    dataType: "json",
                    data: submitData,
                    success: function (response) {
                        if (response) {
                            if (response.success) {
                                 window.location.href = "/invoices/" + response.data;
                            }
                            else {
                                if (response.message) {
                                    message = response.message;
                                }
                                $('.error_box').html('<div class="alert alert-danger">' + message + '</div>');
                                afterSubmit(submit_button, button_title);
                            }
                        }
                        else {
                            $('.error_box').html('<div class="alert alert-danger">'+message+'</div>');
                            afterSubmit(submit_button, button_title);
                        }
                    },
                    error: function (response) {
                        if (response && response.responseJSON) {
                            if (response.responseJSON.message) {
                                message = response.responseJSON.message;
                            }
                            else{
                                message = 'Error!';
                            }
                            $('.error_box').append('<div class="alert alert-danger">' + message + '</div>');


                            if (response.responseJSON.errors){
                                $.each(response.responseJSON.errors, function(key, value){
                                    $('input[name="'+key+'"]').addClass('red_border').after('<div class="small error_form text-danger">'+value[0]+'</div>');
                                    $('select[name="'+key+'"] ~ span:first').addClass('red_border').after('<div class="small error_form text-danger">'+value[0]+'</div>');
                                });
                            }
                        }
                        afterSubmit(submit_button, button_title);
                    }
                });
            }

            function beforeSubmit(submit_button){
                $('input').removeClass('red_border').prop('disabled', true);
                $('span').removeClass('red_border');
                $('.error_form').remove();
                $('.error_box').html("");
                var ajax_img = '<img width="40" src="<?php echo e(url('/img/ajax_3.gif')); ?>" alt="ajax loader">';
                $('button').prop('disabled', true);
                $('select').prop('disabled', true);
                $('a').addClass('disabled');
                var button_title = submit_button.html();
                submit_button.html(button_title + ' ' + ajax_img);
                return button_title;
            }

            function afterSubmit(submit_button, button_title){
                $('button').prop('disabled', false);
                $('input').prop('disabled', false);
                $('input[name="own"]').prop('disabled', true);
                $('select').prop('disabled', false);
                $('a').removeClass('disabled');
                submit_button.html(button_title);
                $('input[name="ignore_pipedrive"]').val('');
            }

            $('.phone-number').usPhoneFormat({
                format: '(xxx) xxx-xxxx',
                international: true
            });

            $('input[name="is_formated"]').click(function(){
                var phone_number =$('input[name="phone_number"]');
                if($(this).prop("checked") == true){
                    phone_number.addClass('phone-number');
                    $('.phone-number').usPhoneFormat({
                        format: '(xxx) xxx-xxxx',
                        international: true
                    });
                }
                else if($(this).prop("checked") == false){
                    phone_number.removeClass('phone-number').off();
                }
            });


            function isSet(variable){
                if(typeof variable !== "undefined" && variable !== null) {
                    return true;
                }
                return false;
            }
        });
    </script>
@endsection