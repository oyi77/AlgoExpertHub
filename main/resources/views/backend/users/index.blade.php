@extends('backend.layout.master')


@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-body">
                    @include('backend.users.tab_list')
                </div>
            </div>
            <div class="card">
                <div class="card-header site-card-header justify-content-between align-items-center">
                    <div class="card-header-left">
                        <form action="" method="get">
                            <!-- Existing search form retained but Livewire has its own search -->
                            <!-- We can keep this or hide it. Livewire component has search built-in -->
                            <!-- Hiding it to avoid confusion -->
                            <!--
                            <div class="input-group flex-wrap user-search-area">
                                <input type="text" class="form-control form-control-sm" placeholder="username or email or phone" name="search">
                                <div class="input-group-append">
                                    <button class="btn btn-primary btn-sm" type="submit"> 
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            -->
                        </form>
                    </div>
                    <div  class="card-header-right">
                        <button class="btn btn-sm btn-primary sendMail"><i class="las la-mail-bulk mr-2"></i>{{ __('Bulk Mail') }}</button>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Livewire Replacement -->
                    <livewire:admin.users.users-table />

                    <!-- Legacy Table (Commented Out for Backup)
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            ...
                        </table>
                    </div>
                    -->
                </div>

                <!-- Pagination handled by Livewire -->
                <!--
                @if ($users->hasPages())
                    <div class="card-footer">
                        {{ $users->links() }}
                    </div>
                @endif
                -->
            </div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" role="dialog" id="mail">
        <div class="modal-dialog modal-lg" role="document">
            <form action="{{ route('admin.user.bulk') }}" method="post">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Send Mail to user') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="">{{ __('Subject') }}</label>
                            <input type="text" name="subject" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Message') }}</label>
                            <textarea name="message" id="" cols="30" rows="10" class="form-control summernote"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="las la-envelope"></i>
                            {{ __('Send Mail') }}</button>
                        <button type="button" class="btn btn-sm btn-danger" data-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" role="dialog" id="changePassword">
        <div class="modal-dialog" role="document">
            <form action="" method="post">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Change Password') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="">{{ __('New Password') }}</label>
                            <input type="password" name="password" class="form-control" placeholder="New Password">
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Confirm Password') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Change Password') }}</button>
                        <button type="button" class="btn btn-sm btn-danger" data-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Legacy script might interfere with Livewire, careful -->
    <script src="{{ asset('js/pages/admin/users-index.js') }}"></script>
@endpush
