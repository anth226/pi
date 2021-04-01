@extends('layouts.appjs')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    @if(!empty($support))
                        <div class="card-header">
                            {{ $support->support_number }} {{ $support->description }}
                        </div>
                        <div class="card-body" id="apppp">
                            <loader :is-visible="isLoading"></loader>
                            <chat-app :support="{{ $support }}"></chat-app>
                        </div>
                    @else
                        <div class="card-header">Support</div>
                    @endif

                </div>
            </div>
        </div>
    </div>

@endsection