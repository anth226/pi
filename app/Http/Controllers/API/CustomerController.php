<?php

namespace App\Http\Controllers\API;

use App\ActionsLog;
use App\Customers;
use App\EmailLogs;
use App\EmailLogsGeneratedInvoices;
use App\EmailTemplates;
use App\Errors;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\InvoiceGeneratorController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\SendEmailController;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\CustomerResource;
use App\InvoiceGenerator;
use App\Invoices;
use App\KmClasses\Pipedrive;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\EmailSender;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\LevelsSalespeople;
use App\PdfTemplates;
use App\Products;
use App\Salespeople;
use App\SecondarySalesPeople;
use App\SentData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Klaviyo\Klaviyo;
use Klaviyo\Model\ProfileModel as KlaviyoProfile;
use Stripe\StripeClient;

class CustomerController extends CustomersController
{
    protected $stripeClient;

    public function __construct()
    {
        $stripeAPIKey = config('stripe.stripeKey');

        $this->stripeClient = new StripeClient($stripeAPIKey);
    }

    /**
     * Get list of customers
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $customers = Customers::with('invoices')->with('invoices.salespersone')->with('invoices.salespeople.salespersone')->orderBy('customers.id','DESC')->paginate(10);

        return response()->json(new CustomerCollection($customers));
    }

    /**
     * Store customer
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'address_1' => 'required|max:120',
            'zip' => 'required|max:120',
            'city' => 'required|max:120',
            'state' => 'required||max:20',
            'email' => 'email|max:120',
            'phone_number' => 'required|max:120|min:10',
            'sales_price' => 'required',
            'subscription_id' => 'required',
            'access_date' => 'required',
            'cc_number' => 'required|digits:4'
        ]);

        // query Stripe subscription object by subscription_id
        try {
            $stripeRes =  $this->stripeClient->subscriptions->retrieve($request->subscription_id);
            $dataArr = $stripeRes->items->data;
            $customerId = $stripeRes->customer;
            $currentPeriodEnd = $stripeRes->current_period_end;
            $currentPeriodStart = $stripeRes->current_period_start;
            $stripeStatus = $stripeRes->status;

            // 1. Customer should be created only if ALL stripe queries finish successfully.
            if (!$stripeRes) {
                return $this->sendError('Stripe queries failed to finish successfully. Can not create customer');
            }

            $customer = Customers::updateOrCreate([
                'email' => $request->email,
                'pi_user_id' => $request->pi_user_id
            ], array_merge($request->only([
                'first_name', 'last_name', 'address_1', 'address_2',
                'zip', 'city', 'state', 'phone_number', 'pi_user_id', 'country'
            ]), ['formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')), 'stripe_customer_id' => $customerId, 'created_from' => 'api']));

            $this->logAction(2, 0, $customer->id);

            foreach ($dataArr as $item) {
                $priceId = $item->price->id;
                // check if priceId is exist on product table
                if (!$product = Products::where('stripe_price_id', $priceId)->first()) {

                    // create new product based on the subscription detail
                    $isProduction = config('app.env') === 'production';

                    // query for product name
                    $stripeProductId = $item->price->product;

                    // Please increment sku in products table for new product
                    // Get latest product sku
                    $latestPro = Products::latest()->first();
                    $latestSku = $latestPro->sku;
                    $stripeProduct = $this->stripeClient->products->retrieve($stripeProductId, []);

                    $product = Products::create([
                        'title' => $stripeProduct ? $stripeProduct->name : 'Product ID '.$stripeProductId,
                        'sku' => intval($latestSku) + 1,
                        'price' => $item->price->unit_amount,
                        'stripe_price_id' => $isProduction ? $priceId : null,
                        'dev_stripe_price_id' => !$isProduction ? $priceId : null,
                    ]);
                    $this->logAction(9, 0, $product->id);
                }

                $salePeopleId = $request->input('salespeople_id') ?? null;

                if ($salePeopleId)
                    $salespeople = LevelsSalespeople::getSalespersonInfo($salePeopleId);

                // save to invoices table
                $invoice_data_to_save = [
                    'customer_id' => $customer->id,
                    'salespeople_id' => $salePeopleId,
                    'product_id' => $product->id,
                    'sales_price' => $request->input('sales_price'),
                    'qty' => request('qty', 0),
                    'access_date' => Carbon::make($request->input('access_date'))->format('Y-m-d'),
                    'cc_number' => $request->input('cc'),
                    'paid' => $request->input('sales_price'),
                    'own' => 0,
                    'paid_at' => Carbon::now(),
                    'deal_type' => 1,
                    'pdftemplate_id' => request('pdf_template_id', 4),
                ];

                $invoice_data_to_save['stripe_subscription_id'] = $item->id;
                $invoice_data_to_save['stripe_customer_id'] = $customerId;
                $invoice_data_to_save['stripe_current_period_end'] = date("Y-m-d H:i:s", $currentPeriodEnd);
                $invoice_data_to_save['stripe_current_period_start'] = date("Y-m-d H:i:s", $currentPeriodStart);
                $invoice_data_to_save['stripe_subscription_status'] = Invoices::STRIPE_STATUSES[$stripeStatus];

                $invoice = Invoices::updateOrCreate([
                    'stripe_subscription_id' => $item->id
                ], $invoice_data_to_save);
                $this->logAction(1, 1, $invoice->id);

                $this->addContacts($customer, 1,  $invoice->id);

                // generate invoice PDF
                $invoice_instance = new InvoicesController();
                SecondarySalesPeople::create( [
                    'salespeople_id' => $salespeople->salespeople_id,
                    'invoice_id'     => $invoice->id,
                    'sp_type' => 1,
                    'earnings'=> 0,
                    'percentage' => $salespeople->level->percentage,
                    'level_id' => $salespeople->level_id
                ] );

                $vp_salespeople = [];
                $biz_dev_salespeople = [];
                $invoice_salespeople = [];

                $invoice_salespeople[] = Salespeople::where('id', $salespeople->salespeople_id)->withTrashed()->value('name_for_invoice');
                $vp_salespeople[] = Salespeople::where('id', $salespeople->salespeople_id)->withTrashed()->value('pipedrive_user_id');

                $pdfTemplate = PdfTemplates::where('id', 4)->value('slug');
                $invoice_instance->generatePDF($invoice->id, $pdfTemplate ?? 'pdfviewmain');

                //calculate salespeople commission on new deal creation
                $invoice_percentages = $invoice_instance->calcEarning($invoice);
                $invoice_instance->savePercentages($invoice_percentages, $invoice->id);

                // send email
                $mailSent = $this->sendInvoiceEmail($invoice->id, 3, $customer->email);
                if (!isset($mailSent['success']) || !$mailSent['success'] ){
                    return $this->sendError($mailSent['message']);
                }
            }
        } catch (\Exception $exception) {
            $this->logError( $exception->getMessage(), 'store');
            return $this->sendError($exception->getMessage());
        }


        if ($customer) {
            // send to Klaviyo
            $res = $this->subscribeKlaviyo($request->email, $request->phone_number, $request->first_name, $request->last_name, false);
            if (!isset($res['success']) || !$res['success'])
            {
                $this->logError($res['message'], 'store');
                return $this->sendError($res['message']);
            }

            // send to sms cal
            $dataToSend = [
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'full_name' => $request->input('first_name').' '.$request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone_number'),
                'source' => 'portfolioinsider',
                'tags' => 'portfolioinsider,portfolio-insider-prime'
            ];

            $res = $this->sendDataToSMSSystem( $dataToSend);
            if (!$res['success'])
            {
                $this->logError($res['message'], 'store');
                return $this->sendError($res['message']);
            }

            // send data to pipedrive
            $dataToSend = [
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'full_name' => $request->input('first_name').' '.$request->input('last_name'),
                'email' => strtolower($request->input('email')),
                'phone' => $request->input('phone_number'),
                'source' => 'portfolio-insider-prime',
                'tags' => 'portfolio-insider-prime',
                'address_1' => $request->input('address_1'),
                'address_2' => !empty($request->input('address_2')) ? $request->input('address_2') : '',
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'zip' => $request->input('zip'),
                'phone_number' => $request->input('phone_number'),
                'formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')),
                'sales_price' => $request->input('sales_price'),
                'paid' => $request->input('paid'),
                'stripe_product_id' => $request->input('product_id')
            ];

            $pipedrive_person = $this->checkPipedrive( $dataToSend );
            if (isset($pipedrive_person['success']) && $pipedrive_person['success'] && isset($pipedrive_person['data']) && $pipedrive_person['data']) {
                $pipedrive_res = $this->updateOrAddPipedriveDeal( $pipedrive_person['data'], $request->input('paid') );
                if ( ! $pipedrive_res['success'] ) {
                    $message = 'Error! Can\'t send data to Pipedrive';
                    if ( ! empty( $pipedrive_res['message'] ) ) {
                        $message = $pipedrive_res['message'];
                    }

                    $this->logError($message, 'store');
                    return $this->sendError($message);
                }
            }

            $dataToUpdatePipedriveSalespeople = [];

            if (count($vp_salespeople)) {
                $dataToUpdatePipedriveSalespeople[config('pipedrive.vp_field_id')] = $vp_salespeople;
            }

            if (count($biz_dev_salespeople)) {
                $dataToUpdatePipedriveSalespeople[config('pipedrive.biz_dev_field_id')] = $biz_dev_salespeople;
            }

            if (count($dataToUpdatePipedriveSalespeople)) {
                Pipedrive::executeCommand(config('pipedrive.api_key'), new Pipedrive\Commands\UpdatePerson($pipedrive_person['data']->id, $dataToUpdatePipedriveSalespeople));
            }
            Pipedrive::executeCommand(config('pipedrive.api_key'), new Pipedrive\Commands\AddNote($pipedrive_res['data'], implode(', ', $invoice_salespeople)));

            $this->getPipedriveLeadSources($customer);

            // After creating a user in the invoice system it should send User_id to the PI System with the success message(if success) else error message.
            return $this->sendResponse([
                'user_id' => $customer->id,
            ], 'Success');
        }

        return $this->sendError("Customer is invalid");
    }

    /**
     * Get detail of customer
     *
     * @param Customers $customer
     */
    public function detail()
    {
        if ($customer = Customers::where('email', request('email'))->first())
        {
            return $this->sendResponse($customer->toArray(), 'Retrieve the customer detail successfully.');
        }
        else {
            $this->logError('Customer not found.', 'detail');
            return $this->sendError([], 'Customer not found.', 400);
        }
    }

