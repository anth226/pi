@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-8 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> Show Salesperson</h2>
                    </div>
                    <div class="pull-right mb-4">
                        <a class="btn btn-primary mt-2" href="{{ route('salespeople.index') }}"> All Salespeople</a>
                        @can('salespeople-edit')
                            <a class="btn btn-info mt-2" href="{{ route('salespeople.edit',$salespeople->id) }}"> Edit</a>
                        @endcan
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-12">

                        <strong>Name for Invoice:</strong>
                        {{ $salespeople->name_for_invoice }}

                </div>
            </div>
            <div class="row">
                <div class="col-md-12">

                    <strong>Name:</strong>
                    {{ $salespeople->first_name }} {{ $salespeople->last_name }}

                </div>
            </div>
            <div class="row">
                <div class="col-md-12">

                    <strong>Email:</strong>
                    {{ $salespeople->email }}

                </div>
            </div>
            <div class="row">
                <div class="col-md-12">

                    <strong>Phone Number:</strong>
                    {{ $salespeople->phone_number }}

                </div>
            </div>


        </div>
    </div>
@endsection