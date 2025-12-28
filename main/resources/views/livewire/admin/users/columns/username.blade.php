<div class="flex items-center">
    <div class="flex-shrink-0 h-10 w-10">
        <img class="h-10 w-10 rounded-full object-cover" src="{{ $row->avatar ?? asset('global/images/default.png') }}" alt="">
    </div>
    <div class="ml-4">
        <div class="text-sm font-medium text-gray-900">
            {{ $row->username }}
        </div>
        <div class="text-sm text-gray-500">
            {{ $row->full_name ?? ($row->first_name . ' ' . $row->last_name) }}
        </div>
    </div>
</div>
