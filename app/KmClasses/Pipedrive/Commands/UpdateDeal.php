<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class UpdateDeal implements CommandInterface
{
    /**
     * @var
     */
    private $person_id, $value;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($person_id, $value)
    {
        $this->person_id = $person_id;
        $this->value = $value;
    }


	function execute(Client $client)
	{

		$data = $client->getInstance()->getDeals()->updateADeal([
			"id" => $this->person_id,
			"value" => $this->value,
			"status" => 'won'
		]);

		return $data;
	}


}
