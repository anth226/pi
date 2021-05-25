<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseController
{
    public function check(Request $request)
    {
        if (Auth::attempt($request->all())) {
            $user = auth()->user();
            $user->setNewApiToken();
            return $this->sendResponse(['api_token' => $user->api_token], 'Success');
        }
    }
}
