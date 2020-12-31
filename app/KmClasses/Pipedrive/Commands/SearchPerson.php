<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class SearchPerson implements CommandInterface
{
    /**
     * @var
     */
    private $email;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
        $data = $client->getInstance()->getPersons()->findPersonsByName([
          "term" => $this->email,
          "search_by_email" => 1
        ]);

        return $data;
    }


}
