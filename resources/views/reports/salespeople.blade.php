@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12 m-auto">
                {{--<div class="row">--}}
                    {{--<div class="col-lg-12 margin-tb">--}}
                        {{--<div class="pull-left">--}}
                            {{--@can('invoice-create')--}}
                                {{--<a class="btn btn-success mb-4 mt-2" href="{{ route('customers-invoices.create') }}"> Create User & Email Invoice</a>--}}
                            {{--@endcan--}}
                            {{--<h2>Salespeople Report</h2>--}}
                        {{--</div>--}}
                    {{--</div>--}}
                {{--</div>--}}
                <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <label class="w-100">
                            Date range
                            <input class="form-control" type="text" id="reportRange">
                        </label>
                    </div>
                </div>

                <div style="padding-right: 11px;padding-left: 11px;">
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3 px-1 mb-1 d-none commission">
                            <div class="card order-card bg-info">
                                <div class="text-center p-2 text-white">
                                    <h3 class="text-center"><span id="commissions">0</span></h3>
                                    <h3 class="lead text-center">Commissions</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($message = Session::get('success'))
                    <div class="alert alert-success">
                        <p>{{ $message }}</p>
                    </div>
                @endif


                <table class="table table-striped table-bordered table-responsive-sm w-100" id="salespeople_report_table">
                    <thead>
                    <tr>
                        <th>Salesperson</th>
                        <th>Commission</th>
                        <th>Total Sales</th>
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
                {{--defaultDate:['{{$firstDate}}','{{sprintf("%s",date("F j, Y"))}}'],--}}
                defaultDate:['{{$lastDate}}','{{$lastDate}}'],
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

            getSummaryData();

            function getReportData()
            {
                getSummaryData();
                table_dt.draw();
            }

            function getSummaryData()
            {
                $.ajax({
                    url: '/spreportsdatatables.data?summary=1&date_range='+$("#reportRange").val(),
                    type: "GET",
                    dataType: "json",
                    success: function (response) {
                        if (response) {
                            if (response.success) {
                                var commission = response.data.commission;
                                if(commission) {
                                    $('.commission').removeClass('d-none');
                                    $('#commissions').html(moneyFormat(commission));
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


            var table = $('table#salespeople_report_table');
            var table_dt = table.DataTable({
                // stateSave: true,
                processing: true,
                serverSide: true,
                order: [
                    [ 1, "desc" ]
                ],
                ajax: {
                    url: "/spreportsdatatables.data",
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
                    { data: 'name_for_invoice', name: 'salespeoples.name_for_invoice', "searchable": true, "sortable": true, render: function(data, type, row){
                        return generateSalespersonName(row, data);
                        } },
                    { data: 'sum', name: 'sum', "searchable": false, "sortable": true,  render: function ( data, type, row ){
                            return moneyFormat(data);
                        }  },
                    { data: 'total_sales', name: 'total_sales', "searchable": false, "sortable": true }
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

            function generateSalespersonName(row, data){
                return '<a href="/salespeople/' +row.salespeople_id + '" target="_blank" title="' + row.first_name + ' ' + row.last_name + '">' +
                    data + '</a>' +
                    ' <small title="Current level">' + row.level2.level.title + ' | ' + row.level2.percentage + '%</small>';
            }
        });
    </script>
@endsection