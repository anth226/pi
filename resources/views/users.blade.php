@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Users List</div>
                    <div class="card-body">

                        @if (session('message'))
                            <div class="alert alert-success" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif
                            <div class="table-responsive">
                                <table class="table table-striped">
                            <tr>
                                <th>User name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered at</th>
                                <th></th>
                            </tr>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @switch($user->admin)
                                            @case(0)
                                            @default
                                                user
                                                @break
                                            @case(1)
                                                admin
                                                @break
                                        @endswitch
                                        @if(auth()->user()->id === $user->id)
                                            (You)
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at }}</td>
                                    <td>
                                        @if(empty($user->approved_at))
                                        <a href="{{ route('admin.users.approve', $user->id) }}"
                                               class="btn btn-primary btn-sm">Approve</a>
                                            @else
                                            @if(auth()->user()->id !== $user->id)
                                            <a href="{{ route('admin.users.disapprove', $user->id) }}"
                                               class="btn btn-danger btn-sm">Disapprove</a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No users found.</td>
                                </tr>
                            @endforelse
                        </table>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection