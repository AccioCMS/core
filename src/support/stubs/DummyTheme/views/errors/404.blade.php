@extends('DummyTheme.views.index')

@section('title', "404")

@section('header')
    @parent
@endsection

@section('content')
    <div class="container">
        <div class="row">

            <div class="col-sm-12 blog-main">

                <div class="blog-post">
                    <h2 class="blog-post-title">{{@trans('base.404.title')}}</h2>
                </div><!-- /.blog-main -->

                <div class="post-wrapper">

                    <p class="blog-post-meta">
                        {{@trans('base.404.description')}}
                    </p>
                    <form method="POST" class="inner-search-form" action="{{route('search.results.post')}}">
                        {{ csrf_field() }}
                        <input type="search" class="form-control mr-sm-2 col-sm-6" placeholder="{{@trans('base.searchPlaceholder')}}" value="" name="keyword">
                        <button type="submit" class="btn btn-primary my-2 my-sm-0">{{@trans('base.searchBtn')}}</button>
                    </form>
                </div>
        </div><!-- /.row -->

    </div>
@endsection