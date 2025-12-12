@extends(Config::themeView('layout.master'))

@section('content')
            <?= Section::render($section->sections) ?>
        @endforeach
        @endif
        @endif
    @endif
@endsection
