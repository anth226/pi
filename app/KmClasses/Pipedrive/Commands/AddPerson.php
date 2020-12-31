<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class AddPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class AddPerson implements CommandInterface
{
    /**
     * @var
     */
    private $name;

    /**
     * @var
     */
    private $email;

    /**
     * @var
     */
    private $phone;

    /**
     * AddPerson constructor.
     * @param $name
     * @param $email
     * @param $phone
     */
    public function __construct($name, $email, $phone)
    {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
        $data = $client->getInstance()->getPersons()->addAPerson([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone
        ]);

        return $data;
    }
}
