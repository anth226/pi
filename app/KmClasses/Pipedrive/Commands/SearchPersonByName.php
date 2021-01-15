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
    private $name, $search_by_email;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($name, $search_by_email = 0)
    {
        $this->name = $name;
        $this->search_by_email = $search_by_email;
    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
        $data = $client->getInstance()->getPersons()->findPersonsByName([
          "term" => $this->name,
          "search_by_email" => $this->search_by_email
        ]);

        return $data;
    }


}
