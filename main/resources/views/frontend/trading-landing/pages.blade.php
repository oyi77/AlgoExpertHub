@extends(Config::themeView('layout.master'))

@section('content')
    @if (isset($pageBuilderContent) && $pageBuilderContent)
        <style>{!! $pageBuilderContent['css'] ?? '' !!}</style>
        <div class="pagebuilder-content">
            {!! $pageBuilderContent['html'] ?? '' !!}
        </div>
    @elseif ($page->widgets)
            <?= Section::render($section->sections) ?>
        @endforeach
        @endif
    @endif
@endsection

