<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'address_1' => 'required|max:120',
            'zip' => 'required|max:120',
            'city' => 'required|max:120',
            'state' => 'required||max:20',
            'email' => 'required|unique:customers,email,NULL,id,deleted_at,NULL|email|max:120',
            'phone_number' => 'required|max:120|min:10',
        ];
    }
}
