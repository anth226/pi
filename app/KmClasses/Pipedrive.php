<?php

namespace App\KmClasses;

use App\Errors;
use App\KmClasses\Pipedrive\Client;
use App\KmClasses\Pipedrive\CommandInterface;
use App\Salespeople;
use App\User;
use Exception;

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

	public function findOwnersOnPipedrive(){
		try {
			$key = config( 'pipedrive.api_key' );
//			$key = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';

			$allUsers    = Pipedrive::executeCommand( $key, new Pipedrive\Commands\getAllUsers() );
			$salespeople = Salespeople::withTrashed()->get();
			if (
				! empty( $salespeople ) &&
				$salespeople->count() &&
				! empty( $allUsers ) &&
				! empty( $allUsers->data ) &&
				! empty( count($allUsers->data) )
			) {
				foreach ( $salespeople as $s ) {
					if ( ! empty( $s->email ) ) {
						foreach ( $allUsers->data as $u ) {
							if (
								!empty($u) &&
								!empty($u->id) &&
								!empty($u->email) &&
								trim( strtolower( $u->email ) ) == trim( strtolower( $s->email ) )
							) {
								Salespeople::where( 'id', $s->id )->update( [ 'pipedrive_user_id' => $u->id ] );
							}
						}
					}
				}
			}

			$users = User::withTrashed()->get();
			if (
				! empty( $users ) &&
				$users->count() &&
				! empty( $allUsers ) &&
				! empty( $allUsers->data ) &&
				! empty( count($allUsers->data) )
			) {
				foreach ( $users as $s ) {
					if ( ! empty( $s->email ) ) {
						foreach ( $allUsers->data as $u ) {
							if (
								!empty($u) &&
								!empty($u->id) &&
								!empty($u->email) &&
								trim( strtolower( $u->email ) ) == trim( strtolower( $s->email ) )
							) {
								User::where( 'id', $s->id )->update( [ 'pipedrive_user_id' => $u->id ] );
							}
						}
					}
				}
			}
			return true;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'Pipedrive calss',
				'function' => 'findOwnersOnPipedrive'
			]);
			return false;
		}
	}

	public function findOwnerOnPipedrive($user_id = 0, $salespeople_id = 0){
		try {
			$key = config( 'pipedrive.api_key' );
//			$key = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';
			if($user_id || $salespeople_id) {
				$allUsers    = Pipedrive::executeCommand( $key, new Pipedrive\Commands\getAllUsers() );

				if($salespeople_id) {
					$salespeople = Salespeople::where('id', $salespeople_id)->withTrashed()->first();
					if (
						! empty( $salespeople ) &&
						$salespeople->count() &&
						! empty( $allUsers ) &&
						! empty( $allUsers->data ) &&
						! empty( count( $allUsers->data ) ) &&
						! empty( $salespeople->email )
					) {
						foreach ( $allUsers->data as $u ) {
							if (
								! empty( $u ) &&
								! empty( $u->id ) &&
								! empty( $u->email ) &&
								trim( strtolower( $u->email ) ) == trim( strtolower( $salespeople->email ) )
							) {
								Salespeople::where( 'id', $salespeople->id )->update( [ 'pipedrive_user_id' => $u->id ] );
							}
						}
					}
				}

				if($user_id) {
					$user = User::where('id', $user_id)->withTrashed()->first();
					if (
						! empty( $user ) &&
						$user->count() &&
						! empty( $allUsers ) &&
						! empty( $allUsers->data ) &&
						! empty( count( $allUsers->data ) ) &&
						! empty( $user->email )
					) {
						foreach ( $allUsers->data as $u ) {
							if (
								! empty( $u ) &&
								! empty( $u->id ) &&
								! empty( $u->email ) &&
								trim( strtolower( $u->email ) ) == trim( strtolower( $user->email ) )
							) {
								User::where( 'id', $user->id )->update( [ 'pipedrive_user_id' => $u->id ] );
							}
						}
					}
				}
			}
			return true;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'Pipedrive calss',
				'function' => 'findOwnerOnPipedrive'
			]);
			return false;
		}
	}
}
