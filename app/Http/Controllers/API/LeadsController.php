<?php

namespace App\Http\Controllers\API;

use App\Leads;
use App\LeadsTags;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;
use App\FormsAnswers;
use App\FormsQuestions;
use App\QuestionsSources;
use App\LeadsData;
use App\PhoneSource;
use App\Uleads;
use App\uEmails;
use App\PhoneAddress;
use App\Tags;
use App\PhoneTags;
use App\EmailTags;
use App\UsStates;
use App\FirstLastNames;
use App\FullNames;
use App\KmClasses\Sms\SmsSender;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use DB;
use App\KmClasses\Sms\AreaCodes;


class LeadsController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//	    $leads = Leads::all();
//
//
//	    return $this->sendResponse($leads->toArray(), 'Leads retrieved successfully.');
    }

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */

	public function store(Request $request)
	{
		$input = $request->all();
		return $this->saveLead($input);
	}

	public function saveLead($input)
	{
		$input['first_name'] = !empty($input['first_name']) ? ucwords(mb_strtolower(substr(trim($input['first_name']),0, 99))) : '';
		$input['last_name'] = !empty($input['last_name']) ? ucwords(mb_strtolower(substr(trim($input['last_name']),0, 99))) : '';
		$input['full_name'] = !empty($input['full_name']) ? ucwords(mb_strtolower(substr(trim($input['full_name']),0, 199))) : '';
		$input['email'] = !empty($input['email']) ? mb_strtolower(substr(trim($input['email']),0, 99)) : '';
		$input['phone'] = !empty($input['phone']) ? mb_strtolower(substr(trim($input['phone']),0, 99)) : '';
		$input['source'] = !empty($input['source']) ? mb_strtolower(substr(trim($input['source']),0, 99)) : '';
		$input['tags'] = !empty($input['tags']) ? mb_strtolower(trim($input['tags'])) : '';
		$input['question_1'] = !empty($input['question_1']) ? $input['question_1'] : '';
		$input['answer_1'] = !empty($input['answer_1']) ? $input['answer_1'] : '';

		$input['first_name_id'] = 0;
		$input['last_name_id'] = 0;
		$input['full_name_id'] = 0;
		if($input['first_name']){
			$input['first_name_id'] = FirstLastNames::getFieldId($input['first_name']);
		}
		if($input['last_name']){
			$input['last_name_id'] = FirstLastNames::getFieldId($input['last_name']);
		}
		if($input['full_name']){
			$input['full_name_id'] = FullNames::getFieldId($input['full_name']);
		}

		if($input['phone']) {
			$input['formated_phone_number'] = FormatUsPhoneNumber::formatPhoneNumber($input['phone']);
		}
		if(empty($input['formated_phone_number'])){
			$input['phone_status'] = 3;
		}
		else{
			$phone_id = Uleads::getFieldId($input['formated_phone_number'], $input['first_name_id'], $input['last_name_id'], $input['full_name_id']);
			if($phone_id){
				$input['phone_id'] =  $phone_id;
				$area_code = substr ($input['formated_phone_number'], 2 ,3);
				if($area_code){
					$us_state = AreaCodes::code_to_state($area_code);
					if($us_state){
						$us_state_id = UsStates::getFieldId($us_state);
						if($us_state_id){
							PhoneAddress::getFieldId($phone_id, $area_code, $us_state_id);
						}
					}
				}
			}
		}
		$input['source'] = !empty($input['source']) ? trim(strtolower($input['source'])) : '';


		$lead = "";
		if($input['email'] || $input['phone']) {

			if (filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
				$email_id = uEmails::getFieldId($input['email'], $input['first_name_id'], $input['last_name_id'], $input['full_name_id']);
				if($email_id){
					$input['email_id'] = $email_id;
				}
			}

			if(!empty($input['formated_phone_number'])) {
				$dub = Leads::where( 'formated_phone_number', '=', $input['formated_phone_number'] )->first();
				if(!empty($dub->id)){
					$input['phone_status'] = 4;
				}
			}
			if($input['source']){
				$id = PhoneSource::getSourceId($input['source']);
				if($id){
					$input['sourceId'] = $id;
				}
			}
			$lead = Leads::create( $input );
			if($lead && $lead->id){
				$input['lead_id'] =  $lead->id;
			}

			$tag_ids = $this->saveTags( $input );
			$lead->tags = $tag_ids;


			if($input['question_1'] || $input['answer_1']) {
				$lead = $this->saveFormData( $input, $lead );
			}

			if($input['phone']) {
				$sender = new SmsSender();
				$sender->welcome( $lead );
			}

		}

		return $this->sendResponse($lead->toArray(), 'Product created successfully.');
	}




	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
