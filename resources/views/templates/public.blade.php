@extends('layouts.app')

@section('title')
    {{ $title }}
@endsection

@section('content')
    {!! \App\Helpers\HtmlSanitizer::clean($page->content) !!}
@endsection
