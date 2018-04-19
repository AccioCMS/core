@extends('app')

<!-- Header extra content -->
@section('header')
    <style>
        .title{
            text-align: center;
            vertical-align: middle;
            margin-top: 150px !important;
            display: block;
            font-size: 35px;
            color: #999;
        }
    </style>
@stop

<!-- Body section -->
    @section('content')
        <div class="right_col" role="main">
            <div class="row">
                <div class="content">
                    <div class="title">
                        <?php
                            if (!isset($message)){
                                echo "YOU DON'T HAVE PERMISSIONS FOR THIS ACTION";
                            }else{
                                echo $message;
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

    @stop

@section('footer')

@stop
