<?php

namespace App\Http\Controllers;

use App\PhoneNumbers;
use Illuminate\Http\Request;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\SmsSender;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Intervention\Image\Facades\Image;

class CallsController extends Controller
{
	public $support_number_id;
	private $img_folder;

	public function __construct(Request $request)
	{
		$this->middleware(['auth']);
		$this->middleware('permission:view-support');
		$this->support_number_id = $request->input('support_id');
		$this->img_folder = 'sms_support_images';
	}

	public function index($user_id)
	{
		$phone_number = PhoneNumbers::first();
		if(!$phone_number || !$phone_number->count()) {
			$phone_number = '';
		}
		return view( 'calls.show', [ 'phone_number' => $phone_number ] );
	}

	public function selectNumber()
	{
		$support_num = Elements::selectSupportNumber('support_num', 'support_num');
		return view('supportsel', ['support_num' => $support_num]);
	}

	public function sendMessage(Request $request){
		$user = Auth::user();
		if($user) {
			$input = $request->all();
			if(
				!empty($input) &&
				!empty( $input['contact_id'] ) &&
				(!empty( $input['text']) || !empty( $input['media']))
			){

				$input_text = !empty($input['text']) ? $input['text'] : '';
				$dataToSave = [
					'contact_id' => $input['contact_id'],
					'message' => $input_text,
					'sent_by_admin' => 1,
					'is_read' => 1
				];

				$message = SupportMessages::create($dataToSave);

				if($message && !empty($message->id)) {
					$sms_media = '';
					if(!empty( $input['media'])) {
						$sms_media = $input['media'];
						MMSMedia::create(
							[
								'messageId' => $message->id,
								'mediaUrl' => $sms_media,
								'MIMEType' => !empty( $input['filetype']) ? $input['filetype'] : 'image/jpeg'
							]
						);
					}

					$contact = SupportContacts::select('support_contacts.contact_number', 'support_numbers.support_number')
					                          ->where('support_contacts.id', $input['contact_id'])
					                          ->leftJoin( 'support_numbers', function ( $join ) {
						                          $join->on( 'support_contacts.support_number_id','support_numbers.id' );
					                          } )
					                          ->first()
					;
					if(!empty($contact) && !empty($contact->contact_number) && !empty($contact->support_number)){
						$sender = new SmsSender();
						$send_res = $sender->sendSupportSMS($contact->contact_number, $contact->support_number, $input_text, $message->id, $sms_media);
						$update_data = [
							'support_messages.is_read' => true,
							'support_contacts.status' => 1
						];
						if(!empty($send_res) && !empty($send_res['error_code'])){
							$update_data['support_messages.sent_by_admin'] = 4;
						}
						SupportMessages::join( 'support_contacts', function ( $join ) use ( $input ) {
							$join->on( 'support_contacts.id', 'support_messages.contact_id' )
							     ->where('support_contacts.id', $input['contact_id'])
							;
						} )
						               ->update($update_data)
						;
					}
				}
				return response()->json($message->load('fromMMS'));
			}
			return response()->json(['success' => false, 'message' => 'Message error',], 404);
		}
		return response()->json(['success' => false, 'message' => 'Auth Error',], 404);
	}


	public function get()
	{
		$contacts = [];
		$support_id = $this->support_number_id;
		if(!empty($support_id)) {
			$contacts = SupportContacts::select('support_contacts.*', 'uleads.id as phone_id', 'fn.name as first_name', 'ln.name as last_name', 'full_names.name as full_name')
			                           ->leftJoin( 'uleads', function ( $join ) {
				                           $join->on( 'support_contacts.contact_number', 'uleads.formated_phone_number' );
			                           } )
			                           ->leftJoin( 'first_last_names as fn', function ( $join ) {
				                           $join->on( 'fn.id', 'uleads.first_name_id' );
			                           } )
			                           ->leftJoin( 'first_last_names as ln', function ( $join ) {
				                           $join->on( 'ln.id', 'uleads.last_name_id' );
			                           } )
			                           ->leftJoin( 'full_names', function ( $join ) {
				                           $join->on( 'full_names.id', 'uleads.full_name_id' );
			                           } )
			                           ->where('support_contacts.support_number_id', $support_id)
			                           ->where('support_contacts.status', 1)
			                           ->orderBy('support_contacts.updated_at', 'desc')
			                           ->get()
			;
			$unreadIds = SupportMessages::select(\DB::raw('`contact_number`, count(`contact_number`) as messages_count'))
			                            ->join( 'support_contacts', function ( $join ) use ($support_id, $contacts) {
				                            $join->on( 'support_contacts.id', 'support_messages.contact_id' )
				                                 ->where('support_contacts.support_number_id', $support_id)
				                            ;
			                            } )
			                            ->where('support_messages.is_read', false)
			                            ->groupBy('support_contacts.contact_number')
			                            ->get()
			;

			$contacts = $contacts->map(function($contact) use ($unreadIds) {
				if(empty($contact->full_name)){
					$first_name = !empty($contact->first_name) ? $contact->first_name : '';
					$last_name = !empty($contact->last_name) ? $contact->last_name : '';
					$contact->full_name = $first_name .' '.$last_name;
				}
				$contactUnread = $unreadIds->where('contact_number', $contact->contact_number)->first();

				$contact->unread = $contactUnread ? $contactUnread->messages_count : 0;

				return $contact;
			});
		}

		return response()->json($contacts);
	}

