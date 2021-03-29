<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class AddPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class GetPersonCustomField implements CommandInterface
{
    /**
     * @var
     */
    private $id;


    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
        $data = $client->getInstance()->getPersons()->getDetailsOfAPerson($this->id);

        return $data;
    }
}
