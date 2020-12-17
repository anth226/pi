@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Salespeople Levels</h2>
                    </div>
                    <div class="pull-right">
                        @can('salespeople-create')
                        <a class="btn btn-success mb-4 mt-2" href="{{ route('levels.create') }}"> Add New Level</a>
                        @endcan
                    </div>
                </div>
            </div>


            @if ($message = Session::get('success'))
                <div class="alert alert-success">
                    <p>{{ $message }}</p>
                </div>
            @endif


            <table class="table table-responsive">
                <tr>
                    <th>Title</th>
                    <th>Percentage</th>
                    <th></th>
                </tr>

                @foreach ($salespeoplelevels as $key => $level)
                    <tr>
                        <td>{{ $level->title }}</td>
                        <td>{{ $level->percentage }}%</td>
                        <td>
                            @can('salespeople-edit')
                            <a class="btn btn-primary mb-1" href="{{ route('levels.edit',$level->id) }}">Edit</a>
                            @endcan
                            {{--@can('salespeople-delete')--}}
                            {{--{!! Form::open(['method' => 'DELETE','route' => ['salespeople.destroy', $user->id],'style'=>'display:inline']) !!}--}}
                            {{--{!! Form::submit('Delete', ['class' => 'btn btn-danger mb-1']) !!}--}}
                            {{--{!! Form::close() !!}--}}
                            {{--@endcan--}}
                        </td>
                    </tr>
                @endforeach
            </table>
            {!! $salespeoplelevels->render() !!}
        </div>
    </div>
@endsection

