<?php

namespace App\Console\Commands;

use App\Errors;
use App\KmClasses\Pipedrive;
use App\Salespeople;
use App\User;
use Illuminate\Console\Command;
use Exception;

class ProcessingPiLeads extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'processing:pileads';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Processing pi persons';

	/**
	 * @return bool
	 */
	public function handle()
	{
		ini_set('memory_limit', '8024M');
		set_time_limit(72000);
		return true;
	}


}
