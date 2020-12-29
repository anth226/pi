<?php
/**
 * Created by PhpStorm.
 * User: K
 * Date: 12/28/2020
 * Time: 12:18 PM
 */

namespace App\KmClasses\Pipedrive;




class ClientPipedrive extends \Pipedrive\Client {

	public function getPersons()
	{
		return PersonsController::getInstance();
	}


}