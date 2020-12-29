<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class SearchPersonByName implements CommandInterface
{
    /**
     * @var
     */
    private $name;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
        $data = $client->getInstance()->getPersons()->findPersonsByName([
          "term" => $this->name,
          "search_by_email" => 0
        ]);

        return $data;
    }


}
