<?php
namespace App\KmClasses\Curl;

use App\Exeptions;

class Shopify extends Api {

	public $config = array();

	public $page_size, $params, $count_params;

	public function __construct($config = array())
	{
		if(!empty($config)) {
			$this->config = $config;
			$this->page_size = !empty($config['PageSize']) ? $config['PageSize'] : 50;
			$this->params = !empty($config['Params']) ? $config['Params'] : false;
			$this->count_params = !empty($config['CountParams']) ? $config['CountParams'] : false;
		}
	}
	
	protected function createBaseUrl($is_graphql = false){
		if(!empty($this->config)){
			$config = $this->config;
			$ShopUrl = !empty($config['ShopUrl']) ? $config['ShopUrl'] : false;
			$ApiKey = !empty($config['ApiKey']) ? $config['ApiKey'] : false;
			$Password = !empty($config['Password']) ? $config['Password'] : false;
			$ApiVersion = !empty($config['ApiVersion']) ? $config['ApiVersion'] : '2020-01';
			$Token = !empty($config['Token']) ? $config['Token'] : false;

			if($is_graphql){
				if($ShopUrl && $Token){
					return 'https://'.$ShopUrl.'/admin/api/'.$ApiVersion.'/';
				}
			}
			else{
				if($ShopUrl && $ApiKey && $Password){
					return 'https://'.$ApiKey.':'.$Password.'@'.$ShopUrl.'/admin/api/'.$ApiVersion.'/';
				}
			}

			return false;
		}
		return false;
	}

	public function getCount($type){
		$baseUrl = $this->createBaseUrl();
		if($baseUrl){
			$url =  $baseUrl.$type.'/count.json';
			if($this->count_params){
				$url = $url.'?'.$this->count_params;
			}
			$res = $this->getResponse($url);
			if(!empty($res) && !empty($res['count'])){
				return $res['count'];
			}
			return false;
		}
		return false;
	}

	protected function getOnePage($type, $page_info = false){
		$baseUrl = $this->createBaseUrl();
		if($baseUrl){
			$url =  $baseUrl.$type.'.json?limit='.$this->page_size;
			if($this->params){
				$url = $baseUrl.$type.'.json?'.$this->params.'&limit='.$this->page_size;
			}
			if($page_info){
				$url = $baseUrl.$type.'.json?limit='.$this->page_size.'&page_info='.$page_info;
			}
			$res = $this->getPageWithCurl($url);
			if($res){
				$items = [];
				$next_url = '';
				if($res['body']) {
					$body =  $res['body'];
					if(!empty($body->{$type})) {
						foreach ( $body->{$type} as $p ) {
							$items[ $p->id ] = $p;
						}
					}
				}
				if(!empty($res['responses']) && !empty($res['responses']['link'])) {
					$result =  explode(',', $res['responses']['link']);
					if($result && count($result)){
						foreach($result as $k => $v){
							$r2 =  explode(';', $v);
							if($r2 && !empty($r2[1]) && trim($r2[1] ) == 'rel="next"' ) {
								$r = explode( 'page_info=', $r2[0] );
								if ( $r && $r[1] ) {
									$r = explode( ';', $r[1] );
									if ( $r && $r[0] ) {
										$next_url = trim( str_replace( '>', '', $r[0] ) );
									}
								}
							}
						}
					}
				}
				return [
					'items' => $items,
					'next_url' => $next_url
				];
			}
			return false;
		}
		return false;
	}

	public function getItem($type, $item_id){
		$baseUrl = $this->createBaseUrl();
		if($baseUrl){
			$url =  $baseUrl.$type.'/'.$item_id.'.json';
			if($this->params){
				$url .= '?'.$this->params;
			}
			$res = $this->getResponse($url, 0);
			return $res;
		}
		return false;
	}

	public function getOneItem($type, $item_id, $params = ''){
		$baseUrl = $this->createBaseUrl();
		if($baseUrl) {
			$url = $baseUrl . $type . '/'.$item_id.'.json';
			if($params){
				$url .= '?'.$params;
			}
		}
		if($this->params){
			$url .= '?'.$this->params;
		}
		if($url) {
			return $this->getResponse( $url, 0 );
		}
		return false;
	}

