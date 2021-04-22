@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>{{ $user->name }}</h2>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <strong>Email:</strong>
                        {{ $user->email }}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <table class="table table-striped table-bordered table-responsive-sm w-100" id="invoices_table">
                        <thead>
                        <tr>
                            <th>Created At</th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {

            var task_type = jQuery.parseJSON('{!! $task_type !!}');
            var task_status = jQuery.parseJSON('{!! $task_status !!}');
            var invoice_status = jQuery.parseJSON('{!! $invoice_status !!}');
            var full_path = '{!! $full_path !!}';

            var table = $('table#invoices_table');
            var table_dt = table.DataTable({
                // stateSave: true,
                createdRow: function( row, data, dataIndex ) {

                },
                processing: true,
                serverSide: true,
                order: [
                    [ 0, "desc" ]
                ],
                ajax: {
                    url: "/support/show-tasks",

                },
                pageLength: 100,

                columns: [
                    { data: 'created_at', name: 'created_at', "searchable": false,   render: function ( data, type, row ){
                            var css_class = '';
                            var css_class_2 = ' text-danger ';
                            if(row.task_status == 1){
                                css_class = ' bg-warning ';
                            }
                            if(row.task_status == 2){
                                css_class = ' bg-success ';
                            }

                            var i_status = invoice_status[row.invoice.status];
                            if(row.invoice.sales_price == 0 && row.invoice.paid == 0){
                                i_status = invoice_status[3];
                            }

                            if(i_status == invoice_status[1]){
                                css_class_2 = ' text-success ';
                            }


                            var res_html =
                                 '<div class="col-12 mb-4">'+
                                        '<div class="card h-100">'+
                                            '<div class="card-header '+css_class+'">'+ task_status[row.task_status]+'</div>'+
                                            '<div class="card-body">'+
                                                '<div class="row">'+
                                                '<div class="col-md-6">'+
                                                '<h5 class="card-title">'+task_type[row.task_type]+'</h5>'+
                                                '<p class="card-text">' +
                                                    '<div>Added at: '+data+'</div>'+
                                                    '<div>Added by: <strong>'+row.added_byuser.name+'</strong></div>';
                                                    if(isSet(row.done_at)){
                                                        res_html += '<hr class="mt-1 mb-1">' +
                                                            '<div>Completed at: '+row.done_at+'</div>' +
                                                            '<div>Completed by: <strong>'+row.done_byuser.name+'</strong></div>';
                                                    }
                                                 res_html +=  '</p>'+
                                                            '</div>';
                                                 res_html += '<div class="col-md-6 mb-2">' +
                                                     // '<div>Invoice #: <strong>'+row.invoice.invoice_number+'</strong></div><hr class="mt-1 mb-1">'+
                                                     '<div>Invoice: <a target="_blank" href="'+full_path+row.invoice.id+'" title="Open a PDF file in a new tab">'+row.invoice.invoice_number+'</a></div><hr class="mt-1 mb-1">'+
                                                     '<div>Customer Name: <strong>'+row.invoice.customer.first_name+' '+row.invoice.customer.last_name+'</strong></div>'+
                                                     '<div>Customer Email: <strong>'+row.invoice.customer.email+'</strong></div>'+
                                                     '<div>Customer Phone#: <strong>'+row.invoice.customer.phone_number+'</strong></div><hr class="mt-1 mb-1">'+
                                                     '<div>Access date: <strong>'+row.invoice.access_date+'</strong></div>'+
                                                     '<div>Status: <strong class="'+css_class_2+'">'+i_status+'</strong></div>'+
                                                     '</div>';

                                                if(row.task_status == 1){
                                                     res_html += '<div class="col-12"><button data-todo_id="'+row.id+'" class="btn btn-info set_complete">Completed</button></div>';
                                                 }
                                            res_html += '</div>';
                                            res_html +=  '</div>' +
                                            '</div>'+
                                        '</div>'+
                                    '</div>';
                            return res_html;
                        } },

                ]
            });

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
        })
    </script>
@endsection