@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Edit Level</h2>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-primary mb-4 mt-2" href="{{ route('levels.index') }}"> All Levels</a>
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


            {!! Form::model($salespeoplelevels, ['method' => 'PATCH','route' => ['levels.update', $salespeoplelevels->id]]) !!}

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Title *:</strong>
                        {!! Form::text('title', null, array('placeholder' => 'Title','class' => 'form-control', 'disabled' => true)) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <strong>Percentage:</strong>
                        {!! Form::text('percentage', null, array('placeholder' => 'Percentage','class' => 'form-control')) !!}
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