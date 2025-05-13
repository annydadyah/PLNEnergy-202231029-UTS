@extends('layouts.app') {{-- Assuming you have an SB Admin 2 layout --}}

@section('title', 'User Profile')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">User Profile</h1>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Account Information</h6>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                    @endif

                    @if($user) {{-- Using the $user variable from the controller --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Full Name</label>
                                <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Email Address</label>
                                <input type="email" class="form-control" value="{{ $user->email }}" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">KWH Meter Code</label>
                                <input type="text" class="form-control" value="{{ $user->kwh_meter_code ?? 'Not Set' }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Account Status</label>
                                <input type="text" class="form-control text-success font-weight-bold" value="Active" readonly>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('profile.edit') }}" class="btn btn-primary shadow-sm">
                                <i class="fas fa-edit fa-sm text-white-50"></i> Edit Profile
                            </a>
                            <button type="button" class="btn btn-danger shadow-sm" data-toggle="modal" data-target="#deleteModal">
                                <i class="fas fa-trash-alt fa-sm text-white-50"></i> Delete Account
                            </button>
                        </div>
                    @else
                        <div class="alert alert-warning" role="alert">
                            User not authenticated. Please <a href="{{ route('login') }}" class="alert-link">log in</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Account Confirmation Modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Are you sure you want to delete your account?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    This action is permanent and cannot be undone. All your data, including transaction history and preferences, will be deleted.
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <form action="{{ route('profile.destroy') }}" method="POST">
                        @csrf
                        @method('DELETE') {{-- Important for DELETE request --}}
                        <button type="submit" class="btn btn-danger">Yes, Delete My Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Optional: Add any custom scripts for this page here --}}
    <script>
        // Optional: Script to automatically close the alert after a few seconds
        window.setTimeout(function() {
            $(".alert-success").fadeTo(500, 0).slideUp(500, function(){
                $(this).remove();
            });
        }, 5000); // 5 seconds
    </script>
@endpush