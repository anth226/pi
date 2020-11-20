@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-8 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }} [{{ $invoice->invoice_number }}]</h2>
                    </div>
                    <div class="pull-right mb-4 ">
                        <a class="btn btn-primary mt-2" href="{{ route('invoices.index') }}"> All Invoices</a>
                        {{--@can('invoice-edit')--}}
                            {{--<a class="btn btn-info mt-2" href="{{ route('invoices.edit',$invoice->id) }}">Edit Invoice</a>--}}
                        {{--@endcan--}}
                        @can('invoice-delete')
                            {!! Form::open(['method' => 'DELETE','route' => ['invoices.destroy', $invoice->id],'style'=>'display:inline;']) !!}
                            {!! Form::submit('Delete', ['class' => 'btn btn-danger mt-2']) !!}
                            {!! Form::close() !!}
                        @endcan
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div>
                        <strong>Invoice N:</strong>
                        {{ $invoice->invoice_number }}
                    </div>
                    <div>
                        <strong>Customer:</strong>
                        <a target="_blank" href="{{ route('customers.show', $invoice->customer_id) }}" title="{{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}">
                            {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}
                        </a>
                        <small>
                            {{ $invoice->customer->email }}
                        </small>
                    </div>
                    <div>
                        <strong>Salesperson:</strong>
                        <a target="_blank" href="{{ route('salespeople.show', $invoice->salespeople_id) }}" title="({{ $invoice->salespersone->first_name }} {{ $invoice->salespersone->last_name }})">
                            {{ $invoice->salespersone->name_for_invoice }}
                        </a>
                    </div>
                    <div>
                        <strong>Product:</strong>
                        {{ $invoice->product->title }}
                    </div>
                    <div>
                        <strong>Quantity:</strong>
                        {{ $invoice->qty }}
                    </div>
                    <div>
                        <strong>Sales Price:</strong>
                        {{ $formated_price }}
                    </div>
                    <div>
                        <strong>Access Date:</strong>
                        {{ $access_date }}
                    </div>
                    <div>
                        <strong>Password:</strong>
                        {{ $invoice->password }}
                    </div>
                    <div>
                        <strong>Email Template:</strong>
                        <a target="_blank" href="/email-templates/templates/edit/{{ $invoice->template->template_slug }}" title="{{ $invoice->template->template_name }}">
                            {{ $invoice->template->template_name }}
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-2 text-muted details_bgcolor">
                        <div>
                            <small>
                                <strong>Created at:</strong>
                                {{ $invoice->created_at }}
                            </small>
                        </div>
                        <div>
                            <small>
                                <strong>Updated at:</strong>
                                {{ $invoice->updated_at }}
                            </small>
                        </div>
                        <div>
                            <small>
                                <strong>Emailed at:</strong>
                                {{ $invoice->updated_at }} <strong>to</strong> {{ $invoice->customer->email }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <a target="_blank" href="{{ $full_path.$invoice->id }}" title="Open a PDF file in a new tab">{{$file_name}}</a><br>
            <a  href="/pdfdownload/{{$invoice->id }}" title="Download a PDF file">Download</a><br>
            <div class="mt-2" style="width:900px;height:1250px;">
                <object style="width:100%;height:100%;" data="{{ $full_path.$invoice->id }}">{{$file_name}}" type="application/pdf">
                    <iframe style="width:100%;height:100%;" src="https://docs.google.com/viewer?url={{ $full_path.$invoice->id }}&embedded=true"></iframe>
                </object>
            </div>

            {{--@include('pdfview')--}}
        </div>
    </div>

@endsection