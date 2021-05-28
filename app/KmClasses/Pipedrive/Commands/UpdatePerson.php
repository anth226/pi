<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class UpdatePerson implements CommandInterface
{
    /**
     * @var
     */
    private $person_id, $custom_fields;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($person_id, array $custom_fields)
    {
        $this->person_id = $person_id;
        $this->custom_fields = $custom_fields;
    }


	function execute(Client $client)
	{
		$data = $client->getInstance()->getPersons()->updateAPersonCustomFields([
			"id" => $this->person_id,
			"custom_fields" => $this->custom_fields
		]);

		return $data;
	}


}
