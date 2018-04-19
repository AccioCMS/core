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
        <p class="lead">An example blog template built with Bootstrap.</p>
    </div>

    <div class="album text-muteds">
        <div class="container">
            <?php

            if(!$posts->isEmpty()){
            // $posts->beforeListEvents();
            ?>
            <ul class="row posts-list">
                <?php
                foreach($posts as $post){
                ?>
                <li>
                    <a href="{{$post->href}}">
                        <img src="{{$post->featuredImageURL(200,150,noImage())}}"  style="width: 100%; display: block;">
                        <span class="title">
                                {{$post->title}}
                        </span>
                        <p class="text">
                            This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                        </p>
                    </a>
                </li>
                <?php
                }
                ?>
            </ul>
            <?php
            //$posts->afterListEvents();
            ?>
            {{$posts->setPath(\Request::url())->render('pagination::bootstrap-4')}}
            <?php
            }else{
            ?>
            <p>
                {{@trans('base.noResults')}}
            </p>
            <?php

            }
            ?>
        </div>
    </div>
@endsection
