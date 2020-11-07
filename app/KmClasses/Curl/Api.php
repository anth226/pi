<?php

namespace App\KmClasses\Curl;

class Api{
	protected function getResponse($url, $array_type = 1){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$response = curl_exec($ch);
		curl_close($ch);
		usleep(1000000);
		return json_decode($response, $array_type);

	}

	protected function getPageWithCurl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$response = curl_exec($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = json_decode(substr($response, $header_size));

		$headers = [];
		$output = rtrim($header);
		$data = explode("\n",$output);
		$headers['status'] = $data[0];
		array_shift($data);

		foreach($data as $part){

			//some headers will contain ":" character (Location for example), and the part after ":" will be lost, Thanks to @Emanuele
			$middle = explode(":",$part,2);

			//Supress warning message if $middle[1] does not exist, Thanks to @crayons
			if ( !isset($middle[1]) ) { $middle[1] = null; }

			$headers[trim(strtolower($middle[0]))] = trim($middle[1]);
		}
		curl_close($ch);
		usleep(1000000);
		return [
			'body' => $body,
			'responses' => $headers
		];
	}

	protected function getPage($url){
		$options['http'] = array(
			'method' => "HEAD",
			'ignore_errors' => 1
		);
		$context = stream_context_create($options);
		$body = file_get_contents($url, NULL, $context);
		$responses = $this->parse_http_response_header($http_response_header);
		return [
			'body' => json_decode(file_get_contents($url), 1),
			'responses' => $responses
		];
	}


	/**
	 * parse_http_response_header
	 *
	 * @param array $headers as in $http_response_header
	 * @return array status and headers grouped by response, last first
	 */
	protected function parse_http_response_header(array $headers)
	{
		$responses = array();
		$buffer = NULL;
		foreach ($headers as $header)
		{
			if ('HTTP/' === substr($header, 0, 5))
			{
				// add buffer on top of all responses
				if ($buffer) array_unshift($responses, $buffer);
				$buffer = array();

				list($version, $code, $phrase) = explode(' ', $header, 3) + array('', FALSE, '');

				$buffer['status'] = array(
					'line' => $header,
					'version' => $version,
					'code' => (int) $code,
					'phrase' => $phrase
				);
				$fields = &$buffer['fields'];
				$fields = array();
				continue;
			}
			list($name, $value) = explode(': ', $header, 2) + array('', '');
			// header-names are case insensitive
			$name = strtoupper($name);
			// values of multiple fields with the same name are normalized into
			// a comma separated list (HTTP/1.0+1.1)
			if (isset($fields[$name]))
			{
				$value = $fields[$name].','.$value;
			}
			$fields[$name] = $value;
		}
		unset($fields); // remove reference
		array_unshift($responses, $buffer);

		return $responses;
	}
}