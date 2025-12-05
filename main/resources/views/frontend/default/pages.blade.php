@extends(Config::theme() . 'layout.master')

@section('content')
    @if (isset($pageBuilderContent) && $pageBuilderContent)
        {{-- Render pagebuilder content --}}
        <style>{!! $pageBuilderContent['css'] ?? '' !!}</style>
        <div class="pagebuilder-content">
            {!! $pageBuilderContent['html'] ?? '' !!}
        </div>
    @elseif ($page->widgets)
        {{-- Render legacy sections --}}
        @foreach ($page->widgets as $section)
            <?= Section::render($section->sections) ?>
        @endforeach
    @endif
@endsection
