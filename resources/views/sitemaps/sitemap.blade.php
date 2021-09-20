<?php
echo '<?xml version="1.0"?>';
$url = env('REACT_APP_BASE_URL');
$exerciseDetailRoute = 'library/exercise/detail';
$materialDetailRoute = 'library/education_material/detail';
$questionnaireDetailRoute = 'library/questionnaire/detail';

$routes = [
    '/',
    'about-us' ,
    'library',
    'contribute',
    'acknowledgment',
    'term-condition',
    'library#education',
    'library#questionnaire',
    'contribute#education',
    'contribute#questionnaire',
]

?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($languages as $language)
        @foreach($routes as $route)
            @if($route === '/')
                <url>
                    <loc>{{ $language->code === config('app.locale') ? $url : $url . $language->code }}</loc>
                </url>
            @else
                <url>
                    <loc>{{ $language->code === config('app.locale') ? $url . $route : $url . $language->code . '/' . $route  }}</loc>
                </url>
            @endif
        @endforeach
        @foreach($exercises as $exercise)
            <url>
                <loc>{{ $language->code === config('app.locale') ? $url . $exerciseDetailRoute . '/' . $exercise->slug : $url . $language->code . '/' .  $exerciseDetailRoute . '/' . $exercise->slug}}</loc>
            </url>
        @endforeach
        @foreach($materials as $material)
            <url>
                <loc>{{ $language->code === config('app.locale') ? $url . $materialDetailRoute . '/' . $material->slug : $url . $language->code . '/' . $materialDetailRoute . '/' . $material->slug}}</loc>
            </url>
        @endforeach
        @foreach($questionnaires as $questionnaire)
            <url>
                <loc>{{ $language->code === config('app.locale') ? $url . $questionnaireDetailRoute . '/' . $questionnaire->slug : $url . $language->code . '/' . $questionnaireDetailRoute . '/' . $questionnaire->slug}}</loc>
            </url>
        @endforeach
    @endforeach
</urlset>
