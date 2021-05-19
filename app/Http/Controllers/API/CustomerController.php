<?php

namespace App\Http\Controllers\API;

use App\Customers;
use App\Http\Controllers\CustomersController;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\CustomerResource;
use App\Invoices;
use App\KmClasses\Sms\FormatUsPhoneNumber;
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
        ]);

        $customer = Customers::create(array_merge($request->all(), ['formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number'))]));
        if ($customer) {
            // send to
            $this->subscribeKlaviyo($request->email, $request->phone_number, $request->first_name, $request->last_name);

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
                $this->sendDataToSMSSystem( $dataToSend);
            }

            // query Stripe and update invoice table with stripe information

            // After creating a user in the invoice system it should send User_id to the PI System with the success message(if success) else error message.
            return $this->sendResponse([
                'user_id' => $customer->id,
            ], 'Success');
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
