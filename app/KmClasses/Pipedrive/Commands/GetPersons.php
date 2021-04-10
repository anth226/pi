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
    private $owner_id, $start, $limit, $filterId;

    /**
     * @var
     */

    public function __construct($owner_id,$filterId =0, $start = 0, $limit = 500)
    {
        $this->owner_id = $owner_id;
        $this->start = $start;
        $this->limit = $limit;
        $this->filterId = $filterId;
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
    	if($this->filterId){
    		$options['filterId'] = $this->filterId;
	    }
        $data = $client->getInstance()->getPersons()->getAllPersons($options);
        return $data;
    }
}
