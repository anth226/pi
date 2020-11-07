<?php

namespace App\Http\Controllers;

use App\EmailCampaigns;
use App\KmClasses\Sms\EmailSender;
use App\Projects;
use App\ProjectsEmails;
use Illuminate\Http\Request;
use App\uEmails;
use App\Exeptions;
use Exception;
use Validator, Input;

class EmailUnsubscribeController extends Controller
{
	public function index(Request $request)
	{
		$project_name = false;
		$project_url = false;
		$subscribe_link = false;
		$error = false;
		try {
			$input = $request->all();
			Validator::make( $input, [
				'e' => 'required',
				'p' => 'required',
				'c' => 'required'
			] )->validate();
			$email_id = uEmails::getEmaiIdByToken( $input['e'] );
			if ( $email_id ) {
				$res = EmailSender::unsubscribe( $email_id, $input['p'],$input['c']);
				$project = Projects::where( 'id', $input['p'] )->first();
				if($project){
					if($project->project_name){
						$project_name = $project->project_name;
					}
					if($project->project_url){
						$project_url = $project->project_url;
					}
				}
				$subscribe_link = '/subscribe_me/?e=' . $input['e'] . '&p=' . $input['p'].'&c='.$input['c'];
			}
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
//			$error = true;
			Exeptions::create( ['error' => $err, 'controller' => 'EmailUnsubscribeController', 'function' => 'index'] );
		}

		return view('emailunsubscribe', [
			'project_name' => $project_name,
			'project_url' => $project_url,
			'subscribe_link' => $subscribe_link,
			'error' => $error
		]);
	}
}
