<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    @section('meta')
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    @show

    @section('css')
        {!!css()!!}
    @show

    <?php event('theme:head_end'); ?>
</head>
<?php event('theme:before_body'); ?>
<body>
    <?php event('theme:body_start'); ?>
    @section('header')
        <nav class="navbar navbar-expand-md fixed-top navbar-dark bg-dark">
            <a class="navbar-brand" href="#">Logo</a>
            <button class="navbar-toggler p-0 border-0" type="button" data-toggle="offcanvas">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="navbar-collapse offcanvas-collapse" id="navbarsExampleDefault">
                {{menu("primary",'','mr-auto')}}

                {{searchForm('vendor.search.bootstrap-4','form-inline my-2 my-lg-0')}}
            </div>
            {{languages('vendor.languages.bootstrap-4-dropdown')}}
        </nav>
        @if(authControllerExist())
            <div class="nav-scroller bg-white box-shadow">
                <nav class="nav nav-underline">
                    @if(!auth()->check())
                        <a href="{{route('auth.login')}}"  class="nav-link {{routeIsActive('auth.login', 'active')}}">
                            Login
                        </a>
                        <a href="{{route('auth.register')}}"  class="nav-link {{routeIsActive('auth.register', 'active')}}">
                            Register
                        </a>
                    @else
                        <a href="{{route('account.dashboard')}}"  class="nav-link {{routeIsActive('account.dashboard', 'active')}}">
                            Dashboard
                        </a>
                        <a href="{{route('auth.logout')}}" class="nav-link">
                            <strong>Logout</strong>
                        </a>
                    @endif
                </nav>
            </div>
        @endif
    @show

    @yield('content')

    @section('footer')
        <div class="container">
            <footer>
                <p>&copy; Company 2017</p>
            </footer>
        </div>
    @show

    @section('js')
        {!! js(['async' => true]) !!}
    @show
    <?php event('theme:body_end'); ?>
</body>
</html>