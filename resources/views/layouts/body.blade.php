<body>
@yield('popup')
<div id="app">
    <nav class="navbar navbar-expand-md navbar-light navbar-laravel">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">{{ config('app.name') }} </a>


            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                <ul class="navbar-nav mr-auto">

                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                            </li>
                        @endif
                    @else

                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>


                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                @if( Gate::check('customer-create') || Gate::check('customer-edit') || Gate::check('customer-delete') || Gate::check('customer-list'))
                                    <a class="dropdown-item" href="/dashboard" >
                                        {{ __('Dashboard') }}
                                    </a>
                                @endif

                                @if( Gate::check('invoice-create'))
                                    <a class="dropdown-item" href="{{ route('customers-invoices.create') }}" >
                                        {{ __('Create Invoice') }}
                                    </a>
                                @endif

                                @if( Gate::check('generated-invoice-create'))
                                    <a class="dropdown-item" href="{{ route('invoice-generator.create') }}" >
                                        {{ __('Invoice Generator') }}
                                    </a>
                                @endif

                                @if( Gate::check('salespeople-create') || Gate::check('salespeople-edit') || Gate::check('salespeople-delete') || Gate::check('salespeople-list'))
                                    <a class="dropdown-item" href="/salespeople" >
                                        {{ __('Salespeople') }}
                                    </a>
                                @endif

                                @if( Gate::check('performance-reports-view'))
                                    <a class="dropdown-item" href="/reports/salespeople" >
                                        {{ __('Salespeople Report') }}
                                    </a>
                                @endif
                                {{--@if( Gate::check('sources-reports-view'))--}}
                                    {{--<a class="dropdown-item" href="/reports/sources" >--}}
                                        {{--{{ __('Sources Report') }}--}}
                                    {{--</a>--}}
                                {{--@endif--}}

                                @if( Gate::check('edit-email-templates') || Gate::check('create-email-templates') || Gate::check('view-email-templates'))
                                    <a class="dropdown-item" href="/email-templates/templates" >
                                        {{ __('Email Templates') }}
                                    </a>
                                @endif

                                @if( Gate::check('user-create') || Gate::check('user-edit') || Gate::check('user-delete') || Gate::check('user-list'))
                                    <a class="dropdown-item" href="/users" >
                                        {{ __('Users') }}
                                    </a>
                                @endif
                                @if( Gate::check('role-create') || Gate::check('role-edit') || Gate::check('role-delete') || Gate::check('role-list'))
                                    <a class="dropdown-item" href="/roles" >
                                        {{ __('Roles') }}
                                    </a>
                                @endif

                                <a class="d-block mx-2 btn btn-info text-white" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>


                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </li>

                    @endguest
                </ul>


            </div>

        </div>


    </nav>

    <main class="py-4">
        @yield('content')
    </main>
</div>

