@extends(Config::theme() . 'layout.master')
@section('content')
    <div class="error-pgae-wrapper">
      <div id="container">

  <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
      viewBox="0 0 200 82.7" style="enable-background:new 0 0 200 82.7;" xml:space="preserve">

      <g id="Calque_1">
          <text id="XMLID_3_" transform="matrix(1.2187 0 0 1 13 75.6393)" class="st0 st1">5</text>
          <text id="XMLID_4_" transform="matrix(1.2187 0 0 1 133.0003 73.6393)" class="st0 st1">0</text>
      </g>
      <g id="Calque_2">
          <g>
              <path id="XMLID_11_" d="M81.8,29.2c4.1-5.7,10.7-9.4,18.3-9.4c6.3,0,12.1,2.7,16.1,6.9c0.6-0.4,1.1-0.7,1.7-1.1
  c-4.4-4.8-10.8-7.9-17.8-7.9c-8.3,0-15.6,4.2-20,10.6C80.7,28.5,81.3,28.8,81.8,29.2z" />
              <path id="XMLID_2_" d="M118.1,53.7c-4,5.7-10.7,9.5-18.2,9.5c-6.3,0-12.1-2.6-16.2-6.8c-0.6,0.4-1.1,0.7-1.7,1.1
  c4.4,4.8,10.8,7.8,17.9,7.8c8.3,0,15.6-4.3,19.9-10.7C119.2,54.5,118.6,54.1,118.1,53.7z" />
              <animateTransform attributeName="transform" type="rotate" from="360 100 41.3" to="0 100 41.3" dur="10s"
                  repeatCount="indefinite" />
          </g>
          <g id="XMLID_6_">
              <g id="XMLID_18_">
                  <circle class="circle" cx="100" cy="41" r="1"></circle>
              </g>
          </g>
          <defs>
              <filter id="blurFilter4" x="-20" y="-20" width="200" height="200">
                  <feGaussianBlur in="SourceGraphic" stdDeviation="2" />
              </filter>
          </defs>
          <path id="XMLID_5_" class="st2" d="M103.8,16.7c0.1,0.3,0.1,0.6,0.1,0.9c11.6,1.9,20.4,11.9,20.4,24.1c0,13.5-10.9,24.4-24.4,24.4
  S75.6,55.1,75.6,41.7c0-3.2,0.6-6.3,1.7-9.1c-0.3-0.2-0.5-0.3-0.7-0.5c-1.2,3-1.9,6.2-1.9,9.6c0,14,11.3,25.3,25.3,25.3
  s25.3-11.3,25.3-25.3C125.3,29,115.9,18.5,103.8,16.7z" />


      </g>
  </svg>

  <div class="message">
    {{__("Ummm, i think we make a mistake, here. Please Tell Us! Just in case we are forget ):")}}
  </div>
  <div class="error-actions mt-4">
    <a href="{{ route('home') }}" class="main-btn btn-sm"> {{__("Back To Home")}}</a>
    @if(Route::has('contact'))
    <a href="{{ route('contact') }}" class="main-btn btn-sm btn-outline-primary ml-2">{{__("Contact Us")}}</a>
    @endif
  </div>
  </div>
    </div>
@endsection

@push('style')
    <style>
      .error-pgae-wrapper {
        min-height: 100vh;
        display: flex;
        flex-wrap: wrap; 
        align-content: center;
        justify-content: center;
        background: linear-gradient(135deg, #1a0a0a 0%, #2e1a1a 100%);
        padding: 40px 20px;
      }
      .error-pgae-wrapper #container {
        width: 100%;
        text-align: center;
      }
      
      /* SVG Text - Make the "50" numbers bright and visible (red/orange for error) */
      .st0 {
          font-family: 'Arial', 'Helvetica', sans-serif;
          font-weight: 700;
      }
      .st1 {
          font-size: 120px;
          fill: #FF6B6B !important; /* Bright red for error visibility */
          stroke: #DC2626;
          stroke-width: 2px;
          filter: drop-shadow(0 0 20px rgba(255, 107, 107, 0.5));
      }
      
      /* SVG Paths - Make all paths bright and visible */
      .st2 {
          fill: #FF6B6B !important; /* Changed from gray to bright red */
          opacity: 0.8;
      }
      svg {
          max-width: 1000px;
          max-height: 600px;
          text-align: center;
          fill: #FF6B6B;
      }
      path#XMLID_5_ {
          fill: #FF6B6B !important;
          filter: url(#blurFilter4);
          opacity: 0.7;
      }
      path#XMLID_11_,
      path#XMLID_2_ {
          fill: #FF6B6B !important;
      }
      .circle {
          animation: out 2s infinite ease-out;
          fill: #FF6B6B;
      }
      
      /* Message Text - High contrast, larger, bold */
      .message {
          color: #FFFFFF !important;
          font-size: 28px !important;
          font-weight: 600 !important;
          margin: 30px 0;
          text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
          letter-spacing: 1px;
          line-height: 1.6;
      }
      
      /* Remove fading brackets - keep them visible */
      .message:after {
          content: "]";
          color: #FF6B6B !important;
          font-size: 28px;
          opacity: 1 !important; /* Always visible */
          margin: 0 15px;
      }
      .message:before {
          content: "[";
          color: #FF6B6B !important;
          font-size: 28px;
          opacity: 1 !important; /* Always visible */
          margin: 0 15px;
      }
      
      /* Button styling for better visibility */
      .error-pgae-wrapper .main-btn {
          background: #FF6B6B !important;
          color: #FFFFFF !important;
          font-weight: 600;
          padding: 12px 30px;
          border-radius: 8px;
          box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
          transition: all 0.3s ease;
          border: none;
      }
      .error-pgae-wrapper .main-btn:hover {
          background: #DC2626 !important;
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
      }
      
      .error-actions {
          display: flex;
          justify-content: center;
          gap: 15px;
          flex-wrap: wrap;
          margin-top: 30px;
      }
      .error-actions .btn-outline-primary {
          border: 2px solid #FF6B6B !important;
          color: #FF6B6B !important;
          background: transparent !important;
          font-weight: 600;
          padding: 12px 30px;
          border-radius: 8px;
      }
      .error-actions .btn-outline-primary:hover {
          background: #FF6B6B !important;
          color: #FFFFFF !important;
          transform: translateY(-2px);
      }
      
      /* Animation - Keep but make more visible */
      @keyframes out {
          0% { r: 1; opacity: 1; }
          25% { r: 5; opacity: 0.6; }
          50% { r: 10; opacity: 0.4; }
          75% { r: 15; opacity: 0.2; }
          100% { r: 20; opacity: 0; }
      }
      
      /* Responsive */
      @media (max-width: 768px) {
          .st1 {
              font-size: 80px;
          }
          .message {
              font-size: 22px !important;
          }
      }
    </style>
@endpush
