<?php

namespace App\Http\Controllers\API;

use App\Customers;
use App\Helpers\SMSHelper;
use App\Helpers\KlaviyouHelper;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\KmClasses\Sms\FormatUsPhoneNumber;

class CustomerController extends BaseController
{

    /**
     * Get list of customers
     * @return array|\Illuminate\Http\Response
     */
    public function index()
    {
        $customers = Customers::with('invoices')->with('invoices.salespersone')->with('invoices.salespeople.salespersone')->orderBy('customers.id','DESC')->paginate(10);

        return $this->sendResponse(CustomerResource::collection($customers), 'Sample message');
    }

    /**
     * Store customer
     */
    public function store(CustomerRequest $request)
    {
        $customer = Customers::create(array_merge($request->all(), ['formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number'))]));
        if ($customer) {
            // send to klaviyou

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
                SMSHelper::sendData($dataToSend);
            }
            // send to pipedrive

            // After creating a user in the invoice system it should send User_id to the PI System with the success message(if success) else error message.
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
    public function update(Customers $customer, CustomerRequest $request)
    {
        $customer->update($request->all());
        return $this->sendResponse((new CustomerResource($customer)), 'Update customer successfully.');
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
        $customer->delete();
        return $this->sendResponse([], 'Customer has been deleted.');
    }
}
