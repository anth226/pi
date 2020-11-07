try {
    window.$ = window.jQuery = require('jquery');
    window.DataTable = window.jQuery = require('datatables.net');
    window.moment = require('moment');
} catch (e) {}

require ('./datatable_bootstrap4.js');
require ('./pusher.js');

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
function createCookie(name, value, days) {
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}


$('document').ready(function () {
    $('#search_phone_number_nav').submit(function (e) {
        e.preventDefault();
        var val = $('#search_phone_number_nav').find('input[name="phone"]').val();
        if(val){
            window.location.href = "/phones/"+val;
        }
    })
});

