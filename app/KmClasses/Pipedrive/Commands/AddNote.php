<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class AddNote implements CommandInterface
{
    /**
     * @var
     */
    private $deal_id, $content;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($deal_id, $content)
    {
        $this->deal_id = $deal_id;
        $this->content = $content;
    }


	function execute(Client $client)
	{
		$note_data = [
			"content" => $this->content,
			"deal_id" => $this->deal_id,
		];

		$data = $client->getInstance()->getNotes()->addANote($note_data);

		return $data;
	}


}
