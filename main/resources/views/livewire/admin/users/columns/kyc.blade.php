<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
    @if($row->kyc_status == 'approved') bg-emerald-100 text-emerald-800
    @elseif($row->kyc_status == 'pending') bg-yellow-100 text-yellow-800
    @elseif($row->kyc_status == 'rejected') bg-red-100 text-red-800
    @else bg-gray-100 text-gray-800 @endif">
    {{ ucfirst($row->kyc_status ?? 'unverified') }}
</span>
