@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> {{ $customer->first_name }} {{ $customer->last_name }}</h2>
                    </div>
                    <div class="pull-right mb-4">
                        <a class="btn btn-primary mt-2" href="/dashboard"> Dashboard</a>

                        {{--@can('customer-edit')--}}
                            {{--<a class="btn btn-info mt-2" href="{{ route('customers.edit',$customer->id) }}"> Edit</a>--}}
                        {{--@endcan--}}

                        {{--@can('invoice-edit')--}}
                            {{--<a class="btn btn-success mt-2" href="{{ route('invoices.create',['customer_id' => $customer->id]) }}"> Create Invoice</a>--}}
                        {{--@endcan--}}
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-6">
                    <div>
                        <strong>Name:</strong>
                        {{ $customer->first_name }} {{ $customer->last_name }}
                    </div>
                    <div>
                        <strong>Address:</strong>
                        @php
                            $customer_state = '';
                            if($customer->state != 'N/A'){
                                $customer_state = ', '.$customer->state;
                            }
                        @endphp
                        {{ $customer->address_1 }} {{ $customer->address_2 }}, {{ $customer->city }}{{ $customer_state }}, {{ $customer->zip }}
                    </div>
                    <div>
                        <strong>Email:</strong>
                        {{ $customer->email }}
                    </div>
                    <div>
                        <strong>Pnone Number:</strong>
                        @php
                            use App\KmClasses\Sms\FormatUsPhoneNumber;
                            echo FormatUsPhoneNumber::nicePhoneNumberFormat($customer->phone_number, $customer->formated_phone_number);
                        @endphp
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-2 text-muted details_bgcolor">
                        <div>
                            <small>
                                <strong>Created at:</strong>
                                {{ $customer->created_at }}
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
                                         case 5:
                                            $service_name = 'Pipedrive';
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
                <div class="col-md-12 mt-2">
                    <div class="form-group">
                        @if(!empty($customer->invoices) && $customer->invoices->count())
                            <strong>Invoices:</strong>
                            @foreach($customer->invoices as $invoice)
                            <a title="Open invoice in a new tab" target="_blank" href="/invoices/{{$invoice->id}}"><span class="badge badge-success">{{ $invoice->invoice_number }}</span></a>
                            {{--@if(isset($user_id) && $user_id == 1)--}}
                                {{--<button class="btn btn-danger mt-2" id="delete_all">Delete Invoice and customer <small>beta</small></button>--}}
                            {{--@endif--}}
                            @endforeach
                        @endif
                    </div>
                </div>


                @if(isset($user_id) && $user_id == 1)

                <div class="col-md-12 mt-2">
                    <form id="add_phone" class="mb-2">
                        <div class="form-row align-items-center">
                            <div class="col-auto">
                                <label class="sr-only" for="phone_number">Phone Number</label>
                                <input type="tel" class="form-control mb-2" name="phone_number" placeholder="Phone Number">
                            </div>
                            <div class="col-auto">
                                <input type="hidden" name="customer_id" value="{{$customer->id}}">
                                <button type="submit" class="btn btn-primary mb-2 submit" >Add Phone Number</button>
                            </div>
                        </div>
                    </form>

                    <form id="add_email">
                        <div class="form-row align-items-center">
                            <div class="col-auto">
                                <label class="sr-only" for="email_address">Email Address</label>
                                <input type="email" class="form-control mb-2" name="email_address" placeholder="Email Address">
                            </div>
                            <div class="col-auto">
                                <input type="hidden" name="customer_id" value="{{$customer->id}}">
                                <button type="submit" class="btn btn-primary mb-2 submit" >Add Email Address</button>
                            </div>
                        </div>
                    </form>

                    <form id="recheck_subscriptions">
                        <div class="form-row align-items-center">
                            <div class="col-auto">
                                <input type="hidden" name="customer_id" value="{{$customer->id}}">
                                <button type="submit" class="btn btn-primary mb-2 submit" >Recheck Subscriptions</button>
                            </div>
                        </div>
                    </form>



                </div>

                <div class="col-md-12 mt-2">

                    <table class="table table-bordered table-responsive-sm w-100" id="contacts_table">
                        <thead>
                        <tr>
                            <th>Created At</th>
                            <th>Contact</th>
                            <th>Subscriptions</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>

                @endif

            </div>


        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {

            var contact_type = jQuery.parseJSON('{!! $contact_type !!}');
            var contact_subtype = jQuery.parseJSON('{!! $contact_subtype !!}');
            var subscription_type = jQuery.parseJSON('{!! $subscription_type !!}');
            var subscription_status = jQuery.parseJSON('{!! $subscription_status !!}');


            $(document).on('submit', '#add_phone', function (event) {
                makeAjaxCall($(this), '/customers-contacts/add-contact');
            });

            $(document).on('submit', '#add_email', function (event) {
                makeAjaxCall($(this),'/customers-contacts/add-contact');
            });

            $(document).on('submit', '#recheck_subscriptions', function (event) {
                makeAjaxCall($(this),'/customers-contacts/recheck_subscriptions');
            });

            $(document).on('click', '.del-contact', function (event) {
                let contact_id = $(this).data('contact_id');
                const current_button = $(this);
                current_button.next('.error').remove();
                const button_content = current_button.html();
                var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                current_button.prop('disabled', 'disabled').append(ajax_img);
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/customers-contacts/delete-contact',
                    type: "POST",
                    dataType: "json",
                    data: {
                        contact_id: contact_id
                    },
                    success: function (response) {
                        cont_table_dt.draw();
                    },
                    error: function (response) {
                        current_button.prop('disabled', '');
                        current_button.html(button_content);
                        current_button.after('<div class="error">'+response.responseJSON.message+'</div>');
                    }
                });
            });

            $(document).on('click', '.unsubscribe_subs', function (event) {
                let subs_id = $(this).data('subsid');
                const current_button = $(this);
                unsubscribeAjax(subs_id, current_button);
            });

            $(document).on('click', '.subscribe_subs', function (event) {
                let contact_id = $(this).data('contact_id');
                let subscription_type = $(this).data('subscription_type');
                const current_button = $(this);
                subscribeAjax(contact_id, subscription_type, current_button);
            });

             @if(isset($user_id) && $user_id == 1)

            var cont_table = $('table#contacts_table');
            var cont_table_dt = cont_table.DataTable({

                processing: true,
                serverSide: true,
                // searching: false,
                order: [
                    [ 0, "desc" ]
                ],
                ajax: {
                    url: "/customers-contacts",
                    data: function ( d ) {
                        return $.extend( {}, d, {
                            customer_id: {{$customer->id}}
                        } );
                    }
                },
                pageLength: 100,

                columns: [
                    { data: 'created_at', name: 'created_at', "sortable": true,"searchable": false, render: function ( data, type, row ){
                            return data+'<div title="'+data+'"><small class="text-muted">Added by: <strong>'+row.user.name+'</strong></small></div>';
                        }},
                    { data: 'contact_term', name: 'contact_term', "sortable": false,"searchable": true, render: function ( data, type, row ){
                            let res_html = data;
                            if(!row.subscriptions.length && !row.is_main_for_invoice_id){
                                res_html += '<div><button data-contact_id="'+row.id+'" type="button" class="btn btn-sm btn-danger del-contact" >Delete Contact</button></div>';
                            }
                            return res_html;
                        }},

                    { data: 'subscriptions', name: 'subscriptions', "sortable": false,"searchable": false, render: function ( data, type, row ){
                            return generateSubs(data, row);
                        }},
                    { data: 'formated_contact_term', name: 'formated_contact_term', "sortable": false,"searchable": true, "visible":false},


                ]
            });

            @endif

            function generateSubs(subscriptions, row){
                const KlaviyoPrimeSmsButton = '<div class="mt-2"><button data-subscription_type="2" data-contact_id="'+row.id+'" type="button" class="btn btn-sm btn-primary subscribe_subs" >Subscribe email to Klaviyo Prime daily</button></div>';
                const smsSystemPrimePhoneButton = '<div class="mt-2"><button data-subscription_type="3" data-contact_id="'+row.id+'" type="button" class="btn btn-sm btn-primary subscribe_subs" >Subscribe phone to SmsSystem:Prime</button></div>';
                const smsSystemPrimeEmailButton = '<div class="mt-2"><button data-subscription_type="4" data-contact_id="'+row.id+'" type="button" class="btn btn-sm btn-primary subscribe_subs" >Subscribe email to SmsSystem:Prime</button></div>';
                const firebaseEmailButton = '<div class="mt-2"><button data-subscription_type="1" data-contact_id="'+row.id+'" type="button" class="btn btn-sm btn-primary subscribe_subs" >Create new Firebase user for the same subscription</button></div>';


                let ifHaveSMSPrimePhone = false;
                let ifHaveSMSPrimeEmail = false;
                let ifHaveSMSPrimeKlaviyo = false;
                let ifHaveFirebaseEmail = false;

                let ret_html = '';
                if(isSet(subscriptions) && subscriptions.length){

                    $.each(subscriptions, function( index, value ) {
                        ret_html += '<div class=card><div class="card-body">';
                        ret_html += '<div>'+subscription_type[value.subscription_type]+'</div>';
                        ret_html += '<div>Status: '+subscription_status[value.subscription_status]+'</div>';
                        ret_html += '<div><small>Created At: '+value.created_at+'</small></div>';
                        ret_html += '<div><small>Created By:'+value.user.name+'</small></div>';
                        if(value.subscription_type != 1 && value.subscription_type < 5 ) {
                            ret_html += '<div><button data-subsid="' + value.id + '" class="btn btn-sm btn-danger mt-2 unsubscribe_subs" >Unsubscribe</button></div>';
                        }
                        ret_html += '</div></div>';
                        if(isSet(value.subscription_type)){
                            if(value.subscription_type == 2){ //Klaviyo: Daily Prime
                                ifHaveSMSPrimeKlaviyo = true;
                            }
                            if(value.subscription_type == 3){ //SMS System: Prime sms
                                ifHaveSMSPrimePhone = true;
                            }
                            if(value.subscription_type == 4){ //SMS System: Prime email
                                ifHaveSMSPrimeEmail = true;
                            }
                            if(value.subscription_type == 1){ //Firebase
                                ifHaveFirebaseEmail = true;
                            }
                        }
                    });
                }


                if(!ifHaveFirebaseEmail && row.contact_type == 0){
                    // ret_html += firebaseEmailButton;
                }
                if(!ifHaveSMSPrimeKlaviyo && row.contact_type == 0){
                    ret_html += KlaviyoPrimeSmsButton;
                }
                if(!ifHaveSMSPrimePhone && row.contact_type == 1){
                    ret_html += smsSystemPrimePhoneButton;
                }
                if(!ifHaveSMSPrimeEmail && row.contact_type == 0){
                    ret_html += smsSystemPrimeEmailButton;
                }


                return ret_html;
            }
            function isSet(variable){
                if(typeof variable !== "undefined" && variable !== null) {
                    return true;
                }
                return false;
            }
            function makeAjaxCall(current_form, url){
                event.preventDefault();
                const current_button = current_form.find('.submit');
                const $form = current_form;
                const submitData = $form.serialize();

                current_form.prev('.error').remove();
                const button_content = current_button.html();
                console.log(button_content);
                const ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                current_button.prop('disabled', 'disabled').append(ajax_img);

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: url,
                    type: "POST",
                    dataType: "json",
                    data: submitData,
                    success: function (response) {
                        cont_table_dt.draw();
                        current_button.prop('disabled', '');
                        current_button.html(button_content);
                    },
                    error: function (response) {
                        current_button.prop('disabled', '');
                        current_button.html(button_content);
                        current_form.before('<div class="error">'+response.responseJSON.message+'</div>');
                    }
                });
            }
            function unsubscribeAjax(subs_id, current_button){
                current_button.next('.error').remove();
                const button_content = current_button.html();
                var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                $('button').prop('disabled', 'disabled');
                current_button.prop('disabled', 'disabled').append(ajax_img);
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/customers-contacts/unsubscribe/'+subs_id,
                    type: "POST",
                    dataType: "json",
                    success: function (response) {
                        cont_table_dt.draw();
                        $('button').prop('disabled', '');
                    },
                    error: function (response) {
                        $('button').prop('disabled', '');
                        current_button.html(button_content);
                        current_button.after('<div class="error">'+response.responseJSON.message+'</div>');
                    }
                });
            }
            function subscribeAjax(contact_id, subscription_type, current_button){
                current_button.next('.error').remove();
                const button_content = current_button.html();
                var ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                $('button').prop('disabled', 'disabled');
                current_button.append(ajax_img);
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: '/customers-contacts/subscribe',
                    type: "POST",
                    dataType: "json",
                    data:{
                        contact_id: contact_id,
                        subscription_type: subscription_type
                    },
                    success: function (response) {
                        cont_table_dt.draw();
                        $('button').prop('disabled', '');
                    },
                    error: function (response) {
                        $('button').prop('disabled', '');
                        current_button.html(button_content);
                        current_button.after('<div class="error">'+response.responseJSON.message+'</div>');
                    }
                });
            }


        })
    </script>
@endsection