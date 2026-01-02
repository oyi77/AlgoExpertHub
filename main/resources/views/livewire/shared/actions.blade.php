{{-- Default actions view for data table rows --}}
{{-- This view is used when no custom actions section is provided --}}
<div class="flex items-center space-x-2">
    {{-- Default: Show view/edit buttons if item has an ID --}}
    @if(isset($item->id))
        <span class="text-gray-400 text-sm">No actions</span>
    @else
        <span class="text-gray-400 text-sm">—</span>
    @endif
</div>
