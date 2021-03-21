<?php


namespace App\Http\Controllers;


use App\ActionsLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Illuminate\Support\Facades\Auth;


class RoleController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','show']]);
		$this->middleware('permission:role-create', ['only' => ['create','store']]);
		$this->middleware('permission:role-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:role-delete', ['only' => ['destroy']]);
	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$roles = Role::orderBy('id','DESC')->paginate(100);
		return view('roles.index',compact('roles'))
			->with('i', ($request->input('page', 1) - 1) * 100);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		$permission = Permission::get();
		return view('roles.create',compact('permission'));
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$this->validate($request, [
			'name' => 'required|unique:roles,name',
			'permission' => 'required',
		]);


		$role = Role::create(['name' => $request->input('name')]);
		$role->syncPermissions($request->input('permission'));

		$user_logged = Auth::user();
		ActionsLog::create([
			'user_id' => $user_logged->id,
			'model' => 7,
			'action' => 0,
			'related_id' => $role->id
		]);


		return redirect()->route('roles.index')
		                 ->with('success','Role created successfully');
	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$role = Role::find($id);
		if($role) {
			$rolePermissions = Permission::join( "role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id" )
			                             ->where( "role_has_permissions.role_id", $id )
			                             ->get();


			return view( 'roles.show', compact( 'role', 'rolePermissions' ) );
		}
		return abort(404);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$role = Role::find($id);
		if($role) {
			$permission      = Permission::get();
			$rolePermissions = DB::table( "role_has_permissions" )->where( "role_has_permissions.role_id", $id )
			                     ->pluck( 'role_has_permissions.permission_id', 'role_has_permissions.permission_id' )
			                     ->all();


			return view( 'roles.edit', compact( 'role', 'permission', 'rolePermissions' ) );
		}
		return abort(404);
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		$this->validate($request, [
			'name' => 'required',
			'permission' => 'required',
		]);


		$role = Role::find($id);

		$old_value = Role::where('id', $id)->value('name');

		$role->name = $request->input('name');
		$role->save();

		$new_value = Role::where('id', $id)->value('name');

		$user_logged = Auth::user();

		if($old_value != $new_value){
			ActionsLog::create( [
				'user_id'    => $user_logged->id,
				'model'      => 7,
				'field_name' => 'name',
				'old_value' => $old_value,
				'new_value' => $new_value,
				'action'     => 1,
				'related_id' => $id
			] );
		}

		$rolePermissions_old = Permission::join( "role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id" )
		                             ->where( "role_has_permissions.role_id", $id )
		                             ->pluck('permissions.name');
		$old_value = !empty($rolePermissions_old) ? implode(', ', $rolePermissions_old->toArray()) : '';



		$role->syncPermissions($request->input('permission'));


		$rolePermissions_new = Permission::join( "role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id" )
		                                 ->where( "role_has_permissions.role_id", $id )
		                                 ->pluck('permissions.name');
		$new_value = !empty($rolePermissions_new) ? implode(', ', $rolePermissions_new->toArray()) : '';

		if($old_value != $new_value) {
			ActionsLog::create( [
				'user_id'    => $user_logged->id,
				'model'      => 7,
				'field_name' => 'permissions',
				'old_value'  => $old_value,
				'new_value'  => $new_value,
				'action'     => 1,
				'related_id' => $id
			] );
		}


		return redirect()->route('roles.index')
		                 ->with('success','Role updated successfully');
	}
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		$rolePermissions_old = Permission::join( "role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id" )
		                                 ->where( "role_has_permissions.role_id", $id )
		                                 ->pluck('permissions.name');
		$old_value = !empty($rolePermissions_old) ? implode(', ', $rolePermissions_old->toArray()) : '';
		DB::table("roles")->where('id',$id)->delete();

		$user_logged = Auth::user();
		ActionsLog::create([
			'user_id' => $user_logged->id,
			'model' => 7,
			'action' => 2,
			'old_value' => $old_value,
			'related_id' => $id
		]);

		return redirect()->route('roles.index')
		                 ->with('success','Role deleted successfully');
	}
}
