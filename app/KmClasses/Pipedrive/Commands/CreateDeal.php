<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class CreateDeal implements CommandInterface
{
    /**
     * @var
     */
    private $person_id, $owner_id, $value, $name;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($person_id, $owner_id, $value, $name)
    {
        $this->person_id = $person_id;
        $this->owner_id = $owner_id;
        $this->value = $value;
        $this->name = $name;
    }


	function execute(Client $client)
	{
		$deal_data = [
			"person_id" => $this->person_id,
			"value" => $this->value,
			"status" => 'won',
			"title" => $this->name  . ' deal'
		];

		if($this->owner_id){
			$deal_data['user_id'] = $this->owner_id;
		}

		$data = $client->getInstance()->getDeals()->addADeal($deal_data);

		return $data;
	}


}