//		$leads = Leads::find($id);
//
//
//		if (is_null($leads)) {
//			return $this->sendError('Lead not found.');
//		}
//
//
//		return $this->sendResponse($leads->toArray(), 'Lead retrieved successfully.');
	}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Leads  $leads
     * @return \Illuminate\Http\Response
     */
    public function edit(Leads $leads)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Leads  $leads
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Leads $leads)
    {
//	    $input = $request->all();
//
//
//	    $validator = Validator::make($input, [
//		    'email' => 'required',
//	    ]);
//
//
//	    if($validator->fails()){
//		    return $this->sendError('Validation Error.', $validator->errors());
//	    }
//
//
//	    $leads->name = $input['name'];
//	    $leads->detail = $input['detail'];
//	    $leads->save();
//
//
//	    return $this->sendResponse($leads->toArray(), 'Lead updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Leads  $leads
     * @return \Illuminate\Http\Response
     */
    public function destroy(Leads $leads)
    {
//	    $leads->delete();
//
//
//	    return $this->sendResponse($leads->toArray(), 'lead deleted successfully.');
    }

    protected function saveFormData($input, $lead){
	    for($i=1; $i<=15; $i++) {
	    	$q_id = 0;
		    if ( !empty($input['question_'.$i] )) {
			    $q = FormsQuestions::addQuestion( $input['question_'.$i] );
			    if($q && $q->id){
				    $lead->{'question_'.$i} = $input['question_'.$i];
				    $q_id = $q->id;
				    $qs = QuestionsSources::addRecord($q->id,$lead->sourceId);
			    }
			}
		    if ( ! empty( $input['answer_'.$i] ) ) {
		       $qs_id = 0;
		       if(!empty($qs) && !empty($qs->id))	{
			       $qs_id = $qs->id;
		       }
		       $a = FormsAnswers::addAnswer( $input['answer_'.$i], $q_id, $qs_id );
			   if($a && $a->id){
			       LeadsData::create(['leadId' => $lead->id, 'answer_id' => $a->id]);
				   $lead->{'answer_'.$i} = $input['answer_'.$i];
			   }
		    }
	    }
	    return $lead;
    }

    protected function saveTags($input){
    	$res = array();
    	if(!empty($input['source'])){
    		$tag_id = Tags::getFieldId($input['source']);
		    if($tag_id){
		    	$res[$tag_id] = $tag_id;
			    $this->saveTag($tag_id, $input);
		    }
    	}
    	if(!empty($input['tags'])){
    		$tags = explode(',', $input['tags']);
    		if(!empty($tags) && is_array($tags) && count($tags)){
    			foreach($tags as $t){
    				$t = trim(substr($t,0, 99));
    				if(!empty($t)){
					    $tag_id = Tags::getFieldId($t);
					    if($tag_id){
						    $res[$tag_id] = $tag_id;
						    $this->saveTag($tag_id, $input);
					    }
				    }
			    }
		    }
	    }
	    return $res;
    }

	protected function saveTag($tag_id, $input){
    	if(!empty($input['email_id'])){
    		EmailTags::getFieldId($input['email_id'], $tag_id);
	    }
		if(!empty($input['phone_id'])){
			PhoneTags::getFieldId($input['phone_id'], $tag_id);
		}
		if(!empty($input['lead_id'])){
			LeadsTags::getFieldId($input['lead_id'], $tag_id);
		}
	}
}
