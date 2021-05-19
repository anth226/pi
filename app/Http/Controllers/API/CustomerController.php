<?php

namespace App\Http\Controllers\API;

use App\Customers;
use App\Http\Controllers\CustomersController;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\CustomerResource;
use App\Invoices;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerController extends CustomersController
{
    public function __construct()
    {

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
//            'qty' => 'required|numeric|min:1',
            'access_date' => 'required',
            'cc_number' => 'required|digits:4'
        ]);

        $customer = Customers::create(array_merge($request->only([
            'first_name', 'last_name', 'address_1', 'address_2',
            'zip', 'city', 'state', 'email', 'phone_number'
        ]), ['formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number'))]));
        if ($customer) {
            // send to
            $resq = $this->subscribeKlaviyo($request->email, $request->phone_number, $request->first_name, $request->last_name);
            $res = json_decode($resq->getContent(), true);
            if (!isset($res['success']) || !$res['success'])
                return $this->sendError($res['message']);

            // send to sms
            $dataToSend = [
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'full_name' => $request->input('first_name').' '.$request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone_number'),
                'source' => 'portfolioinsider',
                'tags' => 'portfolioinsider,portfolio-insider-prime'
            ];

            if(config('app.env') == 'production') {
                $res = $this->sendDataToSMSSystem( $dataToSend);
                if (!$res['success'])
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
            if ( ! $pipedrive_person['success'] ) {
                $message = 'Error! Can\'t send data to Pipedrive';
                if ( ! empty( $pipedrive_res['message'] ) ) {
                    $message = $pipedrive_res['message'];
                }
                return $this->sendError($message);
            }
            if ($pipedrive_person['data']) {
                $pipedrive_res = $this->updateOrAddPipedriveDeal( $pipedrive_person['data'], $request->input('paid') );
                if ( ! $pipedrive_res['success'] ) {
                    $message = 'Error! Can\'t send data to Pipedrive';
                    if ( ! empty( $pipedrive_res['message'] ) ) {
                        $message = $pipedrive_res['message'];
                    }
                    return $this->sendError($message);
                }
            }
            // query Stripe and update invoice table with stripe information
            $stripe_res = $this->sendDataToStripe($dataToSend);
            if(!$stripe_res['success']){
                $message = 'Error! Can\'t send data to stripe';
                if(!empty($stripe_res['message'])){
                    $message = $stripe_res['message'];
                }
                return $this->sendError($message);
            } else {
                $dataToSend['customerId'] = $stripe_res['data']['customer'];
                $dataToSend['subscriptionId'] = $stripe_res['data']['id'];

                $salespeople_id = 1; // should be param from input
                $pdftemplate_id = 1;
                // store in invoices table
                $invoice_data_to_save = [
                    'customer_id' => $customer->id,
                    'salespeople_id' => $salespeople_id,
                    'product_id' => $request->input('product_id'),
                    'sales_price' => $request->input('sales_price'),
                    'qty' => $request->input('qty'),
                    'access_date' => Elements::createDateTime($request->input('access_date')),
                    'cc_number' => $request->input('cc'),
                    'paid' => $request->input('paid'),
                    'own' => $request->input('sales_price') - $request->input('paid'),
                    'paid_at' => Carbon::now(),
                    'pdftemplate_id' => $pdftemplate_id,
                ];

                if(!empty($stripe_res) && !empty($stripe_res['data'])){
                    if(!empty($stripe_res['data']['id'])){
                        $invoice_data_to_save['stripe_subscription_id'] = $stripe_res['data']['id'];
                    }
                    if(!empty($stripe_res['data']['customer'])){
                        $invoice_data_to_save['stripe_customer_id'] = $stripe_res['data']['customer'];
                    }
                    if(!empty($stripe_res['data']['current_period_end'])){
                        $invoice_data_to_save['stripe_current_period_end'] = date("Y-m-d H:i:s",$stripe_res['data']['current_period_end']);
                    }
                    if(!empty($stripe_res['data']['current_period_start'])){
                        $invoice_data_to_save['stripe_current_period_start'] = date("Y-m-d H:i:s",$stripe_res['data']['current_period_start']);
                    }
                    if(!empty($stripe_res['data']['status'])){
                        $invoice_data_to_save['stripe_subscription_status'] = Invoices::STRIPE_STATUSES[$stripe_res['data']['status']];
                    }
                }
                Invoices::create($invoice_data_to_save);
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
    public function detail(Customers $customer)
    {
        return $this->sendResponse(new CustomerResource($customer), 'Retrieve the customer detail successfully.');
    }

    /**
     * Update customer information
     *
     * @param Customers $customer
     * @param CustomerRequest $request
     * @return array|\Illuminate\Http\Response
     */
    public function update(Request $request, $id)
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

        if (!$customer) {
            return $this->sendError([], 'Customer not found');
        }

        $customer->first_name = $request->input('first_name');
        $customer->last_name =  $request->input('last_name');
        $customer->address_1 = $request->input('address_1');
        $customer->address_2 =  !empty($request->input('address_2')) ? $request->input('address_2') : '';
        $customer->zip = $request->input('zip');
        $customer->state = $request->input('state');
        $customer->phone_number = $request->input('phone_number');
        $customer->formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number'));

        if ($customer->save())
            return $this->sendResponse((new CustomerResource($customer)), 'Update customer successfully.');

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
        Invoices::where('customer_id', $customer->id)->delete();
        $customer->delete();
        return $this->sendResponse([], 'Customer has been deleted.');
    }
}
