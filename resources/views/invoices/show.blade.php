@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }} [{{ $invoice->invoice_number }}]</h2>
                        <div class="text-muted mb-4">
                            <div  class="details_bgcolor p-2">
                                <div>
                                    <small>
                                        <strong>Created at:</strong>
                                        {{ $invoice->created_at }}
                                    </small>
                                </div>
                                @if($sentLog && count($sentLog))
                                    @foreach($sentLog as $d)
                                            @php
                                                $service_name = '';
                                                switch ($d->service_type){
                                                    case 1:
                                                        if($d->field == "subscriber_id"){
                                                            $service_name = 'Stripe';
                                                        }
                                                        break;
                                                    case 2:
                                                        $service_name = 'Firebase';
                                                        break;
                                                    case 3:
                                                        $service_name = 'Klaviyo';
                                                        break;
                                                    case 4:
                                                        $service_name = 'SMS System';
                                                        break;
                                                    default:
                                                        $service_name = '';
                                                }
                                            @endphp
                                            @if($service_name)
                                            <div>
                                                <small>
                                                <strong>Sent to {{$service_name}} at: </strong>
                                                {{ $d->created_at}}
                                                </small>
                                            </div>
                                            @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>

                    </div>
                    <div class="pull-right mb-4 ">
                        <a class="btn btn-primary mt-2" href="/dashboard"> Dashboard</a>
                        {{--@can('invoice-edit')--}}
                            {{--<a class="btn btn-info mt-2" href="{{ route('invoices.edit',$invoice->id) }}">Edit Invoice</a>--}}
                        {{--@endcan--}}
                        {{--@can('invoice-delete')--}}
                            {{--{!! Form::open(['method' => 'DELETE','route' => ['invoices.destroy', $invoice->id],'style'=>'display:inline;']) !!}--}}
                            {{--{!! Form::submit('Delete', ['class' => 'btn btn-danger mt-2']) !!}--}}
                            {{--{!! Form::close() !!}--}}
                        {{--@endcan--}}
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
                    @php
                        $salespeople = [];
                        $salespeople[] = $invoice->salespersone->email;
                        $cc = '';
                        $bcc = 'corporate@portfolioinsider.com';
                    @endphp
                    @if(count($invoice->salespeople))
                        @foreach($invoice->salespeople as  $sp)
                            @php
                                $salespeople[] = $sp->salespersone->email;
                            @endphp
                            <div class="px-2">
                                <small>
                                    <strong>Salesperson:</strong>
                                    <a target="_blank" href="{{ route('salespeople.show', $sp->salespersone->id) }}" title="({{ $sp->salespersone->first_name }} {{ $sp->salespersone->last_name }})">
                                        {{ $sp->salespersone->name_for_invoice }}
                                    </a>
                                </small>
                            </div>
                        @endforeach
                    @endif
                    @php
                        if(count($salespeople)){
                            $cc = implode(', ', $salespeople);
                        }
                    @endphp
                    <div>
                        <strong>Product:</strong>
                        {{ $invoice->product->title }}
                    </div>
                    <div>
                        <strong>Quantity:</strong>
                        {{ $invoice->qty }}
                    </div>
                    <div>
                        <strong>CC last 4 digits:</strong>
                        {{ $invoice->cc }}
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
                        <div class="form-group">
                            <strong>CC:</strong>
                            {!! Form::text('cc_email', $cc, array('placeholder' => 'Email','class' => 'form-control', 'id' => 'cc_email_address')) !!}
                        </div>
                        <div class="form-group">
                            <strong>BCC:</strong>
                            {!! Form::text('bcc_email', $bcc, array('placeholder' => 'Email','class' => 'form-control', 'id' => 'bcc_email_address')) !!}
                        </div>
                        <button class="btn btn-primary" id="send_email">Send Invoice Email</button>
                        <div class="err_box"></div>
                    </div>
                    @endcan
                    <div class="text-muted details_bgcolor" id="log_box">
                        @if(!empty($logs) && $logs->count())
                            @foreach ($logs as $k=>$log)
                                <div class="pl-1 pr-1">
                                    <small>
                                        <strong>Sent at </strong>{{ $log->created_at }} <strong>to</strong> {{ $log->to }}
                                    </small>
                                </div>
                            @endforeach
                        @endif
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
            // var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,6})+$/;
            // return regex.test(email);
            return true;
        }

        $('#send_email').on('click', function(){
            var current_button = $(this);
            var email = $('#email_address').val();
            var cc = $('#cc_email_address').val();
            var bcc = $('#bcc_email_address').val();
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
                            email: email,
                            bcc: bcc,
                            cc: cc
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
                                    const returnedData = JSON.parse(response.data);
                                    var str = '';
                                    returnedData.forEach(function(item) {
                                       str += '<div class="pl-1 pr-1"><small><strong>Sent at </strong>'+item.created_at+'<strong> to</strong> '+item.to+'</small></div>';

                                    });
                                    $('#log_box').html(str);
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