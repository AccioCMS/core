@extends('DummyTheme.views.index')

@section('meta')
    {{metaTags(homepage())}}
@endsection

@section('header')
    @parent
@endsection

@section('content')
    <div class="container">
        <h1>{{homepage()->title}}</h1>
        <p class="lead">
            <?php
                print homepage()->content();
            ?>
        </p>
    </div>
@endsection
