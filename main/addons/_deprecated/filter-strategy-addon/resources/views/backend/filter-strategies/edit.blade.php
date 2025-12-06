@extends('backend.layout.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Filter Strategy</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.filter-strategies.update', $filterStrategy->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $filterStrategy->name) }}" required>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $filterStrategy->description) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Visibility *</label>
                            <select name="visibility" class="form-control" required>
                                <option value="PRIVATE" {{ old('visibility', $filterStrategy->visibility) === 'PRIVATE' ? 'selected' : '' }}>Private</option>
                                <option value="PUBLIC_MARKETPLACE" {{ old('visibility', $filterStrategy->visibility) === 'PUBLIC_MARKETPLACE' ? 'selected' : '' }}>Public Marketplace</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="clonable" value="1" {{ old('clonable', $filterStrategy->clonable) ? 'checked' : '' }}>
                                Allow cloning
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="enabled" value="1" {{ old('enabled', $filterStrategy->enabled) ? 'checked' : '' }}>
                                Enabled
                            </label>
                        </div>

                        <div class="form-group">
                            <label>Configuration (JSON) *</label>
                            <textarea name="config" class="form-control" rows="15" required>{{ old('config', json_encode($filterStrategy->config, JSON_PRETTY_PRINT)) }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Strategy</button>
                            <a href="{{ route('admin.filter-strategies.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

