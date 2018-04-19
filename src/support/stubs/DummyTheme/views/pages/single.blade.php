@extends('DummyTheme.views.index')

@section('meta')
    {{metaTags($post)}}
@endsection

@section('header')
    @parent
@endsection

@section('content')
    <div class="container">
        <div class="row">

            <div class="col-sm-8 blog-main">

                <div class="blog-post">
                    <h2 class="blog-post-title">{{$post->title}}</h2>
                    <p class="blog-post-meta">
                        {{$post->created_at->diffForHumans()}} @lang('base.by')
                        <a href="{{ route('user.single',['authorSlug' => $post->cachedUser()->slug])}}">
                            <img class="avatar" src="{{ $post->cachedUser()->avatar(200,200,true)}}" alt="" />
                            {{ $post->cachedUser()->fullName}}
                        </a>
                    </p>

                    <div class="post-wrapper">
                        {{$post->printFeaturedImage()}}

                        <div class="post-content">
                            <?php
                            print $post->content();
                            ?>
                        </div>
                    </div>
                </div>
            </div><!-- /.blog-main -->

            <div class="col-sm-3 offset-sm-1 blog-sidebar">
                <div class="sidebar-module sidebar-module-inset">
                    <h4>About</h4>
                    <p>Etiam porta <em>sem malesuada magna</em> mollis euismod. Cras mattis consectetur purus sit amet fermentum. Aenean lacinia bibendum nulla sed consectetur.</p>
                </div>

            </div><!-- /.blog-sidebar -->

        </div><!-- /.row -->

    </div>
@endsection