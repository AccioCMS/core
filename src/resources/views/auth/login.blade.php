<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ trans("accio::base.titleAdmin") }}</title>

    <!-- Bootstrap -->
    <link href="{{ URL::asset('public/backend/admin-theme-modules/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ URL::asset('public/backend/admin-theme-modules/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- NProgress -->
    <link href="{{ URL::asset('public/backend/admin-theme-modules/nprogress/nprogress.css') }}" rel="stylesheet">
    <!-- Animate.css -->
    <link href="{{ URL::asset('public/backend/admin-theme-modules/animate.css/animate.min.css') }}" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="{{ URL::asset('public/backend/admin-theme-modules/build/css/custom.min.css') }}" rel="stylesheet">
</head>

<body class="login">
<div>
    <a class="hiddenanchor" id="signup"></a>
    <a class="hiddenanchor" id="signin"></a>

    <div class="login_wrapper">
        <div class="animate form login_form">
            <section class="login_content">
                <form class="form-horizontal" role="form" method="POST" action="<?php echo route('backend.auth.loginRequest'); ?>">
                    {{ csrf_field() }}
                    <h1>{{ trans("accio::login.title") }}</h1>

                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="{{ trans("accio::login.username-input") }}" required autofocus>
                        @if ($errors->has('email'))
                            <span class="help-block">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                        <input id="password" type="password" class="form-control" name="password" placeholder="{{ trans("accio::login.password-input") }}" required>
                        @if ($errors->has('password'))
                            <span class="help-block">
                                <strong>{{ $errors->first('password') }}</strong>
                            </span>
                        @endif
                    </div>

                    <?php
                    event('auth:admin:login_form', [Auth::user()]);
                    ?>

                    <div>
                        <button type="submit" class="btn btn-default submit">
                            {{ trans("accio::login.btn") }}
                        </button>
                        <a class="reset_pass" href="#">{{ trans("accio::login.lost-password") }}</a>
                    </div>

                    <div class="clearfix"></div>

                    <div class="separator">
                        <div>
                            <h1><img src="{{ URL::asset('images/logo-new.png') }}" alt=""></h1>
                            <p>{{ trans("accio::login.copyright") }}</p>
                        </div>
                    </div>
                </form>
            </section>
        </div>

    </div>
</div>
</body>
</html>
