@if(!empty($checklist) && $progress < 100)
<div class="d-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="las la-rocket me-2"></i> {{ __('Getting Started') }}
        </h5>
        <span class="badge bg-primary">{{ $progress }}%</span>
    </div>
    <div class="card-body">
        <div class="progress mb-3" style="height: 8px;">
            <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        
        <ul class="list-unstyled mb-0">
            @foreach($checklist as $item)
                <li class="mb-2 d-flex align-items-center">
                    @if($item['completed'])
                        <i class="las la-check-circle text-success me-2"></i>
                        <span class="text-muted text-decoration-line-through">{{ $item['label'] }}</span>
                    @else
                        <i class="las la-circle text-muted me-2"></i>
                        <a href="{{ $item['route'] }}" class="text-decoration-none">{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

