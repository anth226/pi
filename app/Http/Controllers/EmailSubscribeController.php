<?php

namespace App\Http\Controllers;

use App\Projects;
use Illuminate\Http\Request;
use App\KmClasses\Sms\EmailSender;
use App\uEmails;
use App\EmailCampaigns;
use App\ProjectsEmails;
use App\Exeptions;
use Exception;
use Validator, Input;


class EmailSubscribeController extends Controller
{
	public function index(Request $request)
	{
		$project_name = false;
		$project_url = false;
		try {
			$input = $request->all();
			Validator::make( $input, [
				'e' => 'required',
				'p' => 'required',
				'c' => 'required'
			] )->validate();
			$email_token = $input['e'];
			$email_id = uEmails::getEmaiIdByToken( $email_token );
			if ( $email_id ) {
				EmailSender::subscribe( $email_id, $input['p'], $input['c']);
				$project = Projects::where('id', $input['p'] )->first();
				if($project){
					if($project->project_name){
						$project_name = $project->project_name;
					}
					if($project->project_url){
						$project_url = $project->project_url;
					}
				}
			}

		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'EmailSubscribeController', 'function' => 'index'] );
		}

		return view('emailsubscribe', [
			'project_name' => $project_name,
			'project_url' => $project_url
		]);
	}
}
