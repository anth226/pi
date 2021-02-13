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
        <div class="row">
            <div class="col-lg-12 m-auto">
                <div class="row">
                    <div class="col-lg-12 margin-tb">
                        <div class="pull-left">
                            @can('invoice-create')
                                <a class="btn btn-success mb-4 mt-2" href="{{ route('invoice-generator.create') }}"> Generate Invoice</a>
                            @endcan
                            <h2>Generated Invoices</h2>
                        </div>
                        <div class="pull-right">
                            {{--@can('customer-create')--}}
                            {{--<a class="btn btn-success mb-4 mt-2 btn-sm" href="{{ route('customers.create') }}"> Add New Customer</a>--}}
                            {{--@endcan--}}
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
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Paid</th>
                        <th>To Pay</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {

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
                    url: "/invoicesgenerateddatatables.data"
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

                    { data: 'first_name', name: 'first_name',"sortable": false,  render: function ( data, type, row ){
                            return row.first_name+' '+row.last_name
                        }},


                    { data: 'email', name: 'email', "sortable": false },
                    { data: 'phone_number', name: 'phone_number', "sortable": false, className:"text-nowrap"},
                    { data: 'paid', name: 'paid', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                if(data > 0) {
                                    return moneyFormat(data);
                                }
                                else{
                                    return '<div class="text-danger">' + moneyFormat(data) + '</div>';
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
                    { data: 'id', name: 'id', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                return '<a title="Open invoice" href="/invoice-generator/' + data + '"><span class="badge badge-success">View</span></a>';
                            }
                            else{
                                return '';
                            }
                        }},
                    { data: 'last_name', name: 'last_name', "sortable": false,  "visible": false }

                ]
            });

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