<?php

namespace App\Http\Controllers;

use App\ActionsLog;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\InvoiceSupport;
use App\SupportTodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class SupportTasksController extends BaseController
{
	function __construct() {
		$this->middleware( [ 'auth' ] );
		$this->middleware( 'permission:invoice-create|invoice-edit|invoice-delete' );
	}

	function addSupportRep(Request $request){
		try {
			$this->validate( $request, [
				'invoice_id' => 'required',
				'support_rep_user_id' => 'required',
			] );

			$user_logged = Auth::user();

			$invoice_support_before = InvoiceSupport::with('user')->where('invoice_id', $request->input( 'invoice_id' ))->get();
			$before_string = '';
			if($invoice_support_before && count($invoice_support_before)) {
				$support_users = [];
				foreach($invoice_support_before as $ub){
					$support_users[] = $ub->user->name;
				}
				if(count($support_users)){
					$before_string = implode(', ', $support_users);
				}

				InvoiceSupport::where('invoice_id', $request->input( 'invoice_id' ))->delete();
			}

			if(is_array($request->input( 'support_rep_user_id' )) && count($request->input( 'support_rep_user_id' ))) {
				foreach ( $request->input( 'support_rep_user_id' ) as $uid ) {
					InvoiceSupport::create( [
						'invoice_id' => $request->input( 'invoice_id' ),
						'user_id'    => $uid
					] );
				}
			}
			else{
				InvoiceSupport::create( [
					'invoice_id' => $request->input( 'invoice_id' ),
					'user_id'    => $request->input( 'support_rep_user_id' )
				] );
			}

			$invoice_support_after = InvoiceSupport::with('user')->where('invoice_id', $request->input( 'invoice_id' ))->get();
			$after_string = '';
			if($invoice_support_after && count($invoice_support_after)) {
				$support_users = [];
				foreach($invoice_support_after as $ua){
					$support_users[] = $ua->user->name;
				}
				if(count($support_users)){
					$after_string = implode(', ', $support_users);
				}
			}

			if($before_string != $after_string) {
				ActionsLog::create( [
					'user_id'    => $user_logged->id,
					'model'      => 1,
					'field_name' => 'Support',
					'old_value'  => $before_string,
					'new_value'  => $after_string,
					'action'     => 1,
					'related_id' => $request->input( 'invoice_id' )
				] );
			}

			return $this->sendResponse('done');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SupportTasksController',
				'function' => 'addSupportRep'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	function addTask(Request $request){
		try {
			$this->validate( $request, [
				'invoice_id' => 'required',
				'task_id' => 'required',
			] );

			$user_logged = Auth::user();

			SupportTodo::create([
				'invoice_id' => $request->input( 'invoice_id' ),
				'added_by_user_id' => $user_logged->id,
				'task_type' => $request->input( 'task_id' )
			]);

			ActionsLog::create( [
				'user_id'    => $user_logged->id,
				'model'      => 1,
				'field_name' => 'Tasks',
				'new_value'  => SupportTodo::TASK_TYPE[$request->input( 'task_id' )],
				'action'     => 4,
				'related_id' => $request->input( 'invoice_id' )
			] );

			return $this->sendResponse('done');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SupportTasksController',
				'function' => 'addTask'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	function deleteTask(Request $request){
		try {
			$this->validate( $request, [
				'todo_id' => 'required',
			] );

			$user_logged = Auth::user();

			$todo = SupportTodo::find($request->input( 'todo_id' ));

			if($todo && $todo->task_status == 1) {

				SupportTodo::where('id', $request->input( 'todo_id' ))->delete();

				ActionsLog::create( [
					'user_id'    => $user_logged->id,
					'model'      => 1,
					'field_name' => 'Tasks',
					'new_value'  => SupportTodo::TASK_TYPE[ $todo->task_type ],
					'action'     => 5,
					'related_id' => $todo->invoice_id
				] );
			}

			return $this->sendResponse('done');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SupportTasksController',
				'function' => 'deleteTask'
			]);
			return $this->sendError($ex->getMessage());
		}
	}
}
