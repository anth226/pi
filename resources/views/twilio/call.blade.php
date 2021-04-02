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
                            <h2>Test Calls</h2>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="row mb-2">
            <div class="col-lg-12 m-auto">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-12">
                                <input id="call-status" class="form-control" type="text" placeholder="Connecting to Twilio..." readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-12">
                                <button class="btn btn-lg btn-danger hangup-button" disabled onclick="hangUp()">Hang up</button>
                                <button class="btn btn-lg btn-success answer-button" disabled>Answer call</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-lg-12 m-auto">
                <div class="card">
                    <h5 class="card-header">
                    Call Kevin
                    </h5>
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-12">
                                <button onclick="callCustomer('+18184507532')" type="button" class="btn btn-primary btn-lg call-customer-button">818-450-7532</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-lg-12 m-auto">
                <div class="card">
                    <h5 class="card-header">
                        Call Kevin (Google Voice)
                    </h5>
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-12">
                                <button onclick="callCustomer('+17472214363')" type="button" class="btn btn-primary btn-lg call-customer-button">747-221-4363</button>
                            </div>
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

        // Store some selectors for elements we'll reuse
        var callStatus = $("#call-status");
        var answerButton = $(".answer-button");
        var hangUpButton = $(".hangup-button");
        var callCustomerButtons = $(".call-customer-button");

        var device = null;

        /* Helper function to update the call status bar */
        function updateCallStatus(status) {
            callStatus.attr('placeholder', status);
        }

        /* Get a Twilio Client token with an AJAX request */
        $(document).ready(function() {
            setupClient();
        });

        function setupHandlers(device) {
            device.on('ready', function (_device) {
                updateCallStatus("Ready");
            });

            /* Report any errors to the call status display */
            device.on('error', function (error) {
                updateCallStatus("ERROR: " + error.message);
            });

            /* Callback for when Twilio Client initiates a new connection */
            device.on('connect', function (connection) {
                // Enable the hang up button and disable the call buttons
                hangUpButton.prop("disabled", false);
                callCustomerButtons.prop("disabled", true);
                answerButton.prop("disabled", true);

                // If phoneNumber is part of the connection, this is a call from a
                // support agent to a customer's phone
                if ("phoneNumber" in connection.message) {
                    updateCallStatus("In call with " + connection.message.phoneNumber);
                } else {
                    // This is a call from a website user to a support agent
                    updateCallStatus("In call");
                }

                connection.accept(function() {
                    console.log("to_client",connection);
                });
            });

            /* Callback for when a call ends */
            device.on('disconnect', function(connection) {
                // Disable the hangup button and enable the call buttons
                hangUpButton.prop("disabled", true);
                answerButton.prop("disabled", true);
                callCustomerButtons.prop("disabled", false);
                updateCallStatus("Ready");
            });

            /* Callback for when a call canceled */
            device.on('cancel', function(connection) {
                // Disable the hangup button and enable the call buttons
                hangUpButton.prop("disabled", true);
                answerButton.prop("disabled", true);
                callCustomerButtons.prop("disabled", false);
                updateCallStatus("Ready");
            });

            /* Callback for when a call accepted */
            device.on('accept', function(connection) {
               console.log("for_all",connection);
            });

            /* Callback for when Twilio Client receives a new incoming call */
            device.on('incoming', function(connection) {
                // console.log(connection.parameters);
                // console.log(connection.customParameters);
                // console.log(connection.parameters.ForwardedFrom);
                updateCallStatus("Incoming call from " +  connection.parameters.From);

                // Set a callback to be executed when the connection is accepted
                connection.accept(function() {
                    updateCallStatus("In call with customer");
                    console.log("from_client",connection);
                });

                // Set a callback on the answer button and enable it
                answerButton.click(function() {
                    connection.accept();
                });
                hangUpButton.click(function() {
                    connection.reject();
                    updateCallStatus("Ready");
                    answerButton.prop("disabled", true);
                    hangUpButton.prop("disabled", true);
                });
                answerButton.prop("disabled", false);
                hangUpButton.prop("disabled", false);
            });


            //reconnect
            device.on('offline', function(device) {
                setupClient();
            });

        }

        function setupClient() {

            $.post("/twilio-token", {
                // forPage: window.location.pathname,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function (data) {
                // Set up the Twilio Client device with the token
                device = new Twilio.Device();
                device.setup(data.token);

                setupHandlers(device);
            }).fail(function () {
                updateCallStatus("Could not get a token from server!");
            });

        }

        /* Call a customer from a support ticket */
        window.callCustomer = function(phoneNumber) {
            updateCallStatus("Calling " + phoneNumber + "...");

            var params = {"phoneNumber": phoneNumber};

            device.connect(params);
        };

        /* End a call */
        window.hangUp = function() {
            device.disconnectAll();
        };
    </script>
@endsection