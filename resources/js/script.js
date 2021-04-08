try {
    window.$ = window.jQuery = require('jquery');
    window.DataTable = window.jQuery = require('datatables.net');
    window.moment = require('moment');
} catch (e) {}

//require ('./datatable_bootstrap4.js');
//require ('./pusher.js');

// function readCookie(name) {
//     var nameEQ = name + "=";
//     var ca = document.cookie.split(';');
//     for(var i=0;i < ca.length;i++) {
//         var c = ca[i];
//         while (c.charAt(0)==' ') c = c.substring(1,c.length);
//         if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
//     }
//     return null;
// }
// function createCookie(name, value, days) {
//     var expires;
//
//     if (days) {
//         var date = new Date();
//         date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
//         expires = "; expires=" + date.toGMTString();
//     } else {
//         expires = "";
//     }
//     document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
// }


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
    console.log('Loaded!');
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

    /* Callback for when Twilio Client receives a new incoming call */
    device.on('incoming', function(connection) {
        updateCallStatus("Incoming call from " +  connection.parameters.From);

        // Set a callback to be executed when the connection is accepted
        connection.accept(function() {
            updateCallStatus("In call with customer");
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

