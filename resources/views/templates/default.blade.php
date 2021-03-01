<div class="banner-container">
    <img src="{{ env("APP_URL") . '/api/file/' . $page->file_id }}" alt="test" class="w-100">
    <div class="banner-title p-3">
        {{ $page->title }}
    </div>
</div>
<div class="p-3">
    {{ $page->content }}
</div>
