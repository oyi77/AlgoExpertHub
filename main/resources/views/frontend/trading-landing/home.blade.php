@extends(Config::themeView('layout.master'))

@section('content')
    @php
    @endphp
    @if (isset($page) && $page && $page->widgets && $page->widgets->count() > 0)
        @php
            // Check if widgets include hero or banner or plans
            $hasHeroOrBanner = false;
            $hasPlans = false;
            $widgetsToRender = [];
            
            foreach ($page->widgets as $widget) {
                $sectionValue = $widget->sections;
                if (is_string($sectionValue)) {
                    $decoded = json_decode($sectionValue, true);
                    if ($decoded !== null) {
                        $sectionValue = is_array($decoded) ? ($decoded[0] ?? $decoded['name'] ?? $sectionValue) : $decoded;
                    } else {
                        $sectionValue = trim($sectionValue, '"\'');
                    }
                }
                // Check for hero or banner (case-insensitive)
                $sectionValueLower = strtolower($sectionValue);
                if (in_array($sectionValueLower, ['hero', 'banner'])) {
                    $hasHeroOrBanner = true;
                }
                if (in_array($sectionValueLower, ['account-types', 'account_types', 'plans', 'pricing'])) {
                    $hasPlans = true;
                }
                $widgetsToRender[] = $widget;
            }
        @endphp
        {{-- Always show hero first if not in widgets --}}
        @if (!$hasHeroOrBanner)
            @include(Config::themeView('widgets.hero'))
        @endif
        @foreach ($widgetsToRender as $section)
            @php
                try {
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
                        $renderedSection = preg_replace('/data-banner-source="[^"]*"/', 'data-banner-source="home-widgets"', $renderedSection);
                    }
                } catch (\Exception $e) {
                    $renderedSection = '<!-- Error rendering section: ' . htmlspecialchars($e->getMessage()) . ' -->';
                }
            @endphp
            <?= $renderedSection ?>
        @endforeach
        {{-- Always show plans section if not in widgets --}}
        @if (!$hasPlans)
            @include(Config::themeView('widgets.account-types'))
        @endif
    @else
        {{-- Fallback: Show default sections if page/widgets not configured --}}
        @include(Config::themeView('widgets.hero'))
        @include(Config::themeView('widgets.market-trends'))
        @include(Config::themeView('widgets.how_works'))
        @include(Config::themeView('widgets.benefits'))
        @include(Config::themeView('widgets.trading-demo'))
        @include(Config::themeView('widgets.trading-instruments'))
        @include(Config::themeView('widgets.why-choose-us'))
        @include(Config::themeView('widgets.testimonial'))
        @include(Config::themeView('widgets.blog'))
        @include(Config::themeView('widgets.team'))
        @include(Config::themeView('widgets.cta-education'))
        @include(Config::themeView('widgets.account-types'))
        @include(Config::themeView('widgets.footer-cta'))
    @endif
@endsection

