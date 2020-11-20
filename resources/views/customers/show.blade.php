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
                        <a class="btn btn-primary mt-2" href="{{ route('customers.index') }}"> All Customers</a>

                        @can('customer-edit')
                            <a class="btn btn-info mt-2" href="{{ route('customers.edit',$customer->id) }}"> Edit</a>
                        @endcan

                        @can('invoice-edit')
                            <a class="btn btn-success mt-2" href="{{ route('invoices.create',['customer_id' => $customer->id]) }}"> Create Invoice</a>
                        @endcan
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
                        {{ $customer->address_1 }} {{ $customer->address_2 }}, {{ $customer->address_2 }}, {{ $customer->city }}, {{ $customer->state }}, {{ $customer->zip }}
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
                        <div>
                            <small>
                                <strong>Updated at:</strong>
                                {{ $customer->updated_at }}
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-2">
                    <div class="form-group">
                        @if(count($customer->invoices))
                            <strong>Invoices:</strong>
                            @foreach($customer->invoices as $v)
                                <a title="Open invoice in a new tab" target="_blank" href="/invoices/{{$v->id}}"><span class="badge badge-success">{{ $v->invoice_number }}</span></a>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>


        </div>
    </div>
@endsection