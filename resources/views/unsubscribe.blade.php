@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Unsubscribe SMS</div>

                <div class="card-body">
                    <form id="unsubscribe_phone" class="mb-2">
                        <div class="form-row align-items-center">
                            <div class="col-auto">
                                <label class="sr-only" for="phone_number">Phone Number</label>
                                <input type="tel" class="form-control mb-2" name="phone_number" placeholder="Phone Number">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary mb-2 submit" >Unsubscribe Phone Number</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {

            $(document).on('submit', '#unsubscribe_phone', function (event) {
                event.preventDefault();
                makeAjaxCall($(this), '/unsubscribe/none-prime-phone');
            });

            function makeAjaxCall(current_form, url){
                $('.response').remove();
                const current_button = current_form.find('.submit');
                const $form = current_form;
                const submitData = $form.serialize();

                current_form.prev('.error').remove();
                const button_content = current_button.html();
                const ajax_img = '<img width="40" src="{{ url('/img/ajax.gif') }}" alt="ajax loader">';
                current_button.prop('disabled', 'disabled').append(ajax_img);

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: url,
                    type: "POST",
                    dataType: "json",
                    data: submitData,
                    success: function (response) {
                        current_button.prop('disabled', '');
                        current_button.html(button_content);
                        current_form.after('<div class="text-success response">'+response.data+'</div>');
                    },
                    error: function (response) {
                        current_button.prop('disabled', '');
                        current_button.html(button_content);
                        current_form.before('<div class="text-danger response">'+response.responseJSON.message+'</div>');
                    }
                });
            }

        })
    </script>
@endsection
