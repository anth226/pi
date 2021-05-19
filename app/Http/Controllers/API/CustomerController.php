<?php

namespace App\Http\Controllers\API;

use App\ActionsLog;
use App\Customers;
use App\Http\Controllers\CustomersController;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\CustomerResource;
use App\Invoices;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
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
            'email' => 'required|unique:customers,email,NULL,id,deleted_at,NULL|email|max:120',
            'phone_number' => 'required|max:120|min:10',
            'sales_price' => 'required',
            'subscription_id' => 'required',
            'access_date' => 'required',
            'cc_number' => 'required|digits:4'
        ]);

        $customer = Customers::create(array_merge($request->only([
            'first_name', 'last_name', 'address_1', 'address_2',
            'zip', 'city', 'state', 'email', 'phone_number', 'pi_user_id', 'country'
        ]), ['formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number'))]));
        if ($customer) {
            $this->logAction(2, 0, $customer->id);
            // send to Klaviyo
            $resq = $this->subscribeKlaviyo($request->email, $request->phone_number, $request->first_name, $request->last_name);
            $res = json_decode($resq->getContent(), true);
            if (!isset($res['success']) || !$res['success'])
                return $this->sendError($res['message']);

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
                return $this->sendError($res['message']);

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
                    return $this->sendError($message);
                }
            }

            // query Stripe subscription object by subscription_id
            try {
                $stripeRes =  $this->stripeClient->subscriptions->retrieve($request->subscription_id);
                $dataArr = $stripeRes->items->data;
                $customerId = $stripeRes->customer;
                $currentPeriodEnd = $stripeRes->current_period_end;
                $currentPeriodStart = $stripeRes->current_period_start;
                $stripeStatus = $stripeRes->status;

                foreach ($dataArr as $item) {
                    $priceId = $item->price->id;
                    // check if priceId is exist on product table
                    if (!$product = Products::where('stripe_price_id', $priceId)->first()) {
                        // create new product based on the subscription detail
                        $product = Products::create([
                            'title' => $item->price->product,
                            'price' => $item->price->unit_amount,
                            'stripe_price_id' => $priceId,
                            'dev_stripe_price_id' => $priceId,
                        ]);
                        $this->logAction(9, 0, $product->id);
                    }

                    // save to invoices table
                    $invoice_data_to_save = [
                        'customer_id' => $customer->id,
                        'salespeople_id' => $request->input('sale_person_id'),
                        'product_id' => $product->id,
                        'sales_price' => $request->input('sales_price'),
                        'qty' => request('qty', 0),
                        'access_date' => Carbon::make($request->input('access_date'))->format('Y-m-d'),
                        'cc_number' => $request->input('cc'),
                        'paid' => $request->input('sales_price'),
                        'own' => 0,
                        'paid_at' => Carbon::now(),
                        'deal_type' => 1,
                        'pdftemplate_id' => request('pdf_template_id', 1),
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
                }
            } catch (\Exception $exception) {
                return $this->sendError([], $exception->getMessage());
            }

            // After creating a user in the invoice system it should send User_id to the PI System with the success message(if success) else error message.
            return $this->sendResponse([
                'user_id' => $customer->id,
            ], 'Success to create customer!');
        }
    }

    /**
     * Get detail of customer
     *
     * @param Customers $customer
     */
    public function detail($id)
    {
        if ($customer = Customers::find($id))
            return $this->sendResponse(new CustomerResource($customer), 'Retrieve the customer detail successfully.');
        else {
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
    public function postUpdate(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'address_1' => 'required|max:120',
            'zip' => 'required|digits:5',
            'city' => 'required|max:120',
            'state' => 'required||max:20',
            'phone_number' => 'required|max:120|min:10'
        ]);

        $customer = Customers::find($id);

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
                        '$email' => $request->email,
                        '$first_name' => $request->first_name,
                        '$last_name' => $request->last_name,
                        '$phone_number' => $request->phone_number,
                    ];

                    $client->profiles->updateProfile( $profileID['id'], $properties );
                }
            } catch (\Exception $e) {
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
            return $this->sendError([], 'Customer not found');
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

        return $this->sendError([], 'Can not update Customer');
    }

    /**
     * Delete customer
     *
     * @param Customers $customer
     * @return array|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function delete(Customers $customer)
    {
        // remove invoice record
        $invoice = Invoices::where('customer_id', $customer->id)->first();
        if ($invoice) {
            $this->logAction(1, 2, $invoice->id);
            $invoice->delete();
        }

        // TODO: unsubscribe customer from Klaviyou and sms system.
        $this->unsubscribeKlaviyo($customer->email);
        $this->unsubscribeSmsSystem(json_encode($customer->email), json_encode($customer->phone_number));

        // remove customer record
        $this->logAction(2, 2, $customer->id);
        $customer->delete();
        return $this->sendResponse([], 'Customer has been deleted.');
    }

    private function logAction($model, $action, $relatedId){
        return ActionsLog::create([
            'user_id' => 0,
            'model' => $model,
            'action' => $action,
            'related_id' => $relatedId
        ]);
    }
}
