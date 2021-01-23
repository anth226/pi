@extends('layouts.app')

@section('style')
    <style>
        .bg_to_pay{
            background-color: rgba(255,0,0,.1) !important;
        }
        .bg_refunded{
            background-color: rgba(0,0,0,.3) !important;
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
                            {{ $salespeople->level->level->title }} | {{ $salespeople->level->percentage }}%
                        </div>
                    @endcan
                </div>
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-5 m-auto">
                            <h4 class="mb-0"><strong>Payments</strong></h4>
                            <div><small class="text-danger">Payable: <strong>{{$to_pay}}</strong></small></div>
                        </div>
                        <div class="col-md-7">
                            <label class="w-100">
                                Date range
                                <input class="form-control" type="text" id="reportRange2">
                            </label>
                        </div>
                    </div>


                    <div class="row mb-1">
                        <div class="col-md-5 mb-1"></div>
                        <div class="col-md-7 mb-1">
                            <div class="card order-card bg-info">
                                <div class="text-center p-2 text-white">
                                    <h3 class="text-center mb-0"><span id="paid">0</span></h3>
                                    <div class="lead text-center mb-0"><small>Paid</small></div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <table class="table table-striped table-bordered table-responsive-sm w-100" id="payments_log">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Previous Balance</th>
                            <th>Payments</th>
                            <th>New Balance</th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>


            <div class="row mt-4">
                <div class="col-md-7 m-auto">
                    <h4 class="mb-0"><strong>Prime Sales</strong></h4>
                </div>
                <div class="col-md-5">
                    <label class="w-100">
                        Date range
                        <input class="form-control" type="text" id="reportRange">
                    </label>
                </div>
            </div>

            <div style="padding-right: 11px;padding-left: 11px;">
                <div class="row mb-1">
                    <div class="col-md-6 col-lg-4 px-1 mb-1">
                        <div class="card order-card bg-info">
                            <div class="text-center p-2 text-white">
                                <h3 class="text-center"><span id="subscriptions">0</span></h3>
                                <h3 class="lead text-center mb-0">Prime Sales</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 px-1 mb-1">
                        <div class="card order-card bg-primary">
                            <div class="text-center p-2 text-white">
                                <h3 class="text-center"><span id="revenue">0</span></h3>
                                <h3 class="lead text-center mb-0">Revenue</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 px-1 mb-1 commission">
                        <div class="card order-card bg-info">
                            <div class="text-center p-2 text-white">
                                <h3 class="text-center"><span id="commissions">0</span></h3>
                                <h3 class="lead text-center mb-0">Commissions</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <table class="table table-striped table-bordered table-responsive-sm w-100" id="customers_table">
                <thead>
                <tr>
                    <th>Access Date</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Amount</th>
                    <th>Salesperson</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>To Pay</th>
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

            const dateRangeField = document.querySelector("#reportRange");
            const dateRangeField2 = document.querySelector("#reportRange2");

            const dateRangeInput = flatpickr(dateRangeField, {
                mode:"range",
                {{--defaultDate:['{{$firstDate}}','{{sprintf("%s",date("F j, Y"))}}'],--}}
                defaultDate:['{{$firstDate}}','{{$lastDate}}'],
                dateFormat:"F j, Y",
                allowInput:false,
                onClose: function() {
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
            const dateRangeInput2 = flatpickr(dateRangeField2, {
                mode:"range",
                {{--defaultDate:['{{$firstDate}}','{{sprintf("%s",date("F j, Y"))}}'],--}}
                defaultDate:['{{$firstDate}}','{{$lastDate}}'],
                dateFormat:"F j, Y",
                allowInput:false,
                onClose: function() {
                    getPaymentsReportData()
                }
            });

            getDashboardData();

            function getReportData()
            {
                getDashboardData();
                table_dt.draw();
            }

            function getPaymentsReportData()
            {
                table_pay_dt.draw();
            }

            function getDashboardData()
            {
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
                                if(commission) {
                                    $('.commission').removeClass('d-none');
                                    var percent_commission = commission*100/response.data.revenue;
                                    $('#commissions').html(moneyFormat(commission) + '<span class="small text-muted" style="font-size: 1rem;"> '+percent_commission.toFixed(2)+'%</span>');
                                }
                                else{
                                    $('.commission').addClass('d-none');
                                }
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
                },
                processing: true,
                serverSide: true,
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
                pageLength: 10,
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
                            return '<a href="/customers/'+row.customer.id+'" target="_blank">'+row.customer.first_name+' '+row.customer.last_name+'</a>'
                        }},
                    { data: 'paid', name: 'paid', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                return moneyFormat(data) + calculateEarnings(row);
                            }
                            else{
                                return '';
                            }
                        } },
                    { data: 'salespersone', name: 'salespersone',"sortable": false,"searchable": false, className:"text-nowrap", render: function ( data, type, row ){
                            return generateSalespeople(row);
                        }  },
                    { data: 'customer.email', name: 'customer.email', "sortable": false },
                    { data: 'customer.phone_number', name: 'customer.phone_number', "sortable": false, className:"text-nowrap"},
                    { data: 'own', name: 'own', "sortable": true, className:"text-nowrap", "searchable": false, render: function ( data, type, row ){
                            if(data > 0) {
                                return '<div class="text-danger">' + moneyFormat(data) + '</div>';
                            }
                            else{
                                return '';
                            }
                        }  },
                    { data: 'id', name: 'id', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                return '<a title="Open invoice in a new tab" target="_blank" href="/invoices/' + data + '"><span class="badge badge-success">View</span></a>';
                            }
                            else{
                                return '';
                            }
                        }},
                    { data: 'customer.last_name', name: 'customer.last_name', "sortable": false,  "visible": false }

                ]
            });


            var table_pay = $('table#payments_log');
            var table_pay_dt = table_pay.DataTable({
                // stateSave: true,
                processing: true,
                serverSide: true,
                order: [
                    [ 0, "desc" ]
                ],
                ajax: {
                    url: "/sppaylogdatatables.data",
                    data: function ( d ) {
                        return $.extend( {}, d, {
                            date_range: $("#reportRange2").val(),
                            salesperson_id: '{{$salespeople->id}}'
                        } );
                    }
                },
                pageLength: 10,
                searching: false,
                lengthChange: false,
                drawCallback: function(settings)
                {
                    let paid_amount = 0;
                    let new_balance = 0;
                    let paid_data = settings.json.data;
                    let v_id = 0;
                    if(isSet(paid_data) && paid_data.length){
                        $.each(paid_data, function(i, v){
                            if(v.id > v_id){
                                v_id = v.id;
                                new_balance = v.unpaid_balance * 1 - v.paid_amount *1;
                            }
                            paid_amount = paid_amount + v.paid_amount * 1;
                        })
                    }
                    $('#paid').html(moneyFormat(paid_amount));
                    // $('#balance').html(moneyFormat(new_balance));
                },

                // bStateSave: true,
                // dom: 'Bflrtip',
                // buttons: [
                //     'copy', 'excel', 'pdf', 'print', 'colvis'
                // ],
                columns: [
                    { data: 'created_at', name: 'created_at', "searchable": false, "sortable": true, render: function(data, type, row){
                            return formatDate2(data);
                        }  },
                    { data: 'unpaid_balance', name: 'unpaid_balance', "searchable": false, "sortable": true,  render: function ( data, type, row ){
                            return moneyFormat(data);
                        }  },
                    { data: 'paid_amount', name: 'paid_amount', "searchable": false, "sortable": true,  render: function ( data, type, row ){
                            return moneyFormat(data * -1);
                        }  },
                    { data: 'paid_amount', name: 'paid_amount', "searchable": false, "sortable": false,  render: function ( data, type, row ){
                            return moneyFormat(row.unpaid_balance - row.paid_amount);
                        }  }
                ]
            });

            function generateSalespeople(row){
                var ret_data = '';
                if(isSet(row) ) {
                    if(row.salespeople.length){
                        $.each(row.salespeople, function( index, value ) {
                            var additions = '';
                            @if( Gate::check('invoice-create') || Gate::check('salespeople-reports-view-all'))
                                if(value.earnings) {
                                    additions = ' <span><small>' + moneyFormat(value.earnings) + ' <span class="text-muted">('+value.level.title+' | '+value.percentage+'%)</span></small></span>';
                                }
                            @else
                                    @if( Gate::check('salespeople-reports-view-own'))
                                        if(value.earnings && value.salespersone.id == {{$salespeople->id}}) {
                                            additions = ' <span><small>' + moneyFormat(value.earnings) + ' <span class="text-muted">('+value.level.title+' | '+value.percentage+'%)</span></small></span>';
                                        }
                                    @endif
                            @endif
                            if(value.sp_type) {
                                if(value.salespersone.id == {{$salespeople->id}}) {
                                    ret_data += '<div title="' + value.salespersone.first_name + ' ' + value.salespersone.last_name + '">' +
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
                            @if( Gate::check('invoice-create') || Gate::check('salespeople-reports-view-all'))
                                if(value.earnings) {
                                    additions = ' <span><small>' + moneyFormat(value.earnings) + ' <span class="text-muted">('+value.level.title+' | '+value.percentage+'%)</span></small></span>';
                                }
                            @else
                                    @if( Gate::check('salespeople-reports-view-own'))
                                        if(value.earnings  && value.salespersone.id == {{$salespeople->id}}) {
                                            additions = ' <span><small>' + moneyFormat(value.earnings) + ' <span class="text-muted">('+value.level.title+' | '+value.percentage+'%)</span></small></span>';
                                        }
                                    @endif
                            @endif
                            if(!value.sp_type) {
                                if(value.salespersone.id == {{$salespeople->id}}) {
                                    ret_data += '<div style="line-height: 1.1" title="' + value.salespersone.first_name + ' ' + value.salespersone.last_name + '">' +
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
                var y = formattedDate.getFullYear();
                const month = formattedDate.toLocaleString('default', { month: 'short' });
                return month + " " + d + " " + y + " " + formattedDate.getHours() + ":" + formattedDate.getMinutes();
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