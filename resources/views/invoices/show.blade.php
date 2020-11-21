@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }} [{{ $invoice->invoice_number }}]</h2>
                        <div class="text-muted mb-4">
                            <small class="details_bgcolor p-2">
                                <strong>Created at:</strong>
                                {{ $invoice->created_at }}
                            </small>
                        </div>
                    </div>
                    <div class="pull-right mb-4 ">
                        <a class="btn btn-primary mt-2" href="{{ route('invoices.index') }}"> All Invoices</a>
                        {{--@can('invoice-edit')--}}
                            {{--<a class="btn btn-info mt-2" href="{{ route('invoices.edit',$invoice->id) }}">Edit Invoice</a>--}}
                        {{--@endcan--}}
                        @can('invoice-delete')
                            {!! Form::open(['method' => 'DELETE','route' => ['invoices.destroy', $invoice->id],'style'=>'display:inline;']) !!}
                            {!! Form::submit('Delete', ['class' => 'btn btn-danger mt-2']) !!}
                            {!! Form::close() !!}
                        @endcan
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
                        <a target="_blank" href="{{ route('customers.show', $invoice->customer_id) }}" title="{{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}">
                            {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}
                        </a>
                        <small>
                            {{ $invoice->customer->email }}
                        </small>
                    </div>
                    <div>
                        <strong>Salesperson:</strong>
                        <a target="_blank" href="{{ route('salespeople.show', $invoice->salespeople_id) }}" title="({{ $invoice->salespersone->first_name }} {{ $invoice->salespersone->last_name }})">
                            {{ $invoice->salespersone->name_for_invoice }}
                        </a>
                    </div>
                    <div>
                        <strong>Product:</strong>
                        {{ $invoice->product->title }}
                    </div>
                    <div>
                        <strong>Quantity:</strong>
                        {{ $invoice->qty }}
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
                    @can('invoice-create')
                    <div class="mb-2">
                        <input type="hidden" id="invoice_id" value="{{ $invoice->id }}">
                        <div class="form-group">
                            <strong>Email Template *:</strong>
                            {!! Form::select('email_template_id', $template,[], array('class' => 'form-control', 'id' => 'email_template_id')) !!}
                        </div>
                        <div class="form-group">
                            <strong>Email *:</strong>
                            {!! Form::text('email', $invoice->customer->email, array('placeholder' => 'Email','class' => 'form-control', 'id' => 'email_address')) !!}
                        </div>
                        <button class="btn btn-primary" id="send_email">Send Invoice Email</button>
                        <div class="err_box"></div>
                    </div>
                    @endcan
                    <div class="p-2 text-muted details_bgcolor">

                        <div>
                            <small>
                                <strong>Emailed at:</strong>
                                {{ $invoice->updated_at }} <strong>to</strong> {{ $invoice->customer->email }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <a target="_blank" href="{{ $full_path.$invoice->id }}" title="Open a PDF file in a new tab">{{$file_name}}</a><br>
            <a  href="/pdfdownload/{{$invoice->id }}" title="Download a PDF file">Download</a><br>
            <div class="mt-2" style="width:900px;height:1250px;">
                <object style="width:100%;height:100%;" data="{{ $full_path.$invoice->id }}">{{$file_name}}" type="application/pdf">
                    <iframe style="width:100%;height:100%;" src="https://docs.google.com/viewer?url={{ $full_path.$invoice->id }}&embedded=true"></iframe>
                </object>
            </div>

            {{--@include('pdfview')--}}
        </div>
    </div>

@endsection

@section('script')
    <script>
        function isEmail(email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,6})+$/;
            return regex.test(email);
        }

        $('#send_email').on('click', function(){
            var current_button = $(this);
            var email = $('#email_address').val();
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
                            email: email
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
                                    err_box.append(response.data)
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


        })

    </script>
@endsection