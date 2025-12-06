@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'Create A/B Test' }}
@endsection

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Create A/B Test</h4>
                    </div>
                <div class="card-body">
                        <form action="{{ route('admin.srm.ab-tests.store') }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <label>Name *</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Pilot Group Percentage *</label>
                                <input type="number" name="pilot_group_percentage" class="form-control" value="{{ old('pilot_group_percentage', 10) }}" min="1" max="50" step="0.1" required>
                                <small class="form-text text-muted">Percentage of users in pilot group (1-50%)</small>
                            </div>

                            <div class="form-group">
                                <label>Test Duration (Days) *</label>
                                <input type="number" name="test_duration_days" class="form-control" value="{{ old('test_duration_days', 14) }}" min="1" max="90" required>
                            </div>

                            <div class="form-group">
                                <label>Pilot Logic (JSON) *</label>
                                <textarea name="pilot_logic" class="form-control" rows="10" required>{{ old('pilot_logic', '{}') }}</textarea>
                                <small class="form-text text-muted">JSON configuration for pilot group SRM logic</small>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Create Test</button>
                                <a href="{{ route('admin.srm.ab-tests.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </div>
@endsection

