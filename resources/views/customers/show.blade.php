@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-8 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> Show Customer</h2>
                    </div>
                    <div class="pull-right mb-4">
                        <a class="btn btn-primary mt-2" href="{{ route('customers.index') }}"> All Customers</a>
                        @can('customer-delete')
                            {!! Form::open(['method' => 'DELETE','route' => ['customers.destroy', $customer->id],'style'=>'display:inline']) !!}
                            {!! Form::submit('Delete', ['class' => 'btn btn-danger mt-2']) !!}
                            {!! Form::close() !!}
                        @endcan
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
                <div class="col-md-12">

                        <strong>Name:</strong>
                        {{ $customer->first_name }} {{ $customer->last_name }}

                </div>
            </div>
            <div class="row">
                <div class="col-md-12">

                        <strong>Address:</strong>
                        {{ $customer->address_1 }} {{ $customer->address_2 }}, {{ $customer->address_2 }}, {{ $customer->city }}, {{ $customer->state }}, {{ $customer->zip }}

                </div>
            </div>
            <div class="row">
                <div class="col-md-12">

                    <strong>Email:</strong>
                    {{ $customer->email }}

                </div>
            </div>
            <div class="row">
                <div class="col-md-12">

                        <strong>Pnone Number:</strong>
                        @php
                            use App\KmClasses\Sms\FormatUsPhoneNumber;
                            echo FormatUsPhoneNumber::nicePhoneNumberFormat($customer->phone_number, $customer->formated_phone_number);
                        @endphp

                </div>
            </div>


        </div>
    </div>
@endsection