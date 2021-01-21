<div class="modal fade" id="editinvoice" tabindex="-1" role="dialog" aria-labelledby="Edit Customer and Invoice" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Edit Invoice</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            {!! Form::model($invoice, ['method'=>'POST', 'id' => 'invoiceEdit']) !!}
            <div class="modal-body">
                <div class="col-lg-12 m-auto">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>Salesperson for invoice *:</strong>
                                @php
                                    $inv = new \App\Http\Controllers\InvoicesController();
                                    $pr_salespeople = 0;
                                    if(!empty($invoice->salespeople)){
                                        foreach($invoice->salespeople as $sp){
                                            if($sp->sp_type && !empty($sp->salespeople_id)){
                                                $pr_salespeople = $sp->salespeople_id;
                                            }
                                        }
                                    }
                                @endphp
                                {!! Form::select('salespeople_id', [null=>'Please Select'] + $salespeople, $pr_salespeople, array('class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>Salespeople:</strong>
                                @php
                                    $sec_salespeople = [];
                                    if(!empty($invoice->salespeople)){
                                        foreach($invoice->salespeople as $sp){
                                            if(!empty(!$sp->sp_type && $sp->salespeople_id)){
                                                $sec_salespeople[] = $sp->salespeople_id;
                                            }
                                        }
                                    }
                                @endphp
                                {!! Form::select('second_salespeople_id[]', [null=>'Please Select'] + $salespeople, $sec_salespeople, array('class' => 'form-control', 'multiple')) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <strong>Sales Price *:</strong>
                                {!! Form::text('sales_price', $total, array('class' => 'form-control','pattern="^\$\d{1,3}(,\d{3})*(\.\d+)?$"', 'data-type="currency"', 'placeholder="Sales Price"', 'required="required"')) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <strong>Paid:</strong>
                                {!! Form::text('paid', $inv->moneyFormat($invoice->paid), array('class' => 'form-control','pattern="^\$\d{1,3}(,\d{3})*(\.\d+)?$"', 'data-type="currency"', 'placeholder="Paid Today"')) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <strong>To Pay:</strong>
                                {!! Form::text('own', $inv->moneyFormat($invoice->sales_price - $invoice->paid), array('class' => 'form-control','pattern="^\$\d{1,3}(,\d{3})*(\.\d+)?$"', 'data-type="currency"', 'placeholder="TO PAY"', 'disabled' => 'disabled')) !!}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>Access Date *:</strong>
                                {!! Form::text('access_date', null, array('id="access_date"', 'placeholder' => 'Access Date','class' => 'form-control datetimepicker-input', 'data-toggle="datetimepicker"', 'data-target="#access_date"', 'value="'.date("m-d-Y").'"')) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>CC *:</strong>
                                {!! Form::number('cc_number', $invoice['cc_number'], array('placeholder' => 'CC','class' => 'form-control', 'maxlength="4"', 'minlength="4"', 'required="required"')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-2">
                <div class="popup_err_box text-danger"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="saveAndGenerate">Save changes and regenerate invoice</button>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>