	public function getMessagesFor($id)
	{
		SupportMessages::join( 'support_contacts', function ( $join ) use ( $id ) {
			$join->on( 'support_contacts.id', 'support_messages.contact_id' )
			     ->where('support_contacts.id', $id)
			;
		} )
		               ->update(['support_messages.is_read' => true])
		;

		$messages = SupportMessages::where('support_messages.contact_id', $id)->get()->load('fromMMS');

		$emails = SupportContacts::join( 'leads', function ( $join ){
			$join->on( 'leads.formated_phone_number', 'support_contacts.contact_number' );
		} )
		                         ->join( 'u_emails', function ( $join ){
			                         $join->on( 'u_emails.id', 'leads.email_id' );
		                         } )
		                         ->where('support_contacts.id', $id)
		                         ->groupBy('u_emails.id')
		                         ->pluck('u_emails.email')
		;
		$project_url = SupportContacts::select('projects.shopify_url')
		                              ->join( 'support_numbers', function ( $join ){
			                              $join->on( 'support_numbers.id', 'support_contacts.support_number_id' );
		                              } )
		                              ->join( 'projects', function ( $join ){
			                              $join->on( 'projects.id', 'support_numbers.project_id' );
		                              } )
		                              ->where('support_contacts.id', $id)
		                              ->first()
		;
		$response = [
			'messages' => $messages,
			'emails'   => $emails,
			'project_url' => (!empty($project_url) && !empty($project_url->shopify_url)) ? $project_url->shopify_url : ''
		];

		return response()->json($response);
	}

	public function archiveContact($id)
	{
		$contact = SupportContacts::where('id', $id)->update(['status' => 0]);
		return response()->json($contact);
	}

	public function createConversation(Request $request){
		$user = Auth::user();
		if($user && $this->support_number_id) {
			$input = $request->all();
			if(
				!empty($input) &&
				!empty( $input['phone_number'])
			){
				$contact_number = FormatUsPhoneNumber::formatPhoneNumber($input['phone_number']);
				if($contact_number) {
					$support_id = $this->support_number_id;
					$contact = SupportContacts::where( 'support_contacts.support_number_id', $support_id )
					                          ->where( 'support_contacts.contact_number', $contact_number )
					                          ->first();
					if (empty($contact) || !$contact->count()) {
						$contact = SupportContacts::create( [
							'support_number_id' => $support_id,
							'contact_number' => $contact_number,
							'status' => 1
						] );
					}
					if(!empty($contact) && !empty($contact->id)){
						$contact->unread = 0;
						$contact_info = Uleads::select('uleads.id as phone_id', 'fn.name as first_name', 'ln.name as last_name', 'full_names.name as full_name')
						                      ->leftJoin( 'first_last_names as fn', function ( $join ) {
							                      $join->on( 'fn.id', 'uleads.first_name_id' );
						                      } )
						                      ->leftJoin( 'first_last_names as ln', function ( $join ) {
							                      $join->on( 'ln.id', 'uleads.last_name_id' );
						                      } )
						                      ->leftJoin( 'full_names', function ( $join ) {
							                      $join->on( 'full_names.id', 'uleads.full_name_id' );
						                      } )
						                      ->where('uleads.formated_phone_number', $contact_number)
						                      ->first()
						;
						if($contact_info){
							if(empty($contact_info->full_name)){
								$first_name = !empty($contact_info->first_name) ? $contact_info->first_name : '';
								$last_name = !empty($contact_info->last_name) ? $contact_info->last_name : '';
								$contact->full_name = $first_name .' '.$last_name;
							}
							else{
								$contact->full_name = $contact_info->full_name;
							}
							if(!empty($contact_info->phone_id)){
								$contact->phone_id = $contact_info->phone_id;
							}
						}
						return response()->json($contact);
					}
					return response()->json(['success' => false, 'message' => 'Contact create error',], 404);
				}
				return response()->json(['success' => false, 'message' => 'Phone Number Error',], 404);
			}
			return response()->json(['success' => false, 'message' => 'Empty fields',], 404);
		}
		return response()->json(['success' => false, 'message' => 'Auth Error',], 404);
	}

