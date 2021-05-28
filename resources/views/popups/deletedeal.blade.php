@can('invoice-delete')
<div class="modal fade" id="deletedeal" tabindex="-1" role="dialog" aria-labelledby="Delete Deal" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Deal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <strong>Please Unsubscribe Customer</strong>
                <button class="btn btn-danger mt-2" id="unsubscribe_all">Unsubscribe</button>
            </div>
            <div class="p-2">
                <div class="popup_err_box text-danger"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endcan