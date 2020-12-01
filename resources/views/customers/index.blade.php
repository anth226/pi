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
                        <h2>Dashboard</h2>
                    </div>
                    <div class="pull-right">
                        {{--@can('customer-create')--}}
                        {{--<a class="btn btn-success mb-4 mt-2 btn-sm" href="{{ route('customers.create') }}"> Add New Customer</a>--}}
                        {{--@endcan--}}
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
                    <th>Amount</th>
                    <th>Salesperson</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th></th>
                </tr>

                @foreach ($customers as $key => $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td><a href="/customers/{{ $user->id }}" target="_blank">{{ $user->first_name }} {{ $user->last_name }}</a></td>
                        <td>
                            @php
                            $invoices_obj =  new \App\Http\Controllers\InvoicesController();
                            if(!empty($user->invoices) && !empty($user->invoices->sales_price)){
                                echo $invoices_obj->moneyFormat($user->invoices->sales_price);
                            }
                            @endphp
                        </td>
                        <td>
                            @if(!empty($user->invoices) && !empty($user->invoices->salespersone))
                                <div><a href="/salespeople/{{ $user->invoices->salespersone->id }}" target="_blank" title="{{ $user->invoices->salespersone->first_name }} {{ $user->invoices->salespersone->last_name }}">{{ $user->invoices->salespersone->name_for_invoice }}</a></div>
                            @endif
                            @if(!empty($user->invoices) && !empty($user->invoices->salespeople))
                                @foreach($user->invoices->salespeople as $p)
                                        <div style="line-height: 1.1"><a href="/salespeople/{{ $p->salespersone->id }}" target="_blank" title="{{ $p->salespersone->first_name }} {{ $p->salespersone->last_name }}"><small>{{ $p->salespersone->name_for_invoice }}</small></a></div>
                                @endforeach
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>@php echo \App\KmClasses\Sms\FormatUsPhoneNumber::nicePhoneNumberFormat($user->phone_number, $user->formated_phone_number); @endphp</td>
                        <td>
                            <div style="max-width: 300px;">
                                @if(!empty($user->invoices) && !empty($user->invoices->id))
                                        <a title="Open invoice in a new tab" target="_blank" href="/invoices/{{$user->invoices->id}}"><span class="badge badge-success">{{ $user->invoices->invoice_number }}</span></a>
                                @endif
                            </div>
                        </td>

                        <td>

                            {{--<a class="btn btn-info mb-1" href="{{ route('customers.show',$user->id) }}">Show</a>--}}
                            {{--@can('customer-edit')--}}
                                {{--<a class="btn btn-primary mb-1" href="{{ route('customers.edit',$user->id) }}">Edit</a>--}}
                            {{--@endcan--}}
                            {{--@can('invoice-edit')--}}
                                {{--<a class="btn btn-success mb-1" href="{{ route('invoices.create',['customer_id' => $user->id]) }}">Create Invoice</a>--}}
                            {{--@endcan--}}
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