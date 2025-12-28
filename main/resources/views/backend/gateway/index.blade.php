@extends('backend.layout.master')

@section('element')
    <div class="content-main pt-0">
        <div class="row">
            <div class="col-12">
                 <livewire:admin.gateways.gateway-manager />
            </div>
            <!-- Legacy loop commented out
            ...
            -->
        </div>
    </div>
@endsection

@push('style')
    <style>
        .content-main {
            --c-text-primary: #282a32;
            --c-text-secondary: #686b87;
            --c-text-action: #404089;
            --c-accent-primary: #434ce8;
            --c-border-primary: #eff1f6;
            --c-background-primary: #ffffff;
            --c-background-secondary: #fdfcff;
            --c-background-tertiary: #ecf3fe;
            --c-background-quaternary: #e9ecf4;
        }

        .content-main {
            padding-top: 2rem;
            padding-bottom: 6rem;
            flex-grow: 1;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            -moz-column-gap: 1.5rem;
            column-gap: 1.5rem;
            row-gap: 1rem;
        }

        @media (min-width: 600px) {
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1200px) {
            .card-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .card {
            background-color: var(--c-background-primary);
            box-shadow: 0 3px 3px 0 rgba(0, 0, 0, 0.05), 0 5px 15px 0 rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-bottom: .5rem;
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .card-header div {
            display: flex;
            align-items: center;
        }

        .card-header div span {
            height: 40px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .card-header div span img {
            max-height: 100%;
        }

        .card-header div h4 {
            margin-left: 0.75rem;
            font-weight: 500;
        }

        .toggle span {
            display: block;
            width: 40px;
            height: 24px;
            border-radius: 99em;
            background-color: var(--c-background-quaternary);
            box-shadow: inset 1px 1px 1px 0 rgba(0, 0, 0, 0.05);
            position: relative;
            transition: 0.15s ease;
        }

        .toggle span:before {
            content: "";
            display: block;
            position: absolute;
            left: 3px;
            top: 3px;
            height: 18px;
            width: 18px;
            background-color: var(--c-background-primary);
            border-radius: 50%;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.15);
            transition: 0.15s ease;
        }

        .toggle input {
            clip: rect(0 0 0 0);
            -webkit-clip-path: inset(50%);
            clip-path: inset(50%);
            height: 1px;
            overflow: hidden;
            position: absolute;
            white-space: nowrap;
            width: 1px;
        }

        .toggle input:checked+span {
            background-color: var(--c-accent-primary);
        }

        .toggle input:checked+span:before {
            transform: translateX(calc(100% - 2px));
        }

        .toggle input:focus+span {
            box-shadow: 0 0 0 4px var(--c-background-tertiary);
        }

        .card-body {
            padding: 1rem 1.25rem;
            font-size: 0.875rem;
        }

        .card-footer {
            margin-top: auto;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            border-top: 1px solid var(--c-border-primary);
        }

        .card-footer a {
            color: var(--c-text-action);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
        }

        @media (max-width: 1399px) {
            .payment-card h5 {
                font-size: 14px;
            }

            .payment-card a {
                font-size: 13px;
            }
        }
        
        .w-50-percent p{
            width: 50%;
        }
        .w-50-percent p:nth-child(2),
        .w-50-percent p:nth-child(4){
            text-align: right;
        }
    </style>
@endpush

@push('scripts')
    <!-- Legacy scripts removed/commented -->
@endpush
