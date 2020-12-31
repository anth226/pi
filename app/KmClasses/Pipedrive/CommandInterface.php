<?php

namespace App\KmClasses\Pipedrive;

/**
 * Interface CommandInterface
 * @package App\KmClasses\Pipedrive
 */
interface CommandInterface
{
    /**
     * @param Client $client
     * @return mixed
     */
    function execute(Client $client);
}
