@extends('DummyTheme.views.index')

@section('meta')
    {{metaTags($tag)}}
@endsection

@section('header')
    @parent
@endsection

@section('content')
    <div class="blog-header">
        <div class="container">
            <div class="single-tag-wrapper">
                {{$tag->printFeaturedImage(200,200)}}
                <span class="single-tag-content">
                    <h1 >{{$tag['title']}}</h1>
                    <p class="blog-description">{{$tag['description']}}</p>
                </span>
            </div>

        </div>
    </div>

    <div class="album text-muteds">
        <div class="container">
            <?php
            if(!$posts->isEmpty()){
                //$post->beforeListEvents();
                ?>
                <ul class="row posts-list">
                    <?php
                    foreach($posts->items() as $post){
                    ?>
                    <li>
                        <a href="{{$post->href}}">
                            <img src="{{ $post->featuredImageURL(200,200,noImage('no-image-default.png')) }}"  style="width: 100%; display: block;">

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
                //$post->afterListEvents();
                ?>
                {{$posts->links('pagination::bootstrap-4')}}
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