    /**
     * Update customer information
     *
     * @param Customers $customer
     * @param CustomerRequest $request
     * @return array|\Illuminate\Http\Response
     */
    public function postUpdate(Request $request)
    {
        $request->validate([
            'pi_user_id' => 'required',
        ]);

        $customer = Customers::where('pi_user_id', $request->pi_user_id)->first();

        // check if that email has already assigned to other user
        if ($email = $request->email) {
            if (Customers::where('email', $email)->where('pi_user_id', '!=', $request->pi_user_id)->first()) {
                return $this->sendError('Email is already assigned to other user.');
            }

            if ($customer->created_from !== 'api') {
                return $this->sendError('Only allow update email with account created form API.', [], 400);
            }
        }

        // check if update first name last name, update Klaviyo also
        if (
            $customer->first_name !== $request->input('first_name') ||
            $customer->last_name !==  $request->input('last_name') ||
            $customer->phone_number !==  $request->input('phone_number')
        ) {
            // send update to Klaviyo
            try {
                $client = new Klaviyo( config( 'klaviyo.apiKey'), config( 'klaviyo.pubKey'));
                if ($client) {
                    // get Profile ID
                    $profileID = $client->profiles->getProfileIdByEmail($customer->email); // this require Klaviyo Lib ver 2.3.0

                    // update info
                    $properties = [
                        '$first_name' => $request->first_name,
                        '$last_name' => $request->last_name,
                        '$phone_number' => $request->phone_number,
                    ];

                    if ($customer->email !== $request->email) {
                        $properties = array_merge($properties, ['$email' => $request->email,]);
                    }

                    $client->profiles->updateProfile( $profileID['id'], $properties );
                }
            } catch (\Exception $e) {
                $this->logError($e->getMessage(), 'postUpdate');
                return $this->sendError($e->getMessage(), []);
            }
        }

        // check if update phone number, unsubscribe old phone and subscribe new one
        if (
            $customer->phone_number !== $request->input('phone_number')
        ) {
            // send update to SMS, unsubscribe old phone and subscribe new one
            $this->unsubscribeSmsSystem(json_encode($customer->email), json_encode($customer->phone_number), false);
            $this->subscribeSmsSystem($customer->email, $request->phone_number, $customer->first_name, $customer->last_name);
        }

        if (!$customer) {
            $this->logError('Customer not found.', 'postUpdate');
            return $this->sendError([], 'Customer not found.');
        }

        $customer->first_name = $request->input('first_name');
        $customer->last_name =  $request->input('last_name');
        $customer->address_1 = $request->input('address_1');
        $customer->address_2 =  !empty($request->input('address_2')) ? $request->input('address_2') : '';
        $customer->zip = $request->input('zip');
        $customer->state = $request->input('state');
        $customer->country = $request->input('country');
        $customer->pi_user_id = $request->input('pi_user_id');
        $customer->phone_number = $request->input('phone_number');
        $customer->formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number'));

        if ($customer->save())
        {
            $this->logAction(2, 1, $customer->id);
            return $this->sendResponse((new CustomerResource($customer)), 'Update customer successfully.');
        }

        $this->logError('Can not update Customer', 'postUpdate');
        return $this->sendError([], 'Can not update Customer');
    }

