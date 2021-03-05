@if ($page->file_id)
<div class="banner-container">
    <img src="{{ env("APP_URL") . '/api/file/' . $page->file_id }}" alt="banner" class="w-100">
    <div class="banner-title p-3">
        {{ $page->title }}
    </div>
</div>
@else
    <h2 class="p-3">{{ $page->title }}</h2>
@endif
<div class="p-3">
    {!! $page->content !!}
</div>
