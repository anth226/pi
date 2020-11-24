@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        @can('invoice-create')
                            <a class="btn btn-success mb-4 mt-2" href="{{ route('customers-invoices.create') }}"> Create User & Email Invoice</a>
                        @endcan
                        <h2>Customers</h2>
                    </div>
                    <div class="pull-right">
                        @can('customer-create')
                        <a class="btn btn-success mb-4 mt-2 btn-sm" href="{{ route('customers.create') }}"> Add New Customer</a>
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
                    <th>Invoices</th>
                    <th></th>
                </tr>
                @php
                    use App\KmClasses\Sms\FormatUsPhoneNumber;
                @endphp
                @foreach ($customers as $key => $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                        <td>
                            <small>
                                <div>{{ $user->email }}</div>
                                <div>@php echo FormatUsPhoneNumber::nicePhoneNumberFormat($user->phone_number, $user->formated_phone_number); @endphp</div>
                                <div>{{ $user->address_1 }} {{ $user->address_2 }}, {{ $user->city }}, {{ $user->state }}, {{ $user->zip }}</div>
                            </small>
                        </td>
                        <td>
                            <div style="max-width: 300px;">
                                @if(count($user->invoices))
                                    @foreach($user->invoices as $v)
                                        <a title="Open invoice in a new tab" target="_blank" href="/invoices/{{$v->id}}"><span class="badge badge-success">{{ $v->invoice_number }}</span></a>
                                    @endforeach
                                @endif
                            </div>
                        </td>

                        <td>

                            <a class="btn btn-info mb-1" href="{{ route('customers.show',$user->id) }}">Show</a>
                            @can('customer-edit')
                                <a class="btn btn-primary mb-1" href="{{ route('customers.edit',$user->id) }}">Edit</a>
                            @endcan
                            @can('invoice-edit')
                                <a class="btn btn-success mb-1" href="{{ route('invoices.create',['customer_id' => $user->id]) }}">Create Invoice</a>
                            @endcan
                            {{--@can('customer-delete')--}}
                                {{--{!! Form::open(['method' => 'DELETE','route' => ['customers.destroy', $user->id],'style'=>'display:inline']) !!}--}}
                                {{--{!! Form::submit('Delete', ['class' => 'btn btn-danger mb-1']) !!}--}}
                                {{--{!! Form::close() !!}--}}
                            {{--@endcan--}}
                        </td>
                    </tr>
                @endforeach
            </table>
            {!! $customers->render() !!}
        </div>
    </div>
@endsection