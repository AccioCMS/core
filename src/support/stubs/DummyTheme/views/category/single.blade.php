@extends('DummyTheme.views.index')

@section('meta')
    {{metaTags($category)}}
@endsection


@section('header')
    @parent
@endsection

@section('content')

    <div class="container">
        <h1>@yield('title')</h1>
        <p class="lead">Single category</p>
    </div>

    <div class="album text-muteds">
        <div class="container">
            details
        </div>
    </div>
@endsection
