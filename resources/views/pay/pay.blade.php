@extends('layouts.app')

@section('popup')
    <div id="popup" class="position-absolute" style="z-index: 99999;"></div>
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12 m-auto">

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
                        <th>Total Sales</th>
                        <th>Commission</th>
                        <th>Payed</th>
                        <th>Commission to pay</th>
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
            var table = $('table#salespeople_report_table');
            var table_dt = table.DataTable({
                // stateSave: true,
                processing: true,
                serverSide: true,
                order: [
                    [ 1, "desc" ]
                ],
                ajax: {
                    url: "/paydatatables.data",
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
                    { data: 'total_sales', name: 'total_sales', "searchable": false, "sortable": true },
                    { data: 'sum', name: 'sum', "searchable": false, "sortable": true,  render: function ( data, type, row ){
                            return moneyFormat(data);
                        }  },
                    { data: 'paid_sum', name: 'paid_sum', "searchable": false, "sortable": true,  render: function ( data, type, row ){
                            return moneyFormat(data);
                        }  },
                    { data: 'sum', name: 'sum', "searchable": false, "sortable": true,  render: function ( data, type, row ){
                            return moneyFormat(calcNonPaid(row));
                        }  },
                    { data: 'id', name: 'id', "searchable": false, "sortable": false, render: function ( data, type, row ){
                                return '<button class="btn btn-primary pay_button" data-salespeople_id="'+data+'" data-default_amount="'+calcNonPaid(row)+'">Pay</button>';
                        }}

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
                return '<a href="/salespeople/' +row.id + '" target="_blank" title="' + row.first_name + ' ' + row.last_name + '">' +
                    data + '</a>' +
                    ' <small title="Current level">' + row.level.level.title + ' | ' + row.level.percentage + '%</small>';
            }

            function calcNonPaid(row){
                var paid_sum = 0;
                var sum = 0;
                if(isSet(row.paid_sum)){
                    paid_sum = row.paid_sum * 1;
                }
                if(isSet(row.sum)){
                    sum = row.sum * 1;
                }
                return  sum - paid_sum;
            }

            $(document).on( 'click', '.pay_button', function (e) {
                const this_el = $(this);
                const salespeople_id = this_el.data('salespeople_id');
                const amount = this_el.data('default_amount');
                const popup = $('#popup');
                let amount_to_pay = 0;
                if(amount > 0){
                    amount_to_pay = amount;
                }
                const input = '<label>Amount <input autofocus id="amount" class="w-100 mb-1" type="number" step="1" value="' + amount_to_pay + '" style="min-width:120px;"></label>';
                const save = '<button data-salespeople_id="' + salespeople_id + '" id="pay_button" class="w-100 btn btn-success btn-sm on_enter mb-1">Pay</button>';
                const cancel = '<button id="cancel_button" class="w-100 btn btn-primary btn-sm">Cancel</button>';
                $('#popup').find('#amount').focus().click();
                const offset2 = this_el.offset();
                popup.css('top', offset2.top - 20);
                popup.css('left', offset2.left - 60);
                popup.html('<div class="position-absolute popup_spec p-2 border shadow bg-white rounded">' + input + save + cancel +'</div>');
            });


            let width = $(window).width();
            $(window).on('resize', function(){
                if($(window).width() !== width){
                    width = $(window).width();
                    $('#popup').html('');
                }
            });

            $('#popup').on('keypress',function(e) {
                if (e.which === 13) {
                    $('.on_enter').click();
                }
            });

            $(document).on( 'click', '#cancel_button', function (e) {
                $('#popup').html('');
            });

            $(document).on( 'click', '#pay_button', function (e) {
                e.preventDefault();
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/setpaid',
                    type: "POST",
                    data: {
                        'salespeople_id': $('#pay_button').data('salespeople_id'),
                        'amount': $('#amount').val()
                    },
                    success: function (response) {
                        if(response.success ) {
                            table.DataTable().ajax.reload(null, false);
                            $('#popup').html('');
                        }
                        else {
                            console.log('No response');
                        }
                    },
                    error: function (response) {
                        console.log(response);
                    }
                });

            });


        });
    </script>
@endsection