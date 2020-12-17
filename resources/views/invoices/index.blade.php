@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        @can('invoice-create')
                            <a class="btn btn-success mb-4 mt-2" href="{{ route('customers-invoices.create') }}"> Create User & Email Invoice</a>
                        @endcan
                        <h2>Dashboard</h2>
                    </div>
                    <div class="pull-right">
                        {{--@can('customer-create')--}}
                        {{--<a class="btn btn-success mb-4 mt-2 btn-sm" href="{{ route('customers.create') }}"> Add New Customer</a>--}}
                        {{--@endcan--}}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-lg-4">
                    <label class="w-100">
                        Date range
                        <input class="form-control" type="text" id="reportRange">
                    </label>
                </div>
            </div>

            <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 pr-md-0 mb-1">
                        <div class="card order-card" style="background: linear-gradient(45deg, #1E567D, #1D5871);">
                            <div class="text-center p-2 text-white">
                                <h3 class="text-center"><span id="subscriptions">0</span></h3>
                                <h3 class="lead text-center">Subscriptions</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 pr-md-1 pl-md-1 mb-1">
                        <div class="card order-card" style="background: linear-gradient(45deg, #2B97D6, #239FDE);">
                            <div class="text-center p-2 text-white">
                                <h3 class="text-center"><span id="revenue">0</span></h3>
                                <h3 class="lead text-center">Revenue</h3>
                            </div>
                        </div>
                    </div>
            </div>


            @if ($message = Session::get('success'))
                <div class="alert alert-success">
                    <p>{{ $message }}</p>
                </div>
            @endif


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

            const dateRangeInput = flatpickr(dateRangeField, {
                mode:"range",
                defaultDate:['{{$firstDate}}','{{sprintf("%s",date("F j, Y"))}}'],
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
                            {
                                label: "All dates"
                            }
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
                                case 2:
                                    date = ['{{$firstDate}}','{{sprintf("%s",date("F j, Y"))}}'];
                                    break;

                            }
                            fp.setDate(date);
                            fp.close();
                            // fp.set('defaultDate', date);
                        }
                    })
                ]
            });

            getDashboardData();

            function getReportData()
            {
                getDashboardData();
                table_dt.draw();
            }

            function getDashboardData()
            {
                $.ajax({
                    url: '/invoicesdatatables.data?summary=1&date_range='+$("#reportRange").val(),
                    type: "GET",
                    dataType: "json",
                    success: function (response) {
                        if (response) {
                            if (response.success) {
                               $('#subscriptions').html(response.data.count);
                               $('#revenue').html(moneyFormat(response.data.revenue));
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
                processing: true,
                serverSide: true,
                order: [
                    [ 0, "desc" ],
                    [ 1, "desc" ]
                ],
                ajax: {
                    url: "/invoicesdatatables.data",
                    data: function ( d ) {
                        return $.extend( {}, d, {
                            date_range: $("#reportRange").val()
                        } );
                    }
                },
                pageLength: 100,
                // searching: false,
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
                    { data: 'sales_price', name: 'sales_price', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                return moneyFormat(data);
                            }
                            else{
                                return '';
                            }
                        } },
                    { data: 'salespersone', name: 'salespersone',"sortable": false,"searchable": false, render: function ( data, type, row ){
                            return generateSalespeople(row);
                        }  },
                    { data: 'customer.email', name: 'customer.email', "sortable": false },
                    { data: 'customer.phone_number', name: 'customer.phone_number', "sortable": false, className:"text-nowrap"},
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
            function generateSalespeople(row){
                var ret_data = '';
                if(isSet(row) ) {
                    if(row.salespeople.length){
                        $.each(row.salespeople, function( index, value ) {
                            if(value.sp_type) {
                                ret_data += '<div><a href="/salespeople/' + value.salespersone.id + '" target="_blank" title="' + value.salespersone.first_name + ' ' + value.salespersone.last_name + '">' + value.salespersone.name_for_invoice + '</a></div>';
                            }
                        });
                        $.each(row.salespeople, function( index, value ) {
                            if(!value.sp_type) {
                                ret_data += '<div style="line-height: 1.1"><a href="/salespeople/' + value.salespersone.id + '" target="_blank" title="' + value.salespersone.first_name + ' ' + value.salespersone.last_name + '"><small>' + value.salespersone.name_for_invoice + '</small></a></div>';
                            }
                        });
                    }
                }
                return ret_data;
            }

            var moneyFormat = function(num){
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
                return("$" + formatted + ((parts) ? "." + parts[1].substr(0, 2) : ""));
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

            function isSet(variable){
                if(typeof variable !== "undefined" && variable !== null) {
                    return true;
                }
                return false;
            }
        });
    </script>
@endsection