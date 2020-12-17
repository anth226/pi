@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="col-lg-12 m-auto">
            <div class="row">
                <div class="col-lg-12 margin-tb">
                    <div class="pull-left">
                        <h2>Salespeople</h2>
                    </div>
                    <div class="pull-right">
                        @can('salespeople-create')
                        <a class="btn btn-success mb-4 mt-2" href="{{ route('salespeople.create') }}"> Add New Salesperson</a>
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
                    <th>Id</th>
                    <th>Name</th>
                    <th>Details</th>
                    <th>Level</th>
                    <th></th>
                </tr>
                @php
                    use App\KmClasses\Sms\FormatUsPhoneNumber;
                @endphp
                @foreach ($salespeoples as $key => $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name_for_invoice }}</td>
                        <td>
                            <small>
                                <div>{{ $user->first_name }} {{ $user->last_name }}</div>
                                <div>{{ $user->email }}</div>
                                <div>@php echo FormatUsPhoneNumber::nicePhoneNumberFormat($user->phone_number, $user->formated_phone_number); @endphp</div>
                            </small>
                        </td>
                        <td>{{ $user->level->level->title }}</td>
                        <td>
                            <a class="btn btn-info mb-1" href="{{ route('salespeople.show',$user->id) }}">Show</a>
                            @can('salespeople-edit')
                            <a class="btn btn-primary mb-1" href="{{ route('salespeople.edit',$user->id) }}">Edit</a>
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
            {!! $salespeoples->render() !!}
        </div>
    </div>
@endsection