@extends('DummyTheme.views.index')

@section('title', $post->title)

@section('header')
    @parent
@endsection

@section('content')
    <p>ID:<?php print $post->ID; ?>.</p>
    <p>AMP Page</p>
@endsection