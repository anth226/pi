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


            @if ($message = Session::get('success'))
                <div class="alert alert-success">
                    <p>{{ $message }}</p>
                </div>
            @endif


            <table class="table table-striped table-bordered table-responsive-lg w-100" id="customers_table">
                <thead>
                <tr>
                    <th>Date</th>
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
            var table = $('table#customers_table');
            var table_dt = table.DataTable({
                // stateSave: true,
                processing: true,
                serverSide: true,
                order: [[ 0, "desc" ]],
                ajax: '/datatables.data',
                // searching: false,
                bStateSave: true,
                // dom: 'Bflrtip',
                // buttons: [
                //     'copy', 'excel', 'pdf', 'print', 'colvis'
                // ],
                columns: [
                    { data: 'created_at', name: 'created_at', "searchable": false,  render: function ( data, type, row ){
                            return formatDate(data);
                        } },
                    { data: 'first_name', name: 'first_name',"sortable": false,  render: function ( data, type, row ){
                            return '<a href="/customers/'+row.id+'" target="_blank">'+row.first_name+' '+row.last_name+'</a>'
                        }},
                    { data: 'invoices.sales_price', name: 'invoices.sales_price', "searchable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                return moneyFormat(data);
                            }
                            else{
                                return '';
                            }
                        } },
                    { data: 'invoices.salespersone.name_for_invoice', name: 'invoices.salespersone.name_for_invoice',"sortable": false,"searchable": false, render: function ( data, type, row ){
                            return generateSalespeople(row);
                        }  },
                    { data: 'email', name: 'email', "sortable": false },
                    { data: 'phone_number', name: 'phone_number', "sortable": false },
                    { data: 'invoices.id', name: 'invoices.id', "searchable": false, "sortable": false, render: function ( data, type, row ){
                            if(isSet(data)) {
                                return '<a title="Open invoice in a new tab" target="_blank" href="/invoices/' + data + '"><span class="badge badge-success">View</span></a>';
                            }
                            else{
                                return '';
                            }
                        }},
                    { data: 'last_name', name: 'last_name', "sortable": false,  "visible": false }

                ]
            });
            function generateSalespeople(row){
                var ret_data = '';
                if(isSet(row) &&  isSet(row.invoices) ) {
                    if(isSet(row.invoices.salespersone) && isSet(row.invoices.salespersone.name_for_invoice)){
                        ret_data +=  '<div><a href="/salespeople/'+row.invoices.salespersone.id+'" target="_blank" title="'+row.invoices.salespersone.first_name+' '+row.invoices.salespersone.last_name+'">'+row.invoices.salespersone.name_for_invoice+'</a></div>';
                    }
                    if(isSet(row.invoices.salespeople) && row.invoices.salespeople.length){
                        $.each(row.invoices.salespeople, function( index, value ) {
                            ret_data += '<div style="line-height: 1.1"><a href="/salespeople/'+value.salespersone.id+'" target="_blank" title="'+value.salespersone.first_name+' '+value.salespersone.last_name+'"><small>'+value.salespersone.name_for_invoice+'</small></a></div>';
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
                var formattedDate = new Date(date);
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