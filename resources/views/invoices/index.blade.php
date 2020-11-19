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
                @php
                    $inv = new \App\Http\Controllers\InvoicesController();
                    use App\KmClasses\Sms\FormatUsPhoneNumber;
                @endphp
                @foreach ($invoices as $key => $invoice)
                    <tr>
                        <td>{{ $invoice->id }}</td>
                        <td><a target="_blank" href="/customers/{{$invoice->customer->id}}" title="{{ $invoice->customer->email }} @php echo PHP_EOL; echo FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->customer->phone_number, $invoice->customer->formated_phone_number); @endphp">{{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}</a></td>
                        <td><a target="_blank" href="/salespeople/{{$invoice->salespersone->id}}" title="{{ $invoice->salespersone->first_name }} {{ $invoice->salespersone->last_name }}{{PHP_EOL}}{{ $invoice->salespersone->email }}@php echo PHP_EOL; echo FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->salespersone->phone_number, $invoice->salespersone->formated_phone_number); @endphp">{{ $invoice->salespersone->name_for_invoice }}</a></td>
                        <td>
                            <small>
                                <div>{{ $invoice->invoice_number }}</div>
                                <div>
                                    @php
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