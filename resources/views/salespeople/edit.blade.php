@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Edit Salesperson</h2>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-primary mb-4 mt-2" href="{{ route('salespeople.index') }}"> All Salespeople</a>
                        <a class="btn btn-info mb-4 mt-2" href="{{ route('salespeople.show',$salespeople->id) }}">Show</a>
                        @can('salespeople-delete')
                            {!! Form::open(['method' => 'DELETE','route' => ['salespeople.destroy', $salespeople->id],'style'=>'display:inline']) !!}
                            {!! Form::submit('Delete', ['class' => 'btn btn-danger mb-4 mt-2']) !!}
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


            {!! Form::model($salespeople, ['method' => 'PATCH','route' => ['salespeople.update', $salespeople->id]]) !!}
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <strong>Name for Invoice:</strong>
                        {!! Form::text('name_for_invoice', null, array('placeholder' => 'Name for Invoice','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>First Name *:</strong>
                        {!! Form::text('first_name', null, array('placeholder' => 'First Name','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Last Name:</strong>
                        {!! Form::text('last_name', null, array('placeholder' => 'Last Name','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Email:</strong>
                        {!! Form::text('email', null, array('placeholder' => 'Email','class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Phone Number:</strong>
                        {!! Form::text('phone_number', null, array('placeholder' => 'Phone Number','class' => 'form-control')) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>level:</strong>
                        {!! Form::select('level_id[]', $levels,[$salespeople->level->level_id], array('class' => 'form-control', 'multiple')) !!}
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