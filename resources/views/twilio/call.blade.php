@extends('layouts.app')

@section('style')
    <style>
        .bg_to_pay{
            background-color: rgba(255,0,0,.1) !important;
        }
        .bg_refunded{
            background-color: rgba(0,0,0,.3) !important;
        }
        .bg-success-light{
            background-color: rgba(0,255,0,.1) !important;
        }
        .err_box{
            line-height: 1.1;
            margin-top: 0.5rem;
        }
        div.sticky {
            position: -webkit-sticky; /* Safari */
            position: sticky;
            z-index: 99999;
            background-color: #f8fafc;
            top: 0;
        }
        .stat_wrapper{
            padding-right: 11px;
            padding-left: 11px;
            padding-top:3px;
            padding-bottom:10px;
            border-bottom: 1px solid deepskyblue;
        }

        .nextMonthDay.text-danger{
            color:rgba(255,0,0,0.3) !important;
        }

        .nextMonthDay.text-success{
            color:rgba(0,255,0,0.3) !important;
        }

        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
            background: rgba(0,0,255,0.2) !important;
            border-color: rgba(0,0,255,0.2) !important;
        }



        @media (max-width: 1199px) {
            div.sticky {
                border-bottom: 1px solid deepskyblue;
            }
            .stat_wrapper{
                border-bottom: none;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12 m-auto">

                <div class="row">
                    <div class="col-lg-12 margin-tb">
                        <div class="pull-left">
                            <h2>Test Call</h2>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://sdk.twilio.com/js/client/v1.14/twilio.js"></script>
    <script>
        $(document).ready(function() {
            const Device = Twilio.Device;
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post("/twilio-token", {forPage: window.location.pathname}, function(data) {
                // Set up the Twilio Client Device with the token
                Twilio.Device.setup(data.token);
            });
        });

        Twilio.Device.ready(function (device) {
            updateCallStatus("Ready");
        });
    </script>
@endsection