<?php

use App\Errors;

if (!function_exists('send_http_request')) {
    function send_http_request(string $url, $method = 'GET', $params = [])
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request($method, $url, $params);

        return [
            'status_code' => $res->getStatusCode(),
            'content_type' => $res->getHeader('content-type')[0],
            'body' => $res->getBody()
        ];
    }
}

if (!function_exists('log_error')) {
    function log_error(string $error, $controller, $function)
    {
        return Errors::create( [
            'error'      => $error,
            'controller' => $controller,
            'function'   => $function
        ] );
    }
}