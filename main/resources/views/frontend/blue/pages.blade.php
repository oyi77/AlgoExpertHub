@extends(Config::themeView('layout.master'))

@section('content')
            <?= Section::render($section->sections) ?>
        @endforeach
    @endif
@endsection
