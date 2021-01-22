@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12 m-auto">
                <div class="row">
                    <div class="col-lg-12 margin-tb">
                        <div class="pull-left">
                            <h2>Payments Log</h2>
                        </div>
                        <div class="pull-right">
                            @can('payments-manage')
                                <a class="btn btn-primary mb-4 mt-2" href="{{ route('payments') }}"> Payments</a>
                            @endcan
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


                @if ($message = Session::get('success'))
                    <div class="alert alert-success">
                        <p>{{ $message }}</p>
                    </div>
                @endif


                <table class="table table-striped table-bordered table-responsive-sm w-100" id="payments_log">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Salesperson</th>
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
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {

            const dateRangeField = document.querySelector("#reportRange");
            const dateRangeInput = flatpickr(dateRangeField, {
                mode:"range",
                {{--defaultDate:['{{$firstDate}}','{{$lastDate}}'],--}}
                dateFormat:"F j, Y",
                allowInput:false,
                onClose: function() {
                    getReportData();
                }
            });

            function getReportData()
            {
                table_dt.draw();
            }

            var table = $('table#payments_log');
            var table_dt = table.DataTable({
                // stateSave: true,
                processing: true,
                serverSide: true,
                order: [
                    [ 0, "desc" ]
                ],
                ajax: {
                    url: "/paylogdatatables.data"
                },
                pageLength: 100,
                // searching: false,
                // bStateSave: true,
                // dom: 'Bflrtip',
                // buttons: [
                //     'copy', 'excel', 'pdf', 'print', 'colvis'
                // ],
                columns: [
                    { data: 'created_at', name: 'created_at', "searchable": false, "sortable": true, render: function(data, type, row){
                            return formatDate(data);
                        }  },
                    { data: 'salespersone.name_for_invoice', name: 'salespersone.name_for_invoice', "searchable": true, "sortable": true, render: function(data, type, row){
                        return generateSalespersonName(row, data);
                        } },
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

            function generateSalespersonName(row, data){
                return '<a href="/salespeople/' +row.salespeople_id + '" target="_blank" title="' + row.salespersone.first_name + ' ' + row.salespersone.last_name + '">' +
                    data + '</a>' +
                    ' <small title="Current level">' + row.salespersone.level.level.title + ' | ' + row.salespersone.level.percentage + '%</small>' ;
            }

        });
    </script>
@endsection