	public function getPart($type, $since_id = 0){
		$baseUrl = $this->createBaseUrl();
		if($baseUrl){
			$url =  $baseUrl.$type.'.json?limit='.$this->page_size;
			if($since_id){
				$url .= '&since_id='.$since_id;
			}
			if($this->params){
				$url .= '&'.$this->params;
			}

			$res = $this->getResponse($url, 0);

			if($res){
				$items = [];
				$last_id = 0;
				if(!empty($res->{$type})) {
					foreach ( $res->{$type} as $p ) {
						$items[ $p->id ] = $p;
						$last_id = $p->id;
					}
				}
				return [
					'items' => $items,
					'last_id' => $last_id
				];
			}
			return false;
		}
		return false;
	}

	public function getItems($type){
		$count = $this->getCount($type);
		if($count){
			$page_size = $this->page_size;
			$pages_count = ceil($count/$page_size);
			$items = [];
			$page_info = false;
			for($i = 1; $i <= $pages_count; $i++){
				$res = $this->getOnePage($type, $page_info);
				if($res){
					if(!empty($res['items'])){
						foreach ($res['items'] as $id => $p ) {
							$items[ $id ] = $p;
						}
					}
					if(!empty($res['next_url'])){
						$page_info = $res['next_url'];
					}
					else{
						$page_info = false;
						$i = $pages_count;
					}
				}
			}
			return $items;
		}
		return false;
	}

	public function getItemsByParts($type){
		$count = $this->getCount($type);
		if($count){
			$page_size = $this->page_size;
			$pages_count = ceil($count/$page_size);
			$items = [];
			$since_id = 0;
			for($i = 1; $i <= $pages_count; $i++){
				$res = $this->getPart($type, $since_id);
				if($res){
					if(!empty($res['items'])){
						foreach ($res['items'] as $id => $p ) {
							$items[ $id ] = $p;
						}
					}
					if(!empty($res['last_id'])){
						$since_id = $res['last_id'];
					}
				}
			}
			return $items;
		}
		return false;
	}

	public function processingUtnParams(Array $graphql_res){
		if(
			!empty($graphql_res) &&
			!empty($graphql_res['data']) &&
			!empty($graphql_res['data']['order']) &&
			!empty($graphql_res['data']['order']['customerJourney']) &&
			!empty($graphql_res['data']['order']['customerJourney']['moments'])
		){
			$count = count($graphql_res['data']['order']['customerJourney']['moments']);
			if($count){
				$utm_params = [];
				foreach($graphql_res['data']['order']['customerJourney']['moments'] as $utm){
					if(!empty($utm['utmParameters'])) {
						$utm_params = $utm['utmParameters'];
					}
				}
				return $utm_params;
			}
		}
		return [];
	}

	public function shopifyGraphQl($query) {

		$baseUrl = $this->createBaseUrl(true);
		if($baseUrl) {
			$url = $baseUrl."graphql.json";
		    $token = $this->config['Token'];
			$request_headers[] = "X-Shopify-Access-Token: " . $token;
			$request_headers[] = "Content-type: application/graphql";

			$curl = curl_init();

			// Set URL
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, true );
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt($curl, CURLOPT_USERAGENT, 'shopifyApp' );
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers );


//			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
//			curl_setopt( $curl, CURLOPT_MAXREDIRS, 3 );
//			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
			// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 3);
			// curl_setopt($curl, CURLOPT_SSLVERSION, 3);

//			curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 30 );
//			curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );

			// Send request to Shopify and capture any errors
			$response      = curl_exec( $curl );
			$error_number  = curl_errno( $curl );
			$error_message = curl_error( $curl );

			// Close cURL to be nice
			curl_close( $curl );

			// Return an error is cURL has a problem
			if ( $error_number ) {
				Exeptions::create( [ 'error'      => $error_message,
				                        'controller' => 'Shopify class',
				                        'function'   => 'shopifyGraphQl'
				] );
			} else {

				// No error, return Shopify's response by parsing out the body and the headers
				$response = preg_split( "/\r\n\r\n|\n\n|\r\r/", $response, 2 );

//
//				$headers           = array();
//				$header_data       = explode( "\n", $response[0] );
//				$headers['status'] = $header_data[0]; // Does not contain a key, have to explicitly set
//				array_shift( $header_data ); // Remove status, we've already set it above
//				foreach ( $header_data as $part ) {
//					$h                        = explode( ":", $part );
//					$headers[ trim( $h[0] ) ] = trim( $h[1] );
//				}

//				return array( 'headers' => $headers, 'response' => json_decode($response[1], 1) );
				return json_decode($response[1], 1);

			}
		}

	}
}