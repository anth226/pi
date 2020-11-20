@extends('layouts.app')

@section('style')
    <link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
    <style>
        .select2-selection.select2-selection--single {
            display: block;
            width: 100%;
            height: calc(1.6em + .75rem + 2px);
            padding: .45rem .75rem;
            font-size: .9rem;
            font-weight: 400;
            line-height: 1.6;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }
        .select2-selection.select2-selection--single:focus,
        .select2-selection.select2-selection--single:hover {
            color: #495057;
            background-color: #fff;
            border-color: #a1cbef;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(52,144,220,.25);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 90%;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: .9rem;
            font-weight: 400;
            line-height: 1.6;
            color: #495057;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="col-lg-8 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Edit Customer</h2>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-primary mb-4 mt-2" href="{{ route('customers.index') }}"> All Customers</a>
                        <a class="btn btn-info mb-4 mt-2" href="{{ route('customers.show',$customer->id) }}">Show Customer</a>
                        @can('customer-delete')
                            {!! Form::open(['method' => 'DELETE','route' => ['customers.destroy', $customer->id],'style'=>'display:inline']) !!}
                            {!! Form::submit('Delete', ['class' => 'btn btn-danger  mb-4 mt-2']) !!}
                            {!! Form::close() !!}
                        @endcan
                    </div>
                </div>
            </div>


            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            {!! Form::model($customer, ['method' => 'PATCH','route' => ['customers.update', $customer->id]]) !!}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>First Name *:</strong>
                        {!! Form::text('first_name', null, array('placeholder' => 'First Name','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Last Name *:</strong>
                        {!! Form::text('last_name', null, array('placeholder' => 'Last Name','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Address 1 *:</strong>
                        {!! Form::text('address_1', null, array('placeholder' => 'Address 1','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Address 2:</strong>
                        {!! Form::text('address_2', null, array('placeholder' => 'Address 2','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <strong>City *:</strong>
                        {!! Form::text('city', null, array('placeholder' => 'City','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <strong>State *:</strong>
                        {!! Form::select('state', $states, $customerState, array('class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <strong>Zip *:</strong>
                        {!! Form::text('zip', null, array('placeholder' => 'Zip','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Email *:</strong>
                        {!! Form::text('email', null, array('placeholder' => 'Email','class' => 'form-control', 'readonly' => 'true')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Phone Number *:</strong>
                        {!! Form::text('phone_number', null, array('placeholder' => 'Phone Number','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ url('/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $("select").select2({
                width: '100%',
                placeholder: 'Please select'
            });
        });
    </script>
@endsection