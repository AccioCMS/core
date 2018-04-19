@extends('DummyTheme.views.index')

@section('title', trans('auth.register_box_title'))

@section('header')
    @parent
@endsection

@section('content')

    <div class="blog-header">
        <div class="container">
            <h1 class="blog-title">{{ trans('auth.reset_password_box_title') }}</h1>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">

                <?php
                //error message
                if($expired){
                    ?>
                    <div class="alert alert-danger">
                        @lang('passwords.token')
                    </div>
                 <?php
                 }else{
                    ?>
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="panel panel-default">

                        <div class="panel-body">
                            {{ Form::open(['route' => 'Auth.reset.post', 'class' => 'form-horizontal']) }}

                            {{ Form::hidden('email', $email, ['class' => 'form-control', 'placeholder' => trans('validation.attributes.frontend.email')]) }}
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="form-group">
                                {{ Form::label('password', trans('auth.password'), ['class' => 'col-md-4 control-label']) }}
                                <div class="col-md-6">
                                    {{ Form::password('password', ['class' => 'form-control', 'required' => 'required', 'autofocus' => 'autofocus', 'placeholder' => trans('auth.password')]) }}
                                    @if ($errors->has('password'))
                                        <div class="alert alert-danger" role="alert">
                                            {{ $errors->first('password') }}
                                        </div>
                                    @endif
                                </div><!--col-md-6-->
                            </div><!--form-group-->

                            <div class="form-group">
                                {{ Form::label('password_confirmation', trans('auth.password_confirmation'), ['class' => 'col-md-4 control-label']) }}
                                <div class="col-md-6">
                                    {{ Form::password('password_confirmation', ['class' => 'form-control', 'required' => 'required', 'placeholder' => trans('auth.password_confirmation')]) }}

                                </div><!--col-md-6-->
                            </div><!--form-group-->

                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    {{ Form::submit(trans('auth.reset_password_button'), ['class' => 'btn btn-primary']) }}
                                </div><!--col-md-6-->
                            </div><!--form-group-->

                            {{ Form::close() }}

                        </div><!-- panel body -->

                    </div><!-- panel -->
                    <?php
                }
                ?>

            </div><!-- col-md-8 -->
        </div><!-- row -->
@endsection

