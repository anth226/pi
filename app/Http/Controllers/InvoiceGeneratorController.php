<?php

namespace App\Http\Controllers;

use App\KmClasses\Sms\UsStates;
use Illuminate\Http\Request;

class InvoiceGeneratorController extends InvoicesController
{
	public function create(Request $request)
	{
		$states = UsStates::statesUS();
		return view('invoices.invoicegenerator', compact('states'));
	}
}
