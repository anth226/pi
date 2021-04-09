<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class FindPerson implements CommandInterface
{
    /**
     * @var
     */
    private $text,$start, $limit;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($text, $start = 0, $limit = 500)
    {
        $this->text = $text;
	    $this->start = $start;
	    $this->limit = $limit;
    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
        $data = $client->getInstance()->getPersons()->findPersonsByName([
          "term" => $this->text,
          "search_by_email" => 0,
          'start'      => $this->start,
          'limit'      => $this->limit
        ]);
		return $data;
    }


}
