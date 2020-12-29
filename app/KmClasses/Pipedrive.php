<?php

namespace App\KmClasses;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class Pipedrive
 * @package App\KmClasses
 */
class Pipedrive
{
    /**
     * @param $apiKey
     * @param CommandInterface $command
     * @return mixed
     */
    public static function executeCommand($apiKey, CommandInterface $command)
    {
        $client = new Client($apiKey);

        return $client->execute($command);
    }
}
