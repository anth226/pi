<?php

namespace App\KmClasses\Pipedrive\Commands;

use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;

/**
 * Class SearchPerson
 * @package App\KmClasses\Pipedrive\Commands
 */
class FindPersonNew implements CommandInterface
{
    /**
     * @var
     */
    private $term, $exact_match, $field, $include_fields, $organization_id, $start, $limit;

    /**
     * SearchPerson constructor.
     * @param $email
     */
    public function __construct($term, $exact_match = true, $field = 'email', $include_fields = '', $organization_id= '', $start = '', $limit = '')
    {
        $this->term = $term;
        $this->exact_match = $exact_match;
        $this->field = $field;
        $this->include_fields = $include_fields;
        $this->organization_id = $organization_id;
        $this->start = $start;
        $this->limit = $limit;
    }

    /**
     * @param Client $client
     * @return mixed|void
     */
    function execute(Client $client)
    {
    	$dataToSend = [
		    "term" => $this->term,
		    "field" => $this->field,
		    "exact_match" => $this->exact_match
	    ];
    	if($this->include_fields){
		    $dataToSend['include_fields'] = $this->include_fields;
	    }
	    if($this->organization_id){
		    $dataToSend['organization_id'] = $this->organization_id;
	    }
	    if($this->start){
		    $dataToSend['start'] = $this->start;
	    }
	    if($this->limit){
		    $dataToSend['limit'] = $this->limit;
	    }

        $data = $client->getInstance()->getPersons()->findPersons($dataToSend);

        return $data;
    }


}
