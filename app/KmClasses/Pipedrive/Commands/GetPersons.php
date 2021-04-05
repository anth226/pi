<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class AddPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class GetPersons implements CommandInterface
{
    /**
     * @var
     */
    private $owner_id, $start, $limit;

    /**
     * @var
     */

    public function __construct($owner_id, $start = 0, $limit = 500)
    {
        $this->owner_id = $owner_id;
        $this->start = $start;
        $this->limit = $limit;
    }

    /**
     * @param Client $client
     */
    function execute(Client $client)
    {
    	$options = [
		    'userId'    => $this->owner_id,
		    'start'      => $this->start,
		    'limit'      => $this->limit
		];
        $data = $client->getInstance()->getPersons()->getAllPersons($options);
        return $data;
    }
}
