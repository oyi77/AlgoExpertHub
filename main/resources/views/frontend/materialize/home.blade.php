@extends(Config::themeView('layout.master'))

@section('content')
    @push('skip_wow')@endpush
    @push('skip_paroller')@endpush
    @push('skip_tweenmax')@endpush
    @push('skip_viewport')@endpush
    @if (isset($page) && $page instanceof \App\Models\Page && $page->widgets()->exists())
        @foreach ($page->widgets as $section)
       <?= Section::render($section->sections) ?>
     @endforeach
    @endif
@endsection
