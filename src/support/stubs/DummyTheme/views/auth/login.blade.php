@extends('DummyTheme.views.index')

@section('title', trans('auth.register_box_title'))

@section('header')
    @parent
@endsection

@section('content')

    <div class="blog-header">
        <div class="container">
            <h1 class="blog-title">{{ trans('auth.login_title') }}</h1>
        </div>
    </div>

    <div class="container">
        <div class="row">

        <div class="login_wrapper">
            <div class="animate form login_form">
                <section class="login_content">
                    <form class="form-horizontal" role="form" method="POST" action="<?php echo route('auth.login.post'); ?>">
                        {{ csrf_field() }}
                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="{{ trans("login.username-input") }}" required autofocus>
                            @if ($errors->has('email'))
                                <div class="alert alert-danger" role="alert">
                                    {{ $errors->first('email') }}
                                </div>
                            @endif
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <input id="password" type="password" class="form-control" name="password" placeholder="{{ trans("login.password-input") }}" required>
                            @if ($errors->has('password'))
                                <div class="alert alert-danger" role="alert">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <button type="submit" class="btn btn-default submit">
                                {{ trans("auth.login_button") }}
                            </button>
                            <a class="reset-password" href="{{route('auth.forgot')}}">{{ trans("auth.forgot_password") }}</a>
                            <a class="register" href="{{route('auth.register')}}">{{ trans("auth.register_title") }}</a>
                        </div>
                    </form>
                </section>
            </div>
        </div>
      </div><!-- row -->
@endsection
