<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class SearchDeal implements CommandInterface
{
    /**
     * @var
     */
    private $person_id;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($person_id)
    {
        $this->person_id = $person_id;
    }


	function execute(Client $client)
	{

		$data = $client->getInstance()->getPersons()->listDealsAssociatedWithAPerson([
			"id" => $this->person_id
		]);

		return $data;
	}


}
