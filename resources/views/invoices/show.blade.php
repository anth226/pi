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

@section('popup')
    @include('popups.editinvoice')
    @include('popups.createtask')
@endsection

@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }} [{{ $invoice->invoice_number }}]</h2>
                        @if($invoice->status <= 1)
                                <div class="h5 text-success mb-4">{{ \App\Invoices::STATUS[$invoice->status] }}</div>
                            @else
                            @if($invoice->status == 2)
                                <div class="h5 text-danger mb-4">{{ \App\Invoices::STATUS[$invoice->status] }} <span class="small"><button class="btn btn-outline-primary btn-sm" id="unset_refund_requested">Unset</button></span></div>
                            @else
                                @if($invoice->status == 3)
                                    <div class="h5 text-danger mb-4">{{ \App\Invoices::STATUS[$invoice->status] }} <span class="small"><button class="btn btn-outline-primary btn-sm" id="unset_refunded">Unset</button></span></div>
                                @endif
                            @endif
                        @endif

                        @if( Gate::check('invoice-create') || Gate::check('invoice-edit'))
                        <div class="text-muted mb-4">
                            <div  class="details_bgcolor p-2">
                                <div>
                                    <small>
                                        <strong>Created at:</strong>
                                        {{ $invoice->created_at }}
                                    </small>
                                </div>
                                @if($sentLog && count($sentLog))
                                    @foreach($sentLog as $d)
                                            @php
                                                $service_name = '';
                                                switch ($d->service_type){
                                                    case 1:
                                                        if($d->field == "subscriber_id"){
                                                            $service_name = 'Stripe';
                                                        }
                                                        break;
                                                    case 2:
                                                        $service_name = 'Firebase';
                                                        break;
                                                    case 3:
                                                        $service_name = 'Klaviyo';
                                                        break;
                                                    case 4:
                                                        $service_name = 'SMS System';
                                                        break;
                                                    case 5:
                                                        if($d->field == "deal_id"){
                                                            $service_name = 'Pipedrive';
                                                        }
                                                        break;
                                                    default:
                                                        $service_name = '';
                                                }
                                            @endphp
                                            @if($service_name)
                                            <div>
                                                <small>
                                                <strong>Sent to {{$service_name}} at: </strong>
                                                {{ $d->created_at}}
                                                </small>
                                            </div>
                                            @endif
                                    @endforeach
                                @endif
                                @if (count($errors) > 0)
                                    <div class="alert alert-danger">
                                        <strong>Whoops!</strong> There were some problems.<br><br>
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="pull-right mb-4 ">
                        <a class="btn btn-primary mt-2" href="/dashboard"> Dashboard</a>
                        @can('invoice-edit')
                            @if($invoice->status == 1 || $invoice->status == 2)
                                <button class="btn btn-info mt-2" data-toggle="modal" data-target="#editinvoice">Edit Invoice</button>
                            @endif
                            @if($invoice->status != 3)
                                <button class="btn btn-info mt-2" id="refunded">Set as {{ \App\Invoices::STATUS[3] }}</button>
                            @endif
                            @if($invoice->status != 2 && $invoice->status != 3)
                                <button class="btn btn-outline-primary mt-2" id="refund_requested">Set as {{ \App\Invoices::STATUS[2] }}</button>
                            @endif
                        @endcan
                        {{--@can('invoice-delete')--}}
                            {{--{!! Form::open(['method' => 'DELETE','route' => ['invoices.destroy', $invoice->id],'style'=>'display:inline;']) !!}--}}
                            {{--{!! Form::submit('Delete', ['class' => 'btn btn-danger mt-2']) !!}--}}
                            {{--{!! Form::close() !!}--}}
                        {{--@endcan--}}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div>
                        <strong>Invoice N:</strong>
                        {{ $invoice->invoice_number }}
                    </div>
                    <div>
                        <strong>Customer:</strong>
                        <a @can('customer-list') target="_blank" href="{{ route('customers.show', $invoice->customer_id) }}" @endif title="{{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}">
                            {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}
                        </a>
                        <small>
                            {{ $invoice->customer->email }}
                        </small>
                    </div>
                    <div>
                        <strong>Address:</strong>
                        {{ $invoice->customer->address_1 }} {{ $invoice->customer->address_2 }}, {{ $invoice->customer->city }}, {{ $invoice->customer->state }}, {{ $invoice->customer->zip }}
                    </div>
                    <div>
                        <strong>Phone #:</strong>
                        {{ $invoice->customer->phone_number }}
                    </div>
                    @php
                        $salespeople = [];
                        $cc = '';
                        $bcc = 'corporate@portfolioinsider.com';
                        $inv = new \App\Http\Controllers\InvoicesController();
                        $commission = 0;
                    @endphp
                    @if(count($invoice->salespeople))
                        @foreach($invoice->salespeople as  $sp)
                            @php
                                $commission += ($sp->earnings)*1;
                            @endphp
                            @if($sp->sp_type)
                                @php
                                    $salespeople[] = $sp->salespersone->email;
                                @endphp
                                <div>
                                    <strong>Salesperson:</strong>
                                    <a @can('salespeople-list') target="_blank" href="{{ route('salespeople.show', $sp->salespersone->id) }}" @endcan title="({{ $sp->salespersone->first_name }} {{ $sp->salespersone->last_name }})">
                                        {{ $sp->salespersone->name_for_invoice }}
                                    </a>
                                    @can('invoice-create')
                                        {{--@if($sp->earnings > 0)--}}
                                            <span>
                                                <small>
                                                 Earning: {{ $inv->moneyFormat($sp->earnings) }} ({{ $sp->level->title }} / {{ $sp->percentage }}%)
                                                </small>
                                            </span>
                                        {{--@endif--}}
                                    @endcan
                                </div>
                            @endif
                        @endforeach
                        @foreach($invoice->salespeople as  $sp)
                            @if(!$sp->sp_type)
                            @php
                                $salespeople[] = $sp->salespersone->email;
                            @endphp
                            <div class="px-2 small">
                                <strong>Salesperson:</strong>
                                <a @can('salespeople-list') target="_blank" href="{{ route('salespeople.show', $sp->salespersone->id) }}" @endcan title="({{ $sp->salespersone->first_name }} {{ $sp->salespersone->last_name }})">
                                    {{ $sp->salespersone->name_for_invoice }}
                                </a>
                                @can('invoice-create')
                                    {{--@if($sp->earnings > 0)--}}
                                        <span>
                                             Earning: {{ $inv->moneyFormat($sp->earnings) }} ({{ $sp->level->title }} / {{ $sp->percentage }}%)
                                        </span>
                                    {{--@endif--}}
                                @endcan
                            </div>
                            @endif
                        @endforeach
                    @endif
                    @php
                        if(count($salespeople)){
                            $cc = implode(', ', $salespeople);
                        }
                    @endphp
                    <div>
                        <strong>Product:</strong>
                        {{ $invoice->product->title }}
                    </div>
                    {{--<div>--}}
                        {{--<strong>Quantity:</strong>--}}
                        {{--{{ $invoice->qty }}--}}
                    {{--</div>--}}
                    <div>
                        <strong>CC last 4 digits:</strong>
                        {{ $invoice->cc_number }}
                    </div>

                    @if((($invoice->sales_price - $invoice->paid) > 0) && !$invoice->refunded_at)
                        <div class="text-danger">
                            <strong>To Pay:</strong>
                            {{ $inv->moneyFormat($invoice->sales_price - $invoice->paid) }}
                        </div>
                    @endif

                    <div>
                        <strong>Paid:</strong>
                        {{ $inv->moneyFormat($invoice->paid) }}
                        @can('invoice-create')
{{--                            @if($commission)--}}
                                <small> (
                                    @php
                                        $profit = ($invoice->paid)*1 - (($commission)*1);
                                        $percent = 0;
                                        if($invoice->paid > 0){
                                            $percent =  $commission * 100/$invoice->paid;
                                        }
                                        $percent =  number_format($percent, 2, '.', '');
                                    @endphp
                                    <span class="text-success">Net Revenue: {{ $inv->moneyFormat($profit) }}</span> / Commission: {{$inv->moneyFormat($commission) }} | {{$percent}}% )
                                </small>
                                {{--@endif--}}
                        @endcan
                    </div>

                    <div>
                        <strong>Sales Price:</strong>
                        {{ $formated_price }}
                    </div>

                    <div>
                        <strong>Access Date:</strong>
                        {{ $access_date }}
                    </div>

                </div>
                <div class="col-md-6">
                    @if( Gate::check('invoice-create') || Gate::check('send-email-test'))
                    <div class="mb-2">
                        <input type="hidden" id="invoice_id" value="{{ $invoice->id }}">
                        <div class="form-group">
                            <strong>Email Template *:</strong>
                            {!! Form::select('email_template_id', $template,[3], array('class' => 'form-control', 'id' => 'email_template_id')) !!}
                        </div>
                        <div class="form-group">
                            <strong>Email *:</strong>
                            {!! Form::text('email', $invoice->customer->email, array('placeholder' => 'Email','class' => 'form-control', 'id' => 'email_address')) !!}
                        </div>
                        <div class="form-group">
                            <strong>CC:</strong>
                            {!! Form::text('cc_email', $cc, array('placeholder' => 'Email','class' => 'form-control', 'id' => 'cc_email_address')) !!}
                        </div>
                        <div class="form-group">
                            <strong>BCC:</strong>
                            {!! Form::text('bcc_email', $bcc, array('placeholder' => 'Email','class' => 'form-control', 'id' => 'bcc_email_address')) !!}
                        </div>
                        <button class="btn btn-primary" id="send_email">Send Invoice Email</button>
                        <div class="err_box"></div>
                    </div>

                    <div class="text-muted details_bgcolor" id="log_box">
                        @if(!empty($logs) && $logs->count())
                            @foreach ($logs as $k=>$log)
                                <div class="pl-1 pr-1">
                                    <small>
                                        <strong>Sent at </strong>{{ $log->created_at }} <strong>to</strong> {{ $log->to }}
                                    </small>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    @endif
                </div>
            </div>


            @if( Gate::check('support-tasks-create') || Gate::check('support-user-view-all'))

            <div class="card w-100 mt-4 mb-4">
                <div class="card-body">
                    @if( Gate::check('support-tasks-create'))
                        @if($supportReps_select )
                            <div class="row mb-4">
                                <div class="col-lg-8">
                                    <strong>Default Support Representative:</strong>
                                    <div class="row">
                                        <div class="col-md-8 pr-md-1">
                                            <div class="form-group mb-1">
                                                {!! $supportReps_select !!}
                                            </div>
                                        </div>
                                        <div class="col-md-4 pl-md-1">
                                            <div class="form-group mb-1">
                                                <button id="edit_support_rep" class="w-100 btn btn-info">Update</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                        @if($support_todo && count($support_todo) )
                            <div class="row mt-2">
                                <div class="col-lg-8">
                                    <strong>Tasks</strong>
                                </div>
                            </div>
                            <div class="row mb-4">
                                @foreach($support_todo as $todo)
                                    @php
                                        $class = '';
                                        if($todo['task_status'] == 1){
                                            $class = ' bg-warning ';
                                        }
                                        if($todo['task_status'] == 2){
                                            $class = ' bg-success ';
                                        }
                                    @endphp
                                    <div class="col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header  {!! $class !!}">
                                                {!! \App\SupportTodo::TASK_STATUS[$todo['task_status']] !!}
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title">{!! \App\SupportTodo::TASK_TYPE[$todo['task_type']] !!}</h5>
                                                <p class="card-text">
                                                    <div>Added at: {!! $todo['created_at'] !!}</div>
                                                    <div>Added by: <strong>{!! $todo['added_byuser']['name'] !!}</strong></div>
                                                    <div>Task for: <strong><a href="/support-reps/{!! $todo['support_rep']['id'] !!}">{!! $todo['support_rep']['name'] !!}</a></strong></div>
                                                    @if(isset($todo['done_at']))
                                                        <hr class="mt-1 mb-1">
                                                        <div>Completed at: {!! $todo['done_at'] !!}</div>
                                                        <div>Completed by: <strong>{!! $todo['done_byuser']['name'] !!}</strong></div>
                                                    @endif
                                                </p>
                                                @if($todo['task_status'] == 1)
                                                    @if( Gate::check('support-tasks-create'))
                                                        <button data-todo_id="{!! $todo['id'] !!}" class="w-100 btn btn-info remove_todo">Remove</button>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if( Gate::check('support-tasks-create'))
                        <div class="row mt-4">
                            <div class="col-lg-8">
                                <button class="btn btn-info mt-2" data-toggle="modal" data-target="#createtask">Add Task</button>
                            </div>
                        </div>
                        @endif
                </div>
            </div>

            @endif

            <div class="row">
                <div class="col-12"><a target="_blank" href="{{ $full_path.$invoice->id }}" title="Open a PDF file in a new tab">{{$file_name}}</a></div>
                <div class="col-12"><a  href="/pdfdownload/{{$invoice->id }}" title="Download a PDF file">Download</a></div>
                <div class="col-12">
                    <div class="mt-2 d-none d-md-block" style="width:900px;height:1250px;">
                        <object style="width:100%;height:100%;" data="{{ $full_path.$invoice->id.'&v='.rand() }}">{{$file_name}}" type="application/pdf">
                            <iframe style="width:100%;height:100%;" src="https://docs.google.com/viewer?url={{ $full_path.$invoice->id }}&embedded=true"></iframe>
                        </object>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script src="{{ url('/js/jquery.datetimepicker.full.min.js') }}"></script>
    <script src="{{ url('/js/select2.min.js') }}"></script>
    <script>
        function isEmail(email) {
            // var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,6})+$/;
            // return regex.test(email);
            return true;
        }

        $('#send_email').on('click', function(){
            var current_button = $(this);
            var email = $('#email_address').val();
            var cc = $('#cc_email_address').val();
            var bcc = $('#bcc_email_address').val();
            var invoice_id = $('#invoice_id').val();
            var email_template_id = $('#email_template_id').val();
            var err_box = $('.err_box');
            err_box.html('');

            if(email && invoice_id && email_template_id) {
                err_box.addClass('text-danger');
                err_box.removeClass('text-success');
                if(isEmail(email)) {
                    var button_text = current_button.html();
                    var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                    current_button.html(ajax_img);
                    $('.btn').prop('disabled', true);
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: '/send-invoice-email',
                        type: "POST",
                        dataType: "json",
                        data: {
                            invoice_id: invoice_id,
                            email_template_id: email_template_id,
                            email: email,
                            bcc: bcc,
                            cc: cc
                        },
                        success: function (response) {
                            if (response) {
                                if (response.success) {
                                    err_box.removeClass('text-danger');
                                    err_box.addClass('text-success');
                                    err_box.html(response.message);
                                }
                                else {
                                    err_box.html('Error: ' + response.message);
                                }
                                if (response.data) {
                                    const returnedData = JSON.parse(response.data);
                                    var str = '';
                                    returnedData.forEach(function(item) {
                                       str += '<div class="pl-1 pr-1"><small><strong>Sent at </strong>'+item.created_at+'<strong> to</strong> '+item.to+'</small></div>';

                                    });
                                    $('#log_box').html(str);
                                }
                            }
                            else {
                                err_box.html('Error!');
                            }
                            current_button.html(button_text);
                            $('.btn').prop('disabled', false);
                        },
                        error: function (response) {
                            if (response && response.responseJSON) {
                                if (response.responseJSON.message) {
                                    err_box.html(response.responseJSON.message);
                                }
                                else {
                                    err_box.html('Error!');
                                }
                            }
                            current_button.html(button_text);
                            $('.btn').prop('disabled', false);
                        }
                    });
                }
                else{
                    err_box.html('Please enter valid Email.');
                }

            }
            else{
                err_box.html('Empty email address');
            }


        });

        $(document).ready(function() {
            $("select").select2({
                width: '100%',
                placeholder: 'Please select',
                allowClear: true
            });

            var pr_salesperson = $('select[name="salespeople_id"]');
            var second_salesperson = $('select[name="second_salespeople_id[]"]');

            updateSelects(pr_salesperson, second_salesperson);

            pr_salesperson.on('change', function(){
                updateSelects(pr_salesperson, second_salesperson);
            });
            second_salesperson.on('change', function(){
                updateSelects(pr_salesperson, second_salesperson);
            });

            function updateSelects(pr_select, second_select){
                const pr_select_val = pr_select.val();
                const second_select_val = second_select.val();
                const salespeople_to_disable = [];
                if($.isArray(pr_select_val)) {
                    $.each(pr_select_val, function (i, v) {
                        salespeople_to_disable.push(pr_select.find('option[value="'+v+'"]').data('salesperson_id'));
                    });
                }
                else{
                    salespeople_to_disable.push(pr_select.find('option[value="'+pr_select_val+'"]').data('salesperson_id'));
                }

                if($.isArray(second_select_val)) {
                    $.each(second_select_val, function (i, v) {
                        salespeople_to_disable.push(second_select.find('option[value="' + v + '"]').data('salesperson_id'));
                    });
                }
                else{
                    salespeople_to_disable.push(second_select.find('option[value="' + second_select_val + '"]').data('salesperson_id'));
                }

                pr_select.prop('disabled', false);
                second_select.prop('disabled', false);
                pr_select.find($('option')).prop('disabled', false);
                second_select.find($('option')).prop('disabled', false);
                if(salespeople_to_disable) {
                    $.each(salespeople_to_disable, function(i,salesperson){
                        const second_select_el = second_select.find($('option[data-salesperson_id="' + salesperson + '"]'));
                        $.each(second_select_el, function(k,v){
                            const this_val = $(v).val();
                            if($.isArray(second_select_val)){
                                if($.inArray(this_val, second_select_val) === -1){
                                    $(v).prop('disabled', true);
                                }
                            }
                            else {
                                if (this_val !== second_select_val) {
                                    $(v).prop('disabled', true);
                                }
                            }
                        });

                        const pr_select_el = pr_select.find($('option[data-salesperson_id="' + salesperson + '"]'));
                        $.each(pr_select_el, function(k,v){
                            const this_val = $(v).val();
                            if($.isArray(pr_select_val)){
                                if($.inArray(this_val, pr_select_val) === -1){
                                    $(v).prop('disabled', true);
                                }
                            }
                            else {
                                if (this_val !== pr_select_val) {
                                    $(v).prop('disabled', true);
                                }
                            }
                        });
                    });
                }
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
                format:'m-d-Y',
                value: '{{ $access_date }}'
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

            $(document).on('click', '#refund_requested', function (event) {
                var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                $(this).append(ajax_img);
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/invoices/update-status/',
                    type: "POST",
                    dataType: "json",
                    data: {
                       invoice_id:{{$invoice->id}},
                       refundRequested:2
                    },
                    success: function (response) {
                        location.reload();
                    }
                });
            });

            $(document).on('click', '#unset_refund_requested', function (event) {
                var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                $(this).append(ajax_img);
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/invoices/update-status/',
                    type: "POST",
                    dataType: "json",
                    data: {
                        invoice_id:{{$invoice->id}},
                        refundRequested: 1
                    },
                    success: function (response) {
                        location.reload();
                    }
                });
            });

            $(document).on('click', '#refunded', function (event) {
                var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                $(this).append(ajax_img);
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/invoices/update-status/',
                    type: "POST",
                    dataType: "json",
                    data: {
                        invoice_id:{{$invoice->id}},
                        refundRequested:3
                    },
                    success: function (response) {
                        location.reload();
                    }
                });
            });

            $(document).on('click', '#unset_refunded', function (event) {
                var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                $(this).append(ajax_img);
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/invoices/update-status/',
                    type: "POST",
                    dataType: "json",
                    data: {
                        invoice_id:{{$invoice->id}},
                        refundRequested: 1
                    },
                    success: function (response) {
                        location.reload();
                    }
                });
            });

            $(document).on('submit', '#invoiceEdit', function (event) {
                event.preventDefault();

                var popup_err_box = $('.popup_err_box');
                popup_err_box.html('');

                var $form = $(this);
                var submitData = $form.serialize();

                var submitButton = $('#saveAndGenerate');
                var button_text = submitButton.html();
                var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                submitButton.html(button_text+ajax_img);
                $('#editinvoice').find('button').prop('disabled', true);

                // $.ajaxSetup({
                //     headers: {
                //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                //     }
                // });

                $.ajax({
                    url: '/invoices/update/{{$invoice->id}}',
                    type: "POST",
                    dataType: "json",
                    data: submitData,
                    success: function (response) {
                        if (response) {
                            if (response.success) {
                                location.reload();
                            }
                            else {
                                popup_err_box.html('Error: ' + response.message);
                            }
                        }
                        else {
                            popup_err_box.html('Error!');
                        }
                        submitButton.html(button_text);
                        $('#editinvoice').find('button').prop('disabled', false);
                    },
                    error: function (response) {
                        if (response && response.responseJSON) {
                            if (response.responseJSON.message) {
                                popup_err_box.html(response.responseJSON.message);
                            }
                            else {
                                popup_err_box.html('Error!');
                            }
                        }
                        submitButton.html(button_text);
                        $('#editinvoice').find('button').prop('disabled', false);
                    }
                });

            });

            //support rep and tasks

            $(document).on('click', '#edit_support_rep', function (event) {
                var support_rep_user_id = $('select[name="supportRep_id[]"]').val();
                if(support_rep_user_id) {
                    var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                    $(this).append(ajax_img);
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: '/invoices/edit-support-rep/',
                        type: "POST",
                        dataType: "json",
                        data: {
                            invoice_id:{{$invoice->id}},
                            support_rep_user_id: support_rep_user_id
                        },
                        success: function (response) {
                            location.reload();
                        }
                    });
                }
            });

            $(document).on('click', '#add_todo', function (event) {
                var task_id = $('select[name="tasks_select"]').val();
                var support_rep_user_id = $('select[name="supportTaskRep_id[]"]').val();
                if(task_id) {
                    var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                    $(this).append(ajax_img);
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: '/support/add-task',
                        type: "POST",
                        dataType: "json",
                        data: {
                            invoice_id:{{$invoice->id}},
                            task_id: task_id,
                            support_rep_user_id: support_rep_user_id
                        },
                        success: function (response) {
                            location.reload();
                        }
                    });
                }
            });

            $(document).on('click', '.remove_todo', function (event) {
                var todo_id = $(this).data('todo_id');
                if(todo_id) {
                    var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                    $(this).append(ajax_img);
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: '/support/remove-task',
                        type: "POST",
                        dataType: "json",
                        data: {
                            todo_id: todo_id
                        },
                        success: function (response) {
                            location.reload();
                        }
                    });
                }
            });

        });

    </script>
@endsection