    private function logAction($model, $action, $relatedId){
        return ActionsLog::create([
            'user_id' => 1, // change to 1 because 0 is invalid with foreign key
            'model' => $model,
            'action' => $action,
            'related_id' => $relatedId
        ]);
    }

    private function logError($err, $function){
        return Errors::create([
            'error' => $err,
            'controller' => 'CustomerController',
            'function' => $function
        ]);
    }

    protected function sendInvoiceEmail($invoiceId, $emailTemplateID, $to, $salePersonEmail = null){
        try {
            $error = 'Error! No input data.';
            $invoice_id = $invoiceId ?? 0;
            $email_template_id = $emailTemplateID ?? 0;
            $bcc        = 'corporate@portfolioinsider.com';
            $cc        = $salePersonEmail;

			$from_name           = 'Support Portfolio Insider';
			$from_email          = 'support@portfolioinsider.com';

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
                                'user_id'           => 1,
                                'from'              => $from_email,
                                'to'                => $t,
                                'created_at'        => date( 'Y-m-d H:i:s' ),
                                'updated_at'        => date( 'Y-m-d H:i:s' )
                            ];
                        }
                        else{
                            return $this->sendError( $t." is not valid address. please fix and try again.", [], 200, false );
                        }
                    }
                }
                if($bcc && count($bcc)) {
                    foreach ($bcc as $t) {
                        if($this->validateEMAIL($t)) {
                            $dataToLog[] = [
                                'invoice_id'        => $invoice_id,
                                'email_template_id' => $email_template_id,
                                'user_id'           => 1,
                                'from'              => $from_email,
                                'to'                => $t,
                                'created_at'        => date( 'Y-m-d H:i:s' ),
                                'updated_at'        => date( 'Y-m-d H:i:s' )
                            ];
                        }
                        else{
                            return $this->sendError( $t." is not valid address. please fix and try again." , [], 200, false);
                        }
                    }
                }
                if($cc && count($cc)) {
                    foreach ( $cc as $t ) {
                        if($this->validateEMAIL($t)) {
                            $dataToLog[] = [
                                'invoice_id'        => $invoice_id,
                                'email_template_id' => $email_template_id,
                                'user_id'           => 1,
                                'from'              => $from_email,
                                'to'                => $t,
                                'created_at'        => date( 'Y-m-d H:i:s' ),
                                'updated_at'        => date( 'Y-m-d H:i:s' )
                            ];
                        }
                        else{
                            return $this->sendError( $t." is not valid address. please fix and try again." , [], 200, false);
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

                                return $this->sendResponse( json_encode( $logs ), 'Success! Message has been sent' , false);
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
                'controller' => 'CustomerController',
                'function' => 'sendInvoiceEmail'
            ]);
            return $this->sendError( $error, [], 200, false );

        }
        catch (Exception $ex){
            Errors::create([
                'error' => $ex->getMessage(),
                'controller' => 'CustomerController',
                'function' => 'sendInvoiceEmail'
            ]);
            return $this->sendError( $ex->getMessage() , [], 200, false);
        }
    }

    public function validateEMAIL($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        return false;
    }
}
