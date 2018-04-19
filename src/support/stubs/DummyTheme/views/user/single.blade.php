@extends('DummyTheme.views.index')

@section('meta')
    {{metaTags($author)}}
@endsection

@section('header')
    @parent
@endsection

@section('content')
    <div class="container">
        <div class="single-author-wrapper">
            {{$author->avatarImage(200,200, true)}}
            <span class="single-author-content">
                <h1 >{{$author->fullName}}</h1>
                <p class="blog-description">{!! $author->about !!}</p>
            </span>
        </div>
    </div>

    <div class="album text-muteds">
        <div class="container">
            <?php
            if(!$posts->isEmpty()){
            ?>
            <ul class="row posts-list">
                <?php
                foreach($posts as $post){
                ?>
                <li>
                    <a href="{{$post->href}}">
                        <img src="{{$post->featuredImageURL(200,200,noImage()) }}"  style="width: 100%; display: block;">

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
