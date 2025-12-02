@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channel-forwarding.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                    <h4 class="card-title mb-0">{{ $title }}: {{ $channel->name }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.channel-forwarding.assign.store', $channel->id) }}" method="post" id="assignmentForm">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('Assignment Type') }} <span class="text-danger">*</span></label>
                                <select name="assignment_type" id="assignmentType" class="form-control" required>
                                    <option value="">{{ __('Select Type') }}</option>
                                    <option value="user" {{ old('assignment_type', $channel->scope) === 'user' ? 'selected' : '' }}>{{ __('Specific Users') }}</option>
                                    <option value="plan" {{ old('assignment_type', $channel->scope) === 'plan' ? 'selected' : '' }}>{{ __('Subscription Plans') }}</option>
                                    <option value="global" {{ old('assignment_type', $channel->scope) === 'global' ? 'selected' : '' }}>{{ __('Global (All Users)') }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- User Assignment Section -->
                        <div id="userAssignment" style="display: none;">
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('Select Users') }}</label>
                                    <select name="user_ids[]" id="userSelect" class="form-control" multiple style="height: 300px;">
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" {{ in_array($user->id, $assignedUserIds) ? 'selected' : '' }}>
                                                {{ $user->username ?? $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple users') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Plan Assignment Section -->
                        <div id="planAssignment" style="display: none;">
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('Select Plans') }}</label>
                                    <select name="plan_ids[]" id="planSelect" class="form-control" multiple style="height: 300px;">
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}" {{ in_array($plan->id, $assignedPlanIds) ? 'selected' : '' }}>
                                                {{ $plan->name }} ({{ $plan->price_type === 'free' ? __('Free') : '$' . number_format($plan->price, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple plans. All users subscribed to selected plans will receive signals from this channel.') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Global Assignment Section -->
                        <div id="globalAssignment" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                {{ __('This channel will be available to all users in the system.') }}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> {{ __('Save Assignments') }}
                                </button>
                                <a href="{{ route('admin.channel-forwarding.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        document.getElementById('assignmentType').addEventListener('change', function() {
            const type = this.value;
            
            // Hide all sections
            document.getElementById('userAssignment').style.display = 'none';
            document.getElementById('planAssignment').style.display = 'none';
            document.getElementById('globalAssignment').style.display = 'none';
            
            // Show relevant section
            if (type === 'user') {
                document.getElementById('userAssignment').style.display = 'block';
                document.getElementById('userSelect').required = true;
                document.getElementById('planSelect').required = false;
            } else if (type === 'plan') {
                document.getElementById('planAssignment').style.display = 'block';
                document.getElementById('planSelect').required = true;
                document.getElementById('userSelect').required = false;
            } else if (type === 'global') {
                document.getElementById('globalAssignment').style.display = 'block';
                document.getElementById('userSelect').required = false;
                document.getElementById('planSelect').required = false;
            }
        });

        // Initialize on page load
        document.getElementById('assignmentType').dispatchEvent(new Event('change'));
    </script>
    @endpush
@endsection

