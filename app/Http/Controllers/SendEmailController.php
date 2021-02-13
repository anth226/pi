<?php

namespace App\Http\Controllers;

use App\EmailLogs;
use App\EmailLogsGeneratedInvoices;
use App\EmailTemplates;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\InvoiceGenerator;
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
			$error = 'Error! No input data.';
			$input      = $request->all();
			$invoice_id = ! empty( $input['invoice_id'] ) ? $input['invoice_id'] : 0;
			$email_template_id = ! empty( $input['email_template_id'] ) ? $input['email_template_id'] : 0;
			$to         = ! empty( $input['email'] ) ? $input['email'] : '';
			$bcc        = ! empty( $input['bcc'] ) ? $input['bcc'] : '';
			$cc        = ! empty( $input['cc'] ) ? $input['cc'] : '';
			$from_name           = 'Support Portfolio Insider';
			$from_email          = 'support@portfolioinsider.com';
//			$from_email          = 'support@portfolioinsidersystem.com';
			if ( $to && $invoice_id && $email_template_id) {
				$to = array_map('trim', explode(',', $to));
				if($bcc) {
					$bcc = array_map( 'trim', explode( ',', $bcc ) );
					$bcc = array_unique( $bcc );
				}
				if($cc) {
					$cc = array_map( 'trim', explode( ',', $cc ) );
					$cc = array_unique( $cc );
				}

				if(count($to)) {
					foreach ($to as $t) {
						if($this->validateEMAIL($t)) {
							$dataToLog[] = [
								'invoice_id'        => $invoice_id,
								'email_template_id' => $email_template_id,
								'from'              => $from_email,
								'to'                => $t,
								'created_at'        => date( 'Y-m-d H:i:s' ),
								'updated_at'        => date( 'Y-m-d H:i:s' )
							];
						}
						else{
							return $this->sendError( $t." is not valid address. please fix and try again." );
						}
					}
				}
				if($bcc && count($bcc)) {
					foreach ($bcc as $t) {
						if($this->validateEMAIL($t)) {
							$dataToLog[] = [
								'invoice_id'        => $invoice_id,
								'email_template_id' => $email_template_id,
								'from'              => $from_email,
								'to'                => $t,
								'created_at'        => date( 'Y-m-d H:i:s' ),
								'updated_at'        => date( 'Y-m-d H:i:s' )
							];
						}
						else{
							return $this->sendError( $t." is not valid address. please fix and try again." );
						}
					}
				}
				if($cc && count($cc)) {
					foreach ( $cc as $t ) {
						if($this->validateEMAIL($t)) {
							$dataToLog[] = [
								'invoice_id'        => $invoice_id,
								'email_template_id' => $email_template_id,
								'from'              => $from_email,
								'to'                => $t,
								'created_at'        => date( 'Y-m-d H:i:s' ),
								'updated_at'        => date( 'Y-m-d H:i:s' )
							];
						}
						else{
							return $this->sendError( $t." is not valid address. please fix and try again." );
						}
					}
				}
				$invoice = Invoices::with('customer')->with('salespeople.salespersone')
				                   ->with('product')
				                   ->find($invoice_id);
				$template_slug = EmailTemplates::where('id', $email_template_id)->value('template_slug');
				if(count($to)) {
					if ( $template_slug ) {
						$template = 'vendor.maileclipse.templates.' . $template_slug;
						if ( $invoice ) {
							$sender              = new EmailSender();
							$invoiceController   = new InvoicesController();
							$customer_first_name = $invoice->customer->first_name;
							$salesperson         = '';
							if($invoice->salespeople && $invoice->salespeople->count()){
								foreach($invoice->salespeople as  $sp){
									if($sp->sp_type){
										$salesperson = $sp->salespersone->name_for_invoice;
									}
								}
							}
							$subject             = $customer_first_name . ', a warm welcome! (Here\'s your access)';
							$pdfFilename         = $invoiceController->generateFileName( $invoice );
							$path_to_file        = $invoiceController->pdf_path . $pdfFilename;
							$customer_email      = $invoice->customer->email;
							$res                 = $sender->sendEmail( $to, $bcc, $cc, $from_email, $template, $subject, $from_name, $customer_first_name, $salesperson, $path_to_file, $customer_email );
							if ( $res && $res['success'] ) {
								EmailLogs::insert( $dataToLog );
								$logs = EmailLogs::where( 'invoice_id', $invoice_id )->get();

								return $this->sendResponse( json_encode( $logs ), 'Success! Message has been sent' );
							} else {
								if ( $res && ! empty( $res['message'] ) ) {
									$error = $res['message'];
								} else {
									$error = 'Error! Can not send Email.';
								}
							}
						} else {
							$error = 'Error! No valid invoice.';
						}
					} else {
						$error = 'Error! No valid template.';
					}
				}
				else {
					$error = 'Error! Empty email address.';
				}
			}
			Errors::create([
				'error' => $error,
				'controller' => 'SendEmailController',
				'function' => 'sendInvoiceEmail'
			]);
			return $this->sendError( $error );

		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SendEmailController',
				'function' => 'sendInvoiceEmail'
			]);
			return $this->sendError( $ex->getMessage() );
		}
	}

	protected function sendGeneratedInvoiceEmail(Request $request){
		try {
			$error = 'Error! No input data.';
			$input      = $request->all();
			$invoice_id = ! empty( $input['invoice_id'] ) ? $input['invoice_id'] : 0;
			$email_template_id = ! empty( $input['email_template_id'] ) ? $input['email_template_id'] : 0;
			$to         = ! empty( $input['email'] ) ? $input['email'] : '';
			$bcc        = ! empty( $input['bcc'] ) ? $input['bcc'] : '';
			$cc        = ! empty( $input['cc'] ) ? $input['cc'] : '';
			$from_name           = 'Support Portfolio Insider';
			$from_email          = 'support@portfolioinsider.com';
//			$from_email          = 'support@portfolioinsidersystem.com';
			if ( $to && $invoice_id && $email_template_id) {
				$to = array_map('trim', explode(',', $to));
				if($bcc) {
					$bcc = array_map( 'trim', explode( ',', $bcc ) );
					$bcc = array_unique( $bcc );
				}
				if($cc) {
					$cc = array_map( 'trim', explode( ',', $cc ) );
					$cc = array_unique( $cc );
				}

				if(count($to)) {
					foreach ($to as $t) {
						if($this->validateEMAIL($t)) {
							$dataToLog[] = [
								'invoice_id'        => $invoice_id,
								'email_template_id' => $email_template_id,
								'from'              => $from_email,
								'to'                => $t,
								'created_at'        => date( 'Y-m-d H:i:s' ),
								'updated_at'        => date( 'Y-m-d H:i:s' )
							];
						}
						else{
							return $this->sendError( $t." is not valid address. please fix and try again." );
						}
					}
				}
				if($bcc && count($bcc)) {
					foreach ($bcc as $t) {
						if($this->validateEMAIL($t)) {
							$dataToLog[] = [
								'invoice_id'        => $invoice_id,
								'email_template_id' => $email_template_id,
								'from'              => $from_email,
								'to'                => $t,
								'created_at'        => date( 'Y-m-d H:i:s' ),
								'updated_at'        => date( 'Y-m-d H:i:s' )
							];
						}
						else{
							return $this->sendError( $t." is not valid address. please fix and try again." );
						}
					}
				}
				if($cc && count($cc)) {
					foreach ( $cc as $t ) {
						if($this->validateEMAIL($t)) {
							$dataToLog[] = [
								'invoice_id'        => $invoice_id,
								'email_template_id' => $email_template_id,
								'from'              => $from_email,
								'to'                => $t,
								'created_at'        => date( 'Y-m-d H:i:s' ),
								'updated_at'        => date( 'Y-m-d H:i:s' )
							];
						}
						else{
							return $this->sendError( $t." is not valid address. please fix and try again." );
						}
					}
				}
				$invoice = InvoiceGenerator::find($invoice_id);
				$template_slug = EmailTemplates::where('id', $email_template_id)->value('template_slug');
				if(count($to)) {
					if ( $template_slug ) {
						$template = 'vendor.maileclipse.templates.' . $template_slug;
						if ( $invoice ) {
							$sender              = new EmailSender();
							$invoiceController   = new InvoiceGeneratorController();
							$customer_first_name = $invoice->first_name;
							$subject             = 'Your Portfolio Insider Invoice Is Attached';
							$pdfFilename         = $invoiceController->generateFileNameForGeneratedInvoice( $invoice );
							$path_to_file        = [
								[
									'filename' => $invoiceController->pdf_path . $pdfFilename,
									'mime' => 'application/pdf'
								]
							];
							if(!empty($invoiceController->attachments) && count($invoiceController->attachments)){
								foreach($invoiceController->attachments as $a){
									if(!empty($a['filename'])){
										$path_to_file[] = $a;
									}
								}
							}
							$customer_email      = $invoice->email;
							$res                 = $sender->sendEmail( $to, $bcc, $cc, $from_email, $template, $subject, $from_name, $customer_first_name, '', $path_to_file, $customer_email );
							if ( $res && $res['success'] ) {
								EmailLogsGeneratedInvoices::insert( $dataToLog );
								$logs = EmailLogsGeneratedInvoices::where( 'invoice_id', $invoice_id )->get();
								return $this->sendResponse( json_encode( $logs ), 'Success! Message has been sent' );
							} else {
								if ( $res && ! empty( $res['message'] ) ) {
									$error = $res['message'];
								} else {
									$error = 'Error! Can not send Email.';
								}
							}
						} else {
							$error = 'Error! No valid invoice.';
						}
					} else {
						$error = 'Error! No valid template.';
					}
				}
				else {
					$error = 'Error! Empty email address.';
				}
			}
			Errors::create([
				'error' => $error,
				'controller' => 'SendEmailController',
				'function' => 'sendGeneratedInvoiceEmail'
			]);
			return $this->sendError( $error );

		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SendEmailController',
				'function' => 'sendGeneratedInvoiceEmail'
			]);
			return $this->sendError( $ex->getMessage() );
		}
	}

	public function validateEMAIL($email) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return true;
		}
		return false;
	}

}
