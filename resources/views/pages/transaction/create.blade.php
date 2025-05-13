@extends('layouts.app') @section('title', 'Create New Transaction - Customer Portal PLN')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">New Transaction Form</h1>
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back to Transaction List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Transaction Details</h6>
                </div>
                <div class="card-body">
                    {{-- Display validation errors if any --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Display error message from controller (if redirect with error) --}}
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('transactions.store') }}">
                        @csrf {{-- NOTE: The following fields DO NOT need to be filled from the form as they will be handled by the Controller:
                                 - Customer ID (taken from Auth::id())
                                 - Transaction Date (filled with Carbon::now())
                                 - Generated Token (filled only upon successful payment)
                                 - Status (default 'owing')
                         --}}

                        <div class="form-group">
                            <label for="amount">Payment Amount (Rupiah)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                       id="amount" name="amount" placeholder="Example: 50000"
                                       value="{{ old('amount') }}" required min="1"> {{-- min="1" or as needed --}}
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <small class="form-text text-muted">Enter the total amount to be paid.</small>
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select class="form-control @error('payment_method') is-invalid @enderror"
                                    id="payment_method" name="payment_method" required>
                                <option value="" disabled {{ old('payment_method') ? '' : 'selected' }}>Select Payment Method...</option>
                                <option value="E-Wallet" {{ old('payment_method') == 'E-Wallet' ? 'selected' : '' }}>E-Wallet</option>
                                <option value="Virtual Account" {{ old('payment_method') == 'Virtual Account' ? 'selected' : '' }}>Virtual Account</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <small class="form-text text-muted">Choose how you will make the payment.</small>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Create Transaction Bill
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-light ml-2">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column for Additional Information --}}
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Additional Information</h6>
                </div>
                <div class="card-body">
                    <p>
                        After you submit this form, a transaction bill will be created with the initial status "Pending Payment" (Owing).
                    </p>
                    <p>
                        The transaction date will be recorded automatically.
                    </p>
                    <p>
                        Based on the selected payment method:
                        <ul>
                            <li><strong>Virtual Account</strong>: You will receive a VA number for payment.</li>
                            <li><strong>E-Wallet</strong>: You will receive a QR Code to scan.</li>
                        </ul>
                    </p>
                    <p>
                        <i class="fas fa-info-circle text-info"></i> A 20-digit electricity token will be provided after your payment is confirmed.
                    </p>
                    <hr>
                    <p class="small text-muted">
                        Please ensure the amount and payment method you entered are correct.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection