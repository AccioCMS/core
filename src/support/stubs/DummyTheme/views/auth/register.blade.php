@extends('DummyTheme.views.index')

@section('title', trans('auth.register_box_title'))

@section('header')
    @parent
@endsection

@section('content')

    <div class="blog-header">
        <div class="container">
            <h1 class="blog-title">{{ trans('auth.register_title') }}</h1>
        </div>
    </div>

    <div class="container">
        <div class="row">

            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-body">

                        <form class="form-horizontal" role="form" method="POST" action="<?php echo route('auth.register.post'); ?>">
                            {{ csrf_field() }}


                                <div class="form-group">
                                {{ Form::label('firstName', trans('auth.firstName'),
                                ['class' => 'col-md-4 control-label']) }}

                                <div class="col-md-6">
                                    {{ Form::text('firstName', null,[
                                        'class' => 'form-control',
                                        'maxlength' => '191',
                                        'required' => 'required',
                                        'autofocus' => 'autofocus',
                                        'placeholder' => trans('auth.firstName')
                                        ])
                                    }}
                                    @if ($errors->has('firstName'))
                                        <div class="alert alert-danger" role="alert">
                                            {{ $errors->first('firstName') }}
                                        </div>
                                    @endif
                                </div><!--col-md-6-->

                            </div><!--form-group-->


                            <div class="form-group">
                                {{ Form::label('lastName', trans('auth.lastName'),
                                ['class' => 'col-md-4 control-label']) }}

                                <div class="col-md-6">
                                    {{ Form::text('lastName', null,[
                                            'class' => 'form-control',
                                            'maxlength' => '191',
                                            'required' => 'required',
                                            'placeholder' => trans('auth.lastName')
                                        ])
                                    }}
                                    @if ($errors->has('lastName'))
                                        <div class="alert alert-danger" role="alert">
                                            {{ $errors->first('lastName') }}
                                        </div>
                                    @endif
                                </div><!--col-md-6-->
                            </div><!--form-group-->

                            <div class="form-group">
                                {{ Form::label('email', trans('auth.email'), [
                                    'class' => 'col-md-4 control-label'
                                    ])
                                }}
                                <div class="col-md-6">
                                    {{ Form::email('email', null, [
                                            'class' => 'form-control',
                                            'maxlength' => '191',
                                            'required' => 'required',
                                            'placeholder' => trans('auth.email')
                                        ])
                                    }}
                                    @if ($errors->has('email'))
                                        <div class="alert alert-danger" role="alert">
                                            {{ $errors->first('email') }}
                                        </div>
                                    @endif
                                </div><!--col-md-6-->
                            </div><!--form-group-->

                            <div class="form-group">
                                {{ Form::label('password', trans('auth.password'), ['class' => 'col-md-4 control-label']) }}

                                <div class="col-md-6">
                                    {{ Form::password('password', [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => trans('auth.password')
                                        ])
                                    }}
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

                            @if (config('access.captcha.registration'))
                                <div class="form-group">
                                    <div class="col-md-6 col-md-offset-4">
                                        {!! Form::captcha() !!}
                                        {{ Form::hidden('captcha_status', 'true') }}
                                    </div><!--col-md-6-->
                                </div><!--form-group-->
                            @endif

                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    {{ Form::submit(trans('auth.register_button'), ['class' => 'btn btn-primary']) }}
                                </div><!--col-md-6-->
                            </div><!--form-group-->

                        </form>

                    </div><!-- panel body -->

                </div><!-- panel -->

            </div><!-- col-md-8 -->

        </div><!-- row -->
@endsection
