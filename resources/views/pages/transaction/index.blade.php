@extends('layouts.app')

@section('title', 'Transaction History - Customer Portal PLN')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Energy Usage Transaction History</h1>
    <a href="{{ route('transactions.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Transaction
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
</div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Transaction List</h6>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="filter-payment-method">Payment Method:</label>
                <select id="filter-payment-method" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="E-Wallet">E-Wallet</option>
                    <option value="Virtual Account">Virtual Account</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="filter-status">Status:</label>
                <select id="filter-status" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="paid">Paid</option>
                    <option value="owing">Owing</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Token</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions ?? collect([]) as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date->format('d M Y H:i') }}</td>
                        <td>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                        <td>{{ $transaction->payment_method }}</td>
                        <td data-status="{{ $transaction->status }}">
                            <span class="badge badge-{{
                                        $transaction->status == 'pending' || $transaction->status == 'owing' ? 'warning' :
                                        ($transaction->status == 'success' || $transaction->status == 'paid' ? 'success' : 'danger')
                                    }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                        <td>{{ $transaction->generated_token ?? '-' }}</td>
                        <td>
                            <a href="{{ route('transactions.show', $transaction->transaction_id) }}" class="btn btn-info btn-sm" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No transaction data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('template/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('template/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('template/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
    $(document).ready(function() {
        var table = $('#dataTable').DataTable({
            "destroy": true,
            "order": [
                [0, "desc"]
            ],
            "columnDefs": [{
                "type": "date",
                "targets": 0
            }],
            "pagingType": "simple",
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "zeroRecords": "No matching records found",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "No entries to show",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "paginate": {
                    "previous": "Prev",
                    "next": "Next"
                }
            },
            "pageLength": 10,
            "lengthMenu": [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ]
        });

        $('#filter-status').on('change', function() {
            var statusValue = $(this).val();

            if (statusValue) {
                var searchString = statusValue.charAt(0).toUpperCase() + statusValue.slice(1);
                table.column(3).search(searchString, false, true).draw();
            } else {
                // Jika "All" dipilih (value kosong), hapus filter untuk kolom status
                table.column(3).search('').draw();
            }
        });

        // Event listener untuk filter metode pembayaran
        $('#filter-payment-method').on('change', function() {
            var paymentMethodValue = $(this).val();
            if (paymentMethodValue) {
                table.column(2).search('^' + paymentMethodValue + '$', true, false).draw();
            } else {
                table.column(2).search('').draw();
            }
        });

    });
</script>
@endpush
