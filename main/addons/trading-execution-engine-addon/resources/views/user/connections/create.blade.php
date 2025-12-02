@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <h4>{{ __($title) }}</h4>
        </div>
        <div class="card-body">
                        <form action="{{ route('user.execution-connections.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" class="form-control" required>
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Exchange Name</label>
                                <input type="text" name="exchange_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Credentials (JSON)</label>
                                <textarea name="credentials" class="form-control" rows="5" required></textarea>
                                <small class="form-text text-muted">Enter credentials as JSON object</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </form>
                    </div>
                </div>
        </div>
    </div>
@endsection

