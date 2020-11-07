<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendEmail;

use App\Http\Controllers\Controller;

class EmailJobController extends Controller
{
	/**
	 *
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function enqueue(Request $request)
	{
		//$all_emails =
		$details = ['email' => 'markareno@gmail.com'];
		SendEmail::dispatch($details);
	}
}
