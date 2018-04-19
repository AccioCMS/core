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

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>

                @elseif(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif


                <div class="panel panel-default">

                    <div class="panel-body">

                        <form class="form-horizontal" role="form" method="POST" action="<?php echo route('auth.forgot.post'); ?>">
                           {{ csrf_field() }}

                            <div class="form-group">
                                {{ Form::label('email', trans('auth.email'), ['class' => 'col-md-4 control-label']) }}
                                <div class="col-md-6">
                                    {{ Form::email('email', null, ['class' => 'form-control', 'maxlength' => '191', 'required' => 'required', 'autofocus' => 'autofocus', 'placeholder' => trans('auth.email')]) }}
                                    @if ($errors->has('email'))
                                        <div class="alert alert-danger" role="alert">
                                            {{ $errors->first('email') }}
                                        </div>
                                    @endif
                                </div><!--col-md-6-->
                            </div><!--form-group-->

                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    {{ Form::submit(trans('auth.send_password_reset_link_button'), ['class' => 'btn btn-primary']) }}
                                </div><!--col-md-6-->
                            </div><!--form-group-->

                        </form>

                    </div><!-- panel body -->

                </div><!-- panel -->

            </div><!-- col-md-8 -->

        </div><!-- row -->
@endsection
