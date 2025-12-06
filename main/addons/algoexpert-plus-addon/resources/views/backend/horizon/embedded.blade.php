@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i data-feather="monitor"></i> Queue Dashboard (Horizon)
                </h4>
            </div>
            <div class="card-body p-0">
                <iframe 
                    src="{{ $horizonUrl }}" 
                    style="width: 100%; height: calc(100vh - 250px); min-height: 800px; border: none;"
                    title="Horizon Queue Dashboard"
                    id="horizon-iframe">
                </iframe>
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    .content-body {
        padding-bottom: 0;
    }
    
    #horizon-iframe {
        display: block;
    }
</style>
@endpush

@push('script')
<script>
    // Handle iframe load errors
    document.getElementById('horizon-iframe').addEventListener('load', function() {
        try {
            // Check if iframe loaded successfully
            var iframe = this;
            if (iframe.contentWindow) {
                console.log('Horizon dashboard loaded successfully');
            }
        } catch (e) {
            console.error('Error loading Horizon dashboard:', e);
        }
    });
</script>
@endpush
@endsection
