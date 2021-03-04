<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class UpdateNote implements CommandInterface
{
    /**
     * @var
     */
    private $note_id, $content;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($note_id, $content)
    {
        $this->note_id = $note_id;
        $this->content = $content;
    }


	function execute(Client $client)
	{
		$note_data = [
			"content" => $this->content,
			"id" => $this->note_id,
		];

		$data = $client->getInstance()->getNotes()->updateANote($note_data);

		return $data;
	}


}
