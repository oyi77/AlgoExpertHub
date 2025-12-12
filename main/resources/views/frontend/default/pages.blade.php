@extends(Config::themeView('layout.master'))

@section('content')
    @if (isset($pageBuilderContent) && $pageBuilderContent)
        {{-- Render pagebuilder content --}}
        <style>{!! $pageBuilderContent['css'] ?? '' !!}</style>
        <div class="pagebuilder-content">
            {!! $pageBuilderContent['html'] ?? '' !!}
        </div>
    @elseif (isset($page) && $page && $page->widgets)
        {{-- Render legacy sections --}}
        @foreach ($page->widgets as $section)
            @php
                $renderedSection = Section::render($section->sections);
                // Add source identifier if it's a banner
                $sectionValue = $section->sections;
                $decoded = json_decode($sectionValue, true);
                if ($decoded !== null) {
                    $sectionValue = is_array($decoded) ? ($decoded[0] ?? $decoded['name'] ?? $sectionValue) : $decoded;
                } else {
                    $sectionValue = trim($sectionValue, '"\'');
                }
                if ($sectionValue === 'banner') {
                    // Replace the data-banner-source attribute
                    $renderedSection = preg_replace('/data-banner-source="[^"]*"/', 'data-banner-source="pages-widgets"', $renderedSection);
                }
            @endphp
            <?= $renderedSection ?>
        @endforeach
    @endif
@endsection
