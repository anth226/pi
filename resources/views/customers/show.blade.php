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
                        @if(!empty($customer->invoices))
                            <strong>Invoices:</strong>
                            <a title="Open invoice in a new tab" target="_blank" href="/invoices/{{$customer->invoices->id}}"><span class="badge badge-success">{{ $customer->invoices->invoice_number }}</span></a>
                        @endif
                    </div>
                </div>
            </div>


        </div>
    </div>
@endsection