<div class="modal fade" id="createtask" tabindex="-1" role="dialog" aria-labelledby="Create task" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Task</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <strong>Select Task:</strong>
                <div class="form-group mb-2">
                    {!! $tasks_select !!}
                </div>
                <strong>Select Representative:</strong>
                <div class="form-group mb-1">
                    {!! $supportTaskRep_select !!}
                </div>
            </div>
            <div class="p-2">
                <div class="popup_err_box text-danger"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add_todo">Create task</button>
            </div>
        </div>
    </div>
</div>