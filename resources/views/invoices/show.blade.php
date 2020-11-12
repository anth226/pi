@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-8 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> Show Invoice</h2>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-primary mb-4 mt-2" href="{{ route('invoices.index') }}"> Back</a>
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
                        {{ $customer->phone_number }}

                </div>
            </div>
            {{--<div class="row">--}}
                {{--<div class="col-md-12">--}}

                        {{--<strong>CC:</strong>--}}
                        {{--{{ $customer->cc }}--}}

                {{--</div>--}}
            {{--</div>--}}
            {{--<div class="row">--}}
                {{--<div class="col-md-12">--}}
                        {{--<strong>Password:</strong>--}}
                        {{--{{ $customer->password }}--}}
                {{--</div>--}}
            {{--</div>--}}


        </div>
    </div>
@endsection