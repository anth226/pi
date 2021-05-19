<?php


namespace App\Helpers;


use App\Errors;
use Klaviyo\Klaviyo as Klaviyo;
use Klaviyo\Model\ProfileModel as KlaviyoProfile;

class KlaviyouHelper extends HttpHelper
{
    public function sendData($input, $list_id = ''){
        if(config( 'klaviyo.apiKey' ) && config( 'klaviyo.pubKey' ) && config( 'klaviyo.listId' )) {
            $klaviyo        = new Klaviyo( config( 'klaviyo.apiKey' ), config( 'klaviyo.pubKey' ) );
            $klaviyo_listId = config( 'klaviyo.listId' );
        }
        try {
            if($klaviyo && $klaviyo_listId) {
                if ( ! $list_id ) {
                    $list_id = $klaviyo_listId;
                }
                $klaviyo_data = [
                    '$email'        => $input['email'],
                    '$phone_number' => $input['phone'],
                    '$first_name'   => $input['first_name'],
                    '$last_name'    => $input['last_name'],
                ];
                $profile      = new KlaviyoProfile( $klaviyo_data );
                $res          = $klaviyo->lists->addMembersToList( $list_id, [ $profile ] );
                return [
                    'success' => false,
                    'message' => $res
                ];
            }
            $error = "No Klaviyo API Key found";
            log_error( $error, 'KlaviyouHelper', 'sendData');
            return [
                'success' => false,
                'message' => $error
            ];
        }
        catch (Exception $ex){
            $error = $ex->getMessage();
            log_error( $error, 'KlaviyouHelper', 'sendData');
            return [
                'success' => false,
                'message' => $error
            ];
        }
    }
}