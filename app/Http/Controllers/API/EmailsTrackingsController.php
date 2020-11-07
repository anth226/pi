<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\EmailCampaigns;
use App\EmailCampaignsStat;
use App\uEmails;
use App\EmailsResponses;
use App\EmailsTracking;
use Exception;
use App\Exeptions;

class EmailsTrackingsController extends BaseController
{
	public function store(Request $request)
	{
		$this->processingRequest($request);
	}
	public function index(Request $request){
		$this->processingRequest($request);
	}

	public function processingRequest(Request $request){
		try {
			$input = $request->all();
			$input['act'] = !empty($input['act']) ? $input['act'] : '';
			$input['e'] = !empty($input['e']) ? $input['e'] : '';
			$input['c'] = !empty($input['c']) ? $input['c'] : '';

			$email_id = uEmails::getEmaiIdByToken($input['e']);


			$project_id = EmailCampaigns::getProjectIdByCampaignId($input['c']);
			if($email_id && $project_id) {
				if($input['act'] == 'click') {
					$isClicked = EmailsTracking::isAlreadyClicked( $email_id, $input['c'] );
					if ( ! $isClicked ) {
						EmailCampaignsStat::countClicked( $input['c'] );
						EmailsResponses::countClicked( $email_id, $project_id );
					}
				}
				if($input['act'] == 'pxl') {
					$isOpened = EmailsTracking::isAlreadyOpened( $email_id, $input['c'] );
					if(!$isOpened) {
						EmailCampaignsStat::countOpened( $input['c'] );
						EmailsResponses::countOpened( $email_id, $project_id );
					}
				}
				if($input['act'] == 'pur') {
					$isPurchased = EmailsTracking::isAlreadyPurchased( $email_id, $input['c'] );
					if(!$isPurchased) {
						EmailCampaignsStat::countPurchased( $input['c'] );
						EmailsResponses::countPurchased( $email_id, $project_id );
					}
				}
			}

			return $this->sendResponse($input, 'Done');
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'API/EmailsTrackings', 'function' => 'processingRequest error'] );
			return $this->sendError( 'Error');
		}
	}

}
