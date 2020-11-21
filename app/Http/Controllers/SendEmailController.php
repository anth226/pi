<?php

namespace App\Http\Controllers;

use App\EmailTemplates;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\KmClasses\Sms\EmailSender;
use Illuminate\Http\Request;
use Exception;

class SendEmailController extends BaseController
{
	public function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:invoice-create');
	}

	protected function sendInvoiceEmail(Request $request){
		try {
			$input      = $request->all();
			$invoice_id = ! empty( $input['invoice_id'] ) ? $input['invoice_id'] : 0;
			$email_template_id = ! empty( $input['email_template_id'] ) ? $input['email_template_id'] : 0;
			$to         = ! empty( $input['email'] ) ? $input['email'] : 0;
			if ( $to && $invoice_id && $email_template_id) {
				$invoice = Invoices::with('customer')->with('salespersone')
				                   ->with('product')
				                   ->find($invoice_id);
				$template_slug = EmailTemplates::where('id', $email_template_id)->value('template_slug');
				if($template_slug) {
					$template = 'vendor.maileclipse.templates.' . $template_slug;
					if ( $invoice ) {
						$sender              = new EmailSender();
						$invoiceController   = new InvoicesController();
						$from_name           = 'Support Portfolio Insider';
						$from_email          = 'support@portfolioinsider.com';
						$customer_first_name = $invoice->customer->first_name;
						$salesperson         = $invoice->salespersone->name_for_invoice;
						$subject             = $customer_first_name . ', a warm welcome! (Here\'s your access)';
						$pdfFilename         = $invoiceController->generateFileName( $invoice );
						$path_to_file        = $invoiceController->pdf_path . $pdfFilename;
						$res                 = $sender->sendEmail( $to, $from_email, $template, $subject, $from_name, $customer_first_name, $salesperson, $path_to_file );
						if ( $res && $res['success'] ) {
							return $this->sendResponse( ' Message has been sent', 'Success!' );
						}
					} else {
						return $this->sendError( 'Error! No valid invoice.' );
					}
				}
				else {
					return $this->sendError( 'Error! No valid template.' );
				}
			}
			return $this->sendError( 'Error! No input data.' );

		}
		catch (Exception $ex){
			return $this->sendError( $ex->getMessage() );
		}
	}
}
