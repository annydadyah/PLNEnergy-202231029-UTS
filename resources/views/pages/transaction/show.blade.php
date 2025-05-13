@extends('layouts.app')

@section('title', 'Transaction Details - Customer Portal PLN')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Transaction Details</h1>
    <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back to Transaction List
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Transaction Information</h6>
                <div>
                    <span class="badge badge-{{ $transaction->status == 'pending' || $transaction->status == 'owing' ? 'warning' : ($transaction->status == 'success' || $transaction->status == 'paid' ? 'success' : 'danger') }} px-3 py-2">
                        {{ ucfirst($transaction->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h5 class="font-weight-bold text-dark">Transaction Date</h5>
                        <p>{{ $transaction->transaction_date->format('d M Y H:i') }}</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h5 class="font-weight-bold text-dark">Amount</h5>
                        <p class="text-primary font-weight-bold">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</p>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h5 class="font-weight-bold text-dark">Payment Method</h5>
                        <p>{{ $transaction->payment_method }}</p>
                    </div>
                </div>

                @if ($transaction->status == 'success' || $transaction->status == 'paid')
                <div class="row">
                    <div class="col-12 mb-4">
                        <h5 class="font-weight-bold text-dark">Electricity Token</h5>
                        <div class="bg-light p-3 rounded">
                            <code class="text-primary">{{ $transaction->generated_token }}</code>
                        </div>
                        <small class="text-muted">This token can be used on your electricity meter device.</small>
                    </div>
                </div>
                @endif

                @if ($transaction->status == 'owing')
                <div class="row">
                    <div class="col-12 mb-4">
                        <h5 class="font-weight-bold text-dark">Payment</h5>
                        <div class="text-center">
                            @if ($transaction->payment_method == 'Virtual Account')
                            {{-- Display VA Billing Number --}}
                            <p>Virtual Account Number:</p>
                            <h4 class="font-weight-bold">{{ $transaction->payment_code }}</h4>
                            <p class="text-muted">Please make a payment to the Virtual Account number above.</p>
                            @else
                            {{-- Display QR Code for E-Wallet payment method --}}
                            <div style="display:inline-block; padding:10px; border:1px solid #ddd; margin-bottom: 10px;">
                                @if (extension_loaded('gd'))
                                    {!! QrCode::size(200)->generate($transaction->payment_code) !!}
                                @else
                                    {{-- Fallback if GD extension is not enabled --}}
                                    <p class="text-danger">The GD extension for PHP is not enabled. QR Code generation is not available.</p>
                                    <p>Please enable the GD extension in your PHP configuration (php.ini) and restart your web server.</p>
                                @endif
                            </div>
                            <p class="text-muted">Scan QR Code to make a payment</p>
                            @endif
                        </div>
                        <div class="mt-4 d-flex justify-content-center">
                            <button id="confirmPayment" class="btn btn-success mr-2">
                                <i class="fas fa-check"></i> Payment Made
                            </button>
                            <button id="cancelPayment" class="btn btn-danger">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Information</h6>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <i class="fas fa-info-circle text-primary mr-2"></i>
                    @if ($transaction->status == 'owing')
                        @if ($transaction->payment_method == 'Virtual Account')
                            Use the Virtual Account number when making a transaction via the selected payment method.
                        @else
                            Scan the QR Code to make a payment using your E-Wallet.
                        @endif
                    @elseif ($transaction->status == 'success' || $transaction->status == 'paid')
                        The 20-digit token can be used on your prepaid electricity meter.
                    @endif
                </p>
                <p class="mb-0">
                    <i class="fas fa-clock text-warning mr-2"></i>
                    @if ($transaction->status == 'owing')
                        Please make the payment immediately.
                    @else
                        The current transaction status is <strong>{{ ucfirst($transaction->status) }}</strong>.
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Payment Confirmation Modal --}}
<div class="modal fade" id="paymentConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="paymentConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentConfirmationModalLabel">Confirm Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you have made the payment for this transaction?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button id="confirmPaymentAction" type="button" class="btn btn-success">Yes, Payment Made</button>
            </div>
        </div>
    </div>
</div>

{{-- Cancel Payment Modal --}}
<div class="modal fade" id="cancelPaymentModal" tabindex="-1" role="dialog" aria-labelledby="cancelPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelPaymentModalLabel">Cancel Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this transaction?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                <button id="cancelPaymentAction" type="button" class="btn btn-danger">Yes, Cancel</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Confirm Payment
        $('#confirmPayment').click(function() {
            $('#paymentConfirmationModal').modal('show');
        });

        $('#confirmPaymentAction').click(function() {
            // Get the URL from the existing route
            var updateStatusUrl = "{{ route('transactions.updateStatus', $transaction->transaction_id) }}";

            // Perform AJAX request to change status to 'paid'
            $.ajax({
                url: updateStatusUrl,
                type: 'POST', // Use POST because the route uses match(['put', 'patch'])
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: 'PUT', // Add method spoofing for Laravel
                    status: 'paid'
                },
                dataType: 'json', // Add expectation for JSON data type
                success: function(response) {
                    if (response.success) {
                        // Redirect to the transaction details page
                        window.location.href = "{{ route('transactions.show', $transaction->transaction_id) }}";
                    } else {
                        // Display error message from the server
                        alert(response.message || 'Failed to update transaction status');
                    }
                },
                error: function(xhr, status, error) {
                    // Handle various possible errors
                    var errorMessage = 'An error occurred';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 0) {
                        errorMessage = 'Could not connect to the server. Please check your internet connection.';
                    } else if (xhr.status === 422) {
                        errorMessage = 'The submitted data was invalid';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Internal server error';
                    }

                    // Display the error message
                    alert(errorMessage);

                    // Optionally, still redirect to the transaction list page
                    window.location.href = "{{ route('transactions.index') }}";
                }
            });

            // Close the modal
            $('#paymentConfirmationModal').modal('hide');
        });

        // Cancel Payment
        $('#cancelPayment').click(function() {
            $('#cancelPaymentModal').modal('show');
        });

        $('#cancelPaymentAction').click(function() {
            // Get the URL from the existing route
            var updateStatusUrl = "{{ route('transactions.updateStatus', $transaction->transaction_id) }}";

            // Perform AJAX request to change status to 'failed'
            $.ajax({
                url: updateStatusUrl,
                type: 'POST', // Use POST because the route uses match(['put', 'patch'])
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: 'PUT', // Add method spoofing for Laravel
                    status: 'failed'
                },
                dataType: 'json', // Add expectation for JSON data type
                success: function(response) {
                    if (response.success) {
                        // Redirect to the transaction details page
                        window.location.href = "{{ route('transactions.show', $transaction->transaction_id) }}";
                    } else {
                        // Display error message from the server
                        alert(response.message || 'Failed to cancel transaction');
                    }
                },
                error: function(xhr, status, error) {
                    // Handle various possible errors
                    var errorMessage = 'An error occurred';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 0) {
                        errorMessage = 'Could not connect to the server. Please check your internet connection.';
                    } else if (xhr.status === 422) {
                        errorMessage = 'The submitted data was invalid';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Internal server error';
                    }

                    // Display the error message
                    alert(errorMessage);

                    // Optionally, still redirect to the transaction list page
                    window.location.href = "{{ route('transactions.index') }}";
                }
            });

            // Close the modal
            $('#cancelPaymentModal').modal('hide');
        });
    });
</script>
@endpush