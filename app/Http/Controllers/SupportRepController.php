<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SupportRepController extends Controller
{
	function __construct()
	{
		$this->middleware(['auth']);
		$this->middleware('permission:support-user-view-own|support-user-view-all', ['only' => ['show']]);
		$this->middleware('permission:support-user-view-all',['only' => ['index']]);
	}

	public function show($id){
		$user = User::find($id);
		$current_user = Auth::user();
		if($user && ($current_user->id == $id || Gate::check('support-user-view-all'))) {
			return view( 'support.show', compact( 'user' ) );
		}
		return abort(404);
	}

	public function index(Request $request)
	{
		$data = User::whereHas("roles", function($q){ $q->where("name", "Support Rep"); })->orderBy('id','DESC')->paginate(100);
		return view('support.index',compact('data'))
			->with('i', ($request->input('page', 1) - 1) * 100);
	}
}
