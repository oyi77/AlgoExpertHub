<div>
    <div class="mb-4 flex justify-between items-center">
        <!-- Search -->
        <div class="w-1/3">
            <input 
                wire:model.live.debounce.500ms="search" 
                type="text" 
                class="w-full form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                placeholder="Search..."
            >
        </div>

        <!-- Per Page -->
        <div>
            <select 
                wire:model.live="perPage" 
                class="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            >
                <option value="15">15 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow overflow-y-auto relative">
        <table class="border-collapse table-auto w-full whitespace-no-wrap bg-white table-striped relative">
            <thead>
                <tr class="text-left">
                    <th class="py-2 px-3 sticky top-0 border-b border-gray-200 bg-gray-100">
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model.live="selectAll" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                        </label>
                    </th>
                    @foreach($columns as $column)
                        <th 
                            class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-2 text-gray-600 font-bold tracking-wider uppercase text-xs cursor-pointer"
                            @if($column['sortable'] ?? false) wire:click="sortBy('{{ $column['key'] }}')" @endif
                        >
                            {{ $column['label'] }}
                            @if($column['sortable'] ?? false)
                                <span class="ml-1">{{ $this->getSortIcon($column['key']) }}</span>
                            @endif
                        </th>
                    @endforeach
                    <th class="bg-gray-100 sticky top-0 border-b border-gray-200 px-6 py-2 text-gray-600 font-bold tracking-wider uppercase text-xs">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="border-dashed border-t border-gray-200 px-3">
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model.live="selected" value="{{ $item->id }}" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                            </label>
                        </td>
                        @foreach($columns as $column)
                            <td class="border-dashed border-t border-gray-200 px-6 py-3">
                                @if(isset($column['view']))
                                    @include($column['view'], ['row' => $item])
                                @elseif(isset($column['callback']))
                                    {{ call_user_func($column['callback'], $item) }}
                                @else
                                    {{ $item->{$column['key']} }}
                                @endif
                            </td>
                        @endforeach
                        <td class="border-dashed border-t border-gray-200 px-6 py-3">
                            <!-- Actions slot or default actions -->
                            @hasSection('actions')
                                @yield('actions')
                            @else
                                @include('livewire.shared.actions', ['item' => $item])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="border-dashed border-t border-gray-200 px-6 py-3 text-center" colspan="{{ count($columns) + 2 }}">
                            No data found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</div>
