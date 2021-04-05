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
		$this->getLeadsForUsers();
		$this->getLeadsForSalespeople();
		return true;
	}

	public function getLeadsByOwnerOnePage($owner_id, $start = 0, $limit = 500){
		try{
			$res = [
				'data' => [],
				'next_start' => -1
			];
//			$key = config( 'pipedrive.api_key' );
			$key = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';
			$persons  = Pipedrive::executeCommand( $key, new Pipedrive\Commands\GetPersons($owner_id, $start, $limit) );
			if(!empty($persons)){
				if(!empty($persons->data)) {
					$res['data'] = $persons->data;
				}
				if(
					!empty($persons->additionalData) &&
					!empty($persons->additionalData->pagination) &&
					!empty($persons->additionalData->pagination->nextStart)
				)
				{
					$res['next_start'] = $persons->additionalData->pagination->nextStart;
				}

			}
			return $res;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'ProcessingPiLeads',
				'function' => 'getLeadsByOwnerOnePage'
			]);
			return false;
		}
	}

	public function getLeadsByOwner($owner_id){
		try{
			$res = [];
			$next_start = 0;
			while ($next_start >= 0){
				$result = $this->getLeadsByOwnerOnePage($owner_id, $next_start, $limit = 100);
				$res = array_merge($res, $result['data']);
				$next_start = $result['next_start'];
			}
			return $res;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'ProcessingPiLeads',
				'function' => 'getLeadsByOwner'
			]);
			return false;
		}
	}

	public function getLeadsForUsers(){
		try{
			$users = User::get();
			if (! empty( $users ) && $users->count() ) {
				foreach ( $users as $u ) {
					if ( ! empty( $u->pipedrive_user_id ) ) {
						$this->getLeadsByOwner($u->pipedrive_user_id);
					}
				}
			}
			return true;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'ProcessingPiLeads',
				'function' => 'getLeadsForUsers'
			]);
			return false;
		}
	}

	public function getLeadsForSalespeople(){
		try{
			$salespeople = Salespeople::get();
			if (! empty( $salespeople ) && $salespeople->count() ) {
				foreach ( $salespeople as $s ) {
					if ( ! empty( $s->pipedrive_user_id ) ) {
						$this->getLeadsByOwner($s->pipedrive_user_id);
					}
				}
			}
			return true;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'ProcessingPiLeads',
				'function' => 'getLeadsForSalespeople'
			]);
			return false;
		}
	}

}
