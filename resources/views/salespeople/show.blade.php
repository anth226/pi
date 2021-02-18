@extends('layouts.app')

@section('style')
    <style>
        .bg_to_pay{
            background-color: rgba(255,0,0,.1) !important;
        }
        .bg_refunded{
            background-color: rgba(0,0,0,.3) !important;
        }
        .bg-success-light{
            background-color: rgba(0,255,0,.1) !important;
        }
        .err_box{
            line-height: 1.1;
            margin-top: 0.5rem;
        }
        div.sticky {
            position: -webkit-sticky; /* Safari */
            position: sticky;
            z-index: 99999;
            background-color: #f8fafc;
            top: 0;
        }
        .stat_wrapper{
            padding-right: 11px;
            padding-left: 11px;
            padding-top:3px;
            padding-bottom:10px;
            border-bottom: 1px solid deepskyblue;
        }

        @media (max-width: 1199px) {
            div.sticky {
                border-bottom: 1px solid deepskyblue;
            }
            .stat_wrapper{
                border-bottom: none;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> {{ $salespeople->name_for_invoice }}</h2>
                    </div>
                    <div class="pull-right mb-4">
                        @if( Gate::check('salespeople-list') || Gate::check('salespeople-edit') || Gate::check('salespeople-delete'))
                            <a class="btn btn-primary mt-2" href="{{ route('salespeople.index') }}"> All Salespeople</a>
                        @endif
                        @can('salespeople-edit')
                            <a class="btn btn-info mt-2" href="{{ route('salespeople.edit',$salespeople->id) }}"> Edit</a>
                        @endcan
                        {{--@can('salespeople-delete')--}}
                            {{--{!! Form::open(['method' => 'DELETE','route' => ['salespeople.destroy', $salespeople->id],'style'=>'display:inline']) !!}--}}
                            {{--{!! Form::submit('Delete', ['class' => 'btn btn-danger mt-2']) !!}--}}
                            {{--{!! Form::close() !!}--}}
                        {{--@endcan--}}
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-4 mb-2">
                    @can('salespeople-edit')
                        <div class="p-2 mb-2 text-muted details_bgcolor">
                            <div>
                                <small>
                                    <strong>Created at:</strong>
                                    {{ $salespeople->created_at }}
                                </small>
                            </div>
                            <div>
                                <small>
                                    <strong>Updated at:</strong>
                                    {{ $salespeople->updated_at }}
                                </small>
                            </div>
                        </div>
                    @endcan
                    <div>
                        <strong>Name for Invoice:</strong>
                        {{ $salespeople->name_for_invoice }}
                    </div>
                    <div>
                        <strong>Name:</strong>
                        {{ $salespeople->first_name }} {{ $salespeople->last_name }}
                    </div>
                    <div>
                        <strong>Email:</strong>
                        {{ $salespeople->email }}
                    </div>
                    <div>
                        <strong>Phone Number:</strong>
                        @php
                            use App\KmClasses\Sms\FormatUsPhoneNumber;
                            echo FormatUsPhoneNumber::nicePhoneNumberFormat($salespeople->phone_number, $salespeople->formated_phone_number);
                        @endphp
                    </div>
                    @can('salespeople-edit')
                        <div>
                            <strong>Level:</strong>
                            @if(!empty($salespeople->level3))
                                @foreach($salespeople->level3 as $l)
                                    <div class="ml-2">{{ $l->level->title }} | {{ $l->level->percentage }}%</div>
                                @endforeach
                            @endif
                        </div>
                    @endcan
                     <div class="mt-4">
                         <label><input id="sens_info" checked type="checkbox"><span class="ml-2 text-info">Show Sensitive Information</span></label>
                     </div>
                </div>
                <div class="col-md-8">

                </div>
            </div>


            <div class="row mt-4">
                <div class="col-md-7 m-auto">
                    {{--<h4 class="mb-0"><strong>Prime Sales</strong></h4>--}}
                </div>
                <div class="col-md-5">
                    <label class="w-100">
                        Date range
                        <input class="form-control" type="text" id="reportRange">
                    </label>
                </div>
            </div>
        </div>
    </div>


    <div class="sticky w-100">
        <div class="container">
            <div class="col-lg-12 m-auto">
                <div class="stat_wrapper">
                    <div class="row mt-2">
                    <div class="col-6 col-lg-2 px-1 mb-1 my-lg-auto">
                        <div class="card order-card bg-info">
                            <div class="text-center py-2 px-1 text-white">
                                <h4 class="text-center"><span id="subscriptions">0</span></h4>
                                <h4 class="lead text-center mb-0">Prime Sales</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-2 px-1 mb-1 my-lg-auto">
                        <div class="card order-card bg-primary">
                            <div class="text-center py-2 px-1 text-white">
                                <h4 class="text-center"><span id="revenue">0</span></h4>
                                <h4 class="lead text-center mb-0">Revenue</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-2 px-1 mb-1 my-lg-auto">
                        <div class="card order-card bg-info">
                            <div class="text-center py-2 px-1 text-white">
                                <h4 class="text-center"><span id="commissions">0</span></h4>
                                <h4 class="lead text-center mb-0">Commissions</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-2 px-1 mb-1 my-lg-auto">
                        <div class="card order-card discrepancies_stat">
                            <div class="text-center py-2 px-1">
                                <h4 class="text-center"><span id="discrepancies">0</span></h4>
                                <h4 class="lead text-center mb-0">Discrepancies</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-2 px-1 mb-1 my-lg-auto">
                        <div class="card order-card paid_stat">
                            <div class="text-center py-2 px-1">
                                <h2 class="text-center"><span id="paid">0</span></h2>
                                <h2 class="lead text-center mb-0">Paid</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-2 px-1 mb-1 my-lg-auto">
                        <div class="card order-card topay_stat">
                            <div class="text-center py-2 px-1">
                                <h2 class="text-center"><strong><span id="topay">0</span></strong></h2>
                                <h2 class="lead text-center mb-0"><strong>To Pay</strong></h2>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container overflow-auto">
        <div class="col-lg-12 m-auto">

            <div class="discrepancy_box d-none">
                <h4 class="mt-4"><strong>Discrepancies</strong></h4>

                <table class="table table-striped table-bordered table-responsive-sm w-100" id="discrepancy_table">
                    <thead>
                    <tr>
                        <th>Access Date</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>To Pay</th>
                        <th>Salesperson</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>

            <h4 class="mt-4"><strong>Prime Sales</strong></h4>
            <table class="table table-striped table-bordered table-responsive-sm w-100" id="customers_table">
                <thead>
                <tr>
                    <th>Access Date</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Amount</th>
                    <th>To Pay</th>
                    <th>Salesperson</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
@endsection
@section('script')
    <script>

        $(document).ready(function() {

            var payments = {
                commission: 0,
                discrepancies: 0,
                paid: 0,
                toPay: 0
            };

            var dispInvoices = {};
            var dissmissedVal = {};
            var paidVal = {};
            // var dispInvoices = {401:401,407:407, 415:415, 383:383};

            var sens_info_box = $( "#sens_info" );

            var show_sansitive_info = sens_info_box.prop( "checked");

            sens_info_box.change(function() {
                if(this.checked) {
                    show_sansitive_info = true;
                }
                else{
                    show_sansitive_info = false;
                }
                getReportData();
            });

            $(document).on('click', '.refresh_page', function(e){
                e.preventDefault();
                getReportData();
            });


            const dateRangeField = document.querySelector("#reportRange");
            const dateRangeInput = flatpickr(dateRangeField, {
                mode:"range",
                {{--defaultDate:['{{$firstDate}}','{{sprintf("%s",date("F j, Y"))}}'],--}}
                defaultDate:['{{$firstDate}}','{{$lastDate}}'],
                dateFormat:"F j, Y",
                allowInput:false,
                onClose: function() {
                    dispInvoices = {};
                    dissmissedVal = {};
                    paidVal = {};
                    getReportData();
                },
                plugins: [
                    ShortcutButtonsPlugin({
                        button: [
                            {
                                label: "Today"
                            },
                            {
                                label: "Yesterday"
                            },
                            // {
                            //     label: "All dates"
                            // }
                        ],
                        label: "",
                        onClick: function(index, fp) {
                            var date;
                            switch (index) {
                                case 0:
                                    date = new Date();
                                    break;
                                case 1:
                                    date = new Date(Date.now() - 24 * 60 * 60 * 1000);
                                    break;
                            {{--case 2:--}}
                                    {{--date = ['{{$firstDate}}','{{sprintf("%s",date("F j, Y"))}}'];--}}
                                    {{--break;--}}

                            }
                            fp.setDate(date);
                            fp.close();
                            // fp.set('defaultDate', date);
                        }
                    })
                ]
            });

            getDashboard();

            function getReportData()
            {
                getDashboard();
                table_dt.draw();
                table_dt_table_discrepancy.draw();
            }

            function getDashboard()
            {
                getDashboardData();
                getDashboardDataDiscrepancy();
            }


            function getDashboardData(){
                $.ajax({
                    url: '/spersondatatables.data?summary=1&date_range='+$("#reportRange").val()+'&salesperson_id={{$salespeople->id}}',
                    type: "GET",
                    dataType: "json",
                    success: function (response) {
                        if (response) {
                            if (response.success) {
                                $('#subscriptions').html(response.data.count);
                                $('#revenue').html(moneyFormat(response.data.revenue));

                                var commission = response.data.commission;
                                if(isSet(commission)) {
                                    $('#commissions').html(moneyFormat(commission));
                                    payments.commission = commission;
                                }
                                var paid = response.data.paid;
                                if(isSet(paid)) {
                                    $('#paid').html(moneyFormat(paid));
                                    payments.paid = paid;
                                    if(paid < 0){
                                        $('.paid_stat').removeClass('bg-success-light').addClass('text-white').addClass('bg-danger');
                                    }
                                    else{
                                        $('.paid_stat').removeClass('bg-danger').removeClass('text-white').addClass('bg-success-light');
                                    }
                                }
                                calculateToPay();
                            }
                            else {
                                console.log("Error");
                            }
                        }
                        else {
                            console.log('No response');
                        }
                    },
                    error: function (response) {
                        console.log(response);
                    }
                });

            }
            function getDashboardDataDiscrepancy(){
                $.ajax({
                    url: '/spersondatatables.data?summary=1&discrepancy=1&date_range='+$("#reportRange").val()+'&salesperson_id={{$salespeople->id}}',
                    type: "GET",
                    dataType: "json",
                    success: function (response) {
                        if (response) {
                            if (response.success) {
                                var discrepancy = response.data.discrepancy;
                                var discrepancy_box = $('.discrepancy_box');
                                if(discrepancy) {
                                    $('#discrepancies').html(moneyFormat(discrepancy));
                                    payments.discrepancies = discrepancy;
                                    discrepancy_box.removeClass('d-none');
                                    if(discrepancy < 0){
                                        $('.discrepancies_stat').removeClass('bg-success-light').addClass('text-white').addClass('bg-danger');
                                    }
                                    else{
                                        $('.discrepancies_stat').removeClass('bg-danger').removeClass('text-white').addClass('bg-success-light');
                                    }
                                }
                                else{
                                    discrepancy_box.addClass('d-none');
                                    $('#discrepancies').html(moneyFormat(0));
                                    payments.discrepancies = 0;
                                    $('.discrepancies_stat').removeClass('bg-success-light').removeClass('text-white').removeClass('bg-danger');
                                }
                                calculateToPay();

                            }
                            else {
                                console.log("Error");
                            }
                        }
                        else {
                            console.log('No response');
                        }
                    },
                    error: function (response) {
                        console.log(response);
                    }
                });

            }
            function calculateToPay(){
                const toPay = payments.commission * 1 - ( payments.discrepancies * (-1) + payments.paid * 1);
                if(isSet(toPay)) {
                    $('#topay').html(moneyFormat(toPay));
                    payments.toPay = toPay;
                    if(toPay < 0){
                        $('.topay_stat').removeClass('bg-success-light').addClass('text-white').addClass('bg-danger');
                    }
                    else{
                        $('.topay_stat').removeClass('bg-danger').removeClass('text-white').addClass('bg-success-light');
                    }
                }
            }


            var table_discrepancy = $('table#discrepancy_table');
            var table_dt_table_discrepancy = table_discrepancy.DataTable({
                // stateSave: true,
                createdRow: function( row, data, dataIndex ) {
                    var invoice_id = data.id;
                    dispInvoices[invoice_id] = invoice_id;
                    if ( data.own > 0 ) {
                        $(row).addClass('bg_to_pay');
                    }
                    if ( data.sales_price <= 0 ) {
                        $(row).addClass('bg_refunded');
                    }
                    $.each(data.salespeople, function( index, value ) {
                        if(value.salespersone.id == {{$salespeople->id}} && value.paid_at) {
                            $(row).children(':nth-child(7)').addClass('bg-success-light');
                        }
                        if(value.salespersone.id == {{$salespeople->id}} && (value.discrepancy*1)) {
                            dissmissedVal[invoice_id] = value.discrepancy;
                        }
                    });
                },
                processing: true,
                serverSide: true,
                paging: false,
                order: [
                    [ 0, "desc" ],
                    [ 1, "desc" ]
                ],
                ajax: {
                    url: "/spersondatatables.data",
                    data: function ( d ) {
                        return $.extend( {}, d, {
                            date_range: $("#reportRange").val(),
                            discrepancy: 1,
                            salesperson_id: '{{$salespeople->id}}',
                            dispInvoices: dispInvoices
                        } );
                    }
                },
                pageLength: 100,
                searching: false,
                lengthChange: false,
                // bStateSave: true,
                // dom: 'Bflrtip',
                // buttons: [
                //     'copy', 'excel', 'pdf', 'print', 'colvis'
                // ],
                columns: [
                    { data: 'access_date', name: 'access_date', "searchable": false, orderData: [ 0, 1 ],  render: function ( data, type, row ){
                            return formatDate(data);
                        } },
                    { data: 'id', name: 'id', "searchable": false,  "visible": false },

                    { data: 'customer.first_name', name: 'customer.first_name',"sortable": false,  render: function ( data, type, row ){
                            return '<a href="/customers/'+row.customer.id+'" target="_blank">'+row.customer.first_name+' '+row.customer.last_name+'</a><div>'+row.customer.email+'</div><div>'+row.customer.phone_number+'</div>'
                        }},
                    { data: 'paid', name: 'paid', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                if(show_sansitive_info) {
                                    return moneyFormat(data) + calculateEarnings(row);
                                }
                                else{
                                    return moneyFormat(data);
                                }
                            }
                            else{
                                return '';
                            }
                        } },
                    { data: 'own', name: 'own', "sortable": true, className:"text-nowrap", "searchable": false, render: function ( data, type, row ){
                            if(data > 0) {
                                return '<div class="text-danger">' + moneyFormat(data) + '</div>';
                            }
                            else{
                                return '';
                            }
                        }  },
                    { data: 'salespersone', name: 'salespersone',"sortable": false,"searchable": false, className:"text-nowrap", render: function ( data, type, row ){
                            return generateSalespeople(row);
                        }  },
                    { data: 'customer.email', name: 'customer.email', "sortable": false ,  "visible": false},
                    { data: 'customer.phone_number', name: 'customer.phone_number', "sortable": false, className:"text-nowrap" ,  "visible": false},
                    { data: 'id', name: 'id', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                return '<a title="Open invoice in a new tab" target="_blank" href="/invoices/' + data + '"><span class="badge badge-success">View</span></a>';
                            }
                            else{
                                return '';
                            }
                        }},
                    @if( Gate::check('payments-manage'))
                    { data: 'id', name: 'id', "searchable": false, "sortable": false, "visible": true, render: function ( data, type, row ){
                            return showPayButtonDisp(row);
                        }},
                    @endif
                    { data: 'customer.last_name', name: 'customer.last_name', "sortable": false,  "visible": false }

                ]
            });

            var table = $('table#customers_table');
            var table_dt = table.DataTable({
                // stateSave: true,
                createdRow: function( row, data, dataIndex ) {
                    if ( data.own > 0 ) {
                        $(row).addClass('bg_to_pay');
                    }
                    if ( data.sales_price <= 0 ) {
                        $(row).addClass('bg_refunded');
                    }
                    $.each(data.salespeople, function( index, value ) {
                        if(value.salespersone.id == {{$salespeople->id}} && value.paid_at) {
                            $(row).children(':nth-child(7)').addClass('bg-success-light');
                        }
                    });
                },
                processing: true,
                serverSide: true,
                paging: false,
                order: [
                    [ 0, "desc" ],
                    [ 1, "desc" ]
                ],
                ajax: {
                    url: "/spersondatatables.data",
                    data: function ( d ) {
                        return $.extend( {}, d, {
                            date_range: $("#reportRange").val(),
                            salesperson_id: '{{$salespeople->id}}'
                        } );
                    }
                },
                pageLength: 100,
                // searching: false,
                lengthChange: false,
                // bStateSave: true,
                // dom: 'Bflrtip',
                // buttons: [
                //     'copy', 'excel', 'pdf', 'print', 'colvis'
                // ],
                columns: [
                    { data: 'access_date', name: 'access_date', "searchable": false, orderData: [ 0, 1 ],  render: function ( data, type, row ){
                            return formatDate(data);
                        } },
                    { data: 'id', name: 'id', "searchable": false,  "visible": false },

                    { data: 'customer.first_name', name: 'customer.first_name',"sortable": false,  render: function ( data, type, row ){
                            return '<a href="/customers/'+row.customer.id+'" target="_blank">'+row.customer.first_name+' '+row.customer.last_name+'</a><div>'+row.customer.email+'</div><div>'+row.customer.phone_number+'</div>'
                        }},
                    { data: 'paid', name: 'paid', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                if(show_sansitive_info) {
                                    return moneyFormat(data) + calculateEarnings(row);
                                }
                                else{
                                    return moneyFormat(data);
                                }
                            }
                            else{
                                return '';
                            }
                        } },
                    { data: 'own', name: 'own', "sortable": true, className:"text-nowrap", "searchable": false, render: function ( data, type, row ){
                            if(data > 0) {
                                return '<div class="text-danger">' + moneyFormat(data) + '</div>';
                            }
                            else{
                                return '';
                            }
                        }  },
                    { data: 'salespersone', name: 'salespersone',"sortable": false,"searchable": false, className:"text-nowrap", render: function ( data, type, row ){
                            return generateSalespeople(row);
                        }  },
                    { data: 'customer.email', name: 'customer.email', "sortable": false ,  "visible": false},
                    { data: 'customer.phone_number', name: 'customer.phone_number', "sortable": false, className:"text-nowrap" ,  "visible": false},
                    { data: 'id', name: 'id', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                return '<a title="Open invoice in a new tab" target="_blank" href="/invoices/' + data + '"><span class="badge badge-success">View</span></a>';
                            }
                            else{
                                return '';
                            }
                        }},
                        @if( Gate::check('payments-manage'))
                    { data: 'id', name: 'id', "searchable": false, "sortable": false, "visible": true, render: function ( data, type, row ){
                            return showPayButton(row);
                        }},
                        @endif
                    { data: 'customer.last_name', name: 'customer.last_name', "sortable": false,  "visible": false }

                ]
            });



            $(document).on('click', 'button.pay', function(){
                setPaidAjax($(this).data('invoice_id'), $(this).data('paid_amount'), 'pay');
            });

            $(document).on('click', 'button.pay_disc', function(){
                setPaidAjax($(this).data('invoice_id'), $(this).data('paid_amount'), 'pay_disc');
            });

            $(document).on('click', 'button.cancel', function(){
                setPaidAjax($(this).data('invoice_id'), $(this).data('paid_amount'), 'cancel');
            });

            function setPaidAjax(invoice_id, paid_amount, action){
                var dataObj = {
                    'invoice_id': invoice_id,
                    'salespeople_id': {{$salespeople->id}},
                    'paid_amount': paid_amount,
                    'action': action
                };
                var err_box = $('#error_' + invoice_id);
                err_box.html('');
                var current_button = $('button.'+action+'[data-invoice_id="'+invoice_id+'"]');
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
                    url: '/setpaid',
                    type: "POST",
                    dataType: "json",
                    data: dataObj,
                    success: function (response) {
                        if (response) {
                            if (response.success) {
                                getReportData();
                            }
                            else {
                                err_box.html('Error: ' + response.message);
                                current_button.html(button_text);
                                $('.btn').prop('disabled', false);
                                console.log(response);
                            }
                        }
                        else {
                            err_box.html('Error!');
                            current_button.html(button_text);
                            $('.btn').prop('disabled', false);
                            console.log(response);
                        }
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
                        console.log(response);
                    }
                });
            }

            function showPayButton(row){
                var commissionlog = '<div class="small">';
                $.each(row.commission_payments, function(index, value){
                    if(value.salespeople_id == {{$salespeople->id}}) {
                        commissionlog += '<div class="text-nowrap">Paid ' + formatDate2(value.created_at) + ' ' + moneyFormat(value.paid_amount) + '</div>';
                    }
                });
                commissionlog += '</div>';
                var html_str = '';
                $.each(row.salespeople, function( index, value ) {
                    if(value.salespersone.id == {{$salespeople->id}}) {
                        if(!(value.earnings*1) && !(value.discrepancy*1) && !(value.paid_amount*1)) {
                            var add_info = '';
                            var add_commission = '';
                            if (show_sansitive_info) {
                                add_info = 'Total ' + moneyFormat(value.paid_amount);
                                add_commission = commissionlog;
                            }
                            html_str = '<div class="mb-2" style="line-height: 1.2;">' + add_commission +
                                '<div class="text-nowrap"><strong>' + add_info + '</strong></div>' +
                                '</div>';
                        }
                        else {
                            if (!value.paid_at) {
                                @if( Gate::check('payments-manage'))
                                    var pay_button_str = 'Set "Paid"';
                                    if (show_sansitive_info) {
                                        pay_button_str += '<div class="small">' + moneyFormat(value.earnings) + '</div>';
                                    }
                                    html_str = '<button class="btn btn-success pay w-100" data-invoice_id="' + row.id + '" data-paid_amount="' + value.earnings + '">' + pay_button_str + '</button>';
                                    html_str += '<div class="text-danger err_box"><small><span id="error_' + row.id + '" style="line-height: 1.1;"></span></small></div>';
                                @else
                                    html_str = '<div style="min-height: 50px;"></div>';
                                @endif
                            }
                            else {
                                var add_info = '';
                                var add_commission = '';
                                if (show_sansitive_info) {
                                    add_info = 'Total ' + moneyFormat(value.paid_amount);
                                    add_commission = commissionlog;
                                }
                                html_str = '<div class="mb-2" style="line-height: 1.2;">' + add_commission +
                                    // '<div class="text-nowrap">Paid ' + formatDate2(value.paid_at) + '</div>' +
                                    '<div class="text-nowrap"><strong>' + add_info + '</strong></div>' +
                                    '</div>';
                                @if( Gate::check('payments-manage'))
                                if (value.discrepancy * 1 !== 0) {
                                    var discrepancy_button_str = '<div class="text-nowrap">Fix Discrepancy</div> ';
                                    var colorClass = 'bg-success';
                                    if (value.discrepancy < 0) {
                                       colorClass = ' bg-danger ';
                                    }
                                    if (show_sansitive_info) {
                                        discrepancy_button_str += '<div class="small">' + moneyFormat(value.discrepancy) + '</div>';
                                    }
                                    html_str += '<button class="btn btn-primary pay_disc w-100 ' + colorClass + '" data-invoice_id="' + row.id + '" data-paid_amount="' + value.discrepancy + '">' + discrepancy_button_str + '</button>';
                                }
                                else {
                                    var cancel_button_str = 'Unset "Paid"';
                                    // if(show_sansitive_info){
                                    //     cancel_button_str += '<div class="small">' + moneyFormat(value.earnings) + '</div>';
                                    // }
                                    html_str += '<button class="btn btn-sm btn-outline-danger cancel w-100" data-invoice_id="' + row.id + '" data-paid_amount="' + value.earnings + '"><span>' + cancel_button_str + '</small></button>';
                                }
                                html_str += '<div class="text-danger err_box"><small><span id="error_' + row.id + '" ></span></small></div>';
                                @endif
                            }
                        }
                    }
                });
                return html_str;
            }

            function showPayButtonDisp(row){
                var commissionlog = '<div class="small">';
                $.each(row.commission_payments, function(index, value){
                    if(value.salespeople_id == {{$salespeople->id}}) {
                        commissionlog += '<div class="text-nowrap">Paid ' + formatDate2(value.created_at) + ' ' + moneyFormat(value.paid_amount) + '</div>';
                    }
                });
                commissionlog += '</div>';
                var html_str = '';
                $.each(row.salespeople, function( index, value ) {
                    if(value.salespersone.id == {{$salespeople->id}}) {
                        if(!(value.earnings*1) && !(value.discrepancy*1) && !(value.paid_amount*1)) {
                            if(isSet(dissmissedVal[row.id])){
                                html_str = 'Dismissed ' +   moneyFormat(dissmissedVal[row.id]);
                            }
                        }
                        else {
                            if (!value.paid_at) {
                                        @if( Gate::check('payments-manage'))
                                var pay_button_str = 'Set "Paid"';
                                if (show_sansitive_info) {
                                    pay_button_str += '<div class="small">' + moneyFormat(value.earnings) + '</div>';
                                }
                                html_str = '<button class="btn btn-success pay w-100" data-invoice_id="' + row.id + '" data-paid_amount="' + value.earnings + '">' + pay_button_str + '</button>';
                                html_str += '<div class="text-danger err_box"><small><span id="error_' + row.id + '" style="line-height: 1.1;"></span></small></div>';
                                @else
                                    html_str = '<div style="min-height: 50px;"></div>';
                                @endif
                            }
                            else {
                                var add_info = '';
                                var add_commission = '';
                                if (show_sansitive_info) {
                                    add_info = 'Total ' + moneyFormat(value.paid_amount);
                                    add_commission = commissionlog;
                                }
                                html_str = '<div class="mb-2" style="line-height: 1.2;">' + add_commission +
                                    // '<div class="text-nowrap">Paid ' + formatDate2(value.paid_at) + '</div>' +
                                    '<div class="text-nowrap"><strong>' + add_info + '</strong></div>' +
                                    '</div>';
                                @if( Gate::check('payments-manage'))
                                if (value.discrepancy * 1 !== 0) {
                                    var discrepancy_button_str = 'Dismiss';
                                    var colorClass = 'bg-success';
                                    if (value.discrepancy < 0) {
                                        colorClass = ' bg-danger ';
                                    }
                                    if (show_sansitive_info) {
                                        discrepancy_button_str += '<div class="small">' + moneyFormat(value.discrepancy) + '</div>';
                                    }
                                    html_str += '<button class="btn btn-primary pay_disc w-100 ' + colorClass + '" data-invoice_id="' + row.id + '" data-paid_amount="' + value.discrepancy + '">' + discrepancy_button_str + '</button>';
                                }
                                else {
                                    var cancel_button_str = 'Unset "Paid"';
                                    // if(show_sansitive_info){
                                    //     cancel_button_str += '<div class="small">' + moneyFormat(value.earnings) + '</div>';
                                    // }
                                    html_str += '<button class="btn btn-sm btn-outline-danger cancel w-100" data-invoice_id="' + row.id + '" data-paid_amount="' + value.earnings + '"><span>' + cancel_button_str + '</small></button>';
                                }
                                html_str += '<div class="text-danger err_box"><small><span id="error_' + row.id + '" ></span></small></div>';
                                @endif
                            }
                        }
                    }
                });
                return html_str;
            }

            function generateSalespeople(row){
                var ret_data = '';
                if(isSet(row) ) {
                    if(row.salespeople.length){
                        $.each(row.salespeople, function( index, value ) {
                            var additions = '';
                            if(show_sansitive_info) {
                                @if( Gate::check('invoice-create') || Gate::check('salespeople-reports-view-all'))
                                    if (value.earnings) {
                                        additions = ' <span><small>' + moneyFormat(value.earnings) + ' <span class="text-muted">(' + value.level.title + ' | ' + value.percentage + '%)</span></small></span>';
                                    }
                                @else
                                    @if( Gate::check('salespeople-reports-view-own'))
                                        if (value.earnings && value.salespersone.id == {{$salespeople->id}}) {
                                            additions = ' <span><small>' + moneyFormat(value.earnings) + ' <span class="text-muted">(' + value.level.title + ' | ' + value.percentage + '%)</span></small></span>';
                                        }
                                    @endif
                                @endif
                            }
                            if(value.sp_type) {
                                if(value.salespersone.id == {{$salespeople->id}}) {
                                    ret_data += '<div class="h4" title="' + value.salespersone.first_name + ' ' + value.salespersone.last_name + '">' +
                                        value.salespersone.name_for_invoice +
                                        additions +
                                        '</div>';
                                }
                                else {
                                    ret_data += '<div>' +
                                        '<a href="/salespeople/' + value.salespersone.id + '" target="_blank" title="' + value.salespersone.first_name + ' ' + value.salespersone.last_name + '">' + value.salespersone.name_for_invoice + '</a>' +
                                        additions +
                                        '</div>';
                                }
                            }
                        });
                        $.each(row.salespeople, function( index, value ) {
                            var additions = '';
                            if(show_sansitive_info) {
                                @if( Gate::check('invoice-create') || Gate::check('salespeople-reports-view-all'))
                                    if (value.earnings) {
                                        additions = ' <span><small>' + moneyFormat(value.earnings) + ' <span class="text-muted">(' + value.level.title + ' | ' + value.percentage + '%)</span></small></span>';
                                    }
                                @else
                                    @if( Gate::check('salespeople-reports-view-own'))
                                        if (value.earnings && value.salespersone.id == {{$salespeople->id}}) {
                                            additions = ' <span><small>' + moneyFormat(value.earnings) + ' <span class="text-muted">(' + value.level.title + ' | ' + value.percentage + '%)</span></small></span>';
                                        }
                                    @endif
                                @endif
                            }
                            if(!value.sp_type) {
                                if(value.salespersone.id == {{$salespeople->id}}) {
                                    ret_data += '<div class="h4" style="line-height: 1.1" title="' + value.salespersone.first_name + ' ' + value.salespersone.last_name + '">' +
                                        '<small>' + value.salespersone.name_for_invoice + '</small>' +
                                        additions +
                                        '</div>';
                                }
                                else {
                                    ret_data += '<div style="line-height: 1.1">' +
                                        '<a href="/salespeople/' + value.salespersone.id + '" target="_blank" title="' + value.salespersone.first_name + ' ' + value.salespersone.last_name + '"><small>' + value.salespersone.name_for_invoice + '</small></a>' +
                                        additions +
                                        '</div>';
                                }
                            }
                        });
                    }
                }
                return ret_data;
            }

            function calculateEarnings(row){
                var earnings = '';
                @can('invoice-create')
                    earnings = 0;
                if(row.salespeople){
                    $.each(row.salespeople, function(i, val){
                        if(val.earnings){
                            earnings = earnings*1 + val.earnings*1;
                        }
                    })
                }
                if(earnings){
                    var percentage = earnings*100/row.paid;
                    var profit = row.paid*1 - earnings;
                    return '<div class="small"><span class="text-success">'+moneyFormat(profit)+'</span></div><div class="small"><small class="text-muted">'+moneyFormat(earnings)+'('+percentage.toFixed(2)+'%)</small></div>';
                }
                @endcan
                    return earnings;
            }

            var moneyFormat = function(num){
                if(!isSet(num)){
                    num = 0;
                }
                var add_to_res = '';
                if(num < 0){
                    num = num * (-1);
                    add_to_res = '-';
                }
                var str = num.toString().replace("$", ""), parts = false, output = [], i = 1, formatted = null;
                if(str.indexOf(".") > 0) {
                    parts = str.split(".");
                    str = parts[0];
                }
                str = str.split("").reverse();
                for(var j = 0, len = str.length; j < len; j++) {
                    if(str[j] != ",") {
                        output.push(str[j]);
                        if(i%3 == 0 && j < (len - 1)) {
                            output.push(",");
                        }
                        i++;
                    }
                }
                formatted = output.reverse().join("");
                return( add_to_res + "$" + formatted + ((parts) ? "." + parts[1].substr(0, 2) : ""));
            };

            function formatDate(date){
                var formattedDate = new Date(date+'T00:00:01');
                var d = formattedDate.getDate();
                var m =  formattedDate.getMonth();
                m += 1;  // JavaScript months are 0-11
                var y = formattedDate.getFullYear();
                const month = formattedDate.toLocaleString('default', { month: 'short' });
                return month + " " + d + " " + y;
            }

            function formatDate2(date){
                var formattedDate = new Date(date.replace(' ', 'T'));
                var d = formattedDate.getDate();
                var m =  formattedDate.getMonth();
                m += 1;  // JavaScript months are 0-11
                var y = formattedDate.getFullYear().toString().substr(-2);
                const month = formattedDate.toLocaleString('default', { month: 'short' });
                return month + " " + d + " " + y;
            }

            function isSet(variable){
                if(typeof variable !== "undefined" && variable !== null) {
                    return true;
                }
                return false;
            }
        });
    </script>
@endsection