	public function searchContact(Request $request){
		$user = Auth::user();
		if($user && $this->support_number_id) {
			$input = $request->all();
			if(
				!empty($input) &&
				!empty( $input['search_number'])
			){
				$contact_number = FormatUsPhoneNumber::formatPhoneNumber($input['search_number']);
				if($contact_number) {
					$support_id = $this->support_number_id;
					$contact = SupportContacts::where( 'support_contacts.support_number_id', $support_id )
					                          ->where( 'support_contacts.contact_number', $contact_number )
					                          ->first();

					if(!empty($contact) && !empty($contact->id)){
						$contact->unread = 0;
						$contact_info = Uleads::select('uleads.id as phone_id', 'fn.name as first_name', 'ln.name as last_name', 'full_names.name as full_name')
						                      ->leftJoin( 'first_last_names as fn', function ( $join ) {
							                      $join->on( 'fn.id', 'uleads.first_name_id' );
						                      } )
						                      ->leftJoin( 'first_last_names as ln', function ( $join ) {
							                      $join->on( 'ln.id', 'uleads.last_name_id' );
						                      } )
						                      ->leftJoin( 'full_names', function ( $join ) {
							                      $join->on( 'full_names.id', 'uleads.full_name_id' );
						                      } )
						                      ->where('uleads.formated_phone_number', $contact_number)
						                      ->first()
						;
						if($contact_info){
							if(empty($contact_info->full_name)){
								$first_name = !empty($contact_info->first_name) ? $contact_info->first_name : '';
								$last_name = !empty($contact_info->last_name) ? $contact_info->last_name : '';
								$contact->full_name = $first_name .' '.$last_name;
							}
							else{
								$contact->full_name = $contact_info->full_name;
							}
							if(!empty($contact_info->phone_id)){
								$contact->phone_id = $contact_info->phone_id;
							}
						}
						return response()->json($contact);
					}
					return response()->json(['success' => false, 'message' => 'No Contact',], 404);
				}
				return response()->json(['success' => false, 'message' => 'Phone Number Error',], 404);
			}
			return response()->json(['success' => false, 'message' => 'Empty fields',], 404);
		}
		return response()->json(['success' => false, 'message' => 'Auth Error',], 404);
	}


	public function storeImg(Request $request)
	{
		$img_folder = $this->img_folder;
		$photo = $request->file('file');



		if($photo) {
			$img_path = public_path($img_folder);
			if (!is_dir($img_path)) {
				mkdir($img_path, 0777);
				$file = fopen($img_path.'/index.html', 'w');
				if($file) {
					fclose( $file );
				}
			}

			$original_name = str_replace( '.' . $photo->getClientOriginalExtension(), '', basename( $photo->getClientOriginalName() ) );
			$resize_name   = str_replace( [ ' ', '.', ',', ';', ':', '\'', '"', '`' ], '_', $original_name );
			$resize_name   = substr( $resize_name, 0, 10 );
			$resize_name   = urlencode( $resize_name . '_' . date( 'YmdHis' ) . '.' . $photo->getClientOriginalExtension() );

			Image::make( $photo )
			     ->resize( 800, null, function ( $constraints ) {
				     $constraints->aspectRatio();
			     } )
			     ->save( $img_path . '/' . $resize_name );

			return Response::json( [
				'success' => true,
				'url' => url($img_folder . '/' . $resize_name),
				'filename' => $resize_name
			], 200 );
		}
		return Response::json( [
			'success' => false,
			'url' => '',
			'filename' => ''
		], 200 );
	}

	public function destroyImg(Request $request)
	{
		$img_folder = $this->img_folder;
		$img_path = public_path($img_folder);
		$filename = $request->filename;

		if (!is_dir($img_path)) {
			return Response::json(['message' => 'Sorry folder does not exist'], 400);
		}
		if (empty($filename)) {
			return Response::json(['message' => 'Sorry file does not exist'], 400);
		}

		$file_path = $img_path . '/' . $filename;

		if (file_exists($file_path)) {
			unlink($file_path);
		}

		return Response::json(['message' => 'File successfully delete'], 200);
	}
}
