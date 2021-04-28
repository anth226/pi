<?php

namespace App\Http\Controllers;

use App\ActionsLog;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\InvoiceSupport;
use App\KmClasses\Sms\Elements;
use App\SupportTodo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Gate;

class SupportTasksController extends BaseController
{
	function __construct() {
		$this->middleware( [ 'auth' ] );
		$this->middleware( 'permission:support-tasks-create', ['only' => ['addSupportRep','addTask','deleteTask']] );
		$this->middleware( 'permission:support-tasks-create|support-user-view-all', ['only' => ['showInvoicesTasks', 'showAllTasks']] );
		$this->middleware('permission:support-user-view-own|support-user-view-all|support-tasks-create', ['only' => ['showTasks','completeTask']]);

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
				'support_rep_user_id' => 'required',
			] );

			$user_logged = Auth::user();

			$todo = [
				'invoice_id' => $request->input( 'invoice_id' ),
				'added_by_user_id' => $user_logged->id,
				'task_type' => $request->input( 'task_id' ),
				'support_rep_user_id' => $request->input( 'support_rep_user_id' )
			];

			if(!empty($request->input( 'scheduled_at' ))) {
				$todo['scheduled_at'] = Elements::createDateTime( $request->input( 'scheduled_at' ), 'm-d-Y H:i' );
			}

			SupportTodo::create($todo);

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

	function showTasks(Request $request, $user_id){
		try {
			$for_user_id = !empty($user_id) ? $user_id : 0;
			$status = !empty($request->input( 'task_status' )) ? $request->input( 'task_status' ) : 0;
			if($for_user_id) {
				$query = SupportTodo::with( 'invoice.customer' )
				                    ->with( 'addedByuser' )
				                    ->with( 'doneByuser' )
				                    ->with( 'invoice.salespeople.salespersone' )
				                    ->with( 'invoice.salespeople.level' )
				                    ->with( 'invoice.supportReps' )
				                    ->where( 'support_rep_user_id', $for_user_id )
				;
				$query->selectRaw('*, case when scheduled_at >= "'.Carbon::now().'" then 1 else 0 end as is_after');

				if($status && empty( $request['search']['value'])){
					if($status == 1 || $status == 2){
						$query->where('task_status', $status);
						if($status == 1){
							$query->where(function($q) use($status){
								$q->where('scheduled_at', '<', Carbon::now());
								$q->orWhereNull('scheduled_at');
							});
						}
					}
					else{
						if($status == 3){
							$query->where('scheduled_at', '>=', Carbon::now());
							$query->where('task_status', 1);
						}
					}
				}

				return datatables()->eloquent( $query )->toJson();
			}
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SupportTasksController',
				'function' => 'showTasks'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	function showInvoicesTasks(Request $request, $invoice_id){
		try {
			$for_invoice_id = !empty($invoice_id) ? $invoice_id : 0;
			$status = !empty($request->input( 'task_status' )) ? $request->input( 'task_status' ) : 0;
			if($for_invoice_id) {
				$query = SupportTodo::with( 'invoice.customer' )
				                    ->with( 'addedByuser' )
				                    ->with( 'doneByuser' )
				                    ->with( 'supportRep' )
				                    ->with( 'invoice.salespeople.salespersone' )
				                    ->with( 'invoice.salespeople.level' )
				                    ->with( 'invoice.supportReps' )
				                    ->where( 'invoice_id', $for_invoice_id )
				;
				$query->selectRaw('*, case when scheduled_at >= "'.Carbon::now().'" then 1 else 0 end as is_after');

				if($status && empty( $request['search']['value'])){
					if($status == 1 || $status == 2){
						$query->where('task_status', $status);
						if($status == 1){
							$query->where(function($q) use($status){
								$q->where('scheduled_at', '<', Carbon::now());
								$q->orWhereNull('scheduled_at');
							});
						}
					}
					else{
						if($status == 3){
							$query->where('scheduled_at', '>=', Carbon::now());
							$query->where('task_status', 1);
						}
					}
				}

				return datatables()->eloquent( $query )->toJson();
			}
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SupportTasksController',
				'function' => 'showInvoicesTasks'
			]);
			return $this->sendError($ex->getMessage());
		}
	}
	function showAllTasks(Request $request){
		try {
			$status = !empty($request->input( 'task_status' )) ? $request->input( 'task_status' ) : 0;

			$query = SupportTodo::with( 'invoice.customer' )
			                    ->with( 'addedByuser' )
			                    ->with( 'doneByuser' )
			                    ->with( 'supportRep' )
			                    ->with( 'invoice.salespeople.salespersone' )
			                    ->with( 'invoice.salespeople.level' )
			                    ->with( 'invoice.supportReps' )
			;
			$query->selectRaw('*, case when scheduled_at >= "'.Carbon::now().'" then 1 else 0 end as is_after');

			if($status && empty( $request['search']['value'])){
				if($status == 1 || $status == 2){
					$query->where('task_status', $status);
					if($status == 1){
						$query->where(function($q) use($status){
							$q->where('scheduled_at', '<', Carbon::now());
							$q->orWhereNull('scheduled_at');
						});
					}
				}
				else{
					if($status == 3){
						$query->where('scheduled_at', '>=', Carbon::now());
						$query->where('task_status', 1);
					}
				}
			}

			return datatables()->eloquent( $query )->toJson();

		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SupportTasksController',
				'function' => 'showAllTasks'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	function completeTask(Request $request){
		try {
			$this->validate( $request, [
				'todo_id' => 'required',
			] );

			$user_logged = Auth::user();

			$todo = SupportTodo::find($request->input( 'todo_id' ));

			if($todo && $todo->id) {
				if(Gate::check('support-tasks-create') || (Gate::check('support-user-view-own') && $todo->support_rep_user_id == $user_logged->id)) {

					$data_to_update = [
						'done_by_user_id' => $user_logged->id,
						'task_status'     => 2,
						'done_at'         => now()
					];

					SupportTodo::where( 'id', $request->input( 'todo_id' ) )->update( $data_to_update );

					ActionsLog::create( [
						'user_id'    => $user_logged->id,
						'model'      => 1,
						'field_name' => 'Tasks',
						'action'     => 6,
						'related_id' => $todo->invoice_id
					] );
					return $this->sendResponse('done');
				}

			}
			return $this->sendError('Error!');

		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SupportTasksController',
				'function' => 'completeTasks'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	function showTasks_v1(Request $request){
		try {
			$for_user_id = !empty($request->input( 'for_user_id' )) ? $request->input( 'for_user_id' ) : 0;
			$status = !empty($request->input( 'status' )) ? $request->input( 'stats' ) : 0;

			$query =  Invoices::with('customer')
			                  ->with('salespeople.salespersone')
			                  ->with('salespeople.level')
			                  ->with('supportReps')
			                  ->with('supportTodo')
			                  ->with('supportTodoActive')
			                  ->with('supportTodoCompleted')
			                  ->whereHas('supportReps', function ($query) {
				                  $query->where('user_id', 23);
			                  })
			                  ->where(function($q) use($status){
				                  $q->whereHas('supportTodo', function ($q2) use($status){
					                  $q2->whereNotNull('id');
					                  if($status) {
						                  $q2->where( 'task_status', $status );
					                  }
				                  });
				                  $q->orWhere('invoices.status', 2);
			                  })
			;
			if($for_user_id){
				$query->whereHas('supportReps', function ($query) {
					$query->where('user_id', 23);
				});
			}
			return datatables()->eloquent($query)->toJson();
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SupportTasksController',
				'function' => 'showTasks'
			]);
			return $this->sendError($ex->getMessage());
		}
	}
}
