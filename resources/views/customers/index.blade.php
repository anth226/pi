@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-8 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Customers</h2>
                    </div>
                    <div class="pull-right">
                        @can('customer-create')
                        <a class="btn btn-success mb-4 mt-2" href="{{ route('customers.create') }}"> Add New Customer</a>
                        @endcan
                    </div>
                </div>
            </div>


            @if ($message = Session::get('success'))
                <div class="alert alert-success">
                    <p>{{ $message }}</p>
                </div>
            @endif


            <table class="table table-responsive">
                <tr>
                    <th>Id</th>
                    <th>Name</th>
                    <th>Details</th>
                    <th></th>
                </tr>
                @foreach ($customers as $key => $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                        <td>
                            <small>
                                <div>{{ $user->email }}</div>
                                <div>{{ $user->phone_number }}</div>
                                <div>{{ $user->address_1 }} {{ $user->address_2 }}, {{ $user->city }}, {{ $user->state }}, {{ $user->zip }}</div>
                                <div>Password: {{ $user->password }}</div>
                                <div>CC: {{ $user->cc }}</div>
                            </small>
                        </td>

                        <td>
                            <a class="btn btn-info" href="{{ route('customers.show',$user->id) }}">Show</a>
                            @can('customer-edit')
                            <a class="btn btn-primary" href="{{ route('customers.edit',$user->id) }}">Edit</a>
                            @endcan
                            @can('customer-delete')
                            {!! Form::open(['method' => 'DELETE','route' => ['customers.destroy', $user->id],'style'=>'display:inline']) !!}
                            {!! Form::submit('Delete', ['class' => 'btn btn-danger']) !!}
                            {!! Form::close() !!}
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </table>
            {!! $customers->render() !!}
        </div>
    </div>
@endsection