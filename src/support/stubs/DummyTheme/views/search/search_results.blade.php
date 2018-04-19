@extends('DummyTheme.views.index')

@section('title', \App\Models\MenuLink::getCurrent('label'))

@section('meta')
    @parent
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('header')
    @parent
@endsection

@section('content')
    <div class="blog-header">
        <div class="container">
            <h1 class="blog-title">{{trans('search.title')}}</h1>
        </div>
    </div>

    <div class="album text-muteds">
        <div class="container">
            <?php
            if(\Accio\Support\Facades\Search::getKeyword()) {

                if(!$posts->isEmpty()){
                    ?>
                    <p class="lead blog-description">
                        {{trans('search.results.label')}} <strong><?php print \Accio\Support\Facades\Search::getKeyword()?></strong>
                    </p>

                    <ul class="row posts-list">
                        <?php
                        foreach($posts as $post){
                        ?>
                        <li>
                            <a href="{{ $post->href}}">
                                <img src="{{$post->featuredImageURL(200,150,noimage('no-image-default.png')) }}"  style="width: 100%; display: block;">
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
                    {{
                        $posts
                        ->setPath(route('search.results',['keyword'=>searchKeyword()]))
                        ->links('pagination::bootstrap-4')
                    }}
                <?php
                }else{
                    ?>
                    <p>
                        {{@trans('search.results.no_results')}}
                    </p>
                    <?php
                }
            }else{
                ?>
                <p>
                    {{@trans('base.searchTermEmpty')}}
                </p>
                <?php
             }
            ?>
        </div>
    </div>
@endsection


