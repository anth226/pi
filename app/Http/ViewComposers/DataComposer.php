<?php

namespace App\Http\ViewComposers;

use App\Leads;
use App\LeadsLog;
use App\Unsubscribed;
use Illuminate\View\View;
use Carbon\Carbon;

class DataComposer
{
	public $statistics = [];
	/**
	 * Create a movie composer.
	 *
	 * @return void
	 */
	public function __construct()
	{

	}

	/**
	 * Bind data to the view.
	 *
	 * @param  View  $view
	 * @return void
	 */
	public function compose(View $view)
	{
		if (!empty(auth()->user()) && auth()->user()->email_verified_at) {
//			$view->with( 'statistics', $this->statistics );
		}
	}
}