<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;
use Pipedrive\APIHelper;
use Pipedrive\Configuration;
use Pipedrive\Controllers\BaseController;
use Unirest\Request;
use Unirest\Request\Body;

/**
 * Class AddLead
 * @package App\KmClasses\Pipedrive\Commands
 */
class AddLead implements CommandInterface
{
    /**
     * @var
     */
    private $title;

    /**
     * @var
     */
    private $person_id;

    /**
     * AddLead constructor.
     * @param $title
     * @param $person_id
     */
    public function __construct($title, $person_id)
    {
        $this->title = $title;
        $this->person_id = $person_id;
    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
        $url = APIHelper::cleanUrl(Configuration::getBaseUri() . '/leads');

        $_headers = array (
            'user-agent'    => BaseController::USER_AGENT,
            'content-type'  => 'application/json; charset=utf-8',
            'Authorization' => sprintf('Bearer %1$s', Configuration::$oAuthToken->accessToken)
        );

        $_bodyJson = Body::Json([
            'title' => $this->title,
            'person_id' => $this->person_id,
            'label_ids' => ['8836b960-14c6-11eb-8164-2bde16b61d27']
        ]);

        $response = Request::post($url, $_headers, $_bodyJson);

        return $response->body;
    }
}
