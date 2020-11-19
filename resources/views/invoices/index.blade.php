@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-8 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Invoices</h2>
                    </div>
                    <div class="pull-right">
                        @can('invoice-create')
                        <a class="btn btn-success mb-4 mt-2" href="{{ route('invoices.create') }}"> Add New Invoice</a>
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
                    <th>Salesperson</th>
                    <th>Details</th>
                    <th></th>
                </tr>
                @foreach ($invoices as $key => $invoice)
                    <tr>
                        <td>{{ $invoice->id }}</td>
                        <td><a target="_blank" href="/customers/{{$invoice->customer->id}}">{{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}</a></td>
                        <td><a target="_blank" href="/salespeople/{{$invoice->salespersone->id}}">{{ $invoice->salespersone->name_for_invoice }}</a></td>
                        <td>
                            <small>
                                <div>{{ $invoice->invoice_number }}</div>
                                <div>
                                    @php
                                        $inv = new \App\Http\Controllers\InvoicesController();
                                        echo $inv->moneyFormat( $invoice->sales_price * $invoice->qty );
                                     @endphp
                                 </div>
                                <div>
                                    @php
                                        echo $inv->createTimeString($invoice->access_date);
                                    @endphp
                                </div>
                            </small>
                        </td>

                        <td>
                            <a class="btn btn-info mb-1" href="{{ route('invoices.show',$invoice->id) }}">Show</a>
                            {{--@can('invoice-edit')--}}
                            {{--<a class="btn btn-primary mb-1" href="{{ route('invoices.edit',$invoice->id) }}">Edit</a>--}}
                            {{--@endcan--}}
                            @can('invoice-delete')
                            {!! Form::open(['method' => 'DELETE','route' => ['invoices.destroy', $invoice->id],'style'=>'display:inline']) !!}
                            {!! Form::submit('Delete', ['class' => 'btn btn-danger mb-1']) !!}
                            {!! Form::close() !!}
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </table>
            {!! $invoices->render() !!}
        </div>
    </div>
@endsection