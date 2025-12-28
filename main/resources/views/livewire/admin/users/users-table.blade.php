@extends('livewire.shared.data-table')

@section('actions')
    <div class="flex items-center space-x-2">
        <a href="{{ route('admin.user.details', $item) }}" class="text-indigo-600 hover:text-indigo-900 btn btn-sm btn-outline-primary" title="View Details">
            <i class="fa fa-eye"></i>
        </a>
        <button 
            type="button" 
            class="btn btn-sm btn-outline-primary changePassword" 
            data-url="{{ route('admin.user.password.change', $item) }}"
            title="Change Password"
        >
            <i class="fa fa-key"></i>
        </button>
    </div>
@endsection
