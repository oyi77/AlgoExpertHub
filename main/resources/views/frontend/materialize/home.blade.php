@extends(Config::theme() . 'layout.master')

@section('content')
    @push('skip_wow')@endpush
    @push('skip_paroller')@endpush
    @push('skip_tweenmax')@endpush
    @push('skip_viewport')@endpush
    @foreach ($page->widgets as $section)
       <?= Section::render($section->sections) ?>
    @endforeach
@endsection
