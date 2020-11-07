<?php

namespace App\Http\Controllers;

use App\EmailCampaigns;
use App\EmailCampaignsStat;
use App\EmailFromAddresses;
use App\EmailTags;
use App\ProjectsEmails;
use App\Tags;
use Illuminate\Http\Request;
use Validator,Redirect,Response;
use App\KmClasses\Sms\Elements;
use App\KmClasses\MailEclipse\mailEclipse;




class EmailCampaignsController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */

	protected $templates;

	public function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:view-email-campaigns-page|view-admin-pages');
		$this->middleware('permission:create-email-campaign', ['only' => ['store']]);
		$this->middleware('permission:delete-email-campaign', ['only' => ['delete']]);
		$this->middleware('permission:send-email-campaign', ['only' => ['send']]);
		$this->middleware('permission:cancel-email-campaign', ['only' => ['cancel']]);
		$this->templates = $templates = mailEclipse::getTemplates();
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */


	public function index()
	{
		$campaigns = $this->getCampaigns();
		foreach($campaigns as $k => $cam){
			$seg = json_decode($cam->segment_info);
			$cam->c_total = 0;
			$this->addAditionalData($cam, $seg);
			$this->getCampaignData($cam);
		}
		return view('emailcampaigns',
			[
				'campaigns' => $campaigns,
				'sources_select' => Elements::tagsSelect('sources_included','sources se2_sel'),
				'templates_select' => Elements::templatesSelect('email_templates','se2_sel'),
				'fromaddress_select' => Elements::fromAddressSelect('fromaddress_templates','sources se2_sel')
			]);
	}


	/**
	 *  Add new Campaign
	 */
	public function store(Request $request){
		$message = 'Something went wrong! can\'t save to DB' ;
		$success = false;

		$data = $request->all();
		Validator::make($data, [
			'email_subject' => 'required',
			'sources_included' => 'required',
			'fromaddress_templates' => 'required',
			'email_templates' => 'required',
		])->validate();

		$scheduled_at = date( "Y-m-d H:i:s" );
		if ( ! empty( $data['scheduled_at'] ) ) {
			$scheduled_at = $data['scheduled_at'];
		}

		$plus_new = 0;
		$response_type = 0;
		if ( ! empty( $data['enableFilters'] ) ) {
			if ( ! empty( $data['plusNew'] ) ) {
				$plus_new = $data['plusNew'];
			}
			if ( ! empty( $data['responseType'] ) ) {
				$response_type = $data['responseType'];
			}
		}

		$last_days = 0;
		if ( ! empty( $data['last_days'] ) && $response_type) {
			$last_days = $data['last_days'];
		}

		$data_to_save = [
			'subject'           => $data['email_subject'],
			'from_address_id'   => $data['fromaddress_templates'],
			'template'          => $data['email_templates'],
			'scheduled_at'      => $scheduled_at
		];

		$leads_count = EmailTags::calculateTagsLeads( $data['sources_included'], false, $data['fromaddress_templates'], 0, 0, 0, $plus_new, $response_type, $last_days );

		$segment_info = array(
			'tags_included' => $data['sources_included'],
			'added_leads_count' => $leads_count,
			'response_type' => $response_type,
			'last_days'     => $last_days,
			'plus_new' => $plus_new
		);
		$data_to_save['segment_info'] = json_encode($segment_info);


		if($leads_count){
			$campaign = EmailCampaigns::create( $data_to_save );
			if ( $campaign->id ) {
				$message = 'Campaign successfully added';
				session( [ 'status_camp' => $message ] );
				$success = true;
			}
		}
		else{
			$message = 'No leads';
			session( [ 'status_camp' => $message ] );
			$success = false;
		}
		$arr = array('msg' => $message, 'status' => $success);

		return Response()->json($arr);
	}


	/**
	 *  Starting Campaign
	 */
	public function send(Request $request){
		$message = 'Something went wrong! can\'t save to DB' ;
		$success = false;

		$data = $request->all();
		Validator::make($data, [
			'campaign_id' => 'required|numeric',
		])->validate();

		$res = EmailCampaigns::where('id',$data['campaign_id'])->first();

		if($res) {
			if(!empty($res->status) && $res->status != 4){
				$message = 'Campaign is ready to send or sending, can\'t start sending' ;
				$success = false;
			}
			else {
				$r = EmailCampaigns::findOrFail($data['campaign_id'])->update(['status'=>4]);
				if($r) {
					chmod(View('vendor.maileclipse.templates.'.$res['template'])->getPath(),0444);
					$message = 'Campaign added to sending list';
					session( [ 'status_send_camp' => $message ] );
					$success = true;
				}
			}
		}


		$arr = array('msg' => $message, 'status' => $success);

		return Response()->json($arr);
	}

	/**
	 *  Delete Campaign
	 */
	public function delete(Request $request){
		$message = 'Something went wrong! can\'t delete' ;
		$success = false;

		$data = $request->all();
		Validator::make($data, [
			'del_campaign_id' => 'required|numeric',
		])->validate();

		$res = EmailCampaigns::where('id',$data['del_campaign_id'])->first();
		if($res) {
			if(!empty($res->last_leadId)){
				$message = 'Campaign is partially or completely sent, can\'t delete' ;
				$success = false;
			}
			else {
				if(!empty($res->status)){
					$message = 'Campaign is ready to send, scheduled or sending, can\'t delete' ;
					$success = false;
				}
				else {
					$result = EmailCampaigns::findOrFail( $data['del_campaign_id'] )->delete();
					if ( $result ) {
						$message = 'Campaign Deleted';
						session( [ 'status_send_camp' => $message ] );
						$success = true;
					}
				}
			}
		}


		$arr = array('msg' => $message, 'status' => $success);

		return Response()->json($arr);
	}

	/**
	 *  Cancel Campaign
	 */
	public function cancel(Request $request){
		$message = 'Something went wrong! can\'t delete' ;
		$success = false;

		$data = $request->all();
		Validator::make($data, [
			'cancel_campaign_id' => 'required|numeric',
		])->validate();

		$res = EmailCampaigns::where('id',$data['cancel_campaign_id'])->first();

		if($res) {
			if(!empty($res->last_leadId)){
				$message = 'Campaign is partially or completely sent, can\'t cancel' ;
				$success = false;
			}
			else {
				if ( $res->status && ( $res->status == 1 || $res->status == 2 ) ) {
					$message = 'Campaign is already sent or in a sending process, can\'t cancel';
					$success = false;
				} else {
					$r = EmailCampaigns::findOrFail( $data['cancel_campaign_id'] )->update( [ 'status' => 0 ] );
					if ( $r ) {
						$message = 'Campaign canceled';
						session( [ 'status_send_camp' => $message ] );
						$success = true;
					}
				}
			}
		}


		$arr = array('msg' => $message, 'status' => $success);

		return Response()->json($arr);
	}

	protected function getCampaigns(){
		if(auth()->user()->can('use-pagination')) {
			return EmailCampaigns::sortable( [ 'scheduled_at' => 'desc', 'status' => 'desc', 'id' => 'desc' ] )
			                     ->paginate( 10 );
		}
		else{
			return EmailCampaigns::sortable( [ 'scheduled_at' => 'desc', 'status' => 'desc', 'id' => 'desc' ] )->take( 10 )->get();
		}
	}

	protected function addAditionalData(&$cam, $seg){
		$cam->from_address = '';
		$cam->from_name = '';
		$cam->template_name = '';
		$cam->tags = '';
		$cam->added_leads_count = !empty($seg->added_leads_count) ? $seg->added_leads_count : 0;
		$tags = '';
		if ( ! empty( $seg->tags_included ) ) {
			$tags .= '<div><span class="border-bottom font-weight-bold text-nowrap">Tags : </span><div class="mt-2">' . implode( '<br>', Tags::getTagsById( $seg->tags_included ) ) . '</div></div>';
		}
		if(!empty( $seg->plus_new) || ! empty( $seg->response_type )) {
			$tags .= '<div class="card bg-primary text-white p-1"><span class="font-weight-bold text-nowrap">Filter applied : </span>';
			if ( ! empty( $seg->response_type ) ) {
				$tags .= '<div>' . $seg->response_type;
				if(!empty($seg->last_days)){
					$day_string =  'day';
					if($seg->last_days > 1){
						$day_string =  'days';
					}
					$tags .= ' (last '.$seg->last_days.' '.$day_string.')';
				}
				if ( ! empty( $seg->plus_new ) ) {
					$tags .= ' + New Leads';
				}
				$tags .= '</div>';
			}
			else{
				if ( ! empty( $seg->plus_new ) ) {
					$tags .= '<div>New Leads Only</div>';
				}
			}


			$tags .= '</div>';
		}


		$cam->tags = $tags;

		if($cam->template){
			$template = mailEclipse::getTemplates()
			                ->where('template_slug', $cam->template)->first();
			if(!empty($template)) {
				$cam->template_name = $template->template_name;
			}
		}

		if($cam->from_address_id){
			$address = ProjectsEmails::where('id',$cam->from_address_id)->first();
			if($address) {
				if ( $address->email_address ) {
					$cam->from_address = $address->email_address;
				}
				if ( $address->email_name ) {
					$cam->from_name = $address->email_name;
				}
			}
		}
	}

	protected function getCampaignData(&$cam){
		$res = EmailCampaignsStat::getCampaignReport($cam->id)->first();
		if($res && $res->count()){
			$cam->c_sent = $res->sent;
			$cam->c_delivered = $res->delivered;
			$cam->c_bounces_permanent = $res->bounces_permanent;
			$cam->c_bounces_transient = $res->bounces_transient;
			$cam->c_complaints = $res->complaints;
			$cam->c_opened = $res->opened;
			$cam->c_clicked = $res->clicked;
			$cam->c_purchased = $res->purchased;
			$cam->c_errors = $res->errors;
			$cam->c_unsubscribed = $res->unsubscribes;
		}
	}

}
