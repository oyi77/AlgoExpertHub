@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channels.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                    <h4 class="card-title mb-0">{{ $title }}: {{ $channel->name }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.channels.assign.store', $channel->id) }}" method="post" id="assignmentForm">
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
                                                {{ $user->name }} ({{ $user->email }})
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
                                                {{ $plan->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple plans') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Global Assignment Section -->
                        <div id="globalAssignment" style="display: none;">
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>{{ __('Warning') }}:</strong> {{ __('Global assignment will send signals from this channel to ALL users in the system. This includes current and future users.') }}
                            </div>
                        </div>

                        <!-- Current Assignments Display -->
                        @if ($channel->scope === 'user' && $channel->assignedUsers->count() > 0)
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5>{{ __('Currently Assigned Users') }}</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Name') }}</th>
                                                    <th>{{ __('Email') }}</th>
                                                    <th class="text-end">{{ __('Action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($channel->assignedUsers as $user)
                                                    <tr>
                                                        <td>{{ $user->name }}</td>
                                                        <td>{{ $user->email }}</td>
                                                        <td class="text-end">
                                                            <form action="{{ route('admin.channels.users.remove', [$channel->id, $user->id]) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('{{ __('Are you sure?') }}');">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($channel->scope === 'plan' && $channel->assignedPlans->count() > 0)
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5>{{ __('Currently Assigned Plans') }}</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Plan Name') }}</th>
                                                    <th class="text-end">{{ __('Action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($channel->assignedPlans as $plan)
                                                    <tr>
                                                        <td>{{ $plan->name }}</td>
                                                        <td class="text-end">
                                                            <form action="{{ route('admin.channels.plans.remove', [$channel->id, $plan->id]) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('{{ __('Are you sure?') }}');">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($channel->scope === 'global')
                            <div class="alert alert-success">
                                <i class="fa fa-check-circle"></i>
                                <strong>{{ __('Global Assignment Active') }}:</strong> {{ __('This channel is currently assigned globally to all users.') }}
                            </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">{{ __('Save Assignments') }}</button>
                                <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const assignmentType = document.getElementById('assignmentType');
            const userAssignment = document.getElementById('userAssignment');
            const planAssignment = document.getElementById('planAssignment');
            const globalAssignment = document.getElementById('globalAssignment');
            const userSelect = document.getElementById('userSelect');
            const planSelect = document.getElementById('planSelect');

            function toggleSections() {
                const type = assignmentType.value;
                
                userAssignment.style.display = type === 'user' ? 'block' : 'none';
                planAssignment.style.display = type === 'plan' ? 'block' : 'none';
                globalAssignment.style.display = type === 'global' ? 'block' : 'none';

                // Make fields required/optional based on selection
                if (type === 'user') {
                    userSelect.setAttribute('required', 'required');
                    planSelect.removeAttribute('required');
                } else if (type === 'plan') {
                    planSelect.setAttribute('required', 'required');
                    userSelect.removeAttribute('required');
                } else {
                    userSelect.removeAttribute('required');
                    planSelect.removeAttribute('required');
                }
            }

            assignmentType.addEventListener('change', toggleSections);
            toggleSections(); // Initial call
        });
    </script>
@endsection

