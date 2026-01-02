@extends(Config::themeView('layout.master'))

@section('content')
    @if (isset($page) && $page instanceof \App\Models\Page && $page->widgets()->exists())
        @foreach ($page->widgets as $section)
       <?= Section::render($section->sections) ?>
     @endforeach
    @endif
@endsection
