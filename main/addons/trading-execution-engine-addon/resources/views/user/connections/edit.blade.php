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
                        <form action="{{ route('user.execution-connections.update', $connection->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $connection->name }}" required>
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" class="form-control" required>
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}" {{ $connection->type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Exchange Name</label>
                                <input type="text" name="exchange_name" class="form-control" value="{{ $connection->exchange_name }}" required>
                            </div>
                            <div class="form-group">
                                <label>Credentials (JSON)</label>
                                <textarea name="credentials" class="form-control" rows="5" required>{{ json_encode($connection->credentials, JSON_PRETTY_PRINT) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('user.execution-connections.index') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
        </div>
    </div>
@endsection

