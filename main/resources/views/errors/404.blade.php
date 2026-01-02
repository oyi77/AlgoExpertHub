@extends(Config::theme() . 'layout.master')
@section('content')
    <!-- Inline styles as fallback - ensures visibility -->
    <style>
        body { background: linear-gradient(135deg, #0a0e27 0%, #1a1a2e 100%) !important; background-color: #0a0e27 !important; }
        .error-pgae-wrapper { 
            background: linear-gradient(135deg, #0a0e27 0%, #1a1a2e 100%) !important; 
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: calc(100vh - 100px) !important;
            padding-top: 80px !important;
        }
        .error-pgae-wrapper #container {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            width: 100% !important;
        }
        .error-pgae-wrapper svg { 
            max-width: 600px !important; 
            max-height: 300px !important; 
            margin: 0 auto 20px auto !important; 
            display: block !important; 
        }
        .error-pgae-wrapper text { fill: #1AFFD5 !important; stroke: #0D9488 !important; font-size: 80px !important; font-weight: 700 !important; }
        .error-pgae-wrapper .message { 
            color: #FFFFFF !important; 
            font-size: 32px !important; 
            font-weight: 600 !important; 
            margin: 20px auto !important;
            text-align: center !important;
            width: 100% !important;
            display: block !important;
        }
        .error-pgae-wrapper .message:before, .error-pgae-wrapper .message:after { color: #1AFFD5 !important; opacity: 1 !important; animation: none !important; }
        .error-pgae-wrapper .main-btn { 
            background: #1AFFD5 !important; 
            color: #060F11 !important; 
            margin: 20px auto 0 auto !important;
            display: inline-block !important;
        }
    </style>
    <div class="error-pgae-wrapper">
        <div id="container">

    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
        viewBox="0 0 200 82.7" style="enable-background:new 0 0 200 82.7;" xml:space="preserve">

        <g id="Calque_1">
            <text id="XMLID_3_" transform="matrix(1.2187 0 0 1 13 75.6393)" class="st0 st1" fill="#1AFFD5" stroke="#0D9488" stroke-width="2" style="font-size: 80px; font-weight: 700; font-family: Arial, Helvetica, sans-serif; filter: drop-shadow(0 0 20px rgba(26, 255, 213, 0.5));">4</text>
            <text id="XMLID_4_" transform="matrix(1.2187 0 0 1 133.0003 73.6393)" class="st0 st1" fill="#1AFFD5" stroke="#0D9488" stroke-width="2" style="font-size: 80px; font-weight: 700; font-family: Arial, Helvetica, sans-serif; filter: drop-shadow(0 0 20px rgba(26, 255, 213, 0.5));">4</text>
        </g>
        <g id="Calque_2">
            <g>
                <path id="XMLID_11_" d="M81.8,29.2c4.1-5.7,10.7-9.4,18.3-9.4c6.3,0,12.1,2.7,16.1,6.9c0.6-0.4,1.1-0.7,1.7-1.1
    c-4.4-4.8-10.8-7.9-17.8-7.9c-8.3,0-15.6,4.2-20,10.6C80.7,28.5,81.3,28.8,81.8,29.2z" fill="#1AFFD5" />
                <path id="XMLID_2_" d="M118.1,53.7c-4,5.7-10.7,9.5-18.2,9.5c-6.3,0-12.1-2.6-16.2-6.8c-0.6,0.4-1.1,0.7-1.7,1.1
    c4.4,4.8,10.8,7.8,17.9,7.8c8.3,0,15.6-4.3,19.9-10.7C119.2,54.5,118.6,54.1,118.1,53.7z" fill="#1AFFD5" />
                <animateTransform attributeName="transform" type="rotate" from="360 100 41.3" to="0 100 41.3" dur="10s"
                    repeatCount="indefinite" />
            </g>
            <g id="XMLID_6_">
                <g id="XMLID_18_">
                    <circle class="circle" cx="100" cy="41" r="1" fill="#1AFFD5"></circle>
                </g>
            </g>
            <defs>
                <filter id="blurFilter4" x="-20" y="-20" width="200" height="200">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="2" />
                </filter>
            </defs>
            <path id="XMLID_5_" class="st2" d="M103.8,16.7c0.1,0.3,0.1,0.6,0.1,0.9c11.6,1.9,20.4,11.9,20.4,24.1c0,13.5-10.9,24.4-24.4,24.4
    S75.6,55.1,75.6,41.7c0-3.2,0.6-6.3,1.7-9.1c-0.3-0.2-0.5-0.3-0.7-0.5c-1.2,3-1.9,6.2-1.9,9.6c0,14,11.3,25.3,25.3,25.3
    s25.3-11.3,25.3-25.3C125.3,29,115.9,18.5,103.8,16.7z" fill="#1AFFD5" opacity="0.7" />


        </g>
    </svg>

    <div class="message">
        {{__("Page not found")}}
        
    </div>
    <a href="{{ route('home') }}" class="main-btn btn-sm">{{__("Back To Home")}}</a>
    </div>
    </div>
@endsection

@push('style')
    <style id="error-page-styles-404">
        /* Force body background override */
        body {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1a2e 100%) !important;
            background-color: #0a0e27 !important;
        }
        
        .error-pgae-wrapper {
            min-height: calc(100vh - 100px) !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1a2e 100%) !important;
            background-color: #0a0e27 !important;
            padding: 80px 20px 40px 20px !important;
            position: relative !important;
            z-index: 1 !important;
            margin-top: 0 !important;
        }
        .error-pgae-wrapper #container {
            width: 100%;
            max-width: 1200px;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
        }
        
        /* SVG Text - Make the "4" numbers bright and visible */
        .error-pgae-wrapper .st0 {
            font-family: 'Arial', 'Helvetica', sans-serif !important;
            font-weight: 700 !important;
        }
        .error-pgae-wrapper .st1 {
            font-size: 80px !important;
            fill: #1AFFD5 !important; /* Bright teal/cyan for high visibility */
            stroke: #0D9488 !important;
            stroke-width: 2px !important;
            filter: drop-shadow(0 0 20px rgba(26, 255, 213, 0.5)) !important;
        }
        
        /* SVG Paths - Make all paths bright and visible */
        .error-pgae-wrapper .st2 {
            fill: #1AFFD5 !important; /* Changed from gray to bright teal */
            opacity: 0.8 !important;
        }
        .error-pgae-wrapper svg {
            max-width: 600px !important;
            max-height: 300px !important;
            width: 100% !important;
            height: auto !important;
            text-align: center !important;
            fill: #1AFFD5 !important;
            margin: 0 auto 20px auto !important;
            display: block !important;
        }
        .error-pgae-wrapper path#XMLID_5_ {
            fill: #1AFFD5 !important;
            filter: url(#blurFilter4);
            opacity: 0.7 !important;
        }
        .error-pgae-wrapper path#XMLID_11_,
        .error-pgae-wrapper path#XMLID_2_ {
            fill: #1AFFD5 !important;
        }
        .error-pgae-wrapper .circle {
            animation: out 2s infinite ease-out;
            fill: #1AFFD5 !important;
        }
        
        /* Message Text - High contrast, larger, bold, perfectly centered */
        .error-pgae-wrapper .message {
            color: #FFFFFF !important;
            font-size: 32px !important;
            font-weight: 600 !important;
            margin: 20px auto !important;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5) !important;
            letter-spacing: 1px !important;
            text-align: center !important;
            width: 100% !important;
            display: block !important;
        }
        
        /* Remove fading brackets - keep them visible */
        .error-pgae-wrapper .message:after {
            content: "]" !important;
            color: #1AFFD5 !important;
            font-size: 32px !important;
            opacity: 1 !important; /* Always visible */
            margin: 0 15px !important;
            animation: none !important;
        }
        .error-pgae-wrapper .message:before {
            content: "[" !important;
            color: #1AFFD5 !important;
            font-size: 32px !important;
            opacity: 1 !important; /* Always visible */
            margin: 0 15px !important;
            animation: none !important;
        }
        
        /* Button styling for better visibility - perfectly centered */
        .error-pgae-wrapper .main-btn {
            background: #1AFFD5 !important;
            color: #060F11 !important;
            font-weight: 600 !important;
            padding: 12px 30px !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 15px rgba(26, 255, 213, 0.4) !important;
            transition: all 0.3s ease !important;
            margin: 20px auto 0 auto !important;
            display: inline-block !important;
            text-align: center !important;
        }
        .error-pgae-wrapper .main-btn:hover {
            background: #0D9488 !important;
            color: #FFFFFF !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 255, 213, 0.6);
        }
        
        /* Animation - Keep but make more visible */
        @keyframes out {
            0% {
                r: 1;
                opacity: 1;
            }
            25% {
                r: 5;
                opacity: 0.6;
            }
            50% {
                r: 10;
                opacity: 0.4;
            }
            75% {
                r: 15;
                opacity: 0.2;
            }
            100% {
                r: 20;
                opacity: 0;
            }
        }
        
        /* Force SVG text visibility with inline style support */
        .error-pgae-wrapper text#XMLID_3_,
        .error-pgae-wrapper text#XMLID_4_ {
            fill: #1AFFD5 !important;
            stroke: #0D9488 !important;
            stroke-width: 2 !important;
            font-size: 80px !important;
            font-weight: 700 !important;
            font-family: Arial, Helvetica, sans-serif !important;
            filter: drop-shadow(0 0 20px rgba(26, 255, 213, 0.5)) !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .error-pgae-wrapper text#XMLID_3_,
            .error-pgae-wrapper text#XMLID_4_ {
                font-size: 60px !important;
            }
            .error-pgae-wrapper .st1 {
                font-size: 60px !important;
            }
            .error-pgae-wrapper svg {
                max-width: 400px !important;
                max-height: 200px !important;
            }
            .error-pgae-wrapper .message {
                font-size: 24px !important;
            }
        }
    </style>
@endpush
