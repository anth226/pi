<?php

namespace App\KmClasses\Pipedrive;

/**
 * Class Client
 * @package App\KmClasses\Pipedrive
 */
class Client
{
    /**
     * @var \Pipedrive\Client
     */
    private $client;

    /**
     * Client constructor.
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $client = new ClientPipedrive(null, null, null, $apiKey);

        $this->client = $client;
    }

    /**
     * @return \Pipedrive\Client
     */
    public function getInstance():ClientPipedrive
    {
        return $this->client;
    }

    /**
     * @param CommandInterface $command
     * @return mixed
     */
    public function execute(CommandInterface $command)
    {
        return $command->execute($this);
    }
}
