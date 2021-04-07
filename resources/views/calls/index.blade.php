@extends('layouts.appjs')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    @if(!empty($salesperson))
                        <div class="card-header">
                            {{ $salesperson->name_for_invoice }}
                        </div>
                        <div class="card-body" id="apppp">
                            <loader :is-visible="isLoading"></loader>
                            @if(!empty($salesperson->pipedrive_user_id))
                                <call-app :owner_id="{{ $salesperson->pipedrive_user_id }}"></call-app>
                            @endif
                        </div>
                    @else
                        <div class="card-header">Support</div>
                    @endif

                </div>
            </div>
        </div>
    </div>

@endsection