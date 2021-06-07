@extends('layouts.app')

@section('title')
    {{ $title }}
@endsection

@section('content')
    {!! $page->content !!}
@endsection
