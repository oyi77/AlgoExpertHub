@extends(Config::themeView('layout.master'))

@section('content')
    @if (isset($page) && $page && $page->widgets)
        @foreach ($page->widgets as $section)
           <?= Section::render($section->sections) ?>
        @endforeach
    @endif
@endsection
