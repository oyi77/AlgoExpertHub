<ul class="page-link-list">
  <li>
    <a href="#" data-status="all" class="{{ Config::activeMenu(route('admin.user.index')) }}">
      <i class="las la-user"></i> 
      {{ __('All Users') }}
      <span class="noti-count badge badge-pill badge-primary" id="count-all">0</span>
    </a>
  </li>
  <li>
    <a href="#" data-status="active" class="{{ Config::activeMenu(route('admin.user.filter', 'active')) }}">
      <i class="las la-user-check"></i> 
      {{ __('Active Users') }}
      <span class="noti-count badge badge-pill badge-success" id="count-active">0</span>
    </a>
  </li>
  <li>
    <a href="#" data-status="deactive" class="{{ Config::activeMenu(route('admin.user.filter', 'deactive')) }}">
      <i class="las la-user-slash"></i> 
      {{ __('Deactive Users') }}
      <span class="noti-count badge badge-pill badge-danger" id="count-deactive">0</span>
    </a>
  </li>
  <li>
    <a href="#" data-status="kyc_req" class="{{ Config::activeMenu(route('admin.user.kyc.req')) }}">
      <i class="las la-user-shield"></i> 
      {{ __('KYC Requests') }}
      <span class="noti-count badge badge-pill badge-warning" id="count-kyc_req">0</span>
    </a>
  </li>
</ul>
