@extends('layouts.app')

@section('title', 'Edit User Profile')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit User Profile</h1>
        <a href="{{ route('profile.index') }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Profile
        </a>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Account Information Form</h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Oops! An error occurred:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH') {{-- Using PATCH for update --}}

                        <div class="mb-3">
                            <label for="name" class="form-label font-weight-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label font-weight-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="kwh_meter_code" class="form-label font-weight-bold">KWH Meter Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('kwh_meter_code') is-invalid @enderror" id="kwh_meter_code" name="kwh_meter_code" value="{{ old('kwh_meter_code', $user->kwh_meter_code) }}" required>
                            @error('kwh_meter_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">
                        <p class="text-muted small">Leave the password fields blank if you do not wish to change your password.</p>

                        <div class="mb-3">
                            <label for="password" class="form-label font-weight-bold">New Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" aria-describedby="passwordHelp">
                            <small id="passwordHelp" class="form-text text-muted">Minimum 8 characters, combine uppercase, lowercase, numbers, and symbols.</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label font-weight-bold">Confirm New Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            {{-- Error for password_confirmation is usually covered by 'confirmed' in 'password' --}}
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary shadow-sm">
                                <i class="fas fa-save fa-sm text-white-50"></i> Save Changes
                            </button>
                            <a href="{{ route('profile.index') }}" class="btn btn-light shadow-sm ml-2">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
         <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filling Instructions</h6>
                </div>
                <div class="card-body">
                    <p>Ensure all fields marked with an asterisk <span class="text-danger">*</span> (required) are filled in correctly.</p>
                    <p>If you wish to change your password, fill in the "New Password" and "Confirm New Password" fields. Otherwise, leave these two fields blank.</p>
                    <p class="small text-muted">Changes will be saved after you press the "Save Changes" button.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Optional: Script to automatically close the alert after a few seconds
    window.setTimeout(function() {
        $(".alert-danger").fadeTo(500, 0).slideUp(500, function(){
            $(this).remove();
        });
    }, 7000); // 7 seconds
</script>
@endpush