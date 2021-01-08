<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class getAllUsers implements CommandInterface
{


    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct()
    {

    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
        $data = $client->getInstance()->getUsers()->getAllUsers();

        return $data;
    }


}
