@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2> {{ $salespeople->name_for_invoice }}</h2>
                    </div>
                    <div class="pull-right mb-4">
                        @if( Gate::check('salespeople-list') || Gate::check('salespeople-edit') || Gate::check('salespeople-delete'))
                            <a class="btn btn-primary mt-2" href="{{ route('salespeople.index') }}"> All Salespeople</a>
                        @endif
                        @can('salespeople-edit')
                            <a class="btn btn-info mt-2" href="{{ route('salespeople.edit',$salespeople->id) }}"> Edit</a>
                        @endcan
                        {{--@can('salespeople-delete')--}}
                            {{--{!! Form::open(['method' => 'DELETE','route' => ['salespeople.destroy', $salespeople->id],'style'=>'display:inline']) !!}--}}
                            {{--{!! Form::submit('Delete', ['class' => 'btn btn-danger mt-2']) !!}--}}
                            {{--{!! Form::close() !!}--}}
                        {{--@endcan--}}
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-6">
                    <div>
                        <strong>Name for Invoice:</strong>
                        {{ $salespeople->name_for_invoice }}
                    </div>
                    <div>
                        <strong>Name:</strong>
                        {{ $salespeople->first_name }} {{ $salespeople->last_name }}
                    </div>
                    <div>
                        <strong>Email:</strong>
                        {{ $salespeople->email }}
                    </div>
                    <div>
                        <strong>Phone Number:</strong>
                        @php
                            use App\KmClasses\Sms\FormatUsPhoneNumber;
                            echo FormatUsPhoneNumber::nicePhoneNumberFormat($salespeople->phone_number, $salespeople->formated_phone_number);
                        @endphp
                    </div>
                    @can('salespeople-edit')
                        <div>
                            <strong>Level:</strong>
                            {{ $salespeople->level->level->title }} | {{ $salespeople->level->percentage }}%
                        </div>
                    @endcan
                </div>
                <div class="col-md-6">
                    @can('salespeople-edit')
                        <div class="p-2 text-muted details_bgcolor">
                            <div>
                                <small>
                                    <strong>Created at:</strong>
                                    {{ $salespeople->created_at }}
                                </small>
                            </div>
                            <div>
                                <small>
                                    <strong>Updated at:</strong>
                                    {{ $salespeople->updated_at }}
                                </small>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>


        </div>
    </div>
@endsection